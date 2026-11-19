#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Microservizio Python per OCR documenti
Converte PDF in immagini (pdftoppm) ed esegue OCR con Tesseract
Uso: python OcrMicroService.py --file <path> --mime <mime_type> --lang ita+eng --dpi 300
"""

import argparse
import sys
import os
import tempfile
import subprocess
import shutil
from pathlib import Path
from typing import List, Optional


def check_dependencies() -> bool:
    """Verifica che tesseract e pdftoppm siano installati"""
    for cmd in ['tesseract', 'pdftoppm']:
        if not shutil.which(cmd):
            print(f"ERRORE: '{cmd}' non trovato nel PATH", file=sys.stderr)
            return False
    return True


def pdf_to_images(pdf_path: str, dpi: int = 300, temp_dir: str = None) -> List[str]:
    """
    Converte PDF in immagini PNG usando pdftoppm (poppler)
    Restituisce lista di path alle immagini generate
    """
    if temp_dir is None:
        temp_dir = tempfile.mkdtemp(prefix='ocr_')

    output_prefix = os.path.join(temp_dir, 'page')

    # pdftoppm -png -r 300 input.pdf output_prefix
    cmd = [
        'pdftoppm',
        '-png',
        '-r', str(dpi),
        pdf_path,
        output_prefix
    ]

    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=120)
        if result.returncode != 0:
            raise RuntimeError(f"pdftoppm fallito: {result.stderr}")
    except subprocess.TimeoutExpired:
        raise RuntimeError("Timeout conversione PDF")
    except FileNotFoundError:
        raise RuntimeError("pdftoppm non trovato")

    # raccoglie file generati (page-1.png, page-2.png, ...)
    images = sorted(Path(temp_dir).glob('page-*.png'))
    return [str(img) for img in images]


def ocr_image(image_path: str, lang: str = 'ita+eng') -> str:
    """
    Esegue OCR su singola immagine con Tesseract
    """
    # tesseract image.png stdout -l ita+eng
    cmd = [
        'tesseract',
        image_path,
        'stdout',
        '-l', lang,
        '--psm', '6',  # Assume a single uniform block of text
    ]

    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=60)
        if result.returncode != 0:
            raise RuntimeError(f"Tesseract fallito: {result.stderr}")
        return result.stdout
    except subprocess.TimeoutExpired:
        raise RuntimeError("Timeout OCR su immagine")
    except FileNotFoundError:
        raise RuntimeError("Tesseract non trovato")


def ocr_pdf(pdf_path: str, lang: str = 'ita+eng', dpi: int = 300) -> str:
    """
    Esegue OCR su PDF multipagina
    Converte in immagini, OCR su ogni pagina, concatena risultati
    """
    temp_dir = tempfile.mkdtemp(prefix='ocr_pdf_')
    full_text = []

    try:
        images = pdf_to_images(pdf_path, dpi, temp_dir)

        if not images:
            raise RuntimeError("Nessuna pagina estratta dal PDF")

        for i, img_path in enumerate(images, 1):
            text = ocr_image(img_path, lang)
            if text.strip():
                full_text.append(f"--- PAGINA {i} ---\n{text.strip()}")

        return "\n\n".join(full_text)

    finally:
        # pulizia directory temporanea
        shutil.rmtree(temp_dir, ignore_errors=True)


def ocr_image_file(image_path: str, lang: str = 'ita+eng') -> str:
    """
    Esegue OCR su file immagine (JPG, PNG)
    """
    return ocr_image(image_path, lang)


def main():
    parser = argparse.ArgumentParser(
        description='OCR Microservizio per Laravel - Estrae testo da PDF/Immagini'
    )
    parser.add_argument('--file', required=True, help='Path al file da processare')
    parser.add_argument('--mime', required=True, help='MIME type (application/pdf, image/jpeg, image/png)')
    parser.add_argument('--lang', default='ita+eng', help='Lingue Tesseract (default: ita+eng)')
    parser.add_argument('--dpi', type=int, default=300, help='DPI per conversione PDF (default: 300)')

    args = parser.parse_args()

    # verifica dipendenze
    if not check_dependencies():
        sys.exit(1)

    file_path = args.file
    mime_type = args.mime.lower()
    lang = args.lang
    dpi = args.dpi

    if not os.path.exists(file_path):
        print(f"ERRORE: File non trovato: {file_path}", file=sys.stderr)
        sys.exit(1)

    try:
        if mime_type == 'application/pdf':
            text = ocr_pdf(file_path, lang, dpi)
        elif mime_type in ['image/jpeg', 'image/jpg', 'image/png']:
            text = ocr_image_file(file_path, lang)
        else:
            print(f"ERRORE: MIME type non supportato: {mime_type}", file=sys.stderr)
            sys.exit(1)

        # output su stdout (catturato da Laravel Process)
        print(text)

    except Exception as e:
        print(f"ERRORE: {str(e)}", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()