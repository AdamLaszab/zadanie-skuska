import sys
from pypdf import PdfWriter, PdfReader
from pypdf.errors import PdfReadError, WrongPasswordError
import argparse
from PIL import Image
import os
import io

ERR_INVALID_ARGUMENT = "INVALID_ARGUMENT"
ERR_FILE_PROCESSING = "FILE_PROCESSING_ERROR"
ERR_PAGE_RANGE = "PAGE_RANGE_ERROR"
ERR_IO = "IO_ERROR"
ERR_UNEXPECTED = "UNEXPECTED_ERROR"
ERR_DECRYPTION_FAILED = "DECRYPTION_FAILED"

def parse_page_spec(page_spec_str, num_total_pages):
    if not page_spec_str or page_spec_str.lower() == 'all':
        if num_total_pages == 0:
            return []
        return list(range(num_total_pages))

    selected_pages = set()
    parts = page_spec_str.split(',')
    for part in parts:
        part = part.strip()
        try:
            if '-' in part:
                start_str, end_str = part.split('-', 1)
                start = int(start_str) - 1
                if end_str == '':
                    if num_total_pages == 0:
                         raise ValueError(f"{ERR_PAGE_RANGE}::Cannot parse range ending with '-' for an empty PDF.")
                    end = num_total_pages -1
                else:
                    end = int(end_str) - 1
                if not (0 <= start < num_total_pages and 0 <= end < num_total_pages and start <= end):
                    raise ValueError(f"{ERR_PAGE_RANGE}::Invalid page range: '{part}' for PDF with {num_total_pages} pages (1-indexed).")
                selected_pages.update(range(start, end + 1))
            else:
                page_num = int(part) - 1
                if not (0 <= page_num < num_total_pages):
                    raise ValueError(f"{ERR_PAGE_RANGE}::Invalid page number: '{part}' for PDF with {num_total_pages} pages (1-indexed).")
                selected_pages.add(page_num)
        except ValueError as e:
            if str(e).startswith(ERR_PAGE_RANGE):
                raise
            else:
                raise ValueError(f"{ERR_PAGE_RANGE}::Invalid character in page specification: '{part}'. Use numbers, commas, hyphens.")
    if not selected_pages and num_total_pages > 0 and page_spec_str and page_spec_str.lower() != 'all':
        raise ValueError(f"{ERR_PAGE_RANGE}::Page specification '{page_spec_str}' resulted in no pages selected for a PDF with {num_total_pages} pages.")
    return sorted(list(selected_pages))


def merge_pdfs(input_paths, output_path):
    merger = PdfWriter()
    try:
        if not input_paths:
            raise ValueError(f"{ERR_INVALID_ARGUMENT}::No input files provided for merge operation.")
        for pdf_path in input_paths:
            try:
                reader = PdfReader(pdf_path)
                if not reader.pages:
                    raise ValueError(f"{ERR_FILE_PROCESSING}::Input PDF '{os.path.basename(pdf_path)}' has no pages or is unreadable.")
                merger.append(reader)
            except FileNotFoundError:
                raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input PDF not found: {pdf_path}")
            except WrongPasswordError:
                raise ValueError(f"{ERR_DECRYPTION_FAILED}::Input PDF '{os.path.basename(pdf_path)}' is password protected and cannot be merged without decryption.")
            except PdfReadError as pre:
                raise ValueError(f"{ERR_FILE_PROCESSING}::Error reading PDF '{os.path.basename(pdf_path)}': {pre}")
            except Exception as e:
                raise ValueError(f"{ERR_FILE_PROCESSING}::Error processing input PDF '{os.path.basename(pdf_path)}': {type(e).__name__} - {e}")
        if not merger.pages:
             raise ValueError(f"{ERR_FILE_PROCESSING}::No pages were added to the merge output, possibly due to empty or problematic input PDFs.")
        with open(output_path, "wb") as f_out:
            merger.write(f_out)
    except IOError as e:
        raise IOError(f"{ERR_IO}::Error writing merged output PDF {output_path}: {e}")
    except Exception as e:
        if isinstance(e, (ValueError, FileNotFoundError, IOError)): raise
        raise RuntimeError(f"{ERR_FILE_PROCESSING}::Unexpected error during merge: {type(e).__name__} - {e}")
    finally:
        merger.close()

