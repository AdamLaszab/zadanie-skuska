<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\ActivityLogger;

class PdfInertiaController extends Controller
{
    // Názov disku, ktorý budeme používať pre dočasné spracovanie PDF
    private string $processingDisk = 'pdf_temp_processing';

    // Pomocná funkcia na čistenie adresára na špecifikovanom disku
    private function cleanupTempDirectoryOnDisk(string $directoryRelativePath, string $diskName)
    {
        if (Storage::disk($diskName)->exists($directoryRelativePath)) {
            Log::info("Cleaning up temporary directory '{$directoryRelativePath}' on disk '{$diskName}'.");
            Storage::disk($diskName)->deleteDirectory($directoryRelativePath);
        } else {
            Log::warning("Attempted to clean up non-existent directory '{$directoryRelativePath}' on disk '{$diskName}'.");
        }
    }

    public function showMergeForm()
    {
        return Inertia::render('PdfTools/Merge');
    }

    public function processMerge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:2',
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:50000'],
            'output_name' => 'sometimes|nullable|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for PDF merge.', $validator->errors()->toArray());
            return back()->withErrors($validator->errors())->withInput();
        }

        Log::info("PDF Merge process started using disk '{$this->processingDisk}'.");

        $uploadedFiles = $request->file('files');
        $outputName = $request->input('output_name') ?? 'merged-document';

        if (!Str::endsWith($outputName, '.pdf')) {
            $outputName .= '.pdf';
        }
        Log::info("Output name set to: {$outputName}");

        $batchId = Str::uuid();
        // Adresár pre tento batch, relatívny k rootu disku 'pdf_temp_processing'
        $batchDirectoryRelative = $batchId; // Môžete pridať prefix, napr. 'uploads/' . $batchId

        // Absolútna cesta k adresáru pre tento batch (potrebná pre Python Process)
        $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

        Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
        Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

        try {
            // Vytvorí adresár na disku 'pdf_temp_processing'
            if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
                Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
                ActivityLogger::log("merge_inertia_failed", "Failed to create batch upload directory {$batchDirectoryRelative}", $request->user());
                return back()->withErrors(['process_error' => "System error: Could not create temporary upload directory."])->withInput();
            }
            Log::info("Successfully reported creation of directory (absolute): {$batchPathAbsolute}");

            $savedFilePathsAbsolute = [];
            foreach ($uploadedFiles as $index => $file) {
                if (!$file->isValid()) {
                    Log::error("Uploaded file #{$index} is invalid. Original name: '{$file->getClientOriginalName()}'");
                    // $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk); // Zvážte čistenie
                    return back()->withErrors(['process_error' => "One of the uploaded files is invalid."])->withInput();
                }

                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid() . '.' . $extension;
                // Cesta k súboru relatívna k rootu disku 'pdf_temp_processing'
                $fileRelativePathOnDisk = $batchDirectoryRelative . '/' . $filename;

                Log::info("Processing uploaded file #{$index}: trying to store as '{$filename}' to '{$fileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");

                // Uloží súbor na disk 'pdf_temp_processing'
                $file->storeAs($batchDirectoryRelative, $filename, $this->processingDisk);

                // Získame absolútnu cestu k uloženému súboru pre Python
                $currentFilePathAbsolute = Storage::disk($this->processingDisk)->path($fileRelativePathOnDisk);

                if (file_exists($currentFilePathAbsolute)) {
                    Log::info("File #{$index} successfully stored at: {$currentFilePathAbsolute}");
                    $savedFilePathsAbsolute[] = $currentFilePathAbsolute;
                } else {
                    Log::error("File #{$index} FAILED to store/verify at: {$currentFilePathAbsolute}. Original name was '{$file->getClientOriginalName()}'. Path used for storeAs: Dir='{$batchDirectoryRelative}', Name='{$filename}', Disk='{$this->processingDisk}'");
                    // $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk); // Zvážte čistenie
                    return back()->withErrors(['process_error' => "Error saving uploaded file."])->withInput();
                }
            }

            if (empty($savedFilePathsAbsolute) || count($savedFilePathsAbsolute) < 2) {
                Log::error("Not enough files were successfully saved for merging.");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                ActivityLogger::log("merge_inertia_failed", "Not enough files saved for merging", $request->user());
                return back()->withErrors(['process_error' => "Could not save all files for processing."])->withInput();
            }

            $outputTempFilename = Str::uuid() . '.pdf';
            // Absolútna cesta k dočasnému výstupnému súboru na disku 'pdf_temp_processing'
            $outputTempPathAbsolute = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputTempFilename;
            Log::info("Output temporary path (absolute) set to: {$outputTempPathAbsolute}");

            $command = [
                base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
                base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
                "--operation", "merge",
                "--input"
            ];
            $command = array_merge($command, $savedFilePathsAbsolute);
            $command[] = "--output";
            $command[] = $outputTempPathAbsolute;

            Log::info('Executing Python command: ' . implode(' ', $command));
            $result = Process::run($command);

            if (!$result->successful()) {
                $errorOutput = $result->errorOutput();
                Log::error("Python script execution failed. Exit Code: {$result->exitCode()}. Error: {$errorOutput}");
                // DO NOT cleanup $batchDirectoryRelative on $this->processingDisk here immediately if you want to inspect files
                $details = "Message: Failed to merge PDF files (Inertia) | Exit Code: {$result->exitCode()} | Error Output: {$errorOutput}";
                ActivityLogger::log("merge_inertia_failed", $details, $request->user());
                return back()->withErrors(['process_error' => 'Failed to merge PDF files. Details: ' . $errorOutput])->withInput();
            }

            Log::info("Python script executed successfully. STDOUT: " . $result->output());

            if (!file_exists($outputTempPathAbsolute)) {
                Log::error("Output file was not created by Python script at: {$outputTempPathAbsolute}");
                // DO NOT cleanup $batchDirectoryRelative on $this->processingDisk here immediately
                $details = "Output file was not created (Inertia)";
                ActivityLogger::log("merge_inertia_failed", $details, $request->user());
                return back()->withErrors(['process_error' => 'Output file was not created after processing.'])->withInput();
            }
            Log::info("Output file successfully created by Python script: {$outputTempPathAbsolute}");

            $details = "PDF files were merged successfully (Inertia)";
            ActivityLogger::log("merge_inertia_success", $details, $request->user());

            // Pre sťahovanie budeme používať ten istý 'pdf_temp_processing' disk,
            // ale vytvoríme iný (alebo rovnaký) dočasný adresár pre download link.
            // Alebo môžeme súbor streamovať priamo z $outputTempPathAbsolute.
            // Pre jednoduchosť ho ponecháme na 'pdf_temp_processing' disku a len si zapamätáme cestu.

            $finalFileName = $outputName;
            // Cesta k výstupnému súboru relatívna k rootu disku 'pdf_temp_processing'
            $outputTempFileRelativeOnDisk = $batchDirectoryRelative . '/' . $outputTempFilename;


            // Pripravíme URL na stiahnutie
            $downloadToken = Str::random(40);
            // Do session uložíme cestu relatívnu k 'pdf_temp_processing' disku a názov disku
            session(['download_file_path_' . $downloadToken => $outputTempFileRelativeOnDisk]);
            session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
            $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);
            Log::info("Generated download URL: {$downloadUrl} for file (relative to '{$this->processingDisk}' disk): {$outputTempFileRelativeOnDisk}");

            // Tu by ste mali zvážiť, kedy presne vyčistiť $batchDirectoryRelative na $this->processingDisk.
            // Ak download link má byť platný len krátko, môžete to urobiť po určitom čase cez naplánovanú úlohu.
            // Pre tento príklad ho zatiaľ necháme (NEČISTÍME HO HNEĎ PO VYTVORENÍ LINKU).
            // $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);


            return Inertia::render('PdfTools/MergeResult', [
                'successMessage' => 'PDF files merged successfully!',
                'downloadUrl' => $downloadUrl,
                'fileName' => $finalFileName,
            ]);

        } catch (\Throwable $e) {
            Log::critical("Critical error during PDF merge process using disk '{$this->processingDisk}': " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            if (isset($batchDirectoryRelative)) {
                // $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk); // Zvážte čistenie
            }
            ActivityLogger::log("merge_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
            return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
        }
    }
    public function showExtractPagesForm()
    {
        return Inertia::render('PdfTools/ExtractPages');
    }

    public function processExtractPages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:50000'],
            'pages' => ['required', 'string', 'regex:/^[0-9,\-\s]+$/'], // Umožníme aj medzery
            'output_name' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for PDF page extraction.', $validator->errors()->toArray());
            return back()->withErrors($validator->errors())->withInput();
        }

        Log::info("PDF Extract Pages process started using disk '{$this->processingDisk}'.");

        $uploadedFile = $request->file('file');
        $pagesToExtract = $request->input('pages');
        // Odstránime medzery z pagesToExtract pre Python skript
        $pagesToExtractSanitized = $pagesToExtract ? preg_replace('/\s+/', '', $pagesToExtract) : null;

        $outputFileName = $request->input('output_name') ?? 'extracted-pages';
        if (!Str::endsWith($outputFileName, '.pdf')) {
            $outputFileName .= '.pdf';
        }
        Log::info("Output PDF name set to: {$outputFileName}");

        $batchId = Str::uuid();
        $batchDirectoryRelative = $batchId; // Adresár pre tento batch na processingDisk
        $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

        Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
        Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

        try {
            if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
                Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
                return back()->withErrors(['process_error' => "System error: Could not create temporary directory."])->withInput();
            }
            Log::info("Successfully created batch directory (absolute): {$batchPathAbsolute}");

            // Uloženie nahraného súboru
            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $inputTempFilename = Str::uuid() . '.' . $originalExtension; // Dočasný názov pre vstupný súbor
            $inputFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $inputTempFilename;

            Log::info("Processing uploaded file: trying to store as '{$inputTempFilename}' to '{$inputFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
            $uploadedFile->storeAs($batchDirectoryRelative, $inputTempFilename, $this->processingDisk);
            $inputFilePathAbsolute = Storage::disk($this->processingDisk)->path($inputFileRelativePathOnDisk);

            if (!file_exists($inputFilePathAbsolute)) {
                Log::error("Uploaded file FAILED to store/verify at: {$inputFilePathAbsolute}.");
                return back()->withErrors(['process_error' => "Error saving uploaded file."])->withInput();
            }
            Log::info("Uploaded file successfully stored at: {$inputFilePathAbsolute}");

            // Názov a cesta pre výstupný súbor (vytvorený Pythonom)
            $outputTempPythonFilename = Str::uuid() . '.pdf';
            $outputFilePathAbsoluteByPython = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputTempPythonFilename;

            // Príkaz pre Python
            $command = [
                base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
                base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
                "--operation", "extract_pages",
                "--input", $inputFilePathAbsolute,
                "--output", $outputFilePathAbsoluteByPython, // Python vytvorí tento súbor
                "--pages", $pagesToExtractSanitized,
            ];

            Log::info('Executing Python command: ' . implode(' ', $command));
            $result = Process::run($command);

            if (!$result->successful()) {
                $errorOutput = $result->errorOutput();
                Log::error("Python script execution failed for extract_pages. Exit Code: {$result->exitCode()}. Error: {$errorOutput}");
                $details = "Message: Failed to extract pages (Inertia) | Exit Code: {$result->exitCode()} | Error Output: {$errorOutput}";
                ActivityLogger::log("extract_pages_inertia_failed", $details, $request->user());
                return back()->withErrors(['process_error' => 'Failed to extract pages. Details: ' . $errorOutput])->withInput();
            }

            // Python skript by mal na stdout vypísať cestu k výslednému PDF súboru
            // V tomto prípade je to $outputFilePathAbsoluteByPython, ktorú sme mu dali. Overíme, či existuje.
            // Ak Python skript vracia cestu na stdout, použijeme ju: $returnedPath = trim($result->output());
            // Pre konzistenciu s tým, ako to bolo v `PdfApiController`, predpokladáme, že $outputFilePathAbsoluteByPython je správna.

            Log::info("Python script STDOUT for extract_pages: " . trim($result->output())); // Logujeme, čo Python vrátil

            if (!file_exists($outputFilePathAbsoluteByPython)) {
                Log::error("Output PDF file was not created by Python script at: {$outputFilePathAbsoluteByPython}");
                $details = "Python script output error or extracted PDF file not found.";
                ActivityLogger::log("extract_pages_inertia_failed", $details, $request->user());
                return back()->withErrors(['process_error' => 'Processing error: Could not retrieve extracted PDF.'])->withInput();
            }
            Log::info("Extracted PDF file successfully created by Python script: {$outputFilePathAbsoluteByPython}");

            $details = "PDF pages were extracted successfully (Inertia)";
            ActivityLogger::log("extract_pages_inertia_success", $details, $request->user());

            // Pripravíme PDF na stiahnutie
            $downloadToken = Str::random(40);
            // Cesta k výstupnému súboru relatívna k rootu disku 'pdf_temp_processing'
            $outputFileRelativeOnDisk = $batchDirectoryRelative . '/' . $outputTempPythonFilename;

            session(['download_file_path_' . $downloadToken => $outputFileRelativeOnDisk]);
            session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
            session(['download_file_name_' . $downloadToken => $outputFileName]); // Použijeme nami zadaný názov
            $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);

            Log::info("Generated download URL: {$downloadUrl} for extracted PDF (relative to '{$this->processingDisk}' disk): {$outputFileRelativeOnDisk}");

            // Celý $batchDirectoryRelative by sa mal vyčistiť neskôr.
            // Môžeme zmazať pôvodný nahraný vstupný PDF súbor ($inputFileRelativePathOnDisk), ak ho už nepotrebujeme.
            // Storage::disk($this->processingDisk)->delete($inputFileRelativePathOnDisk);

            return Inertia::render('PdfTools/ExtractPagesResult', [
                'successMessage' => 'PDF pages extracted successfully!',
                'downloadUrl' => $downloadUrl,
                'fileName' => $outputFileName,
            ]);

        } catch (\Throwable $e) {
            Log::critical("Critical error during PDF extract_pages process using disk '{$this->processingDisk}': " . $e->getMessage(), ['exception' => $e]);
            if (isset($batchDirectoryRelative)) {
                 // $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            }
            ActivityLogger::log("extract_pages_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
            return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
        }
    }

    public function showRotateForm()
    {
        return Inertia::render('PdfTools/Rotate');
    }

    public function processRotate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:50000'],
            'angle' => ['required', 'integer', 'in:90,180,270,-90,-180,-270'], // PyPDF2 zvláda aj záporné
            'pages' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9,\-\sA-Za-z]+$/'], // Umožníme aj 'all' a medzery
            'output_name' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for PDF page rotation.', $validator->errors()->toArray());
            return back()->withErrors($validator->errors())->withInput();
        }

        Log::info("PDF Rotate Pages process started using disk '{$this->processingDisk}'.");

        $uploadedFile = $request->file('file');
        $angle = $request->input('angle');
        $pagesToRotate = $request->input('pages'); // Môže byť null, "all", alebo "1,2-5"
        $pagesToRotateSanitized = $pagesToRotate ? preg_replace('/\s+/', '', $pagesToRotate) : 'all'; // Default na 'all' ak je null/empty

        $outputNameForDownload = $request->input('output_name') ?? 'rotated-document';
        if (!Str::endsWith($outputNameForDownload, '.pdf')) {
            $outputNameForDownload .= '.pdf';
        }
        Log::info("Output PDF name (for download) set to: {$outputNameForDownload}");

        $batchId = Str::uuid();
        $batchDirectoryRelative = $batchId;
        $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

        Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
        Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

        try {
            if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
                Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
                return back()->withErrors(['process_error' => "System error: Could not create temporary directory."])->withInput();
            }
            Log::info("Successfully created batch directory (absolute): {$batchPathAbsolute}");

            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $inputTempFilename = Str::uuid() . '.' . $originalExtension;
            $inputFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $inputTempFilename;

            Log::info("Storing uploaded file as '{$inputTempFilename}' to '{$inputFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
            $uploadedFile->storeAs($batchDirectoryRelative, $inputTempFilename, $this->processingDisk);
            $inputFilePathAbsolute = Storage::disk($this->processingDisk)->path($inputFileRelativePathOnDisk);

            if (!file_exists($inputFilePathAbsolute)) {
                Log::error("Uploaded file FAILED to store/verify at: {$inputFilePathAbsolute}.");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => "Error saving uploaded file."])->withInput();
            }
            Log::info("Uploaded file successfully stored at: {$inputFilePathAbsolute}");

            $outputPythonFilename = Str::uuid() . '.pdf'; // Toto je NOVÉ otočené PDF
            $outputFilePathAbsoluteByPython = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputPythonFilename;
            Log::info("PHP expects Python to create rotated PDF at: {$outputFilePathAbsoluteByPython}");

            $command = [
                base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
                base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
                "--operation", "rotate",
                "--input", $inputFilePathAbsolute,
                "--output", $outputFilePathAbsoluteByPython,
                "--angle", (string)$angle, // Python očakáva string pre argparse
            ];
            // Pridáme --pages len ak je zadané a nie je 'all' (pretože 'all' je default správanie v našom Python skripte, ak --pages chýba)
            if ($pagesToRotateSanitized && strtolower($pagesToRotateSanitized) !== 'all') {
                $command[] = "--pages";
                $command[] = $pagesToRotateSanitized;
            } elseif (empty($pagesToRotateSanitized)) { // Ak používateľ nič nezadal, pošleme 'all'
                $command[] = "--pages";
                $command[] = "all";
            }
            // Ak je $pagesToRotateSanitized == 'all', neposielame --pages parameter, Python skript si to ošetrí ako default


            Log::info('Executing Python command for rotate: ' . implode(' ', $command));
            $result = Process::run($command);

            Log::info("Python Process (rotate) Ran. Successful: " . ($result->successful() ? 'Yes' : 'No') . ", Exit Code: " . $result->exitCode());
            Log::info("Python STDOUT (rotate): '" . trim($result->output()) . "'");
            Log::info("Python STDERR (rotate): '" . trim($result->errorOutput()) . "'");

            if (!$result->successful()) {
                $errorOutput = $result->errorOutput();
                Log::error("Python script (rotate) execution failed. Error: {$errorOutput}");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Failed to rotate PDF pages. Details: ' . $errorOutput])->withInput();
            }
            
            $finalRotatedFilePathAbsolute = ""; // Definitívna cesta k súboru
            $pythonReturnedPath = trim($result->output());

            if (!empty($pythonReturnedPath) && file_exists($pythonReturnedPath)) {
                $finalRotatedFilePathAbsolute = $pythonReturnedPath;
                 if ($pythonReturnedPath !== $outputFilePathAbsoluteByPython) {
                    Log::warning("Path from Python STDOUT ('{$pythonReturnedPath}') differs from PHP's expected output path ('{$outputFilePathAbsoluteByPython}'), but STDOUT path is valid. Using STDOUT path.");
                }
            } elseif (file_exists($outputFilePathAbsoluteByPython)) {
                Log::warning("Python STDOUT was not a valid path or empty, but file exists at PHP's expected output path: '{$outputFilePathAbsoluteByPython}'. Using this path.");
                $finalRotatedFilePathAbsolute = $outputFilePathAbsoluteByPython;
            } else {
                Log::error("Rotated PDF file was not found. Python STDOUT: '{$pythonReturnedPath}', PHP Expected: '{$outputFilePathAbsoluteByPython}'");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Processing error: Could not retrieve rotated PDF.'])->withInput();
            }
            Log::info("Using final rotated PDF file (absolute path): {$finalRotatedFilePathAbsolute}");

            ActivityLogger::log("rotate_inertia_success", "PDF pages rotated: {$outputNameForDownload}", $request->user());

            $rotatedFileRelativeOnDisk = str_replace(Storage::disk($this->processingDisk)->path(''), '', $finalRotatedFilePathAbsolute);
            $rotatedFileRelativeOnDisk = ltrim($rotatedFileRelativeOnDisk, DIRECTORY_SEPARATOR);

            $downloadToken = Str::random(40);
            session(['download_file_path_' . $downloadToken => $rotatedFileRelativeOnDisk]);
            session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
            session(['download_file_name_' . $downloadToken => $outputNameForDownload]);
            $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);

            Log::info("Generated download URL: {$downloadUrl} for rotated file '{$rotatedFileRelativeOnDisk}' on disk '{$this->processingDisk}'.");

            return Inertia::render('PdfTools/RotateResult', [
                'successMessage' => 'PDF pages rotated successfully!',
                'downloadUrl' => $downloadUrl,
                'fileName' => $outputNameForDownload,
            ]);

        } catch (\Throwable $e) {
            Log::critical("Critical error during PDF rotate: " . $e->getMessage(), ['exception' => $e]);
            if (isset($batchDirectoryRelative)) {
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            }
            ActivityLogger::log("rotate_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
            return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
        }
    }
    public function showDeletePagesForm()
    {
        return Inertia::render('PdfTools/DeletePages');
    }

    public function processDeletePages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:50000'],
            'pages' => ['required', 'string', 'regex:/^[0-9,\-\s]+$/'], // Strany na vymazanie
            'output_name' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for PDF page deletion.', $validator->errors()->toArray());
            return back()->withErrors($validator->errors())->withInput();
        }

        Log::info("PDF Delete Pages process started using disk '{$this->processingDisk}'.");

        $uploadedFile = $request->file('file');
        $pagesToDelete = $request->input('pages');
        $pagesToDeleteSanitized = $pagesToDelete ? preg_replace('/\s+/', '', $pagesToDelete) : null;

        if (empty($pagesToDeleteSanitized)) { // Po sanitizácii, ak bol vstup len medzery
            Log::error('Pages to delete specification was empty after sanitization.');
            return back()->withErrors(['pages' => 'Please specify which pages to delete.'])->withInput();
        }

        $outputNameForDownload = $request->input('output_name') ?? 'document-with-deleted-pages';
        if (!Str::endsWith($outputNameForDownload, '.pdf')) {
            $outputNameForDownload .= '.pdf';
        }
        Log::info("Output PDF name (for download) set to: {$outputNameForDownload}");

        $batchId = Str::uuid();
        $batchDirectoryRelative = $batchId;
        $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

        Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
        Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

        try {
            if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
                Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
                return back()->withErrors(['process_error' => "System error: Could not create temporary directory."])->withInput();
            }
            Log::info("Successfully created batch directory (absolute): {$batchPathAbsolute}");

            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $inputTempFilename = Str::uuid() . '.' . $originalExtension;
            $inputFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $inputTempFilename;

            Log::info("Storing uploaded file as '{$inputTempFilename}' to '{$inputFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
            $uploadedFile->storeAs($batchDirectoryRelative, $inputTempFilename, $this->processingDisk);
            $inputFilePathAbsolute = Storage::disk($this->processingDisk)->path($inputFileRelativePathOnDisk);

            if (!file_exists($inputFilePathAbsolute)) {
                Log::error("Uploaded file FAILED to store/verify at: {$inputFilePathAbsolute}.");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => "Error saving uploaded file."])->withInput();
            }
            Log::info("Uploaded file successfully stored at: {$inputFilePathAbsolute}");

            $outputPythonFilename = Str::uuid() . '.pdf'; // Toto je NOVÉ PDF bez vymazaných strán
            $outputFilePathAbsoluteByPython = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputPythonFilename;
            Log::info("PHP expects Python to create PDF with deleted pages at: {$outputFilePathAbsoluteByPython}");

            $command = [
                base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
                base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
                "--operation", "delete_pages",
                "--input", $inputFilePathAbsolute,
                "--output", $outputFilePathAbsoluteByPython,
                "--pages", $pagesToDeleteSanitized, // Strany na vymazanie
            ];

            Log::info('Executing Python command for delete_pages: ' . implode(' ', $command));
            $result = Process::run($command);

            Log::info("Python Process (delete_pages) Ran. Successful: " . ($result->successful() ? 'Yes' : 'No') . ", Exit Code: " . $result->exitCode());
            Log::info("Python STDOUT (delete_pages): '" . trim($result->output()) . "'");
            Log::info("Python STDERR (delete_pages): '" . trim($result->errorOutput()) . "'");

            if (!$result->successful()) {
                $errorOutput = $result->errorOutput();
                Log::error("Python script (delete_pages) execution failed. Error: {$errorOutput}");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Failed to delete pages from PDF. Details: ' . $errorOutput])->withInput();
            }
            
            $finalProcessedFilePathAbsolute = "";
            $pythonReturnedPath = trim($result->output());

            if (!empty($pythonReturnedPath) && file_exists($pythonReturnedPath)) {
                $finalProcessedFilePathAbsolute = $pythonReturnedPath;
                 if ($pythonReturnedPath !== $outputFilePathAbsoluteByPython) {
                    Log::warning("Path from Python STDOUT ('{$pythonReturnedPath}') differs from PHP's expected output path ('{$outputFilePathAbsoluteByPython}'), but STDOUT path is valid. Using STDOUT path.");
                }
            } elseif (file_exists($outputFilePathAbsoluteByPython)) {
                Log::warning("Python STDOUT was not a valid path or empty, but file exists at PHP's expected output path: '{$outputFilePathAbsoluteByPython}'. Using this path.");
                $finalProcessedFilePathAbsolute = $outputFilePathAbsoluteByPython;
            } else {
                Log::error("Processed PDF (deleted pages) was not found. Python STDOUT: '{$pythonReturnedPath}', PHP Expected: '{$outputFilePathAbsoluteByPython}'");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Processing error: Could not retrieve processed PDF.'])->withInput();
            }
            Log::info("Using final processed PDF (deleted pages) file (absolute path): {$finalProcessedFilePathAbsolute}");

            ActivityLogger::log("delete_pages_inertia_success", "PDF pages deleted: {$outputNameForDownload}", $request->user());

            $processedFileRelativeOnDisk = str_replace(Storage::disk($this->processingDisk)->path(''), '', $finalProcessedFilePathAbsolute);
            $processedFileRelativeOnDisk = ltrim($processedFileRelativeOnDisk, DIRECTORY_SEPARATOR);

            $downloadToken = Str::random(40);
            session(['download_file_path_' . $downloadToken => $processedFileRelativeOnDisk]);
            session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
            session(['download_file_name_' . $downloadToken => $outputNameForDownload]);
            $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);

            Log::info("Generated download URL: {$downloadUrl} for PDF with deleted pages '{$processedFileRelativeOnDisk}' on disk '{$this->processingDisk}'.");

            return Inertia::render('PdfTools/DeletePagesResult', [
                'successMessage' => 'Specified PDF pages deleted successfully!',
                'downloadUrl' => $downloadUrl,
                'fileName' => $outputNameForDownload,
            ]);

        } catch (\Throwable $e) {
            Log::critical("Critical error during PDF delete_pages: " . $e->getMessage(), ['exception' => $e]);
            if (isset($batchDirectoryRelative)) {
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            }
            ActivityLogger::log("delete_pages_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
            return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
        }
    }
 public function showEncryptForm()
    {
        return Inertia::render('PdfTools/Encrypt');
    }

    public function processEncrypt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:50000'],
            'user_password' => ['required', 'string', 'min:4'], // Odporúčam aspoň minimálnu dĺžku
            'owner_password' => ['nullable', 'string', 'min:4', 'different:user_password'], // Ak je zadané, iné ako user_pass
            'output_name' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ], [
            'owner_password.different' => 'The owner password must be different from the user password.'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for PDF encryption.', $validator->errors()->toArray());
            return back()->withErrors($validator->errors())->withInput();
        }

        Log::info("PDF Encrypt process started using disk '{$this->processingDisk}'.");

        $uploadedFile = $request->file('file');
        $userPassword = $request->input('user_password');
        $ownerPassword = $request->input('owner_password'); // Môže byť null

        $outputNameForDownload = $request->input('output_name') ?? 'encrypted-document';
        if (!Str::endsWith($outputNameForDownload, '.pdf')) {
            $outputNameForDownload .= '.pdf';
        }
        Log::info("Output PDF name (for download) set to: {$outputNameForDownload}");

        $batchId = Str::uuid();
        $batchDirectoryRelative = $batchId;
        $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

        Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
        Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

        try {
            if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
                Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
                return back()->withErrors(['process_error' => "System error: Could not create temporary directory."])->withInput();
            }
            Log::info("Successfully created batch directory (absolute): {$batchPathAbsolute}");

            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $inputTempFilename = Str::uuid() . '.' . $originalExtension;
            $inputFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $inputTempFilename;

            Log::info("Storing uploaded file as '{$inputTempFilename}' to '{$inputFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
            $uploadedFile->storeAs($batchDirectoryRelative, $inputTempFilename, $this->processingDisk);
            $inputFilePathAbsolute = Storage::disk($this->processingDisk)->path($inputFileRelativePathOnDisk);

            if (!file_exists($inputFilePathAbsolute)) {
                Log::error("Uploaded file FAILED to store/verify at: {$inputFilePathAbsolute}.");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => "Error saving uploaded file."])->withInput();
            }
            Log::info("Uploaded file successfully stored at: {$inputFilePathAbsolute}");

            $outputPythonFilename = Str::uuid() . '.pdf'; // Toto je NOVÉ zašifrované PDF
            $outputFilePathAbsoluteByPython = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputPythonFilename;
            Log::info("PHP expects Python to create encrypted PDF at: {$outputFilePathAbsoluteByPython}");

            $command = [
                base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
                base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
                "--operation", "encrypt",
                "--input", $inputFilePathAbsolute,
                "--output", $outputFilePathAbsoluteByPython,
                "--user-password", $userPassword,
            ];
            if (!empty($ownerPassword)) {
                $command[] = "--owner-password";
                $command[] = $ownerPassword;
            }

            Log::info('Executing Python command for encrypt: ' . implode(' ', $command));
            $result = Process::run($command);

            Log::info("Python Process (encrypt) Ran. Successful: " . ($result->successful() ? 'Yes' : 'No') . ", Exit Code: " . $result->exitCode());
            Log::info("Python STDOUT (encrypt): '" . trim($result->output()) . "'");
            Log::info("Python STDERR (encrypt): '" . trim($result->errorOutput()) . "'");

            if (!$result->successful()) {
                $errorOutput = $result->errorOutput();
                Log::error("Python script (encrypt) execution failed. Error: {$errorOutput}");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Failed to encrypt PDF. Details: ' . $errorOutput])->withInput();
            }
            
            $finalEncryptedFilePathAbsolute = "";
            $pythonReturnedPath = trim($result->output());

            if (!empty($pythonReturnedPath) && file_exists($pythonReturnedPath)) {
                $finalEncryptedFilePathAbsolute = $pythonReturnedPath;
                 if ($pythonReturnedPath !== $outputFilePathAbsoluteByPython) {
                    Log::warning("Path from Python STDOUT ('{$pythonReturnedPath}') differs from PHP's expected output path ('{$outputFilePathAbsoluteByPython}'), but STDOUT path is valid. Using STDOUT path.");
                }
            } elseif (file_exists($outputFilePathAbsoluteByPython)) {
                Log::warning("Python STDOUT was not a valid path or empty, but file exists at PHP's expected output path: '{$outputFilePathAbsoluteByPython}'. Using this path.");
                $finalEncryptedFilePathAbsolute = $outputFilePathAbsoluteByPython;
            } else {
                Log::error("Encrypted PDF file was not found. Python STDOUT: '{$pythonReturnedPath}', PHP Expected: '{$outputFilePathAbsoluteByPython}'");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Processing error: Could not retrieve encrypted PDF.'])->withInput();
            }
            Log::info("Using final encrypted PDF file (absolute path): {$finalEncryptedFilePathAbsolute}");

            ActivityLogger::log("encrypt_inertia_success", "PDF file encrypted: {$outputNameForDownload}", $request->user());

            $encryptedFileRelativeOnDisk = str_replace(Storage::disk($this->processingDisk)->path(''), '', $finalEncryptedFilePathAbsolute);
            $encryptedFileRelativeOnDisk = ltrim($encryptedFileRelativeOnDisk, DIRECTORY_SEPARATOR);

            $downloadToken = Str::random(40);
            session(['download_file_path_' . $downloadToken => $encryptedFileRelativeOnDisk]);
            session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
            session(['download_file_name_' . $downloadToken => $outputNameForDownload]);
            $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);

            Log::info("Generated download URL: {$downloadUrl} for encrypted PDF '{$encryptedFileRelativeOnDisk}' on disk '{$this->processingDisk}'.");

            return Inertia::render('PdfTools/EncryptResult', [
                'successMessage' => 'PDF file encrypted successfully!',
                'downloadUrl' => $downloadUrl,
                'fileName' => $outputNameForDownload,
            ]);

        } catch (\Throwable $e) {
            Log::critical("Critical error during PDF encrypt: " . $e->getMessage(), ['exception' => $e]);
            if (isset($batchDirectoryRelative)) {
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            }
            ActivityLogger::log("encrypt_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
            return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
        }
    }
    public function showDecryptForm()
    {
        return Inertia::render('PdfTools/Decrypt');
    }

    public function processDecrypt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:50000'],
            'password' => ['required', 'string', 'min:1'], // Heslo na dešifrovanie
            'output_name' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for PDF decryption.', $validator->errors()->toArray());
            return back()->withErrors($validator->errors())->withInput();
        }

        Log::info("PDF Decrypt process started using disk '{$this->processingDisk}'.");

        $uploadedFile = $request->file('file');
        $password = $request->input('password');

        $outputNameForDownload = $request->input('output_name') ?? 'decrypted-document';
        if (!Str::endsWith($outputNameForDownload, '.pdf')) {
            $outputNameForDownload .= '.pdf';
        }
        Log::info("Output PDF name (for download) set to: {$outputNameForDownload}");

        $batchId = Str::uuid();
        $batchDirectoryRelative = $batchId;
        $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

        Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
        Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

        try {
            if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
                Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
                return back()->withErrors(['process_error' => "System error: Could not create temporary directory."])->withInput();
            }
            Log::info("Successfully created batch directory (absolute): {$batchPathAbsolute}");

            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $inputTempFilename = Str::uuid() . '.' . $originalExtension;
            $inputFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $inputTempFilename;

            Log::info("Storing uploaded file as '{$inputTempFilename}' to '{$inputFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
            $uploadedFile->storeAs($batchDirectoryRelative, $inputTempFilename, $this->processingDisk);
            $inputFilePathAbsolute = Storage::disk($this->processingDisk)->path($inputFileRelativePathOnDisk);

            if (!file_exists($inputFilePathAbsolute)) {
                Log::error("Uploaded file FAILED to store/verify at: {$inputFilePathAbsolute}.");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => "Error saving uploaded file."])->withInput();
            }
            Log::info("Uploaded file successfully stored at: {$inputFilePathAbsolute}");

            $outputPythonFilename = Str::uuid() . '.pdf'; // Toto je NOVÉ dešifrované PDF
            $outputFilePathAbsoluteByPython = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputPythonFilename;
            Log::info("PHP expects Python to create decrypted PDF at: {$outputFilePathAbsoluteByPython}");

            $command = [
                base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
                base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
                "--operation", "decrypt",
                "--input", $inputFilePathAbsolute,
                "--output", $outputFilePathAbsoluteByPython,
                "--password", $password,
            ];

            Log::info('Executing Python command for decrypt: ' . implode(' ', $command));
            $result = Process::run($command);

            Log::info("Python Process (decrypt) Ran. Successful: " . ($result->successful() ? 'Yes' : 'No') . ", Exit Code: " . $result->exitCode());
            Log::info("Python STDOUT (decrypt): '" . trim($result->output()) . "'");
            Log::info("Python STDERR (decrypt): '" . trim($result->errorOutput()) . "'");

            if (!$result->successful()) {
                $errorOutput = $result->errorOutput();
                Log::error("Python script (decrypt) execution failed. Error: {$errorOutput}");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Failed to decrypt PDF. Details: ' . $errorOutput])->withInput();
            }
            
            $finalDecryptedFilePathAbsolute = "";
            $pythonReturnedPath = trim($result->output());

            if (!empty($pythonReturnedPath) && file_exists($pythonReturnedPath)) {
                $finalDecryptedFilePathAbsolute = $pythonReturnedPath;
                 if ($pythonReturnedPath !== $outputFilePathAbsoluteByPython) {
                    Log::warning("Path from Python STDOUT ('{$pythonReturnedPath}') differs from PHP's expected output path ('{$outputFilePathAbsoluteByPython}'), but STDOUT path is valid. Using STDOUT path.");
                }
            } elseif (file_exists($outputFilePathAbsoluteByPython)) {
                Log::warning("Python STDOUT was not a valid path or empty, but file exists at PHP's expected output path: '{$outputFilePathAbsoluteByPython}'. Using this path.");
                $finalDecryptedFilePathAbsolute = $outputFilePathAbsoluteByPython;
            } else {
                Log::error("Decrypted PDF file was not found. Python STDOUT: '{$pythonReturnedPath}', PHP Expected: '{$outputFilePathAbsoluteByPython}'");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Processing error: Could not retrieve decrypted PDF.'])->withInput();
            }
            Log::info("Using final decrypted PDF file (absolute path): {$finalDecryptedFilePathAbsolute}");

            ActivityLogger::log("decrypt_inertia_success", "PDF file decrypted: {$outputNameForDownload}", $request->user());

            $decryptedFileRelativeOnDisk = str_replace(Storage::disk($this->processingDisk)->path(''), '', $finalDecryptedFilePathAbsolute);
            $decryptedFileRelativeOnDisk = ltrim($decryptedFileRelativeOnDisk, DIRECTORY_SEPARATOR);

            $downloadToken = Str::random(40);
            session(['download_file_path_' . $downloadToken => $decryptedFileRelativeOnDisk]);
            session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
            session(['download_file_name_' . $downloadToken => $outputNameForDownload]);
            $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);

            Log::info("Generated download URL: {$downloadUrl} for decrypted PDF '{$decryptedFileRelativeOnDisk}' on disk '{$this->processingDisk}'.");

            return Inertia::render('PdfTools/DecryptResult', [
                'successMessage' => 'PDF file decrypted successfully!',
                'downloadUrl' => $downloadUrl,
                'fileName' => $outputNameForDownload,
            ]);

        } catch (\Throwable $e) {
            Log::critical("Critical error during PDF decrypt: " . $e->getMessage(), ['exception' => $e]);
            if (isset($batchDirectoryRelative)) {
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            }
            ActivityLogger::log("decrypt_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
            return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
        }
    }
