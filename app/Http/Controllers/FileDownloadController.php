<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // <--- PRIDAJTE TENTO RIADOK
use Illuminate\Support\Str;

class FileDownloadController extends Controller
{
    public function downloadTemporaryFile(Request $request, $token)
    {
        $sessionPathKey = 'download_file_path_' . $token;
        $sessionDiskKey = 'download_file_disk_' . $token;

        if (!$request->session()->has($sessionPathKey) || !$request->session()->has($sessionDiskKey)) {
            Log::warning("Download link expired or invalid token used: {$token}"); // Príklad logovania
            abort(404, 'File not found or link expired.');
        }

        $filePathRelative = $request->session()->pull($sessionPathKey); // pull() získa a odstráni
        $diskName = $request->session()->pull($sessionDiskKey);         // pull() získa a odstráni

        Log::info("Attempting to download file. Token: {$token}, Disk: {$diskName}, Path: {$filePathRelative}");

        if (!Storage::disk($diskName)->exists($filePathRelative)) {
            Log::error("Attempted to download non-existent file. Disk: {$diskName}, Path: {$filePathRelative}");
            abort(404, 'File does not exist on the specified disk or path.'); // Trochu konkrétnejšia správa
        }

        // Tento log je teraz správne umiestnený
        Log::info("Streaming download for file. Disk: {$diskName}, Path: {$filePathRelative}");
        $fileName = Str::afterLast($filePathRelative, '/');

        // Predvolene sa Content-Disposition nastaví na 'attachment', takže súbor sa stiahne
        return Storage::disk($diskName)->download($filePathRelative, $fileName);
    }
}