def rotate_pages_in_pdf(input_path, output_path, angle, page_spec_str=None):
    writer = PdfWriter()
    try:
        reader = PdfReader(input_path)
        num_total_pages = len(reader.pages)
        if num_total_pages == 0:
             raise ValueError(f"{ERR_FILE_PROCESSING}::Cannot process pages for an empty PDF: '{os.path.basename(input_path)}'.")
        pages_to_rotate = parse_page_spec(page_spec_str, num_total_pages)
        for i, page in enumerate(reader.pages):
            if i in pages_to_rotate:
                page.rotate(angle)
            writer.add_page(page)
        with open(output_path, "wb") as f_out:
            writer.write(f_out)
    except FileNotFoundError:
        raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input PDF not found: {input_path}")
    except WrongPasswordError:
        raise ValueError(f"{ERR_DECRYPTION_FAILED}::Input PDF '{os.path.basename(input_path)}' is password protected. Please decrypt it first.")
    except PdfReadError as pre:
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error reading PDF '{os.path.basename(input_path)}': {pre}")
    except IOError as e:
        raise IOError(f"{ERR_IO}::Error writing rotated PDF {output_path}: {e}")
    except ValueError as ve:
        if str(ve).startswith(ERR_PAGE_RANGE): raise
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error during rotation of '{os.path.basename(input_path)}': {ve}")
    except Exception as e:
        raise RuntimeError(f"{ERR_FILE_PROCESSING}::Unexpected error rotating pages in '{os.path.basename(input_path)}': {type(e).__name__} - {e}")
    finally:
        writer.close()

def delete_pages_from_pdf(input_path, output_path, page_spec_str_to_delete):
    writer = PdfWriter()
    try:
        reader = PdfReader(input_path)
        num_total_pages = len(reader.pages)
        if num_total_pages == 0:
             raise ValueError(f"{ERR_FILE_PROCESSING}::Cannot delete pages from an empty PDF: '{os.path.basename(input_path)}'.")
        pages_to_delete_0_indexed = parse_page_spec(page_spec_str_to_delete, num_total_pages)
        if len(pages_to_delete_0_indexed) == num_total_pages:
            raise ValueError(f"{ERR_INVALID_ARGUMENT}::Deleting all pages specified. Resulting PDF would be empty. Operation aborted for '{os.path.basename(input_path)}'.")
        for i, page in enumerate(reader.pages):
            if i not in pages_to_delete_0_indexed:
                writer.add_page(page)
        with open(output_path, "wb") as f_out:
            writer.write(f_out)
    except FileNotFoundError:
        raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input PDF not found: {input_path}")
    except WrongPasswordError:
        raise ValueError(f"{ERR_DECRYPTION_FAILED}::Input PDF '{os.path.basename(input_path)}' is password protected. Please decrypt it first.")
    except PdfReadError as pre:
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error reading PDF '{os.path.basename(input_path)}': {pre}")
    except IOError as e:
        raise IOError(f"{ERR_IO}::Error writing output PDF after deleting pages: {e}")
    except ValueError as ve:
        if str(ve).startswith(ERR_PAGE_RANGE) or str(ve).startswith(ERR_INVALID_ARGUMENT): raise
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error during page deletion from '{os.path.basename(input_path)}': {ve}")
    except Exception as e:
        raise RuntimeError(f"{ERR_FILE_PROCESSING}::Unexpected error deleting pages from '{os.path.basename(input_path)}': {type(e).__name__} - {e}")
    finally:
        writer.close()

