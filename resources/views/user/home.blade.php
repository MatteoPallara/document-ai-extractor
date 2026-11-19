@extends('layouts.app')

@section('style')
<style>
    .stat-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
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
        height: 250px;
        position: relative;
    }
</style>
@endsection

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-1">Dashboard</h1>
                <p class="text-muted mb-0">Panoramica delle tue elaborazioni</p>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="material-icons">description</i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $stats['documents_count'] }}</h3>
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
                            <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="material-icons">schema</i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $stats['vectors_count'] }}</h3>
                                <small class="text-muted">Vettori</small>
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
                                <h3 class="mb-0">{{ $stats['results_count'] }}</h3>
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
                                <h3 class="mb-0">{{ round($stats['storage_kb'] / 1024, 1) }} MB</h3>
                                <small class="text-muted">Spazio usato</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card stat-card">
                    <div class="card-header-custom">Elaborazioni per Stato</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

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
        </div>

        <!-- OCR Cache Status + Recent Results -->
        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card stat-card">
                    <div class="card-header-custom">Cache OCR</div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 200px;">
                            <canvas id="ocrChart"></canvas>
                        </div>
                        <div class="row text-center mt-3">
                            <div class="col-6">
                                <div class="fw-bold text-success">{{ $ocrCached }}</div>
                                <small class="text-muted">In cache</small>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold text-warning">{{ $ocrPending }}</div>
                                <small class="text-muted">Da processare</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card stat-card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <span>Ultimi Risultati</span>
                        <a href="{{ route('user.results') }}" class="btn btn-sm btn-outline-primary">Vedi tutti</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Documento</th>
                                        <th>Vettore</th>
                                        <th>Modello</th>
                                        <th>Stato</th>
                                        <th>Data</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentResults as $result)
                                    <tr>
                                        <td>{{ $result->document->original_name }}</td>
                                        <td>{{ $result->vector->name }}</td>
                                        <td>{{ $result->ai_model }}</td>
                                        <td>
                                            <span class="status-badge status-{{ $result->status }}">
                                                {{ ucfirst($result->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $result->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('user.results.show', $result) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="material-icons" style="font-size: 1rem;">visibility</i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Nessun risultato recente</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status Chart
    const statusData = @json($resultsByStatus->pluck('count', 'status')->toArray());
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: ['#ffb74d', '#4fc3f7', '#7986cb', '#81c784', '#e57373'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Model Chart
    const modelData = @json($resultsByModel->pluck('count', 'ai_model')->toArray());
    new Chart(document.getElementById('modelChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(modelData),
            datasets: [{
                label: 'Utilizzi',
                data: Object.values(modelData),
                backgroundColor: '#1976d2',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });

    // OCR Chart
    const ocrCached = {{ $ocrCached }};
    const ocrPending = {{ $ocrPending }};
    new Chart(document.getElementById('ocrChart'), {
        type: 'pie',
        data: {
            labels: ['In Cache', 'Da Processare'],
            datasets: [{
                data: [ocrCached, ocrPending],
                backgroundColor: ['#4caf50', '#ff9800'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>
@endsection