public function showOverlayForm()
    {
        return Inertia::render('PdfTools/Overlay');
    }

    public function processOverlay(Request $request)
    {
        // Validácia pre dva súbory: 'main_file' a 'overlay_file'
       $validator = Validator::make($request->all(), [
            'main_file'    => ['required','file','mimes:pdf','max:50000'],
            'overlay_file' => [
                'required',
                'file',
                'mimes:pdf,png,jpeg,jpg',   // ← allow images now
                'max:50000'
            ],
            'overlay_page_number' => ['sometimes','nullable','integer','min:1'],
            'target_pages'        => ['sometimes','nullable','string','regex:/^[0-9,\-\sA-Za-z]+$/'],
            'output_name'         => ['sometimes','nullable','string','max:255','regex:/^[a-zA-Z0-9_-]+$/'],
        ]);


        if ($validator->fails()) {
            Log::error('Validation failed for PDF overlay.', $validator->errors()->toArray());
            return back()->withErrors($validator->errors())->withInput();
        }

        Log::info("PDF Overlay process started using disk '{$this->processingDisk}'.");

        $mainUploadedFile = $request->file('main_file');
        $overlayUploadedFile = $request->file('overlay_file');
        $overlayPageNumber = $request->input('overlay_page_number', 1); // Default na prvú stranu overlay PDF
        $targetPagesSpec = $request->input('target_pages');
        $targetPagesSpecSanitized = $targetPagesSpec ? preg_replace('/\s+/', '', $targetPagesSpec) : 'all'; // Default na 'all'

        $outputNameForDownload = $request->input('output_name') ?? 'overlaid-document';
        if (!Str::endsWith($outputNameForDownload, '.pdf')) {
            $outputNameForDownload .= '.pdf';
        }
        Log::info("Output PDF name (for download) set to: {$outputNameForDownload}");

        $batchId = Str::uuid();
        $batchDirectoryRelative = $batchId;
        $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

        Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
        Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

        try {
            if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
                Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
                return back()->withErrors(['process_error' => "System error: Could not create temporary directory."])->withInput();
            }
            Log::info("Successfully created batch directory (absolute): {$batchPathAbsolute}");

            // Uloženie hlavného súboru
            $mainInputTempFilename = 'main_' . Str::uuid() . '.' . $mainUploadedFile->getClientOriginalExtension();
            $mainFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $mainInputTempFilename;
            Log::info("Storing main file as '{$mainInputTempFilename}' to '{$mainFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
            $mainUploadedFile->storeAs($batchDirectoryRelative, $mainInputTempFilename, $this->processingDisk);
            $mainFilePathAbsolute = Storage::disk($this->processingDisk)->path($mainFileRelativePathOnDisk);

            if (!file_exists($mainFilePathAbsolute)) {
                Log::error("Main uploaded file FAILED to store/verify at: {$mainFilePathAbsolute}.");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => "Error saving main PDF file."])->withInput();
            }
            Log::info("Main PDF file successfully stored at: {$mainFilePathAbsolute}");

            // Uloženie overlay súboru
            $overlayInputTempFilename = 'overlay_' . Str::uuid() . '.' . $overlayUploadedFile->getClientOriginalExtension();
            $overlayFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $overlayInputTempFilename;
            Log::info("Storing overlay file as '{$overlayInputTempFilename}' to '{$overlayFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
            $overlayUploadedFile->storeAs($batchDirectoryRelative, $overlayInputTempFilename, $this->processingDisk);
            $overlayFilePathAbsolute = Storage::disk($this->processingDisk)->path($overlayFileRelativePathOnDisk);

            if (!file_exists($overlayFilePathAbsolute)) {
                Log::error("Overlay uploaded file FAILED to store/verify at: {$overlayFilePathAbsolute}.");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => "Error saving overlay PDF file."])->withInput();
            }
            Log::info("Overlay PDF file successfully stored at: {$overlayFilePathAbsolute}");


            $outputPythonFilename = Str::uuid() . '.pdf'; // Toto je NOVÉ prekryté PDF
            $outputFilePathAbsoluteByPython = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputPythonFilename;
            Log::info("PHP expects Python to create overlaid PDF at: {$outputFilePathAbsoluteByPython}");

            $command = [
                base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
                base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
                "--operation", "overlay",
                "--input", $mainFilePathAbsolute, // Alebo --input, podľa vášho argparse
                "--overlay-pdf", $overlayFilePathAbsolute,
                "--output", $outputFilePathAbsoluteByPython,
                "--overlay-page-number", (string)$overlayPageNumber,
            ];
            if ($targetPagesSpecSanitized && strtolower($targetPagesSpecSanitized) !== 'all') {
                $command[] = "--pages"; // Alebo --pages, podľa vášho argparse
                $command[] = $targetPagesSpecSanitized;
            } elseif (empty($targetPagesSpecSanitized)) {
                 $command[] = "--pages";
                 $command[] = "all";
            }
            // Ak je 'all', Python skript by to mal brať ako default, ak parameter chýba alebo je 'all'

            Log::info('Executing Python command for overlay: ' . implode(' ', $command));
            $result = Process::run($command);

            Log::info("Python Process (overlay) Ran. Successful: " . ($result->successful() ? 'Yes' : 'No') . ", Exit Code: " . $result->exitCode());
            Log::info("Python STDOUT (overlay): '" . trim($result->output()) . "'");
            Log::info("Python STDERR (overlay): '" . trim($result->errorOutput()) . "'");

            if (!$result->successful()) {
                $errorOutput = $result->errorOutput();
                Log::error("Python script (overlay) execution failed. Error: {$errorOutput}");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Failed to overlay PDF files. Details: ' . $errorOutput])->withInput();
            }
            
            $finalOverlaidFilePathAbsolute = "";
            $pythonReturnedPath = trim($result->output());

            if (!empty($pythonReturnedPath) && file_exists($pythonReturnedPath)) {
                $finalOverlaidFilePathAbsolute = $pythonReturnedPath;
                 if ($pythonReturnedPath !== $outputFilePathAbsoluteByPython) {
                    Log::warning("Path from Python STDOUT ('{$pythonReturnedPath}') differs from PHP's expected output path ('{$outputFilePathAbsoluteByPython}'), but STDOUT path is valid. Using STDOUT path.");
                }
            } elseif (file_exists($outputFilePathAbsoluteByPython)) {
                Log::warning("Python STDOUT was not a valid path or empty, but file exists at PHP's expected output path: '{$outputFilePathAbsoluteByPython}'. Using this path.");
                $finalOverlaidFilePathAbsolute = $outputFilePathAbsoluteByPython;
            } else {
                Log::error("Overlaid PDF file was not found. Python STDOUT: '{$pythonReturnedPath}', PHP Expected: '{$outputFilePathAbsoluteByPython}'");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Processing error: Could not retrieve overlaid PDF.'])->withInput();
            }
            Log::info("Using final overlaid PDF file (absolute path): {$finalOverlaidFilePathAbsolute}");

            ActivityLogger::log("overlay_inertia_success", "PDF files overlaid: {$outputNameForDownload}", $request->user());

            $overlaidFileRelativeOnDisk = str_replace(Storage::disk($this->processingDisk)->path(''), '', $finalOverlaidFilePathAbsolute);
            $overlaidFileRelativeOnDisk = ltrim($overlaidFileRelativeOnDisk, DIRECTORY_SEPARATOR);

            $downloadToken = Str::random(40);
            session(['download_file_path_' . $downloadToken => $overlaidFileRelativeOnDisk]);
            session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
            session(['download_file_name_' . $downloadToken => $outputNameForDownload]);
            $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);

            Log::info("Generated download URL: {$downloadUrl} for overlaid PDF '{$overlaidFileRelativeOnDisk}' on disk '{$this->processingDisk}'.");

            // Zvážte zmazanie $mainFileRelativePathOnDisk a $overlayFileRelativePathOnDisk
            // Storage::disk($this->processingDisk)->delete([$mainFileRelativePathOnDisk, $overlayFileRelativePathOnDisk]);

            return Inertia::render('PdfTools/OverlayResult', [
                'successMessage' => 'PDF files overlaid successfully!',
                'downloadUrl' => $downloadUrl,
                'fileName' => $outputNameForDownload,
            ]);

        } catch (\Throwable $e) {
            Log::critical("Critical error during PDF overlay: " . $e->getMessage(), ['exception' => $e]);
            if (isset($batchDirectoryRelative)) {
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            }
            ActivityLogger::log("overlay_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
            return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
        }
    }
    public function showExtractTextForm()
    {
        return Inertia::render('PdfTools/ExtractText');
    }

    public function processExtractText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:50000'],
            'pages' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9,\-\sA-Za-z]+$/'], // napr. "all", "1,3-5"
            'output_name' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for PDF text extraction.', $validator->errors()->toArray());
            return back()->withErrors($validator->errors())->withInput();
        }

        Log::info("PDF Extract Text process started using disk '{$this->processingDisk}'.");

        $uploadedFile = $request->file('file');
        $pagesToExtractFrom = $request->input('pages');
        $pagesToExtractFromSanitized = $pagesToExtractFrom ? preg_replace('/\s+/', '', $pagesToExtractFrom) : 'all'; // Default na 'all'

        $outputNameForDownload = $request->input('output_name') ?? 'extracted-text';
        // Výstup bude .txt súbor
        if (!Str::endsWith(strtolower($outputNameForDownload), '.txt')) {
            $outputNameForDownload .= '.txt';
        }
        Log::info("Output TXT name (for download) set to: {$outputNameForDownload}");

        $batchId = Str::uuid();
        $batchDirectoryRelative = $batchId;
        $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

        Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
        Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

        try {
            if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
                Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
                return back()->withErrors(['process_error' => "System error: Could not create temporary directory."])->withInput();
            }
            Log::info("Successfully created batch directory (absolute): {$batchPathAbsolute}");

            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $inputTempFilename = Str::uuid() . '.' . $originalExtension; // Vstupný PDF
            $inputFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $inputTempFilename;

            Log::info("Storing uploaded PDF as '{$inputTempFilename}' to '{$inputFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
            $uploadedFile->storeAs($batchDirectoryRelative, $inputTempFilename, $this->processingDisk);
            $inputFilePathAbsolute = Storage::disk($this->processingDisk)->path($inputFileRelativePathOnDisk);

            if (!file_exists($inputFilePathAbsolute)) {
                Log::error("Uploaded PDF FAILED to store/verify at: {$inputFilePathAbsolute}.");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => "Error saving uploaded PDF."])->withInput();
            }
            Log::info("Uploaded PDF successfully stored at: {$inputFilePathAbsolute}");

            // Názov a cesta pre výstupný .txt súbor (vytvorený Pythonom)
            $outputPythonTxtFilename = Str::uuid() . '.txt';
            $outputTxtFilePathAbsoluteByPython = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputPythonTxtFilename;
            Log::info("PHP expects Python to create extracted text file at: {$outputTxtFilePathAbsoluteByPython}");

            $command = [
                base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
                base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
                "--operation", "extract_text",
                "--input", $inputFilePathAbsolute,
                "--output", $outputTxtFilePathAbsoluteByPython, // Python vytvorí tento .txt súbor
            ];
            if ($pagesToExtractFromSanitized && strtolower($pagesToExtractFromSanitized) !== 'all') {
                $command[] = "--pages";
                $command[] = $pagesToExtractFromSanitized;
            } elseif (empty($pagesToExtractFromSanitized)) {
                $command[] = "--pages";
                $command[] = "all";
            }


            Log::info('Executing Python command for extract_text: ' . implode(' ', $command));
            $result = Process::run($command);

            Log::info("Python Process (extract_text) Ran. Successful: " . ($result->successful() ? 'Yes' : 'No') . ", Exit Code: " . $result->exitCode());
            Log::info("Python STDOUT (extract_text): '" . trim($result->output()) . "'");
            Log::info("Python STDERR (extract_text): '" . trim($result->errorOutput()) . "'");

            if (!$result->successful()) {
                $errorOutput = $result->errorOutput();
                Log::error("Python script (extract_text) execution failed. Error: {$errorOutput}");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Failed to extract text from PDF. Details: ' . $errorOutput])->withInput();
            }
            
            $finalExtractedTextFilePathAbsolute = "";
            $pythonReturnedPath = trim($result->output());

            if (!empty($pythonReturnedPath) && file_exists($pythonReturnedPath)) {
                $finalExtractedTextFilePathAbsolute = $pythonReturnedPath;
                 if ($pythonReturnedPath !== $outputTxtFilePathAbsoluteByPython) {
                    Log::warning("Path from Python STDOUT ('{$pythonReturnedPath}') differs from PHP's expected output path ('{$outputTxtFilePathAbsoluteByPython}'), but STDOUT path is valid. Using STDOUT path.");
                }
            } elseif (file_exists($outputTxtFilePathAbsoluteByPython)) {
                Log::warning("Python STDOUT was not a valid path or empty, but file exists at PHP's expected output path: '{$outputTxtFilePathAbsoluteByPython}'. Using this path.");
                $finalExtractedTextFilePathAbsolute = $outputTxtFilePathAbsoluteByPython;
            } else {
                Log::error("Extracted text file was not found. Python STDOUT: '{$pythonReturnedPath}', PHP Expected: '{$outputTxtFilePathAbsoluteByPython}'");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Processing error: Could not retrieve extracted text file.'])->withInput();
            }
            Log::info("Using final extracted text file (absolute path): {$finalExtractedTextFilePathAbsolute}");

            ActivityLogger::log("extract_text_inertia_success", "Text extracted from PDF: {$outputNameForDownload}", $request->user());

            $extractedTextFileRelativeOnDisk = str_replace(Storage::disk($this->processingDisk)->path(''), '', $finalExtractedTextFilePathAbsolute);
            $extractedTextFileRelativeOnDisk = ltrim($extractedTextFileRelativeOnDisk, DIRECTORY_SEPARATOR);

            $downloadToken = Str::random(40);
            session(['download_file_path_' . $downloadToken => $extractedTextFileRelativeOnDisk]);
            session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
            session(['download_file_name_' . $downloadToken => $outputNameForDownload]); // Názov pre stiahnutie .txt
            $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);

            Log::info("Generated download URL: {$downloadUrl} for extracted text file '{$extractedTextFileRelativeOnDisk}' on disk '{$this->processingDisk}'.");

            // Zvážte, či chcete zobraziť obsah textu priamo, alebo len dať link na stiahnutie.
            // Pre konzistenciu s ostatnými nástrojmi, pošleme na result stránku s download linkom.
            return Inertia::render('PdfTools/ExtractTextResult', [
                'successMessage' => 'Text extracted from PDF successfully!',
                'downloadUrl' => $downloadUrl,
                'fileName' => $outputNameForDownload,
            ]);

        } catch (\Throwable $e) {
            Log::critical("Critical error during PDF extract_text: " . $e->getMessage(), ['exception' => $e]);
            if (isset($batchDirectoryRelative)) {
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            }
            ActivityLogger::log("extract_text_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
            return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
        }
    }
    public function showReversePagesForm()
{
    return Inertia::render('PdfTools/ReversePages');
}

