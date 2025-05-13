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
                'error_output' => $result->errorOutput(),
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


    /**
     * 
     * Handles PDF rotate requests.
     */
    public function rotate(Request $request): JsonResponse
    {
        // TODO: Implement actual rotate logic
        return response()->json(['message' => 'Rotate endpoint reached successfully.'], 200);
    }

    /**
     * Handles PDF delete pages requests.
     */
    public function deletePages(Request $request): JsonResponse
    {
        // TODO: Implement actual delete pages logic
        return response()->json(['message' => 'Delete Pages endpoint reached successfully.'], 200);
    }

    /**
     * Handles PDF extract pages requests.
     */
    public function extractPages(Request $request): JsonResponse
    {
        // TODO: Implement actual extract pages logic
        return response()->json(['message' => 'Extract Pages endpoint reached successfully.'], 200);
    }

    /**
     * Handles PDF encrypt requests.
     */
    public function encrypt(Request $request): JsonResponse
    {
        // TODO: Implement actual encrypt logic
        return response()->json(['message' => 'Encrypt endpoint reached successfully.'], 200);
    }

    /**
     * Handles PDF decrypt requests.
     */
    public function decrypt(Request $request): JsonResponse
    {
        // TODO: Implement actual decrypt logic
        return response()->json(['message' => 'Decrypt endpoint reached successfully.'], 200);
    }

    /**
     * Handles PDF overlay requests.
     */
    public function overlay(Request $request): JsonResponse
    {
        // TODO: Implement actual overlay logic
        return response()->json(['message' => 'Overlay endpoint reached successfully.'], 200);
    }

    /**
     * Handles PDF extract text requests.
     */
    public function extractText(Request $request): JsonResponse
    {
        // TODO: Implement actual extract text logic
        return response()->json(['message' => 'Extract Text endpoint reached successfully.'], 200);
    }

    /**
     * Handles PDF reverse pages requests.
     */
    public function reversePages(Request $request): JsonResponse
    {
        // TODO: Implement actual reverse pages logic
        return response()->json(['message' => 'Reverse Pages endpoint reached successfully.'], 200);
    }

    /**
     * Handles PDF duplicate pages requests.
     */
    public function duplicatePages(Request $request): JsonResponse
    {
        // TODO: Implement actual duplicate pages logic
        return response()->json(['message' => 'Duplicate Pages endpoint reached successfully.'], 200);
    }
}