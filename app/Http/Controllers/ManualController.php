<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\App; // Pre získanie aktuálneho jazyka
use Illuminate\Support\Facades\View; // Pre kontrolu existencie view
use Barryvdh\DomPDF\Facade\Pdf as DomPDF; // Import fasády

use Illuminate\Support\Facades\Log;

class ManualController extends Controller
{
    public function show()
    {
        $locale = App::getLocale(); // Získa aktuálny jazyk (sk, en)
        $viewName = "manual.{$locale}.index"; // Predpokladáme views/manual/sk/index.blade.php atď.

        // Overíme, či view pre daný jazyk existuje, inak fallback na angličtinu (alebo default)
        if (!View::exists($viewName)) {
            Log::warning("Manual view '{$viewName}' not found, falling back to 'en'.");
            $locale = 'en'; // Fallback jazyk
            $viewName = "manual.{$locale}.index";
            if (!View::exists($viewName)) {
                 // Ak ani anglický manuál neexistuje, zobrazíme chybu alebo prázdnu stránku
                 Log::error("Default manual view '{$viewName}' also not found.");
                 // Tu by ste mohli vrátiť chybovú Inertia stránku
                 abort(404, 'User manual not found.');
            }
        }

        // Načítame obsah Blade view ako string pre poslanie do Inertie
        // Toto je jednoduchý spôsob. Môžete mať komplexnejšiu štruktúru s viacerými sekciami.
        try {
            $manualContentHtml = view($viewName)->render();
        } catch (\Throwable $th) {
            Log::error("Error rendering manual view '{$viewName}': " . $th->getMessage());
            abort(500, 'Could not load the user manual.');
        }


        return Inertia::render('Manual/Show', [
            'manualContentHtml' => $manualContentHtml,
            'currentLocale' => $locale,
        ]);
    }
public function exportPdf()
    {
        $locale = App::getLocale();
        $viewName = "manual.{$locale}.index";

        if (!View::exists($viewName)) {
            Log::warning("Manual PDF export: view '{$viewName}' not found, falling back to 'en'.");
            $locale = 'en';
            $viewName = "manual.{$locale}.index";
             if (!View::exists($viewName)) {
                Log::error("Manual PDF export: default view '{$viewName}' also not found.");
                abort(404, 'User manual content not found for PDF export.');
            }
        }

        try {
            // Načítanie Blade view priamo pre PDF generátor
            // $manualContentHtml = view($viewName)->render(); // Toto by ste mohli použiť, ak vaša knižnica berie HTML string
            // Alebo priamo načítanie view
            $pdf = DomPDF::loadView($viewName, ['dataForView' => []]); // Ak view potrebuje nejaké dáta

            // Voliteľné: nastavenie veľkosti papiera a orientácie
            // $pdf->setPaper('A4', 'portrait');

            $fileName = "uzivatelska-prirucka-{$locale}.pdf";
            return $pdf->download($fileName);

        } catch (\Throwable $th) {
            Log::error("Error generating manual PDF from view '{$viewName}': " . $th->getMessage());
            // Vrátiť nejakú chybovú odpoveď alebo presmerovanie
            return redirect()->route('manual.show')->with('error', 'Could not generate PDF manual.');
        }
    }
}