public function processReversePages(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => ['required', 'file', 'mimes:pdf', 'max:50000'],
        'output_name' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
    ]);

    if ($validator->fails()) {
        Log::error('Validation failed for PDF page reversing.', $validator->errors()->toArray());
        return back()->withErrors($validator->errors())->withInput();
    }

    Log::info("PDF Reverse Pages process started using disk '{$this->processingDisk}'.");

    $uploadedFile = $request->file('file');
    $outputNameForDownload = $request->input('output_name') ?? 'reversed-document';
    if (!Str::endsWith($outputNameForDownload, '.pdf')) {
        $outputNameForDownload .= '.pdf';
    }
    Log::info("Output PDF name (for download) set to: {$outputNameForDownload}");

    $batchId = Str::uuid();
    $batchDirectoryRelative = $batchId;
    $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

    Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
    Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

    try {
        if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
            Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
            return back()->withErrors(['process_error' => "System error: Could not create temporary directory."])->withInput();
        }
        Log::info("Successfully created batch directory (absolute): {$batchPathAbsolute}");

        $originalExtension = $uploadedFile->getClientOriginalExtension();
        $inputTempFilename = Str::uuid() . '.' . $originalExtension;
        $inputFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $inputTempFilename;

        Log::info("Storing uploaded file as '{$inputTempFilename}' to '{$inputFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
        $uploadedFile->storeAs($batchDirectoryRelative, $inputTempFilename, $this->processingDisk);
        $inputFilePathAbsolute = Storage::disk($this->processingDisk)->path($inputFileRelativePathOnDisk);

        if (!file_exists($inputFilePathAbsolute)) {
            Log::error("Uploaded file FAILED to store/verify at: {$inputFilePathAbsolute}.");
            $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            return back()->withErrors(['process_error' => "Error saving uploaded file."])->withInput();
        }
        Log::info("Uploaded file successfully stored at: {$inputFilePathAbsolute}");

        $outputPythonFilename = Str::uuid() . '.pdf'; // Toto je NOVÉ PDF s obrátenými stranami
        $outputFilePathAbsoluteByPython = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputPythonFilename;
        Log::info("PHP expects Python to create reversed PDF at: {$outputFilePathAbsoluteByPython}");

        $command = [
            base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
            base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
            "--operation", "reverse_pages",
            "--input", $inputFilePathAbsolute,
            "--output", $outputFilePathAbsoluteByPython,
        ];

        Log::info('Executing Python command for reverse_pages: ' . implode(' ', $command));
        $result = Process::run($command);

        Log::info("Python Process (reverse_pages) Ran. Successful: " . ($result->successful() ? 'Yes' : 'No') . ", Exit Code: " . $result->exitCode());
        Log::info("Python STDOUT (reverse_pages): '" . trim($result->output()) . "'");
        Log::info("Python STDERR (reverse_pages): '" . trim($result->errorOutput()) . "'");

        if (!$result->successful()) {
            $errorOutput = $result->errorOutput();
            Log::error("Python script (reverse_pages) execution failed. Error: {$errorOutput}");
            $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            return back()->withErrors(['process_error' => 'Failed to reverse PDF pages. Details: ' . $errorOutput])->withInput();
        }
        
        $finalReversedFilePathAbsolute = "";
        $pythonReturnedPath = trim($result->output());

        if (!empty($pythonReturnedPath) && file_exists($pythonReturnedPath)) {
            $finalReversedFilePathAbsolute = $pythonReturnedPath;
            if ($pythonReturnedPath !== $outputFilePathAbsoluteByPython) {
                Log::warning("Path from Python STDOUT ('{$pythonReturnedPath}') differs from PHP's expected output path ('{$outputFilePathAbsoluteByPython}'), but STDOUT path is valid. Using STDOUT path.");
            }
        } elseif (file_exists($outputFilePathAbsoluteByPython)) {
            Log::warning("Python STDOUT was not a valid path or empty, but file exists at PHP's expected output path: '{$outputFilePathAbsoluteByPython}'. Using this path.");
            $finalReversedFilePathAbsolute = $outputFilePathAbsoluteByPython;
        } else {
            Log::error("Reversed PDF file was not found. Python STDOUT: '{$pythonReturnedPath}', PHP Expected: '{$outputFilePathAbsoluteByPython}'");
            $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            return back()->withErrors(['process_error' => 'Processing error: Could not retrieve reversed PDF.'])->withInput();
        }
        Log::info("Using final reversed PDF file (absolute path): {$finalReversedFilePathAbsolute}");

        ActivityLogger::log("reverse_pages_inertia_success", "PDF pages reversed: {$outputNameForDownload}", $request->user());

        $reversedFileRelativeOnDisk = str_replace(Storage::disk($this->processingDisk)->path(''), '', $finalReversedFilePathAbsolute);
        $reversedFileRelativeOnDisk = ltrim($reversedFileRelativeOnDisk, DIRECTORY_SEPARATOR);

        $downloadToken = Str::random(40);
        session(['download_file_path_' . $downloadToken => $reversedFileRelativeOnDisk]);
        session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
        session(['download_file_name_' . $downloadToken => $outputNameForDownload]);
        $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);

        Log::info("Generated download URL: {$downloadUrl} for reversed PDF '{$reversedFileRelativeOnDisk}' on disk '{$this->processingDisk}'.");

        return Inertia::render('PdfTools/ReversePagesResult', [
            'successMessage' => 'PDF pages reversed successfully!',
            'downloadUrl' => $downloadUrl,
            'fileName' => $outputNameForDownload,
        ]);

    } catch (\Throwable $e) {
        Log::critical("Critical error during PDF reverse_pages: " . $e->getMessage(), ['exception' => $e]);
        if (isset($batchDirectoryRelative)) {
            $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
        }
        ActivityLogger::log("reverse_pages_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
        return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
    }
}
 public function showDuplicatePagesForm()
    {
        return Inertia::render('PdfTools/DuplicatePages');
    }

    public function processDuplicatePages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:50000'],
            'pages' => ['required', 'string', 'regex:/^[0-9,\-\sA-Za-z]+$/'], // Umožní 'all'
            'duplicate_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'output_name' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for PDF page duplication.', $validator->errors()->toArray());
            return back()->withErrors($validator->errors())->withInput();
        }

        Log::info("PDF Duplicate Pages process started using disk '{$this->processingDisk}'.");

        $uploadedFile = $request->file('file');
        $pagesToDuplicate = $request->input('pages');
        $pagesToDuplicateSanitized = $pagesToDuplicate ? preg_replace('/\s+/', '', $pagesToDuplicate) : 'all';
        $duplicateCount = (int) $request->input('duplicate_count', 1); // Cast na int, default na 1

        $outputNameForDownload = $request->input('output_name') ?? 'duplicated-pages-document';
        if (!Str::endsWith($outputNameForDownload, '.pdf')) {
            $outputNameForDownload .= '.pdf';
        }
        Log::info("Output PDF name (for download) set to: {$outputNameForDownload}");

        $batchId = Str::uuid();
        $batchDirectoryRelative = $batchId;
        $batchPathAbsolute = Storage::disk($this->processingDisk)->path($batchDirectoryRelative);

        Log::info("Batch directory (relative to disk '{$this->processingDisk}'): {$batchDirectoryRelative}");
        Log::info("Batch path (absolute for Python): {$batchPathAbsolute}");

        try {
            if (!Storage::disk($this->processingDisk)->makeDirectory($batchDirectoryRelative)) {
                Log::error("Storage::makeDirectory FAILED for: '{$batchDirectoryRelative}' on disk '{$this->processingDisk}'.");
                return back()->withErrors(['process_error' => "System error: Could not create temporary directory."])->withInput();
            }
            Log::info("Successfully created batch directory (absolute): {$batchPathAbsolute}");

            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $inputTempFilename = Str::uuid() . '.' . $originalExtension;
            $inputFileRelativePathOnDisk = $batchDirectoryRelative . '/' . $inputTempFilename;

            Log::info("Storing uploaded file as '{$inputTempFilename}' to '{$inputFileRelativePathOnDisk}' on disk '{$this->processingDisk}'.");
            $uploadedFile->storeAs($batchDirectoryRelative, $inputTempFilename, $this->processingDisk);
            $inputFilePathAbsolute = Storage::disk($this->processingDisk)->path($inputFileRelativePathOnDisk);

            if (!file_exists($inputFilePathAbsolute)) {
                Log::error("Uploaded file FAILED to store/verify at: {$inputFilePathAbsolute}.");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => "Error saving uploaded file."])->withInput();
            }
            Log::info("Uploaded file successfully stored at: {$inputFilePathAbsolute}");

            $outputPythonFilename = Str::uuid() . '.pdf';
            $outputFilePathAbsoluteByPython = $batchPathAbsolute . DIRECTORY_SEPARATOR . $outputPythonFilename;
            Log::info("PHP expects Python to create duplicated pages PDF at: {$outputFilePathAbsoluteByPython}");

            $command = [
                base_path(env('PYTHON_VENV_EXECUTABLE', 'python')),
                base_path(env('PYTHON_SCRIPT_PATH', 'scripts/pdf_processor.py')),
                "--operation", "duplicate_pages",
                "--input", $inputFilePathAbsolute,
                "--output", $outputFilePathAbsoluteByPython,
                "--pages", $pagesToDuplicateSanitized,
                "--duplicate-count", (string)$duplicateCount, // Python očakáva string
            ];

            Log::info('Executing Python command for duplicate_pages: ' . implode(' ', $command));
            $result = Process::run($command);

            Log::info("Python Process (duplicate_pages) Ran. Successful: " . ($result->successful() ? 'Yes' : 'No') . ", Exit Code: " . $result->exitCode());
            Log::info("Python STDOUT (duplicate_pages): '" . trim($result->output()) . "'");
            Log::info("Python STDERR (duplicate_pages): '" . trim($result->errorOutput()) . "'");

            if (!$result->successful()) {
                $errorOutput = $result->errorOutput();
                Log::error("Python script (duplicate_pages) execution failed. Error: {$errorOutput}");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Failed to duplicate PDF pages. Details: ' . $errorOutput])->withInput();
            }
            
            $finalProcessedFilePathAbsolute = "";
            $pythonReturnedPath = trim($result->output());

            if (!empty($pythonReturnedPath) && file_exists($pythonReturnedPath)) {
                $finalProcessedFilePathAbsolute = $pythonReturnedPath;
                if ($pythonReturnedPath !== $outputFilePathAbsoluteByPython) {
                    Log::warning("Path from Python STDOUT ('{$pythonReturnedPath}') differs from PHP's expected output path ('{$outputFilePathAbsoluteByPython}'), but STDOUT path is valid. Using STDOUT path.");
                }
            } elseif (file_exists($outputFilePathAbsoluteByPython)) {
                Log::warning("Python STDOUT was not a valid path or empty, but file exists at PHP's expected output path: '{$outputFilePathAbsoluteByPython}'. Using this path.");
                $finalProcessedFilePathAbsolute = $outputFilePathAbsoluteByPython;
            } else {
                Log::error("Duplicated pages PDF file was not found. Python STDOUT: '{$pythonReturnedPath}', PHP Expected: '{$outputFilePathAbsoluteByPython}'");
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
                return back()->withErrors(['process_error' => 'Processing error: Could not retrieve duplicated pages PDF.'])->withInput();
            }
            Log::info("Using final duplicated pages PDF file (absolute path): {$finalProcessedFilePathAbsolute}");

            ActivityLogger::log("duplicate_pages_inertia_success", "PDF pages duplicated: {$outputNameForDownload}", $request->user());

            $processedFileRelativeOnDisk = str_replace(Storage::disk($this->processingDisk)->path(''), '', $finalProcessedFilePathAbsolute);
            $processedFileRelativeOnDisk = ltrim($processedFileRelativeOnDisk, DIRECTORY_SEPARATOR);

            $downloadToken = Str::random(40);
            session(['download_file_path_' . $downloadToken => $processedFileRelativeOnDisk]);
            session(['download_file_disk_' . $downloadToken => $this->processingDisk]);
            session(['download_file_name_' . $downloadToken => $outputNameForDownload]);
            $downloadUrl = route('file.download.temporary', ['token' => $downloadToken]);

            Log::info("Generated download URL: {$downloadUrl} for duplicated pages PDF '{$processedFileRelativeOnDisk}' on disk '{$this->processingDisk}'.");

            return Inertia::render('PdfTools/DuplicatePagesResult', [
                'successMessage' => 'PDF pages duplicated successfully!',
                'downloadUrl' => $downloadUrl,
                'fileName' => $outputNameForDownload,
            ]);

        } catch (\Throwable $e) {
            Log::critical("Critical error during PDF duplicate_pages: " . $e->getMessage(), ['exception' => $e]);
            if (isset($batchDirectoryRelative)) {
                $this->cleanupTempDirectoryOnDisk($batchDirectoryRelative, $this->processingDisk);
            }
            ActivityLogger::log("duplicate_pages_inertia_CRITICAL_ERROR", "Exception: " . $e->getMessage(), $request->user());
            return back()->withErrors(['process_error' => 'A critical error occurred. Please contact support.'])->withInput();
        }
    }
}