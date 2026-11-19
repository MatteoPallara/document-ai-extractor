@extends('layouts.app')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-1">Configurazione</h1>
                <p class="text-muted mb-0">Gestisci API Keys e configurazione Ollama</p>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row">
            <!-- API Keys -->
            <div class="col-12 col-xl-7 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="material-icons me-2">vpn_key</i>API Keys</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#apiKeyModal">
                            <i class="material-icons me-1">add</i>Nuova API Key
                        </button>
                    </div>
                    <div class="card-body">
                        @if($apiKeys->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="material-icons" style="font-size: 3rem;">vpn_key</i>
                            <p class="mt-2">Nessuna API Key configurata</p>
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>Provider</th>
                                        <th>Creato</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($apiKeys as $key)
                                    <tr>
                                        <td>{{ $key->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $key->provider === 'openai' ? 'success' : ($key->provider === 'google' ? 'warning' : 'info') }}">
                                                {{ ucfirst($key->provider) }}
                                            </span>
                                        </td>
                                        <td>{{ $key->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <form action="{{ route('config.api-keys.destroy', $key) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminare questa API Key?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="material-icons" style="font-size: 1rem;">delete</i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Ollama Config -->
            <div class="col-12 col-xl-5 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="material-icons me-2">memory</i>Ollama Locale</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('config.ollama.update') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-medium">Endpoint Ollama</label>
                                <input type="url" class="form-control" name="ollama_endpoint"
                                       value="{{ $ollamaEndpoint }}" placeholder="http://localhost:11434" required>
                                <div class="form-text">URL del server Ollama locale (default: http://localhost:11434)</div>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="material-icons me-1">save</i>Salva Configurazione
                            </button>
                        </form>

                        <hr class="my-3">

                        <div class="text-center">
                            <button class="btn btn-outline-primary" id="testOllamaBtn">
                                <i class="material-icons me-1">wifi</i>Test Connessione
                            </button>
                            <div id="ollamaTestResult" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuova API Key -->
<div class="modal fade" id="apiKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('config.api-keys.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nuova API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="name" placeholder="es. Mia OpenAI Key" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Provider</label>
                        <select class="form-select" name="provider" required>
                            <option value="">-- Seleziona --</option>
                            <option value="openai">OpenAI</option>
                            <option value="google">Google (Gemini)</option>
                            <option value="anthropic">Anthropic (Claude)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="password" class="form-control" name="key" placeholder="sk-... o API Key" required autocomplete="off">
                        <div class="form-text">La chiave verrà criptata nel database</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('testOllamaBtn');
    const resultDiv = document.getElementById('ollamaTestResult');
    const endpointInput = document.querySelector('input[name="ollama_endpoint"]');

    testBtn.addEventListener('click', async function() {
        const endpoint = endpointInput.value || 'http://localhost:11434';
        testBtn.disabled = true;
        testBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Test...';
        resultDiv.innerHTML = '';

        try {
            const response = await fetch('{{ route('elaborate.ollama-models') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ endpoint })
            });
            const data = await response.json();
            if (data.models) {
                resultDiv.innerHTML = '<div class="alert alert-success py-2 mb-0">Connesso! ' + data.models.length + ' modelli trovati.</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger py-2 mb-0">Errore: ' + (data.error || 'Sconosciuto') + '</div>';
            }
        } catch (e) {
            resultDiv.innerHTML = '<div class="alert alert-danger py-2 mb-0">Errore connessione: ' + e.message + '</div>';
        } finally {
            testBtn.disabled = false;
            testBtn.innerHTML = '<i class="material-icons me-1">wifi</i>Test Connessione';
        }
    });
});
</script>
@endsection