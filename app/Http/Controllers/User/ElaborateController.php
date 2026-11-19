<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Models\Vector;
use App\Models\Result;
use App\Models\ApiKey;
use App\Services\OcrService;
use App\Services\AiExtractionService;
use App\Services\ResultFormatterService;

class ElaborateController extends Controller
{
    public function index()
    {
        $documents = Auth::user()->documents()->latest()->get();
        $vectors = Auth::user()->vectors()->latest()->get();
        $apiKeys = Auth::user()->apiKeys()->get();
        $ollamaEndpoint = Auth::user()->ollama_endpoint ?? 'http://localhost:11434';

        return view('user.elaborate', compact('documents', 'vectors', 'apiKeys', 'ollamaEndpoint'));
    }

    public function start(Request $request, OcrService $ocrService, AiExtractionService $aiService, ResultFormatterService $formatterService)
    {
        $request->validate([
            'document_id' => 'required|exists:documents,id',
            'vector_id' => 'required|exists:vectors,id',
            'mode' => 'required|in:cloud,ollama',
            'model' => 'required|string',
            'api_key_id' => 'required_if:mode,cloud|exists:api_keys,id',
            'output_format' => 'required|in:json,csv,xml',
        ]);

        $user = Auth::user();

        // verifica proprietà
        $document = $user->documents()->findOrFail($request->document_id);
        $vector = $user->vectors()->findOrFail($request->vector_id);

        $apiKey = null;
        if ($request->mode === 'cloud') {
            $apiKey = $user->apiKeys()->findOrFail($request->api_key_id);
        }

        $ollamaEndpoint = $request->mode === 'ollama' ? ($user->ollama_endpoint ?? 'http://localhost:11434') : null;

        // crea record risultato con status pending
        $result = Result::create([
            'document_id' => $document->id,
            'vector_id' => $vector->id,
            'data' => [],
            'ai_model' => $request->model,
            'status' => 'pending',
        ]);

        // processa in background (sync per semplicità, single user)
        try {
            // Step 1: OCR
            $result->update(['status' => 'processing_ocr']);
            $ocrText = $ocrService->extract($document);

            // Step 2: LLM Extraction
            $result->update(['status' => 'processing_llm']);
            $extractedData = $aiService->extract(
                $document,
                $vector,
                $request->mode,
                $request->model,
                $apiKey,
                $ollamaEndpoint
            );

            // Step 3: Salvataggio
            $result->update([
                'data' => $extractedData,
                'status' => 'completed',
            ]);

            return response()->json([
                'success' => true,
                'result_id' => $result->id,
                'redirect' => route('user.results.show', $result),
            ]);

        } catch (\Exception $e) {
            $result->update([
                'status' => 'failed',
                'data' => ['error' => $e->getMessage()],
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'result_id' => $result->id,
            ], 500);
        }
    }

    public function status(Result $result)
    {
        if ($result->document->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'status' => $result->status,
            'data' => $result->data,
        ]);
    }

    public function fetchOllamaModels(Request $request, AiExtractionService $aiService)
    {
        $endpoint = $request->input('endpoint', Auth::user()->ollama_endpoint ?? 'http://localhost:11434');

        try {
            $models = $aiService->fetchOllamaModels($endpoint);
            return response()->json(['models' => $models]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchCloudModels(Request $request, AiExtractionService $aiService)
    {
        $apiKey = Auth::user()->apiKeys()->find($request->input('api_key_id'));

        if (!$apiKey) {
            return response()->json(['error' => 'API Key non trovata'], 404);
        }

        try {
            $models = $aiService->fetchCloudModels($apiKey->provider, $apiKey->key);
            return response()->json(['models' => $models]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}