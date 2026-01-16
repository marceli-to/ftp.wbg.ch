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

        // Store chunk
        $chunkNumber = (int) ($uploadOffset / 5000000); // 5MB chunk size
        Storage::disk('local')->put("{$chunkDir}/chunk_{$chunkNumber}", $chunkContent);

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

        // Get all chunk files sorted by number
        $chunkFiles = collect(Storage::disk('local')->files($chunkDir))
            ->filter(fn($file) => str_contains($file, 'chunk_'))
            ->sort()
            ->values();

        // Generate final file path
        $extension = pathinfo($metadata['original_name'], PATHINFO_EXTENSION);
        $finalPath = 'files/' . Str::uuid() . ($extension ? ".{$extension}" : '');

        // Assemble chunks into final file
        $storagePath = Storage::disk('local')->path($finalPath);
        $finalFile = fopen($storagePath, 'wb');

        foreach ($chunkFiles as $chunkFile) {
            $chunkPath = Storage::disk('local')->path($chunkFile);
            $chunk = fopen($chunkPath, 'rb');
            stream_copy_to_stream($chunk, $finalFile);
            fclose($chunk);
        }

        fclose($finalFile);

        // Get file info
        $fileSize = filesize($storagePath);
        $mimeType = mime_content_type($storagePath);

        // Create database record
        $file = File::create([
            'user_id' => $metadata['user_id'],
            'original_name' => $metadata['original_name'],
            'display_name' => pathinfo($metadata['original_name'], PATHINFO_FILENAME),
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
