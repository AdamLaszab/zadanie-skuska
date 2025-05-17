<!-- PDF Alchemist User Manual - Optimized for Vue.js/Inertia integration -->
<div class="pdfcar-manual"> <!-- You might want to rename this class to pdf-alchemist-manual -->
  <style>
    /* Custom styles for the PDF Alchemist manual that work within the existing layout */
    /* Ensure UTF-8 and a suitable font for PDF generation if this style block is directly used by DomPDF */
    @charset "UTF-8";
    body { /* This body tag is conceptual if embedded; applies to PDF rendering context */
        font-family: 'DejaVu Sans', sans-serif; /* Crucial for Slovak characters in PDF */
    }
    h1, h2, h3, h4, h5, h6, p, li, td, th, span, div, a {
        font-family: 'DejaVu Sans', sans-serif; /* Ensure all text elements use it */
    }
    pre, code {
        font-family: 'DejaVu Sans Mono', monospace; /* For code blocks */
    }

    .pdfcar-manual { /* Consider renaming to .pdf-alchemist-manual */
      --primary-color: #3498db;
      --secondary-color: #2980b9;
      --accent-color: #e67e22;
      --border-color: #e1e4e8;
    }

    .pdfcar-manual .manual-header { /* Consider renaming to .pdf-alchemist-manual .manual-header */
      background:rgb(255, 255, 255); /* Changed to white for better contrast with the SVG */
      color: black;
      padding: 2rem;
      text-align: center;
      border-radius: 0.5rem 0.5rem 0 0;
      margin-bottom: 2rem;
    }

    /* Adjusted logo container for the SVG */
    .pdfcar-manual .logo-container { /* Consider renaming to .pdf-alchemist-manual .logo-container */
      width: 100px; /* Adjust as needed for your SVG's desired display size */
      height: 100px; /* Adjust as needed */
      margin: 0 auto 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .pdfcar-manual .logo-container img { /* Style for the img tag inside logo-container */
        max-width: 100%;
        max-height: 100%;
        display: block; /* Prevents extra space below the image */
    }

    .pdfcar-manual .toc { /* Consider renaming to .pdf-alchemist-manual .toc */
      background-color: #f8f9fa;
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-bottom: 2rem;
      border: 1px solid var(--border-color);
    }

    .pdfcar-manual .toc h3 {
      margin-top: 0;
      color: var(--primary-color);
      font-size: 1.25rem;
      margin-bottom: 1rem;
    }

    .pdfcar-manual .toc ul {
      list-style-type: none;
      padding-left: 0;
      margin-bottom: 0;
    }

    .pdfcar-manual .toc li {
      margin-bottom: 0.5rem;
    }

    .pdfcar-manual .toc a {
      color: inherit;
      text-decoration: none;
      display: block;
      padding: 0.5rem;
      border-radius: 0.25rem;
      transition: background-color 0.2s;
    }

    .pdfcar-manual .toc a:hover {
      background-color: rgba(52, 152, 219, 0.1);
      color: var(--primary-color);
    }

    .pdfcar-manual .toc .subitem {
      padding-left: 1.5rem;
      font-size: 0.95rem;
    }

    .pdfcar-manual section {
      margin-bottom: 3rem;
    }

    .pdfcar-manual h2 {
      color: var(--primary-color);
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 0.5rem;
      margin-bottom: 1.5rem;
    }

    .pdfcar-manual h3 {
      color: var(--secondary-color);
      margin-top: 2rem;
      margin-bottom: 1rem;
    }

    .pdfcar-manual h4 {
      margin-top: 1.5rem;
      margin-bottom: 0.75rem;
      font-weight: 600;
    }

    .pdfcar-manual .feature-box {
      background-color: #f5f7fa;
      border-left: 4px solid var(--accent-color);
      padding: 1rem;
      margin: 1.5rem 0;
      border-radius: 0 0.25rem 0.25rem 0;
    }

    .pdfcar-manual .feature-box h4 {
      color: var(--accent-color);
      margin-top: 0;
      margin-bottom: 0.5rem;
    }

    .pdfcar-manual .api-table {
      width: 100%;
      border-collapse: collapse;
      margin: 1rem 0;
    }

    .pdfcar-manual .api-table th,
    .pdfcar-manual .api-table td {
      padding: 0.75rem;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
    }

    .pdfcar-manual .api-table th {
      background-color: #f5f7fa;
      font-weight: 600;
    }

    .pdfcar-manual .manual-footer {
      text-align: center;
      padding: 1.5rem;
      color: #888;
      font-size: 0.9rem;
      border-top: 1px solid var(--border-color);
      margin-top: 2rem;
    }

    /* Dark mode support */
    .dark .pdfcar-manual .toc,
    .dark .pdfcar-manual .feature-box {
      background-color: #2d3748;
      border-color: #4a5568;
    }

    .dark .pdfcar-manual .api-table th {
      background-color: #2d3748;
    }

    .dark .pdfcar-manual .toc a:hover {
      background-color: rgba(66, 153, 225, 0.2);
    }

    /* Styles for code blocks from your parent Inertia component, or define here if this is standalone for PDF */
    .pdfcar-manual .prose pre, /* Assuming .prose is on a parent, or add it to .pdfcar-manual if needed */
    .pdfcar-manual pre { /* General pre for PDF if not using .prose */
      background-color: #f5f5f5;
      padding: 1em;
      overflow-x: auto;
      border-radius: 0.375rem;
      border: 1px solid #ddd; /* Added for PDF clarity */
    }
    .dark .pdfcar-manual .prose pre,
    .dark .pdfcar-manual pre {
      background-color: #374151;
      color: #d1d5db;
      border-color: #4a5568; /* Added for PDF clarity */
    }
    .pdfcar-manual .prose code, /* Assuming .prose is on a parent */
    .pdfcar-manual code { /* General code for PDF if not using .prose */
      font-family: monospace; /* Ensure this is set if not inherited */
      background-color: #eef1f3;
      padding: 0.125em 0.25em;
      border-radius: 0.25rem;
    }
    .dark .pdfcar-manual .prose code,
    .dark .pdfcar-manual code {
      background-color: #4b5563;
      color: #e5e7eb;
    }
  </style>

  <div class="manual-header">
    <div class="logo-container">
      <img src="{{ asset('images/potion-svgrepo-com.svg') }}" alt="PDF Alchemist">
    </div>
    <h1 class="text-2xl font-bold mb-2">PDF Alchemist</h1>
    <p>Používateľská príručka</p>
  </div>

  <div class="toc">
    <h3>Obsah</h3>
    <ul>
      <li><a href="#uvod">1. Úvod do Aplikácie</a></li>
      <li><a href="#frontend-pouzitie">2. Webové Rozhranie</a></li>
      <li class="subitem"><a href="#frontend-merge">2.1. Spájanie PDF (Merge)</a></li>
      <li class="subitem"><a href="#frontend-encrypt">2.2. Šifrovanie PDF (Encrypt)</a></li>
      <li class="subitem"><a href="#frontend-decrypt">2.3. Dešifrovanie PDF (Decrypt)</a></li>
      <li class="subitem"><a href="#frontend-reverse">2.4. Obrátenie poradia strán (Reverse)</a></li>
      <li class="subitem"><a href="#frontend-rotate">2.5. Otáčanie strán (Rotate)</a></li>
      <li class="subitem"><a href="#frontend-extract-pages">2.6. Extrahovanie strán (Extract Pages)</a></li>
      <li class="subitem"><a href="#frontend-delete-pages">2.7. Mazanie strán (Delete Pages)</a></li>
      <li class="subitem"><a href="#frontend-overlay">2.8. Prekrývanie PDF (Overlay/Vodoznak)</a></li>
      <li class="subitem"><a href="#frontend-extract-text">2.9. Extrahovanie textu (Extract Text)</a></li>
      <li class="subitem"><a href="#frontend-duplicate-pages">2.10. Duplikovanie strán (Duplicate Pages)</a></li>
      <li><a href="#api-pouzitie">3. API Rozhranie</a></li>
      <li class="subitem"><a href="#api-auth">3.1. Autentifikácia</a></li>
      <li class="subitem"><a href="#api-endpoints">3.2. Dostupné API Endpoints</a></li>
    </ul>
  </div>

  <section id="uvod">
    <h2>1. Úvod do Aplikácie PDF Alchemist</h2>
    <p>PDF Alchemist je vaša komplexná online aplikácia pre jednoduché a rýchle spracovanie PDF súborov. Naše rozhranie vám umožňuje vykonávať širokú škálu úprav PDF súborov efektívne a bez potreby inštalácie softvéru.</p>

    <div class="feature-box">
      <h4>Kľúčové funkcie PDF Alchemist zahŕňajú:</h4>
      <ul>
        <li>Spájanie viacerých PDF súborov do jedného dokumentu (Merge)</li>
        <li>Šifrovanie PDF súborov heslom (Encrypt)</li>
        <li>Dešifrovanie PDF súborov pomocou hesla (Decrypt)</li>
        <li>Obrátenie poradia strán v PDF (Reverse)</li>
        <li>Otáčanie jednotlivých alebo všetkých strán (Rotate)</li>
        <li>Extrahovanie vybraných strán do nového PDF (Extract Pages)</li>
        <li>Mazanie špecifických strán z PDF (Delete Pages)</li>
        <li>Pridávanie vodoznaku alebo prekrývanie s iným PDF (Overlay)</li>
        <li>Extrahovanie textového obsahu z PDF (Extract Text)</li>
        <li>Duplikovanie vybraných strán v rámci PDF (Duplicate Pages)</li>
        <li>Rozdeľovanie PDF na menšie časti (Split)</li>
        <li>Kompresia veľkých súborov (Compress)</li>
      </ul>
    </div>
  </section>

  <section id="frontend-pouzitie">
    <h2>2. Používanie cez Webové Rozhranie</h2>
    <p>Naše webové rozhranie poskytuje intuitívny a používateľsky prívetivý prístup ku všetkým nástrojom pre úpravu PDF súborov. Nižšie nájdete popis jednotlivých funkcií.</p>

    <article id="frontend-merge">
      <h3>2.1. Spájanie PDF súborov (Merge)</h3>
      <p>Pre spojenie dvoch alebo viacerých PDF súborov do jedného postupujte nasledovne:</p>
      <ol>
        <li>V navigačnom menu kliknite na "<strong>Spojiť PDF</strong>".</li>
        <li>Pomocou tlačidla "<strong>Vybrať súbory</strong>" alebo presunutím myšou nahrajte PDF súbory, ktoré chcete spojiť.</li>
        <li>Súbory môžete usporiadať presunutím podľa požadovaného poradia.</li>
        <li>Zadajte voliteľný názov výstupného súboru.</li>
        <li>Kliknite na tlačidlo "<strong>Spojiť PDF</strong>".</li>
        <li>Po dokončení spracovania sa zobrazí odkaz na stiahnutie výsledného súboru.</li>
      </ol>
    </article>

    <article id="frontend-encrypt">
      <h3>2.2. Šifrovanie PDF (Encrypt)</h3>
      <p>Zabezpečte svoje PDF súbory heslom proti neoprávnenému otvoreniu alebo úpravám.</p>
      <ol>
        <li>Vyberte nástroj "<strong>Šifrovať PDF</strong>".</li>
        <li>Nahrajte PDF súbor, ktorý chcete zašifrovať.</li>
        <li>Zadajte silné heslo pre otvorenie dokumentu (user password).</li>
        <li>Voliteľne môžete zadať aj heslo vlastníka (owner password), ktoré obmedzí oprávnenia ako tlač, kopírovanie obsahu atď.</li>
        <li>Potvrďte šifrovanie. Stiahnite si zašifrovaný súbor.</li>
      </ol>
    </article>

    <article id="frontend-decrypt">
      <h3>2.3. Dešifrovanie PDF (Decrypt)</h3>
      <p>Odstráňte heslovú ochranu z PDF súboru, ak poznáte potrebné heslo.</p>
      <ol>
        <li>Zvoľte funkciu "<strong>Dešifrovať PDF</strong>".</li>
        <li>Nahrajte zašifrovaný PDF súbor.</li>
        <li>Zadajte heslo potrebné na otvorenie alebo úpravu súboru.</li>
        <li>Kliknite na "<strong>Dešifrovať</strong>". Po úspešnom odstránení ochrany si môžete stiahnuť nechránený súbor.</li>
      </ol>
      <div class="feature-box">
        <h4>Poznámka</h4>
        <p>Dešifrovanie je možné iba ak máte oprávnenie (poznáte heslo). Nie je určené na obchádzanie legitímnej ochrany.</p>
      </div>
    </article>

    <article id="frontend-reverse">
      <h3>2.4. Obrátenie poradia strán (Reverse)</h3>
      <p>Zmeňte poradie strán v PDF dokumente z poslednej na prvú.</p>
      <ol>
        <li>V menu vyberte "<strong>Obrátiť strany</strong>".</li>
        <li>Nahrajte PDF súbor.</li>
        <li>Aplikácia automaticky obráti poradie všetkých strán.</li>
        <li>Stiahnite si upravený PDF súbor.</li>
      </ol>
    </article>

    <article id="frontend-rotate">
      <h3>2.5. Otáčanie strán (Rotate)</h3>
      <p>Opravte orientáciu strán alebo otočte špecifické strany v PDF.</p>
      <ol>
        <li>Kliknite na nástroj "<strong>Otočiť strany</strong>".</li>
        <li>Nahrajte váš PDF súbor.</li>
        <li>Zobrazí sa náhľad strán. Môžete vybrať jednotlivé strany, rozsah strán, alebo všetky strany.</li>
        <li>Zvoľte uhol otočenia (90° doprava, 90° doľava, 180°).</li>
        <li>Aplikujte zmeny a stiahnite si výsledný PDF.</li>
      </ol>
    </article>

    <article id="frontend-extract-pages">
      <h3>2.6. Extrahovanie strán (Extract Pages)</h3>
      <p>Vytvorte nový PDF súbor obsahujúci iba vybrané strany z pôvodného dokumentu.</p>
      <ol>
        <li>Vyberte "<strong>Extrahovať strany</strong>".</li>
        <li>Nahrajte PDF súbor.</li>
        <li>Zadajte čísla strán alebo rozsah strán, ktoré chcete extrahovať (napr. 1-3, 5, 7-9).</li>
        <li>Kliknite na "<strong>Extrahovať</strong>". Nový PDF súbor s vybranými stranami bude pripravený na stiahnutie.</li>
      </ol>
    </article>

    <article id="frontend-delete-pages">
      <h3>2.7. Mazanie strán (Delete Pages)</h3>
      <p>Odstráňte nepotrebné strany z vášho PDF dokumentu.</p>
      <ol>
        <li>Zvoľte nástroj "<strong>Zmazať strany</strong>".</li>
        <li>Nahrajte PDF súbor.</li>
        <li>Špecifikujte čísla strán alebo rozsahy strán, ktoré chcete odstrániť.</li>
        <li>Potvrďte výber. PDF súbor bez odstránených strán bude dostupný na stiahnutie.</li>
      </ol>
    </article>

    <article id="frontend-overlay">
      <h3>2.8. Prekrývanie PDF</h3>
      <p>Pridajte vodoznak (textový alebo obrázkový) alebo prekryte PDF súbor iným PDF súborom (napr. hlavičkový papier).</p>
      <ol>
        <li>V menu zvoľte "<strong>Prekryť PDF</strong>".</li>
        <li>Nahrajte hlavný PDF súbor.</li>
        <li>Pre prekrytie: nahrajte druhé PDF alebo obrázok, ktorý sa má použiť ako prekrytie.</li>
        <li>Nastavte, či sa má aplikovať na všetky strany alebo len na vybrané.</li>
        <li>Spracujte a stiahnite upravený PDF.</li>
      </ol>
    </article>

    <article id="frontend-extract-text">
      <h3>2.9. Extrahovanie textu (Extract Text)</h3>
      <p>Získajte všetok textový obsah z PDF súboru do jednoduchého textového formátu (.txt).</p>
      <ol>
        <li>Vyberte "<strong>Extrahovať text</strong>".</li>
        <li>Nahrajte PDF súbor.</li>
        <li>Aplikácia spracuje súbor a extrahuje text.</li>
        <li>Extrahovaný text si môžete skopírovať alebo stiahnuť ako .txt súbor.</li>
      </ol>
      <div class="feature-box">
        <h4>Obmedzenia</h4>
        <p>Kvalita extrahovaného textu závisí od toho, či PDF obsahuje skutočný text alebo iba obrázky textu (naskenované dokumenty). Pre obrázky textu je potrebné OCR (Optical Character Recognition), ktoré táto funkcia nemusí pokrývať.</p>
      </div>
    </article>

    <article id="frontend-duplicate-pages">
      <h3>2.10. Duplikovanie strán (Duplicate Pages)</h3>
      <p>Vytvorte kópie vybraných strán v rámci vášho PDF dokumentu.</p>
      <ol>
        <li>Zvoľte nástroj "<strong>Duplikovať strany</strong>".</li>
        <li>Nahrajte PDF súbor.</li>
        <li>Vyberte strany, ktoré chcete duplikovať, a koľkokrát sa má každá vybraná strana duplikovať.</li>
        <li>Určite, či sa majú duplikáty vložiť hneď za originálom alebo na koniec dokumentu.</li>
        <li>Spracujte a stiahnite PDF s duplikovanými stranami.</li>
      </ol>
    </article>
  </section>

  <section id="api-pouzitie">
    <h2>3. Používanie cez API (PDF Alchemist)</h2>
    <p>Aplikácia PDF Alchemist poskytuje aj API rozhranie pre programatické spracovanie PDF súborov. API je ideálne pre integráciu do vašich vlastných aplikácií alebo automatizáciu spracovania dokumentov.</p>

    <article id="api-auth">
      <h3>3.1. Autentifikácia</h3>
      <p>Pre prístup k API je potrebný API kľúč. Ten si môžete vygenerovať vo vašom profile po prihlásení.</p>
      <p>API kľúč posielajte v HTTP hlavičke <code>X-API-Key</code>:</p>
      <pre><code>X-API-Key: VÁŠ_API_KĽÚČ</code></pre>

      <div class="feature-box">
        <h4>Bezpečnostné upozornenie</h4>
        <p>API kľúč nikdy nezdieľajte a neukládajte v klientskom kóde aplikácií. Vždy ho spravujte na serverovej strane.</p>
      </div>
    </article>

    <article id="api-endpoints">
      <h3>3.2. Dostupné API Endpoints</h3>
      <p>Naše API poskytuje prístup k všetkým funkciám dostupným cez webové rozhranie. Každá funkcia (spájanie, šifrovanie, otáčanie atď.) má svoj špecifický endpoint.</p>
      <p>Napríklad pre spájanie PDF súborov:</p>
      <table class="api-table">
        <tr>
          <th>Funkcia</th>
          <th>Endpoint (Príklad)</th>
          <th>Metóda</th>
        </tr>
        <tr>
          <td>Spájanie PDF</td>
          <td><code>POST /api/v1/pdf/merge</code></td>
          <td>POST</td>
        </tr>
        <tr>
          <td>Šifrovanie PDF</td>
          <td><code>POST /api/v1/pdf/encrypt</code></td>
          <td>POST</td>
        </tr>
        <tr>
          <td>Extrahovanie textu</td>
          <td><code>POST /api/v1/pdf/extract-text</code></td>
          <td>POST</td>
        </tr>
        <tr>
          <td colspan="3"><em>...a podobne pre všetky ostatné funkcie.</em></td>
        </tr>
      </table>
      <p>Detailnú špecifikáciu každého API endpointu, vrátane požadovaných parametrov a formátov odpovedí, nájdete v samostatnej API dokumentácii dostupnej po prihlásení v sekcii "API Dokumentácia".</p>

      <h4>Príklad (cURL pre Merge):</h4>
      <pre><code>curl -X POST https://pdfalchemist.example.com/api/v1/pdf/merge \
-H "X-API-Key: VÁŠ_API_KĽÚČ" \
-H "Accept: application/json" \
-F "files[]=@/cesta/k/subor1.pdf" \
-F "files[]=@/cesta/k/subor2.pdf" \
-F "output_name=moj_spojeny_dokument" \
--output výsledok.pdf</code></pre>

      <h4>Úspešná odpoveď (napr. 200 OK pre Merge):</h4>
      <p>Priamy download PDF súboru alebo JSON odpoveď s odkazom na stiahnutie/dátami.</p>

      <h4>Chybová odpoveď (napr. 422 Unprocessable Entity):</h4>
      <pre><code>{
    "message": "Validation failed",
    "errors": {
        "files": ["The files field is required."]
    }
}</code></pre>
    </article>
  </section>

  <div class="manual-footer">
    <p>Copyright © 2025 PDF Alchemist. Všetky práva vyhradené.</p>
  </div>
</div>