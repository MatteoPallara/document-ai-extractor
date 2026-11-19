<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Models\Result;
use Exception;

/**
 * Servizio per formattare i risultati in JSON, CSV o XML
 */
class ResultFormatterService
{
    // separatore CSV
    protected string $csvDelimiter = ';';

    // encoding output
    protected string $encoding = 'UTF-8';

    /**
     * Formatta i dati del risultato nel formato richiesto
     */
    public function format(Result $result, string $format): string
    {
        $data = $result->data;

        return match (strtolower($format)) {
            'json' => $this->toJson($data),
            'csv' => $this->toCsv($data),
            'xml' => $this->toXml($data),
            default => throw new Exception("Formato non supportato: {$format}"),
        };
    }

    /**
     * Converte in JSON formattato
     */
    public function toJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Converte in CSV con separatore ;
     * Per oggetti nidificati, appiattisce con notazione punto
     */
    public function toCsv(array $data): string
    {
        // appiattisce array nidificati
        $flat = $this->flattenArray($data);

        if (empty($flat)) {
            return '';
        }

        $headers = array_keys($flat);
        $values = array_values($flat);

        // escape per CSV
        $escapedValues = array_map(fn($v) => $this->escapeCsvValue($v), $values);

        return implode($this->csvDelimiter, $headers) . "\n" .
               implode($this->csvDelimiter, $escapedValues) . "\n";
    }

    /**
     * Converte in XML
     */
    public function toXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="' . $this->encoding . '"?><result></result>');
        $this->arrayToXml($data, $xml);
        return $xml->asXML();
    }

    /**
     * Salva il file formattato nello storage e restituisce il path
     */
    public function save(Result $result, string $format): string
    {
        $content = $this->format($result, $format);
        $extension = strtolower($format);
        $filename = "result_{$result->id}_{$result->document_id}.{$extension}";
        $path = "results/{$filename}";

        Storage::put($path, $content);

        return $path;
    }

    /**
     * Restituisce il mime type per il formato
     */
    public function getMimeType(string $format): string
    {
        return match (strtolower($format)) {
            'json' => 'application/json',
            'csv' => 'text/csv; charset=' . $this->encoding,
            'xml' => 'application/xml',
            default => 'application/octet-stream',
        };
    }

    /**
     * Restituisce l'estensione file per il formato
     */
    public function getExtension(string $format): string
    {
        return strtolower($format);
    }

    /**
     * Genera anteprima della struttura vuota basata sul vettore
     */
    public function previewStructure(array $schema, string $format): string
    {
        // crea dati di esempio basati sui tipi
        $sampleData = [];
        foreach ($schema as $key => $type) {
            $sampleData[$key] = $this->getSampleValue($type);
        }

        return $this->format(new class($sampleData) extends Result {
            public function __construct(array $data)
            {
                $this->data = $data;
            }
        }, $format);
    }

    /**
     * Appiattisce array multidimensionale con notazione punto
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Escape valore per CSV
     */
    protected function escapeCsvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        $str = (string) $value;
        // escape virgolette e avvolge in virgolette se contiene delimitatore, newline o virgolette
        if (str_contains($str, $this->csvDelimiter) || str_contains($str, "\n") || str_contains($str, '"')) {
            return '"' . str_replace('"', '""', $str) . '"';
        }
        return $str;
    }

    /**
     * Converte array ricorsivamente in XML
     */
    protected function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            // sanitizza key per XML
            $key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
            if (is_numeric($key)) {
                $key = "item_{$key}";
            }

            if (is_array($value)) {
                $child = $xml->addChild($key);
                $this->arrayToXml($value, $child);
            } else {
                $xml->addChild($key, $value === null ? '' : (string) $value);
            }
        }
    }

    /**
     * Genera valore di esempio per tipo
     */
    protected function getSampleValue(string $type): mixed
    {
        return match (strtolower($type)) {
            'string' => 'esempio_testo',
            'integer' => 123,
            'float' => 123.45,
            'date' => '2025-01-15',
            'boolean' => true,
            default => 'valore',
        };
    }

    /**
     * Imposta separatore CSV
     */
    public function setCsvDelimiter(string $delimiter): self
    {
        $this->csvDelimiter = $delimiter;
        return $this;
    }
}