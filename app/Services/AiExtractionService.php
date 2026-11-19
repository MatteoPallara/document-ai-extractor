<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ApiKey;
use App\Models\Vector;
use App\Models\Document;
use Exception;

/**
 * Servizio per l'estrazione dati tramite LLM (Cloud API o Ollama locale)
 */
class AiExtractionService
{
    // timeout per richieste HTTP
    protected int $timeout = 120;

    // max caratteri testo OCR da inviare
    protected int $maxTextLength = 50000;

    /**
     * Estrae dati strutturati dal testo OCR usando il modello specificato
     */
    public function extract(
        Document $document,
        Vector $vector,
        string $mode, // 'cloud' o 'ollama'
        string $model,
        ?ApiKey $apiKey = null,
        ?string $ollamaEndpoint = null
    ): array {
        // costruisce prompt dinamico
        $prompt = $this->buildPrompt($document->content, $vector->getSchema());

        // trunca testo se troppo lungo
        $prompt = $this->truncatePrompt($prompt);

        Log::info('Avvio estrazione LLM', [
            'document_id' => $document->id,
            'vector_id' => $vector->id,
            'mode' => $mode,
            'model' => $model,
        ]);

        if ($mode === 'ollama') {
            return $this->callOllama($prompt, $model, $ollamaEndpoint);
        }

        return $this->callCloudApi($prompt, $model, $apiKey);
    }

    /**
     * Costruisce il prompt per l'LLM con schema dinamico
     */
    protected function buildPrompt(string $ocrText, array $schema): string
    {
        $fields = [];
        foreach ($schema as $key => $type) {
            $fields[] = "- {$key} (tipo: {$type})";
        }
        $fieldsList = implode("\n", $fields);

        return <<<PROMPT
Sei un estrattore di dati strutturati. Analizza il testo OCR fornito ed estrai le informazioni seguendo ESATTAMENTE lo schema sotto.
Restituisci SOLO un JSON valido, SENZA markdown, SENZA spiegazioni, SENZA testo aggiuntivo.

SCHEMA RICHIESTO:
{$fieldsList}

TESTO OCR:
{$ocrText}

REGOLE:
1. Se un campo non è presente nel testo, usa null
2. Rispetta i tipi: string = testo, integer = numero intero, float = decimale, date = YYYY-MM-DD, boolean = true/false
3. Per date, usa formato ISO YYYY-MM-DD
4. Per importi, usa numero con punto decimale (es. 1250.50)
5. Restituisci SOLO l'oggetto JSON

JSON:
PROMPT;
    }

    /**
     * Tronca il prompt se supera la lunghezza massima
     */
    protected function truncatePrompt(string $prompt): string
    {
        if (strlen($prompt) <= $this->maxTextLength) {
            return $prompt;
        }

        // mantiene l'inizio (istruzioni) e la fine (testo OCR troncato)
        $headerEnd = strpos($prompt, 'TESTO OCR:') + 10;
        $header = substr($prompt, 0, $headerEnd);
        $available = $this->maxTextLength - strlen($header) - 100; // margine

        if ($available < 1000) {
            $available = 1000;
        }

        $ocrText = substr($prompt, $headerEnd);
        $truncated = substr($ocrText, 0, $available) . "\n\n[TESTO TRONCATO PER LUNGHEZZA]";

        return $header . $truncated;
    }

    /**
     * Chiama API Ollama locale
     */
    protected function callOllama(string $prompt, string $model, ?string $endpoint): array
    {
        $endpoint = $endpoint ?? 'http://localhost:11434';

        $response = Http::timeout($this->timeout)
            ->post("{$endpoint}/api/generate", [
                'model' => $model,
                'prompt' => $prompt,
                'format' => 'json',
                'stream' => false,
                'options' => [
                    'temperature' => 0.1,
                    'top_p' => 0.9,
                ],
            ]);

        if ($response->failed()) {
            throw new Exception("Errore Ollama ({$response->status()}): " . $response->body());
        }

        $data = $response->json();
        $jsonText = $data['response'] ?? '';

        return $this->parseJsonResponse($jsonText);
    }