def extract_specific_pages(input_path, output_path, page_spec_str_to_extract):
    writer = PdfWriter()
    try:
        reader = PdfReader(input_path)
        num_total_pages = len(reader.pages)
        if num_total_pages == 0:
             raise ValueError(f"{ERR_FILE_PROCESSING}::Cannot extract pages from an empty PDF: '{os.path.basename(input_path)}'.")
        pages_to_extract_0_indexed = parse_page_spec(page_spec_str_to_extract, num_total_pages)
        if not pages_to_extract_0_indexed :
            raise ValueError(f"{ERR_PAGE_RANGE}::Page specification for extraction resulted in no pages selected for '{os.path.basename(input_path)}'.")
        for i in pages_to_extract_0_indexed:
            writer.add_page(reader.pages[i])
        with open(output_path, "wb") as f_out:
            writer.write(f_out)
    except FileNotFoundError:
        raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input PDF not found: {input_path}")
    except WrongPasswordError:
        raise ValueError(f"{ERR_DECRYPTION_FAILED}::Input PDF '{os.path.basename(input_path)}' is password protected. Please decrypt it first.")
    except PdfReadError as pre:
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error reading PDF '{os.path.basename(input_path)}': {pre}")
    except IOError as e:
        raise IOError(f"{ERR_IO}::Error writing extracted pages PDF: {e}")
    except ValueError as ve:
        if str(ve).startswith(ERR_PAGE_RANGE): raise
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error during page extraction from '{os.path.basename(input_path)}': {ve}")
    except Exception as e:
        raise RuntimeError(f"{ERR_FILE_PROCESSING}::Unexpected error extracting pages from '{os.path.basename(input_path)}': {type(e).__name__} - {e}")
    finally:
        writer.close()

def encrypt_pdf_file(input_path, output_path, user_password, owner_password=None):
    writer = PdfWriter()
    try:
        reader = PdfReader(input_path)
        if reader.is_encrypted:
             raise ValueError(f"{ERR_FILE_PROCESSING}::Input PDF '{os.path.basename(input_path)}' is already encrypted. Decrypt it first if you want to re-encrypt with different settings.")
        if not reader.pages:
            raise ValueError(f"{ERR_FILE_PROCESSING}::Cannot encrypt an empty PDF: '{os.path.basename(input_path)}'.")
        for page in reader.pages:
            writer.add_page(page)
        writer.encrypt(user_password, owner_password=owner_password)
        with open(output_path, "wb") as f_out:
            writer.write(f_out)
    except FileNotFoundError:
        raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input PDF not found: {input_path}")
    except WrongPasswordError:
        raise ValueError(f"{ERR_DECRYPTION_FAILED}::Cannot re-encrypt PDF '{os.path.basename(input_path)}' as it is password protected (and unreadable without password). Decrypt it first.")
    except PdfReadError as pre:
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error reading PDF '{os.path.basename(input_path)}': {pre}")
    except IOError as e:
        raise IOError(f"{ERR_IO}::Error writing encrypted PDF: {e}")
    except Exception as e:
        if isinstance(e, ValueError) and str(e).startswith(ERR_FILE_PROCESSING): raise
        raise RuntimeError(f"{ERR_FILE_PROCESSING}::Error encrypting '{os.path.basename(input_path)}': {type(e).__name__} - {e}")
    finally:
        writer.close()

def decrypt_pdf_file(input_path, output_path, password):
    writer = PdfWriter()
    reader = None
    try:
        try:
            reader = PdfReader(input_path, password=password)
        except WrongPasswordError:
            raise ValueError(f"{ERR_DECRYPTION_FAILED}::Incorrect password provided for PDF '{os.path.basename(input_path)}'.")
        except PdfReadError as e:
            if "password" in str(e).lower() or "decrypt" in str(e).lower():
                raise ValueError(f"{ERR_DECRYPTION_FAILED}::Incorrect password or unable to decrypt PDF '{os.path.basename(input_path)}'. Details: {e}")
            else:
                raise
        if not reader.is_encrypted and not reader.pages and os.path.getsize(input_path) > 0: # Non-encrypted but problematic
             raise ValueError(f"{ERR_FILE_PROCESSING}::Input PDF '{os.path.basename(input_path)}' is not encrypted but appears empty or unreadable.")
        elif not reader.is_encrypted:
            pass
        elif reader.is_encrypted:
            if not reader.decrypt(password):
                raise ValueError(f"{ERR_DECRYPTION_FAILED}::Password was not accepted for decryption by the decrypt() method for PDF '{os.path.basename(input_path)}'.")
        if not reader.pages and os.path.getsize(input_path) > 1024 :
             raise ValueError(f"{ERR_FILE_PROCESSING}::PDF '{os.path.basename(input_path)}' became empty after password attempt, possibly corrupted.")
        for page in reader.pages:
            writer.add_page(page)
        with open(output_path, "wb") as f_out:
            writer.write(f_out)
    except FileNotFoundError:
        raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input PDF not found: {input_path}")
    except IOError as e:
        raise IOError(f"{ERR_IO}::Error writing decrypted PDF {output_path}: {e}")
    except ValueError as ve:
        if str(ve).startswith(tuple(f"{code}::" for code in [ERR_DECRYPTION_FAILED, ERR_FILE_PROCESSING, ERR_INVALID_ARGUMENT])):
            raise
        else:
             raise RuntimeError(f"{ERR_FILE_PROCESSING}::Unexpected ValueError during decryption of '{os.path.basename(input_path)}': {ve}")
    except Exception as e:
        if isinstance(e, (PdfReadError, WrongPasswordError)): raise
        raise RuntimeError(f"{ERR_FILE_PROCESSING}::Unexpected error during PDF decryption of '{os.path.basename(input_path)}': {type(e).__name__} - {e}")
    finally:
        if writer:
            writer.close()

