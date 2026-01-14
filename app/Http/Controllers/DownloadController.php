<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function show(string $token)
    {
        $file = File::where('token', $token)->first();

        if (!$file) {
            abort(404);
        }

        if ($file->isExpired()) {
            abort(410, 'Diese Datei ist abgelaufen.');
        }

        return view('download', [
            'file' => $file,
        ]);
    }

    public function download(string $token)
    {
        $file = File::where('token', $token)->first();

        if (!$file) {
            abort(404);
        }

        if ($file->isExpired()) {
            abort(410, 'Diese Datei ist abgelaufen.');
        }

        $path = Storage::disk('local')->path($file->storage_path);

        return response()->download($path, $file->original_name);
    }
}
