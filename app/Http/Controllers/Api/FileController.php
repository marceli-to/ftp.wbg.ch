<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index()
    {
        $files = File::orderBy('created_at', 'desc')->get();

        return response()->json($files->map(function ($file) {
            return [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'formatted_size' => $file->formatted_size,
                'download_url' => $file->download_url,
            ];
        }));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('files', 'local');

        $file = File::create([
            'user_id' => $request->user()->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'storage_path' => $path,
            'size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getMimeType(),
            'token' => File::generateToken(),
        ]);

        return response()->json([
            'id' => $file->id,
            'original_name' => $file->original_name,
            'formatted_size' => $file->formatted_size,
            'download_url' => $file->download_url,
        ], 201);
    }

    public function destroy(File $file)
    {
        Storage::disk('local')->delete($file->storage_path);
        $file->delete();

        return response()->json(['message' => 'File deleted']);
    }
}
