<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Process;

class PdfApiController extends Controller
{
    private function cleanupTempFiles($directory)
    {
        if (file_exists($directory)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            
            rmdir($directory);
        }
    }

    public function merge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:2',
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'output_name' => 'sometimes|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $uploadedFiles = $request->file('files');
        $outputName = $request->input('output_name') ?? 'merged-document.pdf';
        
        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }
    
        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.pdf';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;
    
        $savedFilePaths = [];
        foreach ($uploadedFiles as $index => $file) {
            $filename = Str::uuid() . '.pdf';
            $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
            
            $file->move($uploadPath, $filename);
            
            $savedFilePaths[] = $filePath;
        }
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "merge",
            "--input"
        ];
        
        $command = array_merge($command, $savedFilePaths);
        
        $command[] = "--output";
        $command[] = $outputPath;

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to merge PDF files',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    public function rotate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'angle' => ['required', 'integer', 'in:90,180,270,-90,-180,-270'],
            'pages' => ['sometimes', 'string', 'regex:/^[0-9,\-]+$/'],
            'output_name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $uploadedFile = $request->file('file');
        $angle = $request->input('angle');
        $pages = $request->input('pages');
        $outputName = $request->input('output_name') ?? 'rotated-document.pdf';
        
        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }
    
        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.pdf';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;
    
        $filename = Str::uuid() . '.pdf';
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->move($uploadPath, $filename);
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "rotate",
            "--input",
            $filePath,
            "--output",
            $outputPath,
            "--angle",
            $angle,
            "--pages",
            $pages
        ];

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to rotate pages in PDF file',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    public function deletePages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'pages' => ['required', 'string', 'regex:/^[0-9,\-]+$/'],
            'output_name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $uploadedFile = $request->file('file');
        $pages = $request->input('pages');
        $outputName = $request->input('output_name') ?? 'new-document.pdf';
        
        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }
    
        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.pdf';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;
    
        $filename = Str::uuid() . '.pdf';
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->move($uploadPath, $filename);
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "delete_pages",
            "--input",
            $filePath,
            "--output",
            $outputPath,
            "--pages",
            $pages
        ];

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to delete pages from PDF file',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    public function extractPages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'pages' => ['required', 'string', 'regex:/^[0-9,\-]+$/'],
            'output_name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $uploadedFile = $request->file('file');
        $pages = $request->input('pages');
        $outputName = $request->input('output_name') ?? 'new-document.pdf';
        
        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }
    
        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.pdf';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;
    
        $filename = Str::uuid() . '.pdf';
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->move($uploadPath, $filename);
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "extract_pages",
            "--input",
            $filePath,
            "--output",
            $outputPath,
            "--pages",
            $pages
        ];

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to extract pages from PDF file',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    public function encrypt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'user_password' => ['required', 'string', 'min:1'],
            'owner_password' => ['sometimes', 'string', 'min:1'],
            'output_name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedFile = $request->file('file');
        $userPassword = $request->input('user_password');
        $ownerPassword = $request->input('owner_password');
        $outputName = $request->input('output_name') ?? 'encrypted-document.pdf';
        
        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }

        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.pdf';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;

        $filename = Str::uuid() . '.pdf';
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->move($uploadPath, $filename);
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "encrypt",
            "--input",
            $filePath,
            "--output",
            $outputPath,
            "--user-password",
            $userPassword,
            "--owner-password",
            $ownerPassword
        ];

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to encrypt PDF file',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    public function decrypt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'password' => ['required', 'string', 'min:1'],
            'output_name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedFile = $request->file('file');
        $password = $request->input('password');
        $outputName = $request->input('output_name') ?? 'decrypted-document.pdf';
        
        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }

        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.pdf';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;

        $filename = Str::uuid() . '.pdf';
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->move($uploadPath, $filename);
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "decrypt",
            "--input",
            $filePath,
            "--output",
            $outputPath,
            "--password",
            $password
        ];

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to decrypt PDF file',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    public function overlay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:2|max:2',
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'overlay_page_number' => ['sometimes', 'integer', 'min:1'],
            'pages' => ['sometimes', 'string', 'regex:/^[0-9,\-]+$/'],
            'output_name' => 'sometimes|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $uploadedFiles = $request->file('files');
        $overlayPageNumber = $request->input('overlay_page_number', 1);
        $pages = $request->input('pages');
        $outputName = $request->input('output_name') ?? 'merged-document.pdf';
        
        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }
    
        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.pdf';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;
    
        $savedFilePaths = [];
        foreach ($uploadedFiles as $index => $file) {
            $filename = Str::uuid() . '.pdf';
            $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
            
            $file->move($uploadPath, $filename);
            
            $savedFilePaths[] = $filePath;
        }
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "overlay",
            "--input",
            $savedFilePaths[0],
            "--overlay-pdf",
            $savedFilePaths[1],
            "--overlay-page-number",
            $overlayPageNumber,
            "--pages",
            $pages,
            "--output",
            $outputPath
        ];

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to overlay PDF files',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    public function extractText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'pages' => ['sometimes', 'string', 'regex:/^[0-9,\-]+$/'],
            'output_name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $uploadedFile = $request->file('file');
        $pages = $request->input('pages');
        $outputName = $request->input('output_name') ?? 'extracted-text.txt';
        
        if (!Str::endsWith($outputName, '.txt')) {
            $outputName .= '.txt';
        }
    
        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.txt';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;
    
        $filename = Str::uuid() . '.pdf';
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->move($uploadPath, $filename);
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "extract_text",
            "--input",
            $filePath,
            "--pages",
            $pages,
            "--output",
            $outputPath
        ];

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to extract text from PDF file',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    public function reversePages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'output_name' => 'sometimes|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $uploadedFile = $request->file('file');
        $outputName = $request->input('output_name') ?? 'reversed-document.pdf';
        
        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }
    
        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.pdf';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;
    
        $filename = Str::uuid() . '.pdf';
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->move($uploadPath, $filename);
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "reverse_pages",
            "--input",
            $filePath,
            "--output",
            $outputPath
        ];

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to reverse pages in PDF file',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    public function duplicatePages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:5000'],
            'pages' => ['required', 'string', 'regex:/^[0-9,\-]+$/'],
            'duplicate_count' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'output_name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $uploadedFile = $request->file('file');
        $pages = $request->input('pages');
        $duplicateCount = $request->input('duplicate_count', 1);
        $outputName = $request->input('output_name') ?? 'new-document.pdf';
        
        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }
    
        $batchId = Str::uuid();
        $uploadPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdfs' . DIRECTORY_SEPARATOR . $batchId);
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $outputFilename = Str::uuid() . '.pdf';
        $outputPath = $uploadPath . DIRECTORY_SEPARATOR . $outputFilename;
    
        $filename = Str::uuid() . '.pdf';
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->move($uploadPath, $filename);
        
        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE')),
            base_path(env('PYTHON_SCRIPT_PATH')),
            "--operation",
            "duplicate_pages",
            "--input",
            $filePath,
            "--output",
            $outputPath,
            "--pages",
            $pages,
            "--duplicate-count",
            $duplicateCount
        ];

        $result = Process::run($command, function ($type, $buffer) {
            \Log::info("Process output ({$type}): {$buffer}");
        });
        
        if ($result->exitCode() != 0 || $result->failed()) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to duplicate pages in PDF file',
                'error_details' => [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $result->errorOutput()
                ]
            ], 500);
        }
        if (!file_exists($outputPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }
}