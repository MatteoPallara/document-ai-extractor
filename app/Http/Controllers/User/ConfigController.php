<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ApiKey;
use App\Services\AiExtractionService;

class ConfigController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $apiKeys = $user->apiKeys()->orderBy('created_at', 'desc')->get();
        $ollamaEndpoint = $user->ollama_endpoint ?? 'http://localhost:11434';

        return view('user.config', compact('apiKeys', 'ollamaEndpoint'));
    }

    public function storeApiKey(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:google,openai,anthropic',
            'key' => 'required|string',
        ]);

        $apiKey = Auth::user()->apiKeys()->create([
            'name' => $request->name,
            'provider' => $request->provider,
            'key' => $request->key,
        ]);

        return redirect()->route('user.config')
            ->with('success', 'API Key salvata con successo');
    }

    public function destroyApiKey(ApiKey $apiKey)
    {
        if ($apiKey->user_id !== Auth::id()) {
            abort(403);
        }

        $apiKey->delete();

        return redirect()->route('user.config')
            ->with('success', 'API Key eliminata');
    }

    public function updateOllama(Request $request)
    {
        $request->validate([
            'ollama_endpoint' => 'required|url|max:255',
        ]);

        Auth::user()->update([
            'ollama_endpoint' => $request->ollama_endpoint,
        ]);

        return redirect()->route('user.config')
            ->with('success', 'Endpoint Ollama aggiornato');
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