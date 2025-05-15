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
}