IMAGE_EXTS = {'.png', '.jpg', '.jpeg'}
def _make_pdf_reader(path: str) -> PdfReader:
    """
    If `path` is an image, convert it in-memory to a 1-page PDF
    (Pillow auto-embeds any alpha mask). Otherwise, read the PDF.
    """
    ext = os.path.splitext(path)[1].lower()
    if ext in IMAGE_EXTS:
        img = Image.open(path)
        buf = io.BytesIO()
        img.save(buf, format="PDF")
        buf.seek(0)
        return PdfReader(buf)
    else:
        return PdfReader(path)

def overlay_pdf_pages(
    main_pdf_path: str,
    overlay_path: str,
    output_path: str,
    overlay_page_number: int = 1,
    target_pages_spec: str = None
):
    writer = PdfWriter()
    try:
        main_reader    = PdfReader(main_pdf_path)
        overlay_reader = _make_pdf_reader(overlay_path)

        # --- Validation ---
        if not main_reader.pages:
            raise ValueError(f"Main PDF '{os.path.basename(main_pdf_path)}' has no pages.")
        if not overlay_reader.pages:
            raise ValueError(f"Overlay '{os.path.basename(overlay_path)}' has no pages.")

        idx = overlay_page_number - 1
        if not (0 <= idx < len(overlay_reader.pages)):
            raise ValueError(
                f"Overlay page {overlay_page_number} out of range (1–{len(overlay_reader.pages)})."
            )

        overlay_page = overlay_reader.pages[idx]
        total_pages  = len(main_reader.pages)
        pages_to_do  = parse_page_spec(target_pages_spec, total_pages)

        # --- Build each page ---
        for i, main_page in enumerate(main_reader.pages):
            if i in pages_to_do:
                # 1) measure main page
                mlx, mly = main_page.mediabox.lower_left
                mux, muy = main_page.mediabox.upper_right
                main_w, main_h = mux - mlx, muy - mly

                # 2) measure overlay
                olx, oly = overlay_page.mediabox.lower_left
                oux, ouy = overlay_page.mediabox.upper_right
                ol_w, ol_h = oux - olx, ouy - oly

                # 3) create a blank “canvas” the size of the main page
                bg = writer.add_blank_page(width=main_w, height=main_h)

                # 4) draw overlay (centered)
                tx = (main_w - ol_w) / 2
                ty = (main_h - ol_h) / 2
                bg.merge_translated_page(overlay_page, tx, ty)

                # 5) draw main page content *on top*
                bg.merge_page(main_page)

                # (bg is already in writer.pages)
            else:
                # pages that aren’t targeted just get copied straight over
                writer.add_page(main_page)

        # --- Write out ---
        with open(output_path, "wb") as f_out:
            writer.write(f_out)

    except FileNotFoundError as fnf:
        missing = main_pdf_path if not os.path.exists(main_pdf_path) else overlay_path
        raise FileNotFoundError(f"Input not found: {missing}") from fnf

    except WrongPasswordError as wpe:
        raise ValueError(f"Encrypted PDF—decrypt first: {wpe}")

    except PdfReadError as pre:
        raise ValueError(f"PDF read error: {pre}")

    except IOError as ioe:
        raise IOError(f"Write error: {ioe}")

    finally:
        writer.close()

