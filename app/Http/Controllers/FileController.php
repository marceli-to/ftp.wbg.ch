<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index()
    {
        $files = File::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard', compact('files'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'display_name' => 'nullable|string|max:255',
            'expiration_type' => 'required|in:1_week,1_month,1_year,never',
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $displayName = $request->input('display_name') ?: pathinfo($originalName, PATHINFO_FILENAME);

        $token = File::generateToken();
        $storagePath = $uploadedFile->store('files', 'local');

        $file = File::create([
            'user_id' => $request->user()->id,
            'token' => $token,
            'display_name' => $displayName,
            'original_name' => $originalName,
            'storage_path' => $storagePath,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'expiration_type' => $request->input('expiration_type'),
            'expires_at' => File::calculateExpiresAt($request->input('expiration_type')),
        ]);

        return redirect('/')->with('success', 'Datei erfolgreich hochgeladen');
    }

    public function update(Request $request, File $file)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
        ]);

        $file->update([
            'display_name' => $request->input('display_name'),
        ]);

        return redirect('/')->with('success', 'Datei umbenannt');
    }

    public function destroy(File $file)
    {
        Storage::disk('local')->delete($file->storage_path);
        $file->delete();

        return redirect('/')->with('success', 'Datei gelöscht');
    }
}
