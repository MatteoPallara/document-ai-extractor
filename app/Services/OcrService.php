<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use App\Models\Document;
use Exception;

/**
 * Servizio per l'estrazione testo via OCR (Tesseract)
 * Usa un microservizio Python per la conversione PDF->Immagini e OCR
 */
class OcrService
{
    // percorso allo script Python
    protected string $pythonScript;

    // lingue Tesseract (italiano + inglese)
    protected string $languages = 'ita+eng';

    // DPI per conversione PDF
    protected int $dpi = 300;

    // timeout in secondi per processo OCR
    protected int $timeout = 300;

    public function __construct()
    {
        $this->pythonScript = base_path('OcrMicroService.py');
    }

    /**
     * Estrae il testo da un documento
     * Se il documento ha già il contenuto in cache, lo restituisce direttamente
     */
    public function extract(Document $document): string
    {
        // controlla cache OCR
        if ($document->hasOcrCache()) {
            Log::info('OCR cache hit per documento: ' . $document->id);
            return $document->content;
        }

        Log::info('Avvio OCR per documento: ' . $document->id);

        $filePath = Storage::path($document->path);

        if (!file_exists($filePath)) {
            throw new Exception('File non trovato: ' . $filePath);
        }

        // esegue OCR tramite script Python
        $text = $this->runOcr($filePath, $document->mime_type);

        // salva in cache
        $document->update(['content' => $text]);

        Log::info('OCR completato per documento: ' . $document->id . ' (' . strlen($text) . ' caratteri)');

        return $text;
    }

    /**
     * Esegue l'OCR chiamando lo script Python
     */
    protected function runOcr(string $filePath, string $mimeType): string
    {
        $command = [
            'python',
            $this->pythonScript,
            '--file' => $filePath,
            '--mime' => $mimeType,
            '--lang' => $this->languages,
            '--dpi' => (string) $this->dpi,
        ];

        try {
            $result = Process::run($command)
                ->timeout($this->timeout)
                ->throwOnError();

            $output = trim($result->output());

            if (empty($output)) {
                throw new Exception('OCR non ha prodotto testo per: ' . $filePath);
            }

            return $output;

        } catch (Exception $e) {
            Log::error('Errore OCR: ' . $e->getMessage(), [
                'file' => $filePath,
                'mime' => $mimeType,
            ]);
            throw new Exception('Errore durante OCR: ' . $e->getMessage());
        }
    }

    /**
     * Verifica che Tesseract e pdftoppm siano disponibili
     */
    public function checkDependencies(): array
    {
        $checks = [
            'tesseract' => false,
            'pdftoppm' => false,
            'python' => false,
        ];

        try {
            $checks['tesseract'] = Process::run(['tesseract', '--version'])->successful();
        } catch (\Throwable) {}

        try {
            $checks['pdftoppm'] = Process::run(['pdftoppm', '-v'])->successful();
        } catch (\Throwable) {}

        try {
            $checks['python'] = Process::run(['python', '--version'])->successful();
        } catch (\Throwable) {}

        return $checks;
    }

    /**
     * Imposta le lingue per l'OCR
     */
    public function setLanguages(string $languages): self
    {
        $this->languages = $languages;
        return $this;
    }

    /**
     * Imposta DPI per conversione PDF
     */
    public function setDpi(int $dpi): self
    {
        $this->dpi = $dpi;
        return $this;
    }
}