def extract_text_from_pdf(pdf_path, page_spec_str=None, output_text_path=None):
    all_text_parts = []
    try:
        reader = PdfReader(pdf_path)
        num_total_pages = len(reader.pages)
        if num_total_pages == 0:
            with open(output_text_path, "w", encoding="utf-8") as f_text:
                f_text.write("[PDF is empty - No text extracted]")
            return output_text_path
        pages_to_extract_from = parse_page_spec(page_spec_str, num_total_pages)
        for i in pages_to_extract_from:
            try:
                page_text = reader.pages[i].extract_text()
                if page_text and page_text.strip():
                    all_text_parts.append(f"--- Page {i+1} ---\n{page_text.strip()}\n\n")
            except Exception as page_ex:
                all_text_parts.append(f"--- Page {i+1} ---\n[Error extracting text from this page: {page_ex}]\n\n")
        extracted_content = "".join(all_text_parts).strip()
        with open(output_text_path, "w", encoding="utf-8") as f_text:
            f_text.write(extracted_content if extracted_content else "[No text extracted from selected pages or PDF is image-based/password protected without password]")
        return output_text_path
    except FileNotFoundError:
        raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input PDF not found: {pdf_path}")
    except WrongPasswordError as wpe:
        with open(output_text_path, "w", encoding="utf-8") as f_text: # Still create output file with error
            f_text.write(f"[Cannot extract text: PDF '{os.path.basename(pdf_path)}' is password protected. Password needed.]")
        raise ValueError(f"{ERR_DECRYPTION_FAILED}::PDF '{os.path.basename(pdf_path)}' for text extraction is password protected. {wpe}")
    except PdfReadError as pre:
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error reading PDF for text extraction: {pre}")
    except IOError as e:
        raise IOError(f"{ERR_IO}::Error writing extracted text to {output_text_path}: {e}")
    except ValueError as ve:
        if str(ve).startswith(ERR_PAGE_RANGE): raise
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error during text extraction from '{os.path.basename(pdf_path)}': {ve}")
    except Exception as e:
        raise RuntimeError(f"{ERR_FILE_PROCESSING}::Unexpected error extracting text from '{os.path.basename(pdf_path)}': {type(e).__name__} - {e}")

def reverse_pdf_pages(input_path, output_path):
    writer = PdfWriter()
    try:
        reader = PdfReader(input_path)
        if not reader.pages:
            raise ValueError(f"{ERR_FILE_PROCESSING}::Main PDF '{os.path.basename(input_path)}' for reverse has no pages.")
        for i in range(len(reader.pages) - 1, -1, -1):
            writer.add_page(reader.pages[i])
        with open(output_path, "wb") as f_out:
            writer.write(f_out)
    except FileNotFoundError:
        raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input PDF not found: {input_path}")
    except WrongPasswordError as e:
        raise ValueError(f"{ERR_DECRYPTION_FAILED}::Input PDF '{os.path.basename(input_path)}' is password protected. Details: {e}")
    except PdfReadError as pre:
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error reading PDF '{os.path.basename(input_path)}': {pre}")
    except IOError as e:
        raise IOError(f"{ERR_IO}::Error writing output PDF: {e}")
    except Exception as e:
        raise RuntimeError(f"{ERR_FILE_PROCESSING}::Unexpected error reversing pages: {type(e).__name__} - {e}")
    finally:
        writer.close()

