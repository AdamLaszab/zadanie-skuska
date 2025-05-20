<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;

/**
 * @OA\Tag(name="Manual", description="User manual access and PDF export")
 */
class ManualApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/manual",
     *     tags={"Manual"},
     *     summary="Get user manual HTML content",
     *     description="Returns rendered HTML content of the localized user manual.",
     *     @OA\Response(
     *         response=200,
     *         description="Manual HTML loaded successfully",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Manual not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Rendering error"
     *     )
     * )
     */
    public function show(Request $request)
    {
        $locale = App::getLocale();
        $viewName = "manual.{$locale}.index";

        if (!View::exists($viewName)) {
            Log::warning("Manual view '{$viewName}' not found, fallback to 'en'");
            $locale = 'en';
            $viewName = "manual.{$locale}.index";
            if (!View::exists($viewName)) {
                Log::error("Manual fallback view '{$viewName}' not found.");
                return response()->json(['message' => 'User manual not found.'], 404);
            }
        }

        try {
            $manualContentHtml = view($viewName)->render();
        } catch (\Throwable $th) {
            Log::error("Error rendering manual view: " . $th->getMessage());
            return response()->json(['message' => 'Could not load manual content.'], 500);
        }

        return response()->json([
            'locale' => $locale,
            'html' => $manualContentHtml
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/manual/pdf",
     *     tags={"Manual"},
     *     summary="Download user manual as PDF",
     *     description="Returns the user manual as a PDF file for the current locale.",
     *     @OA\Response(
     *         response=200,
     *         description="PDF manual downloaded",
     *     ),
     *     @OA\Response(response=404, description="Manual not found"),
     *     @OA\Response(response=500, description="PDF generation error")
     * )
     */
    public function exportPdf(Request $request)
    {
        $locale = App::getLocale();
        $viewName = "manual.{$locale}.index";

        if (!View::exists($viewName)) {
            $locale = 'en';
            $viewName = "manual.{$locale}.index";
            if (!View::exists($viewName)) {
                return response()->json(['message' => 'Manual content not found for PDF export.'], 404);
            }
        }

        try {
            $pdf = DomPDF::loadView($viewName, ['dataForView' => []]);
            $fileName = "user-manual-{$locale}.pdf";
            return $pdf->download($fileName);
        } catch (\Throwable $th) {
            Log::error("PDF generation failed: " . $th->getMessage());
            return response()->json(['message' => 'Could not generate PDF manual.'], 500);
        }
    }
}
