<!-- PDF Alchemist User Manual - Optimized for Vue.js/Inertia integration -->
<div class="pdfcar-manual"> <!-- Keeping the existing class for compatibility -->
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

    .pdfcar-manual {
      --primary-color: #3498db;
      --secondary-color: #2980b9;
      --accent-color: #e67e22;
      --border-color: #e1e4e8;
    }

    .pdfcar-manual .manual-header {
      background:rgb(255, 255, 255); /* Changed to white for better contrast with the SVG */
      color: black;
      padding: 2rem;
      text-align: center;
      border-radius: 0.5rem 0.5rem 0 0;
      margin-bottom: 2rem;
    }

    /* Adjusted logo container for the SVG */
    .pdfcar-manual .logo-container {
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

    .pdfcar-manual .toc {
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
      <li class="subitem"><a href="#frontend-extract-pages">2.2. Extracting Pages (Extract Pages)</a></li>
      <li class="subitem"><a href="#frontend-rotate">2.3. Rotating Pages (Rotate)</a></li>
      <li class="subitem"><a href="#frontend-delete-pages">2.4. Deleting Pages (Delete Pages)</a></li>
      <li class="subitem"><a href="#frontend-encrypt">2.5. Encrypting PDF (Encrypt)</a></li>
      <li class="subitem"><a href="#frontend-decrypt">2.6. Decrypting PDF (Decrypt)</a></li>
      <li class="subitem"><a href="#frontend-overlay">2.7. Overlaying PDF (Overlay/Watermark)</a></li>
      <li class="subitem"><a href="#frontend-extract-text">2.8. Extracting Text (Extract Text)</a></li>
      <li class="subitem"><a href="#frontend-reverse">2.9. Reversing Page Order (Reverse)</a></li>
      <li class="subitem"><a href="#frontend-duplicate-pages">2.10. Duplicating Pages (Duplicate Pages)</a></li>
      <li><a href="#api-usage">3. API Usage</a></li>
      <li class="subitem"><a href="#api-auth">3.1. Authentication</a></li>
      <li class="subitem"><a href="#api-endpoints">3.2. Available API Endpoints</a></li>
      <li class="subitem"><a href="#api-examples">3.3. API Examples</a></li>
    </ul>
  </div>

  <section id="introduction">
    <h2>1. Introduction to the PDF Alchemist Application</h2>
    <p>PDF Alchemist is a comprehensive web application for efficient PDF file processing. Our interface allows you to perform a wide range of PDF modifications without installing additional software.</p>

    <div class="feature-box">
      <h4>Key features of PDF Alchemist include:</h4>
      <ul>
        <li>Merging multiple PDF files into a single document</li>
        <li>Extracting specific pages from a PDF into a new file</li>
        <li>Rotating individual or all pages in a PDF</li>
        <li>Deleting specific pages from a PDF</li>
        <li>Encrypting PDF files with a password</li>
        <li>Decrypting PDF files using a password</li>
        <li>Adding a watermark or overlaying with another PDF/image</li>
        <li>Extracting text content from a PDF</li>
        <li>Reversing the order of pages in a PDF</li>
        <li>Duplicating selected pages within a PDF</li>
      </ul>
    </div>
  </section>

  <section id="web-interface-usage">
    <h2>2. Using the Web Interface</h2>
    <p>Our web interface provides an intuitive and user-friendly access to all PDF editing tools. All tools are accessible from the Dashboard, which shows cards for each available PDF operation.</p>

    <article id="frontend-merge">
      <h3>2.1. Merging PDF Files (Merge)</h3>
      <p>To combine two or more PDF files into one, follow these steps:</p>
      <ol>
        <li>Navigate to <strong>Dashboard</strong> to see all available tools.</li>
        <li>Click on <strong>Merge PDFs</strong> card or the <strong>Open Tool</strong> button.</li>
        <li>Click the "<strong>Select Files</strong>" button to upload the PDF files you want to merge.</li>
        <li>You can optionally provide a name for the output file in the "Output Name" field.</li>
        <li>Click the "<strong>Merge PDFs</strong>" button.</li>
        <li>After processing is complete, a download button will appear to save the resulting merged PDF.</li>
      </ol>
    </article>

    <article id="frontend-extract-pages">
      <h3>2.2. Extracting Pages (Extract Pages)</h3>
      <p>Create a new PDF file containing only selected pages from the original document.</p>
      <ol>
        <li>From the <strong>Dashboard</strong>, find and click on the <strong>Extract Pages</strong> card.</li>
        <li>Upload your PDF file using the file selector.</li>
        <li>Enter the page numbers or page range you want to extract (e.g., 1,3-5,7).</li>
        <li>Optionally specify a name for the output file.</li>
        <li>Click "<strong>Extract Pages</strong>". After processing, you can download the new PDF containing only the selected pages.</li>
      </ol>
    </article>

    <article id="frontend-rotate">
      <h3>2.3. Rotating Pages (Rotate)</h3>
      <p>Correct page orientation or rotate specific pages in a PDF.</p>
      <ol>
        <li>From the <strong>Dashboard</strong>, click on the <strong>Rotate PDF</strong> card.</li>
        <li>Upload your PDF file.</li>
        <li>Select the rotation angle (90°, 180°, 270°) from the dropdown menu.</li>
        <li>Specify which pages to rotate by entering page numbers or ranges (e.g., "1,3-5" or "all" for all pages).</li>
        <li>Optionally provide a name for the output file.</li>
        <li>Click "<strong>Rotate Pages</strong>" and download the result after processing.</li>
      </ol>
    </article>

    <article id="frontend-delete-pages">
      <h3>2.4. Deleting Pages (Delete Pages)</h3>
      <p>Remove unnecessary pages from your PDF document.</p>
      <ol>
        <li>From the <strong>Dashboard</strong>, select the <strong>Delete Pages</strong> card.</li>
        <li>Upload the PDF file you want to modify.</li>
        <li>Enter the page numbers or page ranges you want to remove (e.g., 1,3,5-7).</li>
        <li>Optionally specify a name for the output file.</li>
        <li>Click "<strong>Delete Pages</strong>" and download the result after processing.</li>
      </ol>
    </article>

    <article id="frontend-encrypt">
      <h3>2.5. Encrypting PDF (Encrypt)</h3>
      <p>Secure your PDF files with a password against unauthorized opening or editing.</p>
      <ol>
        <li>From the <strong>Dashboard</strong>, find and click on the <strong>Encrypt PDF</strong> card.</li>
        <li>Upload the PDF file you want to encrypt.</li>
        <li>Enter a user password that will be required to open the document.</li>
        <li>Optionally, enter an owner password that provides additional permissions control.</li>
        <li>Optionally provide a name for the output file.</li>
        <li>Click "<strong>Encrypt PDF</strong>" and download the encrypted file after processing.</li>
      </ol>
      <div class="feature-box">
        <h4>Note</h4>
        <p>Remember to store your passwords in a safe place. If you lose the password, you won't be able to access the encrypted PDF.</p>
      </div>
    </article>

    <article id="frontend-decrypt">
      <h3>2.6. Decrypting PDF (Decrypt)</h3>
      <p>Remove password protection from a PDF file when you have the necessary password.</p>
      <ol>
        <li>From the <strong>Dashboard</strong>, find and click on the <strong>Decrypt PDF</strong> card.</li>
        <li>Upload the encrypted PDF file.</li>
        <li>Enter the password required to open the file.</li>
        <li>Optionally specify a name for the output file.</li>
        <li>Click "<strong>Decrypt PDF</strong>" and download the unprotected file after processing.</li>
      </ol>
      <div class="feature-box">
        <h4>Note</h4>
        <p>Decryption is only possible if you have authorization (know the password). The tool is not designed to bypass legitimate protection mechanisms.</p>
      </div>
    </article>

    <article id="frontend-overlay">
      <h3>2.7. Overlaying PDF</h3>
      <p>Add a watermark or overlay a PDF file with another PDF file or image.</p>
      <ol>
        <li>From the <strong>Dashboard</strong>, locate and click on the <strong>Overlay PDF</strong> card.</li>
        <li>Upload the main PDF file that will serve as the base document.</li>
        <li>Upload the overlay PDF or image file that will be placed on top of the main file.</li>
        <li>Specify which page from the overlay file to use (typically page 1).</li>
        <li>Enter the page numbers or page ranges of the main PDF where the overlay should be applied.</li>
        <li>Optionally provide a name for the output file.</li>
        <li>Click "<strong>Apply Overlay</strong>" and download the result after processing.</li>
      </ol>
    </article>

    <article id="frontend-extract-text">
      <h3>2.8. Extracting Text (Extract Text)</h3>
      <p>Extract all text content from a PDF file into a plain text format (.txt).</p>
      <ol>
        <li>From the <strong>Dashboard</strong>, find and click on the <strong>Extract Text</strong> card.</li>
        <li>Upload the PDF file you want to extract text from.</li>
        <li>Optionally specify which pages to extract text from.</li>
        <li>Provide a name for the output text file if desired.</li>
        <li>Click "<strong>Extract Text</strong>" and download the resulting .txt file after processing.</li>
      </ol>
      <div class="feature-box">
        <h4>Limitations</h4>
        <p>The quality of the extracted text depends on whether the PDF contains actual text or just images of text (scanned documents). For scanned documents, the extraction may not produce accurate results as this tool does not include OCR (Optical Character Recognition) functionality.</p>
      </div>
    </article>

    <article id="frontend-reverse">
      <h3>2.9. Reversing Page Order (Reverse)</h3>
      <p>Change the order of pages in a PDF document from last to first.</p>
      <ol>
        <li>From the <strong>Dashboard</strong>, locate and click on the <strong>Reverse Pages</strong> card.</li>
        <li>Upload the PDF file you want to reverse.</li>
        <li>Optionally provide a name for the output file.</li>
        <li>Click "<strong>Reverse Pages</strong>" and download the modified PDF after processing.</li>
      </ol>
    </article>

    <article id="frontend-duplicate-pages">
      <h3>2.10. Duplicating Pages (Duplicate Pages)</h3>
      <p>Create copies of selected pages within your PDF document.</p>
      <ol>
        <li>From the <strong>Dashboard</strong>, find and click on the <strong>Duplicate Pages</strong> card.</li>
        <li>Upload the PDF file.</li>
        <li>Enter the page numbers or page ranges you want to duplicate.</li>
        <li>Specify how many copies you want to make of each selected page (Duplicate Count).</li>
        <li>Optionally provide a name for the output file.</li>
        <li>Click "<strong>Duplicate Pages</strong>" and download the result after processing.</li>
      </ol>
    </article>
  </section>

  <section id="api-usage">
    <h2>3. Using the API</h2>
    <p>PDF Alchemist provides a RESTful API for programmatic PDF processing. This allows you to integrate PDF operations into your own applications or automate document workflows.</p>

    <article id="api-auth">
      <h3>3.1. Authentication</h3>
      <p>To use the API, you need to authenticate with an API key. You can generate or regenerate your API key in your profile settings.</p>

      <h4>Obtaining an API Key</h4>
      <ol>
        <li>Log in to your PDF Alchemist account.</li>
        <li>Navigate to your profile page.</li>
        <li>Click on "Generate API Key" or "Regenerate API Key".</li>
        <li>Copy and securely store your API key. Note that for security reasons, you won't be able to view the key again after navigating away.</li>
      </ol>

      <h4>Using the API Key</h4>
      <p>Include your API key in the HTTP Authorization header as a Bearer token:</p>
      <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>

      <div class="feature-box">
        <h4>Security Warning</h4>
        <p>Treat your API key as a password. Never share it or store it in client-side code. Always transmit it over HTTPS and manage it securely on your server.</p>
      </div>
    </article>

    <article id="api-endpoints">
      <h3>3.2. Available API Endpoints</h3>
      <p>The API provides endpoints for all PDF operations available in the web interface. All API routes are prefixed with <code>/api/pdf/</code>.</p>
      <table class="api-table">
        <tr>
          <th>Operation</th>
          <th>Endpoint</th>
          <th>Method</th>
          <th>Required Parameters</th>
        </tr>
        <tr>
          <td>Merge PDFs</td>
          <td><code>/api/pdf/merge</code></td>
          <td>POST</td>
          <td>files[] (array of PDF files)</td>
        </tr>
        <tr>
          <td>Extract Pages</td>
          <td><code>/api/pdf/extract-pages</code></td>
          <td>POST</td>
          <td>file, pages</td>
        </tr>
        <tr>
          <td>Rotate Pages</td>
          <td><code>/api/pdf/rotate</code></td>
          <td>POST</td>
          <td>file, angle</td>
        </tr>
        <tr>
          <td>Delete Pages</td>
          <td><code>/api/pdf/delete-pages</code></td>
          <td>POST</td>
          <td>file, pages</td>
        </tr>
        <tr>
          <td>Encrypt PDF</td>
          <td><code>/api/pdf/encrypt</code></td>
          <td>POST</td>
          <td>file, user_password</td>
        </tr>
        <tr>
          <td>Decrypt PDF</td>
          <td><code>/api/pdf/decrypt</code></td>
          <td>POST</td>
          <td>file, password</td>
        </tr>
        <tr>
          <td>Overlay PDF</td>
          <td><code>/api/pdf/overlay</code></td>
          <td>POST</td>
          <td>files[] (2 files: base and overlay)</td>
        </tr>
        <tr>
          <td>Extract Text</td>
          <td><code>/api/pdf/extract-text</code></td>
          <td>POST</td>
          <td>file</td>
        </tr>
        <tr>
          <td>Reverse Pages</td>
          <td><code>/api/pdf/reverse-pages</code></td>
          <td>POST</td>
          <td>file</td>
        </tr>
        <tr>
          <td>Duplicate Pages</td>
          <td><code>/api/pdf/duplicate-pages</code></td>
          <td>POST</td>
          <td>file, pages</td>
        </tr>
      </table>

      <div class="feature-box">
        <h4>Common Optional Parameters</h4>
        <p>Most endpoints accept an optional <code>output_name</code> parameter to specify the name of the output file.</p>
      </div>
      
      <p>For detailed API documentation including all parameters, request/response formats, and examples, refer to the Swagger documentation available at <code>/swagger</code> after logging in.</p>
    </article>

    <article id="api-examples">
      <h3>3.3. API Examples</h3>

      <h4>Example: Merging PDF Files</h4>
      <pre><code>curl -X POST https://your-domain.com/api/pdf/merge \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json" \
  -F "files[]=@/path/to/file1.pdf" \
  -F "files[]=@/path/to/file2.pdf" \
  -F "output_name=merged_document"</code></pre>

      <h4>Example: Rotating PDF Pages</h4>
      <pre><code>curl -X POST https://your-domain.com/api/pdf/rotate \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json" \
  -F "file=@/path/to/document.pdf" \
  -F "angle=90" \
  -F "pages=1,3-5" \
  -F "output_name=rotated_document"</code></pre>

      <h4>Example: Encrypting a PDF</h4>
      <pre><code>curl -X POST https://your-domain.com/api/pdf/encrypt \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json" \
  -F "file=@/path/to/document.pdf" \
  -F "user_password=SecurePass123" \
  -F "owner_password=AdminPass456" \
  -F "output_name=encrypted_document"</code></pre>

      <h4>Error Handling</h4>
      <p>In case of errors, the API will return an appropriate HTTP status code and a JSON response with details:</p>
      <pre><code>{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "file": ["The file field is required."],
    "pages": ["The pages must be a string."]
  }
}</code></pre>
    </article>
  </section>

  <div class="manual-footer">
    <p>Copyright © 2025 PDF Alchemist. All rights reserved.</p>
  </div>
</div>