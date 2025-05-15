<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FileTestController extends Controller
{
    public function showForm()
    {
        $phpInfo = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'max_file_uploads'    => ini_get('max_file_uploads'),
            'memory_limit'        => ini_get('memory_limit'),
        ];

        $uploadDir   = 'uploads/test';
        $absolutePath = storage_path('app/' . $uploadDir);
        $dirInfo      = [
            'target_directory' => $uploadDir,
            'absolute_path'    => $absolutePath,
            'exists'           => file_exists($absolutePath) ? 'Yes' : 'No',
            'writable'         => is_writable($absolutePath) ? 'Yes' : 'No',
        ];

        $serverInfo = [
            'php_version'     => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'disk_free_space' => disk_free_space('/') . ' bytes',
            'temp_dir'        => sys_get_temp_dir(),
        ];

        if (! file_exists($absolutePath)) {
            Storage::disk('local')->makeDirectory($uploadDir);
            $dirInfo['exists']   = file_exists($absolutePath) ? 'Yes' : 'No';
            $dirInfo['writable'] = is_writable($absolutePath) ? 'Yes' : 'No';
        }

        return Inertia::render('FileTest/Form', compact('phpInfo', 'dirInfo', 'serverInfo'));
    }

    public function processUpload(Request $request)
    {
        $phpInfo = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'max_file_uploads'    => ini_get('max_file_uploads'),
            'memory_limit'        => ini_get('memory_limit'),
        ];

        $uploadDir   = 'uploads/test';
        $absolutePath = storage_path('app/' . $uploadDir);
        $dirInfo      = [
            'target_directory' => $uploadDir,
            'absolute_path'    => $absolutePath,
            'exists'           => file_exists($absolutePath) ? 'Yes' : 'No',
            'writable'         => is_writable($absolutePath) ? 'Yes' : 'No',
        ];

        $serverInfo = [
            'php_version'     => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'disk_free_space' => disk_free_space('/') . ' bytes',
            'temp_dir'        => sys_get_temp_dir(),
        ];

        $result = [
            'success' => false,
            'message' => 'No file was uploaded',
            'details' => [],
        ];

        if (! $request->hasFile('testFile')) {
            return Inertia::render('FileTest/Form', compact('phpInfo', 'dirInfo', 'serverInfo', 'result'));
        }

        $file = $request->file('testFile');
        $fileInfo = [
            'original_name' => $file->getClientOriginalName(),
            'size'          => $file->getSize(),
            'mime_type'     => $file->getMimeType(),
            'extension'     => $file->getClientOriginalExtension(),
            'error_code'    => $file->getError(),
            'is_valid'      => $file->isValid() ? 'Yes' : 'No',
        ];

        if (! $file->isValid()) {
            $result['message'] = 'Invalid file upload';
            $result['details'] = $fileInfo;

            return Inertia::render('FileTest/Form', compact('phpInfo', 'dirInfo', 'serverInfo', 'result'));
        }

        try {
            // Ensure directory exists and is writable
            Storage::disk('local')->makeDirectory($uploadDir);

            // Build filename and paths
            $filename     = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $relativePath = "$uploadDir/$filename";
            $fullPath     = storage_path('app/' . $relativePath);

            // Move the uploaded file directly
            $moved = $file->move(dirname($fullPath), basename($fullPath));

            if ($moved && file_exists($fullPath)) {
                $result = [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'details' => [
                        'stored_path' => $relativePath,
                        'full_path'   => $fullPath,
                        'file_size'   => filesize($fullPath),
                    ],
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Failed to move uploaded file',
                    'details' => [
                        'expected_path' => $fullPath,
                    ],
                ];
            }
        } catch (\Exception $e) {
            Log::error('FileTest upload error', ['error' => $e->getMessage()]);

            $result = [
                'success' => false,
                'message' => 'Exception during file upload: ' . $e->getMessage(),
                'details' => [],
            ];
        }

        return Inertia::render('FileTest/Form', compact('phpInfo', 'dirInfo', 'serverInfo', 'result', 'fileInfo'));
    }
}
