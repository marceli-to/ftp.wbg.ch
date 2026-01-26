<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkUploadController extends Controller
{
    /**
     * Start a new chunked upload (POST)
     * FilePond sends: Upload-Length header with total file size
     * Returns: unique upload ID
     */
    public function start(Request $request)
    {
        $uploadId = Str::uuid()->toString();
        $uploadLength = $request->header('Upload-Length');
        $fileName = $request->header('Upload-Name', 'unknown');

        // Create temp directory for chunks
        $chunkDir = "chunks/{$uploadId}";
        Storage::disk('local')->makeDirectory($chunkDir);

        // Store upload metadata
        Storage::disk('local')->put("{$chunkDir}/metadata.json", json_encode([
            'upload_length' => $uploadLength,
            'upload_offset' => 0,
            'original_name' => $fileName,
            'user_id' => $request->user()->id,
            'created_at' => now()->toIso8601String(),
        ]));

        return response($uploadId, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Receive a chunk (PATCH)
     * FilePond sends: Upload-Offset, Upload-Length, Upload-Name headers + chunk data
     */
    public function chunk(Request $request, string $uploadId)
    {
        $chunkDir = "chunks/{$uploadId}";

        if (!Storage::disk('local')->exists("{$chunkDir}/metadata.json")) {
            return response('Upload not found', 404);
        }

        $metadata = json_decode(Storage::disk('local')->get("{$chunkDir}/metadata.json"), true);

        $uploadOffset = (int) $request->header('Upload-Offset');
        $uploadLength = (int) $request->header('Upload-Length');
        $uploadName = $request->header('Upload-Name', $metadata['original_name']);

        // Get chunk content
        $chunkContent = $request->getContent();
        $chunkSize = strlen($chunkContent);

        // Store chunk using zero-padded offset for correct sorting
        // Using offset ensures chunks are stored in the exact position they belong
        $paddedOffset = str_pad($uploadOffset, 20, '0', STR_PAD_LEFT);
        Storage::disk('local')->put("{$chunkDir}/chunk_{$paddedOffset}", $chunkContent);

        // Update metadata
        $newOffset = $uploadOffset + $chunkSize;
        $metadata['upload_offset'] = $newOffset;
        $metadata['original_name'] = $uploadName;
        Storage::disk('local')->put("{$chunkDir}/metadata.json", json_encode($metadata));

        // Check if upload is complete
        if ($newOffset >= $uploadLength) {
            return $this->assembleFile($uploadId, $metadata, $request);
        }

        return response('', 204)
            ->header('Upload-Offset', $newOffset);
    }

    /**
     * Check upload progress (HEAD)
     * Used for resuming interrupted uploads
     */
    public function progress(Request $request, string $uploadId)
    {
        $chunkDir = "chunks/{$uploadId}";

        if (!Storage::disk('local')->exists("{$chunkDir}/metadata.json")) {
            return response('Upload not found', 404);
        }

        $metadata = json_decode(Storage::disk('local')->get("{$chunkDir}/metadata.json"), true);

        return response('', 200)
            ->header('Upload-Offset', $metadata['upload_offset'])
            ->header('Upload-Length', $metadata['upload_length']);
    }

    /**
     * Cancel upload (DELETE)
     */
    public function cancel(Request $request, string $uploadId)
    {
        $chunkDir = "chunks/{$uploadId}";

        if (Storage::disk('local')->exists($chunkDir)) {
            Storage::disk('local')->deleteDirectory($chunkDir);
        }

        return response('', 204);
    }

    /**
     * Assemble chunks into final file
     */
    private function assembleFile(string $uploadId, array $metadata, Request $request)
    {
        $chunkDir = "chunks/{$uploadId}";
        $expectedSize = (int) $metadata['upload_length'];

        // Get all chunk files sorted numerically by offset (extracted from filename)
        $chunkFiles = collect(Storage::disk('local')->files($chunkDir))
            ->filter(fn($file) => str_contains($file, 'chunk_'))
            ->sortBy(function ($file) {
                // Extract the offset number from filename (chunk_00000000000000000000)
                preg_match('/chunk_(\d+)$/', $file, $matches);
                return (int) ($matches[1] ?? 0);
            })
            ->values();

        if ($chunkFiles->isEmpty()) {
            Storage::disk('local')->deleteDirectory($chunkDir);
            return response('No chunks found', 500);
        }

        // Generate final file path
        $extension = pathinfo($metadata['original_name'], PATHINFO_EXTENSION);
        $finalPath = 'files/' . Str::uuid() . ($extension ? ".{$extension}" : '');

        // Assemble chunks into final file using binary mode
        $storagePath = Storage::disk('local')->path($finalPath);
        $finalFile = fopen($storagePath, 'wb');

        if ($finalFile === false) {
            Storage::disk('local')->deleteDirectory($chunkDir);
            return response('Failed to create output file', 500);
        }

        $totalBytesWritten = 0;

        foreach ($chunkFiles as $chunkFile) {
            $chunkPath = Storage::disk('local')->path($chunkFile);
            $chunk = fopen($chunkPath, 'rb');

            if ($chunk === false) {
                fclose($finalFile);
                unlink($storagePath);
                Storage::disk('local')->deleteDirectory($chunkDir);
                return response('Failed to read chunk', 500);
            }

            $bytesWritten = stream_copy_to_stream($chunk, $finalFile);
            fclose($chunk);

            if ($bytesWritten === false) {
                fclose($finalFile);
                unlink($storagePath);
                Storage::disk('local')->deleteDirectory($chunkDir);
                return response('Failed to write chunk', 500);
            }

            $totalBytesWritten += $bytesWritten;
        }

        fclose($finalFile);

        // Verify final file size matches expected size
        $fileSize = filesize($storagePath);

        if ($fileSize !== $expectedSize) {
            unlink($storagePath);
            Storage::disk('local')->deleteDirectory($chunkDir);
            return response("File size mismatch: expected {$expectedSize}, got {$fileSize}", 500);
        }

        // Use finfo for reliable MIME type detection
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($storagePath);

        if ($mimeType === false) {
            $mimeType = 'application/octet-stream';
        }

        // Create database record
        $file = File::create([
            'user_id' => $metadata['user_id'],
            'original_name' => $metadata['original_name'],
            'storage_path' => $finalPath,
            'size' => $fileSize,
            'mime_type' => $mimeType,
            'token' => File::generateToken(),
        ]);

        // Clean up chunks
        Storage::disk('local')->deleteDirectory($chunkDir);

        // Return the file ID (FilePond expects this as plain text)
        return response($file->id, 200)
            ->header('Content-Type', 'text/plain');
    }
}