def duplicate_pages_in_pdf(input_path, output_path, page_spec_str_to_duplicate, duplicate_count=1):
    writer = PdfWriter()
    try:
        reader = PdfReader(input_path)
        num_total_pages = len(reader.pages)
        if num_total_pages == 0:
            raise ValueError(f"{ERR_FILE_PROCESSING}::Main PDF '{os.path.basename(input_path)}' for duplicate has no pages.")
        pages_to_duplicate_0_indexed = parse_page_spec(page_spec_str_to_duplicate, num_total_pages)
        if duplicate_count < 0:
            raise ValueError(f"{ERR_INVALID_ARGUMENT}::Duplicate count must be a non-negative integer.")
        for i in range(num_total_pages):
            page = reader.pages[i]
            writer.add_page(page)
            if i in pages_to_duplicate_0_indexed:
                for _ in range(duplicate_count):
                    writer.add_page(page)
        with open(output_path, "wb") as f_out:
            writer.write(f_out)
    except FileNotFoundError:
        raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input PDF not found: {input_path}")
    except WrongPasswordError as e:
        raise ValueError(f"{ERR_DECRYPTION_FAILED}::Input PDF '{os.path.basename(input_path)}' is password protected. Details: {e}")
    except PdfReadError as pre:
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error reading PDF '{os.path.basename(input_path)}': {pre}")
    except IOError as e:
        raise IOError(f"{ERR_IO}::Error writing output PDF: {e}")
    except ValueError as ve:
        if str(ve).startswith(ERR_PAGE_RANGE) or str(ve).startswith(ERR_INVALID_ARGUMENT): raise
        raise ValueError(f"{ERR_FILE_PROCESSING}::Error during page duplication in '{os.path.basename(input_path)}': {ve}")
    except Exception as e:
        raise RuntimeError(f"{ERR_FILE_PROCESSING}::Unexpected error duplicating pages: {type(e).__name__} - {e}")
    finally:
        writer.close()

