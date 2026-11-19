<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Document;
use App\Models\Result;
use App\Models\ApiKey;

class StatsController extends Controller
{
    public function index()
    {
        // Statistiche generali
        $totalUsers = User::count();
        $totalDocuments = Document::count();
        $totalResults = Result::count();

        // Spazio occupato documenti
        $storageUsedKb = Document::sum('size_in_kb');
        $storageUsedMb = round($storageUsedKb / 1024, 2);

        // Documenti per tipo MIME
        $documentsByMime = Document::select('mime_type', DB::raw('count(*) as count'))
            ->groupBy('mime_type')
            ->get();

        // Risultati per status
        $resultsByStatus = Result::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Chiamate AI per modello
        $aiCallsByModel = Result::select('ai_model', DB::raw('count(*) as count'))
            ->groupBy('ai_model')
            ->orderByDesc('count')
            ->get();

        // Chiamate AI per provider (via API Keys usate)
        $apiKeys = ApiKey::select('provider', DB::raw('count(*) as count'))
            ->groupBy('provider')
            ->get();

        // Tasso di errore
        $failedResults = Result::where('status', 'failed')->count();
        $errorRate = $totalResults > 0 ? round(($failedResults / $totalResults) * 100, 2) : 0;

        // Utenti per ruolo
        $usersByRole = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get();

        // Ultimi 10 documenti elaborati
        $recentResults = Result::with(['document', 'vector', 'document.user'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.stats', compact(
            'totalUsers',
            'totalDocuments',
            'totalResults',
            'storageUsedMb',
            'documentsByMime',
            'resultsByStatus',
            'aiCallsByModel',
            'apiKeys',
            'errorRate',
            'usersByRole',
            'recentResults'
        ));
    }
}