    /**
     * Chiama API Cloud (OpenAI, Google, Anthropic)
     */
    protected function callCloudApi(string $prompt, string $model, ?ApiKey $apiKey): array
    {
        if (!$apiKey) {
            throw new Exception('API Key richiesta per modalità cloud');
        }

        $provider = $apiKey->provider;
        $key = $apiKey->key; // già decriptata dal cast

        return match ($provider) {
            'openai' => $this->callOpenAI($prompt, $model, $key),
            'google' => $this->callGoogle($prompt, $model, $key),
            'anthropic' => $this->callAnthropic($prompt, $model, $key),
            default => throw new Exception("Provider non supportato: {$provider}"),
        };
    }

    /**
     * Chiama OpenAI API
     */
    protected function callOpenAI(string $prompt, string $model, string $key): array
    {
        $response = Http::timeout($this->timeout)
            ->withToken($key)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Sei un estrattore di dati. Restituisci solo JSON valido.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            throw new Exception("Errore OpenAI ({$response->status()}): " . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        return $this->parseJsonResponse($content);
    }

    /**
     * Chiama Google Gemini API
     */
    protected function callGoogle(string $prompt, string $model, string $key): array
    {
        $response = Http::timeout($this->timeout)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if ($response->failed()) {
            throw new Exception("Errore Google ({$response->status()}): " . $response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        return $this->parseJsonResponse($text);
    }

    /**
     * Chiama Anthropic Claude API
     */
    protected function callAnthropic(string $prompt, string $model, string $key): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 4000,
                'temperature' => 0.1,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new Exception("Errore Anthropic ({$response->status()}): " . $response->body());
        }

        $text = $response->json('content.0.text');
        return $this->parseJsonResponse($text);
    }

    /**
     * Pulisce e parsifica la risposta JSON dall'LLM
     */
    protected function parseJsonResponse(string $response): array
    {
        // rimuove blocchi markdown ```json ... ```
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($response));
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        // prova parsing diretto
        $decoded = json_decode($cleaned, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // tenta di estrarre JSON da testo più ampio
        if (preg_match('/\{.*\}/s', $cleaned, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        Log::error('Impossibile parsare risposta LLM', ['response' => $response]);
        throw new Exception('Risposta LLM non è JSON valido: ' . json_last_error_msg());
    }

    /**
     * Recupera modelli disponibili da Ollama
     */
    public function fetchOllamaModels(string $endpoint): array
    {
        $response = Http::timeout(30)
            ->get("{$endpoint}/api/tags");

        if ($response->failed()) {
            throw new Exception("Errore connessione Ollama: " . $response->body());
        }

        $models = $response->json('models', []);
        return array_map(fn($m) => $m['name'], $models);
    }

    /**
     * Recupera modelli disponibili per provider cloud
     */
    public function fetchCloudModels(string $provider, string $key): array
    {
        return match ($provider) {
            'openai' => $this->fetchOpenAIModels($key),
            'google' => $this->fetchGoogleModels($key),
            'anthropic' => ['claude-3-5-sonnet', 'claude-3-5-haiku', 'claude-3-opus'],
            default => [],
        };
    }

    protected function fetchOpenAIModels(string $key): array
    {
        $response = Http::withToken($key)->get('https://api.openai.com/v1/models');
        if ($response->failed()) return [];
        return collect($response->json('data', []))
            ->filter(fn($m) => str_contains($m['id'], 'gpt'))
            ->pluck('id')
            ->toArray();
    }

    protected function fetchGoogleModels(string $key): array
    {
        $response = Http::get("https://generativelanguage.googleapis.com/v1beta/models?key={$key}");
        if ($response->failed()) return [];
        return collect($response->json('models', []))
            ->filter(fn($m) => str_contains($m['name'], 'gemini'))
            ->map(fn($m) => str_replace('models/', '', $m['name']))
            ->toArray();
    }
}