def main():
    parser = argparse.ArgumentParser(description="PDF processing script using pypdf.", add_help=False)
    required_args = parser.add_argument_group('required arguments')
    optional_args = parser.add_argument_group('optional arguments')

    required_args.add_argument('--operation', required=True,
                        choices=['merge', 'rotate', 'delete_pages', 'extract_pages',
                                 'encrypt', 'decrypt', 'overlay', 'extract_text',
                                 'reverse_pages', 'duplicate_pages'],
                        help="The PDF operation to perform.")
    required_args.add_argument('--input', nargs='+', required=True,
                        help="Full path(s) to the input PDF file(s). First input is primary for most ops.")
    required_args.add_argument('--output', required=True,
                        help="Full path for the output PDF/text file.")

    optional_args.add_argument('--pages',
                        help="Page specification (e.g., '1,3-5,all') for rotate, delete_pages, extract_pages, extract_text, overlay (target pages), duplicate_pages. Default: 'all' where applicable.")
    optional_args.add_argument('--angle', type=int, choices=[0, 90, 180, 270],
                        help="Rotation angle (for 'rotate' operation).")
    optional_args.add_argument('--user-password', help="User password for encryption.")
    optional_args.add_argument('--owner-password', help="Owner password for encryption (optional).")
    optional_args.add_argument('--password', help="Password for decryption.")
    optional_args.add_argument('--overlay-pdf', help="Path to the PDF to use as an overlay/watermark.")
    optional_args.add_argument('--overlay-page-number', type=int, default=1,
                        help="1-indexed page from overlay-pdf to use (e.g., 0 for 1st page). Default: 0.")
    optional_args.add_argument('--duplicate-count', type=int, default=1,
                        help="Number of *additional* copies for 'duplicate_pages' (default: 1).")
    optional_args.add_argument('-h', '--help', action='help', default=argparse.SUPPRESS,
                        help='Show this help message and exit.')

    args = parser.parse_args()
    output_file_generated = None

    try:
        for in_path in args.input:
            if not os.path.exists(in_path):
                raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Input file not found: {in_path}")
            if not os.path.isfile(in_path):
                 raise ValueError(f"{ERR_INVALID_ARGUMENT}::Input path is not a file: {in_path}")

        if args.operation not in ['merge'] and len(args.input) != 1:
            raise ValueError(f"{ERR_INVALID_ARGUMENT}::Operation '{args.operation}' requires exactly one primary input PDF via --input (received {len(args.input)}).")

        if args.operation == 'merge':
            if len(args.input) < 2:
                raise ValueError(f"{ERR_INVALID_ARGUMENT}::Merge operation requires at least two input files.")
            merge_pdfs(args.input, args.output)
            output_file_generated = args.output
        elif args.operation == 'rotate':
            if args.angle is None:
                raise ValueError(f"{ERR_INVALID_ARGUMENT}::Rotate operation requires --angle.")
            rotate_pages_in_pdf(args.input[0], args.output, args.angle, args.pages or 'all')
            output_file_generated = args.output
        elif args.operation == 'delete_pages':
            if not args.pages:
                raise ValueError(f"{ERR_INVALID_ARGUMENT}::Delete pages operation requires --pages to delete.")
            delete_pages_from_pdf(args.input[0], args.output, args.pages)
            output_file_generated = args.output
        elif args.operation == 'extract_pages':
            if not args.pages:
                raise ValueError(f"{ERR_INVALID_ARGUMENT}::Extract pages operation requires --pages to extract.")
            extract_specific_pages(args.input[0], args.output, args.pages)
            output_file_generated = args.output
        elif args.operation == 'encrypt':
            if not args.user_password:
                raise ValueError(f"{ERR_INVALID_ARGUMENT}::Encrypt operation requires --user-password.")
            encrypt_pdf_file(args.input[0], args.output, args.user_password, args.owner_password)
            output_file_generated = args.output
        elif args.operation == 'decrypt':
            if not args.password:
                raise ValueError(f"{ERR_INVALID_ARGUMENT}::Decrypt operation requires --password.")
            decrypt_pdf_file(args.input[0], args.output, args.password)
            output_file_generated = args.output
        elif args.operation == 'overlay':
            if not args.overlay_pdf:
                raise ValueError(f"{ERR_INVALID_ARGUMENT}::Overlay operation requires --overlay-pdf.")
            if not os.path.exists(args.overlay_pdf):
                 raise FileNotFoundError(f"{ERR_FILE_PROCESSING}::Overlay PDF file not found: {args.overlay_pdf}")
            overlay_pdf_pages(args.input[0], args.overlay_pdf, args.output, args.overlay_page_number, args.pages or 'all')
            output_file_generated = args.output
        elif args.operation == 'extract_text':
            output_file_generated = extract_text_from_pdf(args.input[0], args.pages or 'all', args.output)
        elif args.operation == 'reverse_pages':
            reverse_pdf_pages(args.input[0], args.output)
            output_file_generated = args.output
        elif args.operation == 'duplicate_pages':
            if not args.pages:
                raise ValueError(f"{ERR_INVALID_ARGUMENT}::Duplicate pages operation requires --pages to specify which pages to duplicate.")
            if args.duplicate_count < 0:
                 raise ValueError(f"{ERR_INVALID_ARGUMENT}::--duplicate-count must be 0 or greater.")
            duplicate_pages_in_pdf(args.input[0], args.output, args.pages, args.duplicate_count)
            output_file_generated = args.output

        if output_file_generated:
            print(output_file_generated, end='')
        else:
            raise RuntimeError(f"{ERR_UNEXPECTED}::Operation '{args.operation}' completed but no output file path was determined.")
        sys.exit(0)

    except FileNotFoundError as fnf:
        print(str(fnf), file=sys.stderr)
        sys.exit(4)
    except WrongPasswordError as wpe:
        print(f"{ERR_DECRYPTION_FAILED}::Password error occurred: {wpe}", file=sys.stderr)
        sys.exit(7)
    except PdfReadError as pre:
        print(f"{ERR_FILE_PROCESSING}::Failed to read or parse PDF: {pre}", file=sys.stderr)
        sys.exit(5)
    except ValueError as ve:
        print(str(ve), file=sys.stderr)
        if str(ve).startswith(ERR_DECRYPTION_FAILED): sys.exit(7)
        elif str(ve).startswith(ERR_PAGE_RANGE): sys.exit(8)
        else: sys.exit(2)
    except IOError as ioe:
        print(str(ioe), file=sys.stderr)
        sys.exit(3)
    except RuntimeError as rte:
        print(str(rte), file=sys.stderr)
        sys.exit(6)
    except Exception as e:
        print(f"{ERR_UNEXPECTED}::An unexpected error occurred: {type(e).__name__} - {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()