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
use App\Services\ActivityLogger;

class PdfApiController
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

    /**
     * @OA\Post(
     *     path="/api/pdf/merge",
     *     tags={"PDF"},
     *     summary="Merge multiple PDF files into one",
     *     description="Uploads multiple PDF files and merges them into one downloadable PDF document.",
     *     operationId="mergePDFs",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"files"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Array of PDF files to merge"
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional output file name without .pdf extension",
     *                     example="my-merged-document"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF merged successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Merging failed or output file was not created"
     *     )
     * )
     */

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
            $details = "Message: Failed to merge PDF files | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("merge", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("merge", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $details = "PDF files were merged successfully";
        ActivityLogger::log("merge", $details);
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    /**
     * @OA\Post(
     *     path="/api/pdf/rotate",
     *     tags={"PDF"},
     *     summary="Rotate pages in a PDF file",
     *     description="Rotates specified pages (or all pages) in an uploaded PDF file by a given angle.",
     *     operationId="rotatePDF",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "angle"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF file to rotate"
     *                 ),
     *                 @OA\Property(
     *                     property="angle",
     *                     type="integer",
     *                     description="Rotation angle (90, 180, 270, or negative)",
     *                     example=90,
     *                     enum={90, 180, 270, -90, -180, -270}
     *                 ),
     *                 @OA\Property(
     *                     property="pages",
     *                     type="string",
     *                     description="Pages to rotate (e.g. '1,2,4-6'), optional",
     *                     example="1,3-5"
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional name for the output file (without .pdf)",
     *                     example="rotated-document"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF rotated successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="PDF rotation failed or output not created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to rotate pages")
     *         )
     *     )
     * )
     */

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
            $details = "Message: Failed to rotate pages in PDF file | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("rotate", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("rotate", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $details = "PDF pages were rotated successfully";
        ActivityLogger::log("rotate", $details);
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    /**
     * @OA\Post(
     *     path="/api/pdf/delete-pages",
     *     tags={"PDF"},
     *     summary="Delete specific pages from a PDF file",
     *     description="Uploads a PDF and removes specified pages from it. Returns the modified PDF.",
     *     operationId="deletePages",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "pages"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF file to modify"
     *                 ),
     *                 @OA\Property(
     *                     property="pages",
     *                     type="string",
     *                     description="Pages to delete, e.g. '1,3,5-7'",
     *                     example="1,3-5"
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional name for the output file (without .pdf)",
     *                     example="cleaned-document"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF modified successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Deletion failed or output not created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete pages from PDF file")
     *         )
     *     )
     * )
     */

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
            $details = "Message: Failed to delete pages from PDF file | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("delete_pages", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("delete_pages", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $details = "PDF pages were deleted successfully";
        ActivityLogger::log("delete_pages", $details);
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    /**
     * @OA\Post(
     *     path="/api/pdf/extract-pages",
     *     tags={"PDF"},
     *     summary="Extract specific pages from a PDF file",
     *     description="Uploads a PDF file and extracts the specified pages into a new document.",
     *     operationId="extractPages",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "pages"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF file to extract pages from"
     *                 ),
     *                 @OA\Property(
     *                     property="pages",
     *                     type="string",
     *                     description="Pages to extract (e.g. '2,4-6')",
     *                     example="2,4-6"
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional name for the output file (without .pdf)",
     *                     example="extracted-pages"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF pages extracted successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Extraction failed or output not created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to extract pages from PDF file")
     *         )
     *     )
     * )
     */

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
            $details = "Message: Failed to extract pages from PDF file | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("extract_pages", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("extract_pages", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $details = "PDF pages were extracted successfully";
        ActivityLogger::log("extract_pages", $details);
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    /**
     * @OA\Post(
     *     path="/api/pdf/encrypt",
     *     tags={"PDF"},
     *     summary="Encrypt a PDF file with a password",
     *     description="Uploads a PDF file and encrypts it with a user password (and optional owner password). Returns the encrypted PDF.",
     *     operationId="encryptPDF",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "user_password"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF file to encrypt"
     *                 ),
     *                 @OA\Property(
     *                     property="user_password",
     *                     type="string",
     *                     description="Password required to open the PDF",
     *                     example="user123"
     *                 ),
     *                 @OA\Property(
     *                     property="owner_password",
     *                     type="string",
     *                     description="Owner password (optional)",
     *                     example="admin456"
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional name for the encrypted output file (without .pdf)",
     *                     example="secured-document"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF encrypted successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Encryption failed or output not created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to encrypt PDF file")
     *         )
     *     )
     * )
     */


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
            $details = "Message: Failed to encrypt PDF file | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("encrypt", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("encrypt", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $details = "PDF file was encrypted successfully";
        ActivityLogger::log("encrypt", $details);

        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    /**
     * @OA\Post(
     *     path="/api/pdf/decrypt",
     *     tags={"PDF"},
     *     summary="Decrypt a password-protected PDF file",
     *     description="Uploads a password-protected PDF and removes its encryption using the provided password. Returns the decrypted PDF.",
     *     operationId="decryptPDF",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "password"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Password-protected PDF file"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="Password to unlock the PDF",
     *                     example="user123"
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional name for the decrypted output file (without .pdf)",
     *                     example="unlocked-document"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF decrypted successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Decryption failed or output not created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to decrypt PDF file")
     *         )
     *     )
     * )
     */

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
            $details = "Message: Failed to decrypt PDF file | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("decrypt", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("decrypt", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
    
        $details = "PDF file was decrypted successfully";
        ActivityLogger::log("decrypt", $details);

        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    /**
     * @OA\Post(
     *     path="/api/pdf/overlay",
     *     tags={"PDF"},
     *     summary="Overlay one PDF over another",
     *     description="Uploads two PDF files. Overlays the second one (or selected page of it) over the first one on specified pages. Returns the resulting PDF.",
     *     operationId="overlayPDF",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"files"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="files",
     *                     type="array",
     *                     description="Exactly two PDF files: [base PDF, overlay PDF]",
     *                     @OA\Items(type="string", format="binary")
     *                 ),
     *                 @OA\Property(
     *                     property="overlay_page_number",
     *                     type="integer",
     *                     description="Page number from overlay PDF to use",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="pages",
     *                     type="string",
     *                     description="Pages in base PDF to apply overlay to (e.g. '1,3-5')",
     *                     example="1,2,4-6"
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional name for the output file (without .pdf)",
     *                     example="overlayed-document"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF overlay successful"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Overlay failed or output not created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to overlay PDF files")
     *         )
     *     )
     * )
     */

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
            $details = "Message: Failed to overlay PDF files | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("overlay", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("overlay", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $details = "PDF files were overlayed successfully";
        ActivityLogger::log("overlay", $details);
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    /**
     * @OA\Post(
     *     path="/api/pdf/extract-text",
     *     tags={"PDF"},
     *     summary="Extract text content from a PDF file",
     *     description="Uploads a PDF file and extracts the text content (optionally from specific pages). Returns a plain text file.",
     *     operationId="extractTextFromPDF",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF file to extract text from"
     *                 ),
     *                 @OA\Property(
     *                     property="pages",
     *                     type="string",
     *                     description="Pages to extract text from (e.g. '1,2,4-5')",
     *                     example="1,3-5"
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional name for the output text file (without .txt)",
     *                     example="extracted-text"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Text extracted successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Text extraction failed or output not created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to extract text from PDF file")
     *         )
     *     )
     * )
     */

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
            $details = "Message: Failed to extract text from PDF file | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("extract_text", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("extract_text", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $details = "Text was successfully extracted from PDF file";
        ActivityLogger::log("extract_text", $details);
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

  /**
     * @OA\Post(
     *     path="/api/pdf/reverse-pages",
     *     tags={"PDF"},
     *     summary="Reverse the order of pages in a PDF file",
     *     description="Uploads a PDF file and returns a new PDF with the page order reversed.",
     *     operationId="reversePDFPages",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF file whose pages should be reversed"
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional name for the output file (without .pdf)",
     *                     example="reversed-pages"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF pages reversed successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Reversal failed or output not created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to reverse pages in PDF file")
     *         )
     *     )
     * )
     */

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
            $details = "Message: Failed to reverse pages in PDF file | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("reverse_pages", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("reverse_pages", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $details = "PDF pages were reversed successfully";
        ActivityLogger::log("reverse_pages", $details);
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }

   /**
     * @OA\Post(
     *     path="/api/pdf/duplicate-pages",
     *     tags={"PDF"},
     *     summary="Duplicate specific pages in a PDF file",
     *     description="Uploads a PDF file and duplicates the specified pages a given number of times. Returns the modified PDF.",
     *     operationId="duplicatePDFPages",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "pages"},
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF file to duplicate pages from"
     *                 ),
     *                 @OA\Property(
     *                     property="pages",
     *                     type="string",
     *                     description="Pages to duplicate (e.g. '2,4-5')",
     *                     example="2,4-5"
     *                 ),
     *                 @OA\Property(
     *                     property="duplicate_count",
     *                     type="integer",
     *                     minimum=1,
     *                     maximum=10,
     *                     description="How many times to duplicate selected pages (default 1)",
     *                     example=2
     *                 ),
     *                 @OA\Property(
     *                     property="output_name",
     *                     type="string",
     *                     description="Optional name for the output file (without .pdf)",
     *                     example="duplicated-pages"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF pages duplicated successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Duplication failed or output not created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to duplicate pages in PDF file")
     *         )
     *     )
     * )
     */

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
            $details = "Message: Failed to duplicate pages in PDF file | Exit Code: {$result->exitCode()} | Error Output: {$result->errorOutput()}";
            ActivityLogger::log("duplicate_pages", $details);
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
            $details = "Output file was not created";
            ActivityLogger::log("duplicate_pages", $details);
            return response()->json([
                'success' => false,
                'message' => 'Output file was not created',
                'path' => $outputPath
            ], 500);
        }
        
        $details = "PDF pages were duplicated successfully";
        ActivityLogger::log("duplicate_pages", $details);
        
        $fileContent = file_get_contents($outputPath);
        
        $this->cleanupTempFiles($uploadPath);
    
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $outputName . '"')
            ->header('Content-Length', strlen($fileContent));
    }
}