<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Result;
use App\Services\ResultFormatterService;

class ResultController extends Controller
{
    public function index()
    {
        $results = Auth::user()->results()
            ->with(['document', 'vector'])
            ->latest()
            ->paginate(20);

        return view('user.results', compact('results'));
    }

    public function show(Result $result)
    {
        if ($result->document->user_id !== Auth::id()) {
            abort(403);
        }

        $result->load(['document', 'vector']);
        return view('user.results.show', compact('result'));
    }

    public function download(Result $result, string $format, ResultFormatterService $formatterService)
    {
        if ($result->document->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array(strtolower($format), ['json', 'csv', 'xml'])) {
            abort(400, 'Formato non supportato');
        }

        try {
            $path = $formatterService->save($result, $format);
            $content = Storage::get($path);
            $mimeType = $formatterService->getMimeType($format);
            $extension = $formatterService->getExtension($format);
            $filename = "risultato_{$result->id}.{$extension}";

            return response($content)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Errore download: ' . $e->getMessage()]);
        }
    }

    public function destroy(Result $result)
    {
        if ($result->document->user_id !== Auth::id()) {
            abort(403);
        }

        $result->delete();

        return redirect()->route('user.results')
            ->with('success', 'Risultato eliminato');
    }
}