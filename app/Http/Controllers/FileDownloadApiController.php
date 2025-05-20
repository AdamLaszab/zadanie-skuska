<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Download",
 *     description="File download using a temporary token"
 * )
 */
class FileDownloadApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/download/{token}",
     *     tags={"Download"},
     *     summary="Download file using temporary token",
     *     description="Downloads a file if a valid session token exists.",
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Temporary download token",
     *         @OA\Schema(type="string", example="abc123xyz")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download (binary)",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="File not found or token expired",
     *     )
     * )
     */
    public function download(Request $request, string $token)
    {
        $sessionPathKey = 'download_file_path_' . $token;
        $sessionDiskKey = 'download_file_disk_' . $token;

        if (!$request->session()->has($sessionPathKey) || !$request->session()->has($sessionDiskKey)) {
            Log::warning("Invalid or expired download token: {$token}");
            return response()->json([
                'message' => 'File not found or link expired.'
            ], 404);
        }

        $filePathRelative = $request->session()->pull($sessionPathKey);
        $diskName = $request->session()->pull($sessionDiskKey);

        Log::info("Attempting file download. Token: {$token}, Disk: {$diskName}, Path: {$filePathRelative}");

        if (!Storage::disk($diskName)->exists($filePathRelative)) {
            Log::error("File not found on disk. Disk: {$diskName}, Path: {$filePathRelative}");
            return response()->json([
                'message' => 'File does not exist on the specified disk or path.'
            ], 404);
        }

        $fileName = Str::afterLast($filePathRelative, '/');

        return Storage::disk($diskName)->download($filePathRelative, $fileName);
    }
}
