<?php

namespace App\Http\Controllers\User;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Models\Vector;
use App\Models\Result;

class HomeController extends UserController
{
    public function index(): View
    {
        $user = Auth::user();

        // Statistiche utente
        $stats = [
            'documents_count' => $user->documents()->count(),
            'vectors_count' => $user->vectors()->count(),
            'results_count' => $user->results()->count(),
            'completed_count' => $user->results()->where('status', 'completed')->count(),
            'failed_count' => $user->results()->where('status', 'failed')->count(),
            'storage_kb' => $user->documents()->sum('size_in_kb'),
        ];

        // Risultati per modello AI
        $resultsByModel = $user->results()
            ->select('ai_model', DB::raw('count(*) as count'))
            ->groupBy('ai_model')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Risultati per status
        $resultsByStatus = $user->results()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Ultimi 5 risultati
        $recentResults = $user->results()
            ->with(['document', 'vector'])
            ->latest()
            ->limit(5)
            ->get();

        // Documenti con/senza cache OCR
        $ocrCached = $user->documents()->whereNotNull('content')->where('content', '!=', '')->count();
        $ocrPending = $user->documents()->where(function ($q) {
            $q->whereNull('content')->orWhere('content', '');
        })->count();

        return view('user.home', compact(
            'stats',
            'resultsByModel',
            'resultsByStatus',
            'recentResults',
            'ocrCached',
            'ocrPending'
        ));
    }
}