<!-- PDF Alchemist User Manual - Optimized for Vue.js/Inertia integration -->
<div class="pdfcar-manual"> <!-- You might want to rename this class to pdf-alchemist-manual -->
  <style>
    /* Custom styles for the PDF Alchemist manual that work within the existing layout */
    /* Ensure UTF-8 and a suitable font for PDF generation if this style block is directly used by DomPDF */
    @charset "UTF-8";
    body { /* This body tag is conceptual if embedded; applies to PDF rendering context */
        font-family: 'DejaVu Sans', sans-serif; /* Crucial for special characters in PDF */
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
      <img src="{{ asset('images/potion-svgrepo-com.svg') }}" alt="PDF Alchemist Logo">
    </div>
    <h1 class="text-2xl font-bold mb-2">PDF Alchemist</h1>
    <p>User Manual</p>
  </div>

  <div class="toc">
    <h3>Table of Contents</h3>
    <ul>
      <li><a href="#introduction">1. Introduction to the Application</a></li>
      <li><a href="#web-interface-usage">2. Web Interface Usage</a></li>
      <li class="subitem"><a href="#frontend-merge">2.1. Merging PDF Files (Merge)</a></li>
      <li class="subitem"><a href="#frontend-encrypt">2.2. Encrypting PDF (Encrypt)</a></li>
      <li class="subitem"><a href="#frontend-decrypt">2.3. Decrypting PDF (Decrypt)</a></li>
      <li class="subitem"><a href="#frontend-reverse">2.4. Reversing Page Order (Reverse)</a></li>
      <li class="subitem"><a href="#frontend-rotate">2.5. Rotating Pages (Rotate)</a></li>
      <li class="subitem"><a href="#frontend-extract-pages">2.6. Extracting Pages (Extract Pages)</a></li>
      <li class="subitem"><a href="#frontend-delete-pages">2.7. Deleting Pages (Delete Pages)</a></li>
      <li class="subitem"><a href="#frontend-overlay">2.8. Overlaying PDF (Overlay/Watermark)</a></li>
      <li class="subitem"><a href="#frontend-extract-text">2.9. Extracting Text (Extract Text)</a></li>
      <li class="subitem"><a href="#frontend-duplicate-pages">2.10. Duplicating Pages (Duplicate Pages)</a></li>
      <li><a href="#api-usage">3. API Usage</a></li>
      <li class="subitem"><a href="#api-auth">3.1. Authentication</a></li>
      <li class="subitem"><a href="#api-endpoints">3.2. Available API Endpoints</a></li>
    </ul>
  </div>

  <section id="introduction">
    <h2>1. Introduction to the PDF Alchemist Application</h2>
    <p>PDF Alchemist is your comprehensive online application for simple and fast processing of PDF files. Our interface allows you to perform a wide range of PDF file modifications efficiently and without the need to install software.</p>

    <div class="feature-box">
      <h4>Key features of PDF Alchemist include:</h4>
      <ul>
        <li>Merging multiple PDF files into a single document (Merge)</li>
        <li>Encrypting PDF files with a password (Encrypt)</li>
        <li>Decrypting PDF files using a password (Decrypt)</li>
        <li>Reversing the order of pages in a PDF (Reverse)</li>
        <li>Rotating individual or all pages (Rotate)</li>
        <li>Extracting selected pages into a new PDF (Extract Pages)</li>
        <li>Deleting specific pages from a PDF (Delete Pages)</li>
        <li>Adding a watermark or overlaying with another PDF (Overlay)</li>
        <li>Extracting text content from a PDF (Extract Text)</li>
        <li>Duplicating selected pages within a PDF (Duplicate Pages)</li>
        <li>Splitting PDF into smaller parts (Split)</li>
        <li>Compressing large files (Compress)</li>
      </ul>
    </div>
  </section>

  <section id="web-interface-usage">
    <h2>2. Using the Web Interface</h2>
    <p>Our web interface provides an intuitive and user-friendly access to all PDF editing tools. Below you will find a description of individual functions.</p>

    <article id="frontend-merge">
      <h3>2.1. Merging PDF Files (Merge)</h3>
      <p>To combine two or more PDF files into one, follow these steps:</p>
      <ol>
        <li>In the navigation menu, click on "<strong>Merge PDF</strong>".</li>
        <li>Using the "<strong>Select Files</strong>" button or by dragging and dropping, upload the PDF files you want to merge.</li>
        <li>You can arrange the files by dragging them into the desired order.</li>
        <li>Enter an optional name for the output file.</li>
        <li>Click the "<strong>Merge PDF</strong>" button.</li>
        <li>After processing is complete, a link to download the resulting file will be displayed.</li>
      </ol>
    </article>

    <article id="frontend-encrypt">
      <h3>2.2. Encrypting PDF (Encrypt)</h3>
      <p>Secure your PDF files with a password against unauthorized opening or editing.</p>
      <ol>
        <li>Select the "<strong>Encrypt PDF</strong>" tool.</li>
        <li>Upload the PDF file you want to encrypt.</li>
        <li>Enter a strong password to open the document (user password).</li>
        <li>Optionally, you can also enter an owner password, which will restrict permissions such as printing, copying content, etc.</li>
        <li>Confirm the encryption. Download the encrypted file.</li>
      </ol>
    </article>

    <article id="frontend-decrypt">
      <h3>2.3. Decrypting PDF (Decrypt)</h3>
      <p>Remove password protection from a PDF file if you know the necessary password.</p>
      <ol>
        <li>Choose the "<strong>Decrypt PDF</strong>" function.</li>
        <li>Upload the encrypted PDF file.</li>
        <li>Enter the password required to open or edit the file.</li>
        <li>Click "<strong>Decrypt</strong>". After successfully removing the protection, you can download the unprotected file.</li>
      </ol>
      <div class="feature-box">
        <h4>Note</h4>
        <p>Decryption is only possible if you have authorization (know the password). It is not intended to bypass legitimate protection.</p>
      </div>
    </article>

    <article id="frontend-reverse">
      <h3>2.4. Reversing Page Order (Reverse)</h3>
      <p>Change the order of pages in a PDF document from last to first.</p>
      <ol>
        <li>In the menu, select "<strong>Reverse Pages</strong>".</li>
        <li>Upload the PDF file.</li>
        <li>The application will automatically reverse the order of all pages.</li>
        <li>Download the modified PDF file.</li>
      </ol>
    </article>

    <article id="frontend-rotate">
      <h3>2.5. Rotating Pages (Rotate)</h3>
      <p>Correct page orientation or rotate specific pages in a PDF.</p>
      <ol>
        <li>Click on the "<strong>Rotate PDF</strong>" tool.</li>
        <li>Upload your PDF file.</li>
        <li>A preview of the pages will be displayed. You can select individual pages, a range of pages, or all pages.</li>
        <li>Choose the rotation angle (90° right, 90° left, 180°).</li>
        <li>Apply the changes and download the resulting PDF.</li>
      </ol>
    </article>

    <article id="frontend-extract-pages">
      <h3>2.6. Extracting Pages (Extract Pages)</h3>
      <p>Create a new PDF file containing only selected pages from the original document.</p>
      <ol>
        <li>Select "<strong>Extract Pages</strong>".</li>
        <li>Upload the PDF file.</li>
        <li>Enter the page numbers or page range you want to extract (e.g., 1-3, 5, 7-9).</li>
        <li>Click "<strong>Extract</strong>". A new PDF file with the selected pages will be ready for download.</li>
      </ol>
    </article>

    <article id="frontend-delete-pages">
      <h3>2.7. Deleting Pages (Delete Pages)</h3>
      <p>Remove unnecessary pages from your PDF document.</p>
      <ol>
        <li>Choose the "<strong>Delete Pages</strong>" tool.</li>
        <li>Upload the PDF file.</li>
        <li>Specify the page numbers or page ranges you want to remove.</li>
        <li>Confirm your selection. The PDF file without the deleted pages will be available for download.</li>
      </ol>
    </article>

    <article id="frontend-overlay">
      <h3>2.8. Overlaying PDF</h3>
      <p>Add a watermark (text or image) or overlay a PDF file with another PDF file (e.g., letterhead).</p>
      <ol>
        <li>In the menu, choose "<strong>Overlay PDF</strong>".</li>
        <li>Upload the main PDF file.</li>
    
        <li>Upload the second PDF or image file to be used as the overlay.</li>
        <li>Set whether the overlay should be applied to all pages or only selected ones.</li>
        <li>Process and download the modified PDF.</li>
      </ol>
    </article>

    <article id="frontend-extract-text">
      <h3>2.9. Extracting Text (Extract Text)</h3>
      <p>Get all text content from a PDF file into a simple text format (.txt).</p>
      <ol>
        <li>Select "<strong>Extract Text</strong>".</li>
        <li>Upload the PDF file.</li>
        <li>The application will process the file and extract the text.</li>
        <li>You can copy the extracted text or download it as a .txt file.</li>
      </ol>
      <div class="feature-box">
        <h4>Limitations</h4>
        <p>The quality of the extracted text depends on whether the PDF contains actual text or just images of text (scanned documents). For images of text, OCR (Optical Character Recognition) is required, which this function may not cover.</p>
      </div>
    </article>

    <article id="frontend-duplicate-pages">
      <h3>2.10. Duplicating Pages (Duplicate Pages)</h3>
      <p>Create copies of selected pages within your PDF document.</p>
      <ol>
        <li>Choose the "<strong>Duplicate Pages</strong>" tool.</li>
        <li>Upload the PDF file.</li>
        <li>Select the pages you want to duplicate and how many times each selected page should be duplicated.</li>
        <li>Specify whether duplicates should be inserted immediately after the original or at the end of the document.</li>
        <li>Process and download the PDF with duplicated pages.</li>
      </ol>
    </article>
  </section>

  <section id="api-usage">
    <h2>3. Using the API (PDF Alchemist)</h2>
    <p>The PDF Alchemist application also provides an API interface for programmatic processing of PDF files. The API is ideal for integration into your own applications or for automating document processing.</p>

    <article id="api-auth">
      <h3>3.1. Authentication</h3>
      <p>An API key is required to access the API. You can generate it in your profile after logging in.</p>
      <p>Send the API key in the HTTP header <code>X-API-Key</code>:</p>
      <pre><code>X-API-Key: YOUR_API_KEY</code></pre>

      <div class="feature-box">
        <h4>Security Warning</h4>
        <p>Never share your API key or store it in client-side application code. Always manage it on the server-side.</p>
      </div>
    </article>

    <article id="api-endpoints">
      <h3>3.2. Available API Endpoints</h3>
      <p>Our API provides access to all functions available through the web interface. Each function (merging, encrypting, rotating, etc.) has its specific endpoint.</p>
      <p>For example, for merging PDF files:</p>
      <table class="api-table">
        <tr>
          <th>Function</th>
          <th>Endpoint (Example)</th>
          <th>Method</th>
        </tr>
        <tr>
          <td>Merge PDF</td>
          <td><code>POST /api/v1/pdf/merge</code></td>
          <td>POST</td>
        </tr>
        <tr>
          <td>Encrypt PDF</td>
          <td><code>POST /api/v1/pdf/encrypt</code></td>
          <td>POST</td>
        </tr>
        <tr>
          <td>Extract Text</td>
          <td><code>POST /api/v1/pdf/extract-text</code></td>
          <td>POST</td>
        </tr>
        <tr>
          <td colspan="3"><em>...and similarly for all other functions.</em></td>
        </tr>
      </table>
      <p>Detailed specifications for each API endpoint, including required parameters and response formats, can be found in the separate API documentation available after logging in, in the "API Documentation" section.</p>

      <h4>Example (cURL for Merge):</h4>
      <pre><code>curl -X POST https://pdfalchemist.example.com/api/v1/pdf/merge \
-H "X-API-Key: YOUR_API_KEY" \
-H "Accept: application/json" \
-F "files[]=@/path/to/file1.pdf" \
-F "files[]=@/path/to/file2.pdf" \
-F "output_name=my_merged_document" \
--output result.pdf</code></pre>

      <h4>Successful Response (e.g., 200 OK for Merge):</h4>
      <p>Direct download of the PDF file or a JSON response with a download link/data.</p>

      <h4>Error Response (e.g., 422 Unprocessable Entity):</h4>
      <pre><code>{
    "message": "Validation failed",
    "errors": {
        "files": ["The files field is required."]
    }
}</code></pre>
    </article>
  </section>

  <div class="manual-footer">
    <p>Copyright © 2025 PDF Alchemist. All rights reserved.</p>
  </div>
</div>