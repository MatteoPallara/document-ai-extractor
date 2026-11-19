@extends('layouts.app')

@section('style')
<style>
    .json-viewer {
        background: #1e1e1e;
        color: #d4d4d4;
        border-radius: 8px;
        padding: 1.5rem;
        font-family: 'Monospace', monospace;
        font-size: 0.85rem;
        line-height: 1.6;
        max-height: 600px;
        overflow: auto;
        white-space: pre-wrap;
    }
    .json-key { color: #9cdcfe; }
    .json-string { color: #ce9178; }
    .json-number { color: #b5cea8; }
    .json-boolean { color: #569cd6; }
    .json-null { color: #808080; }
</style>
@endsection

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h1 class="h3 mb-1">Dettaglio Risultato</h1>
                        <p class="text-muted mb-0">{{ $result->document->original_name }} → {{ $result->vector->name }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.results.download', ['result' => $result->id, 'format' => 'json']) }}" class="btn btn-outline-primary">
                            <i class="material-icons me-1">download</i>JSON
                        </a>
                        <a href="{{ route('user.results.download', ['result' => $result->id, 'format' => 'csv']) }}" class="btn btn-outline-info">
                            <i class="material-icons me-1">download</i>CSV
                        </a>
                        <a href="{{ route('user.results.download', ['result' => $result->id, 'format' => 'xml']) }}" class="btn btn-outline-secondary">
                            <i class="material-icons me-1">download</i>XML
                        </a>
                        <a href="{{ route('user.results') }}" class="btn btn-outline-dark">
                            <i class="material-icons me-1">arrow_back</i>Indietro
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="row mb-4">
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header">Informazioni Documento</div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr><th style="width: 150px;">File</th><td>{{ $result->document->original_name }}</td></tr>
                            <tr><th>Tipo</th><td>{{ $result->document->mime_type }}</td></tr>
                            <tr><th>Dimensione</th><td>{{ $result->document->size_in_kb >= 1024 ? number_format($result->document->size_in_kb / 1024, 1) . ' MB' : $result->document->size_in_kb . ' KB' }}</td></tr>
                            <tr><th>Caricato</th><td>{{ $result->document->created_at->format('d/m/Y H:i') }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header">Informazioni Elaborazione</div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr><th style="width: 150px;">Vettore</th><td>{{ $result->vector->name }} ({{ strtoupper($result->vector->format) }})</td></tr>
                            <tr><th>Modello AI</th><td>{{ $result->ai_model }}</td></tr>
                            <tr><th>Stato</th><td><span class="status-badge status-{{ $result->status }}">{{ ucfirst(str_replace('_', ' ', $result->status)) }}</span></td></tr>
                            <tr><th>Data</th><td>{{ $result->created_at->format('d/m/Y H:i:s') }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dati Estratti -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="material-icons me-2">code</i>Dati Estratti (JSON)</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="copyJson()">
                    <i class="material-icons me-1">content_copy</i>Copia
                </button>
            </div>
            <div class="card-body p-0">
                <pre id="jsonViewer" class="json-viewer mb-0">{{ json_encode($result->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>

        @if($result->status === 'failed')
        <div class="card mt-3 border-danger">
            <div class="card-header bg-danger text-white">Errore</div>
            <div class="card-body">
                <pre class="text-danger mb-0">{{ $result->data['error'] ?? 'Errore sconosciuto' }}</pre>
            </div>
        </div>
        @endif
    </div>
</div>

@section('script')
<script>
// Syntax highlighting semplice per JSON
document.addEventListener('DOMContentLoaded', function() {
    const viewer = document.getElementById('jsonViewer');
    if (!viewer) return;

    const json = viewer.textContent;
    try {
        const obj = JSON.parse(json);
        viewer.innerHTML = syntaxHighlight(obj);
    } catch (e) {
        // mantiene testo originale
    }
});

function syntaxHighlight(obj) {
    const json = JSON.stringify(obj, null, 2);
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function(match) {
        let cls = 'json-number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'json-key';
            } else {
                cls = 'json-string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'json-boolean';
        } else if (/null/.test(match)) {
            cls = 'json-null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

function copyJson() {
    const viewer = document.getElementById('jsonViewer');
    navigator.clipboard.writeText(viewer.textContent).then(() => {
        const btn = event.target.closest('button');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="material-icons me-1">check</i>Copiato!';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-success');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    });
}
</script>
@endsection