@extends('layouts.app')

@section('style')
<style>
    .stat-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .chart-container {
        height: 300px;
        position: relative;
    }
    .recent-result-row:hover {
        background: #f8f9fa;
    }
</style>
@endsection

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-1">Statistiche Globali</h1>
                <p class="text-muted mb-0">Panoramica dell'utilizzo del sistema</p>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="material-icons">people</i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $totalUsers }}</h3>
                                <small class="text-muted">Utenti Totali</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="material-icons">description</i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $totalDocuments }}</h3>
                                <small class="text-muted">Documenti</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                                <i class="material-icons">assessment</i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $totalResults }}</h3>
                                <small class="text-muted">Elaborazioni</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                                <i class="material-icons">storage</i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $storageUsedMb }} MB</h3>
                                <small class="text-muted">Spazio Occupato</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card stat-card">
                    <div class="card-header-custom">Documenti per Tipo</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="mimeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card stat-card">
                    <div class="card-header-custom">Risultati per Stato</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card stat-card">
                    <div class="card-header-custom">Modelli AI Più Usati</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="modelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card stat-card">
                    <div class="card-header-custom">API Keys per Provider</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="providerChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Rate + Users by Role -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card stat-card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <span>Tasso di Errore</span>
                        <span class="badge {{ $errorRate > 10 ? 'bg-danger' : ($errorRate > 5 ? 'bg-warning' : 'bg-success') }}">{{ $errorRate }}%</span>
                    </div>
                    <div class="card-body text-center py-4">
                        <div class="display-4 fw-bold text-{{ $errorRate > 10 ? 'danger' : ($errorRate > 5 ? 'warning' : 'success') }}">{{ $errorRate }}%</div>
                        <p class="text-muted mb-0">{{ $failedResults }} fallite su {{ $totalResults }} totali</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card stat-card">
                    <div class="card-header-custom">Utenti per Ruolo</div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 200px;">
                            <canvas id="roleChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Results -->
        <div class="card stat-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="material-icons me-2">history</i>Ultimi Risultati</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Utente</th>
                                <th>Documento</th>
                                <th>Vettore</th>
                                <th>Modello</th>
                                <th>Stato</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentResults as $result)
                            <tr class="recent-result-row">
                                <td>
                                    <div class="fw-medium">{{ $result->document->user->name }}</div>
                                    <small class="text-muted">{{ $result->document->user->email }}</small>
                                </td>
                                <td>{{ $result->document->original_name }}</td>
                                <td>{{ $result->vector->name }}</td>
                                <td>{{ $result->ai_model }}</td>
                                <td>
                                    <span class="status-badge status-{{ $result->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $result->status)) }}
                                    </span>
                                </td>
                                <td>{{ $result->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Nessun risultato</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mime Chart
    const mimeData = @json($documentsByMime->pluck('count', 'mime_type')->toArray());
    new Chart(document.getElementById('mimeChart'), {
        type: 'pie',
        data: {
            labels: Object.keys(mimeData).map(m => m.replace('application/', '').replace('image/', '')),
            datasets: [{
                data: Object.values(mimeData),
                backgroundColor: ['#ef5350', '#66bb6a', '#42a5f5'],
                borderWidth: 0,
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Status Chart
    const statusData = @json($resultsByStatus->pluck('count', 'status')->toArray());
    const statusColors = {
        'pending': '#ffb74d',
        'processing_ocr': '#4fc3f7',
        'processing_llm': '#7986cb',
        'completed': '#81c784',
        'failed': '#e57373'
    };
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData).map(s => s.replace('_', ' ')),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: Object.keys(statusData).map(s => statusColors[s] || '#9e9e9e'),
                borderWidth: 0,
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Model Chart
    const modelData = @json($aiCallsByModel->pluck('count', 'ai_model')->toArray());
    new Chart(document.getElementById('modelChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(modelData),
            datasets: [{
                label: 'Chiamate',
                data: Object.values(modelData),
                backgroundColor: '#1976d2',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });

    // Provider Chart
    const providerData = @json($apiKeys->pluck('count', 'provider')->toArray());
    const providerColors = { 'openai': '#00d4aa', 'google': '#fbbc05', 'anthropic': '#e91e63' };
    new Chart(document.getElementById('providerChart'), {
        type: 'pie',
        data: {
            labels: Object.keys(providerData).map(p => p.charAt(0).toUpperCase() + p.slice(1)),
            datasets: [{
                data: Object.values(providerData),
                backgroundColor: Object.keys(providerData).map(p => providerColors[p] || '#9e9e9e'),
                borderWidth: 0,
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Role Chart
    const roleData = @json($usersByRole->pluck('count', 'role')->toArray());
    new Chart(document.getElementById('roleChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(roleData).map(r => r.charAt(0).toUpperCase() + r.slice(1)),
            datasets: [{
                data: Object.values(roleData),
                backgroundColor: ['#1976d2', '#4caf50'],
                borderWidth: 0,
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
});
</script>
@endsection