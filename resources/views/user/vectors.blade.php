@extends('layouts.app')

@section('style')
<style>
    .field-row {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        align-items: center;
    }
    .field-row input, .field-row select {
        flex: 1;
    }
    .field-row .btn {
        flex-shrink: 0;
    }
</style>
@endsection

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">Vettori</h1>
                        <p class="text-muted mb-0">Definisci gli schemi di estrazione dati per i tuoi documenti</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vectorModal">
                        <i class="material-icons me-1">add</i>Nuovo Vettore
                    </button>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($vectors->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="material-icons" style="font-size: 4rem; color: #ddd;">schema</i>
                <h4 class="mt-3 text-muted">Nessun vettore definito</h4>
                <p class="text-muted">Crea il tuo primo vettore per definire i campi da estrarre</p>
                <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#vectorModal">
                    <i class="material-icons me-1">add</i>Crea Vettore
                </button>
            </div>
        </div>
        @else
        <div class="row g-4">
            @foreach($vectors as $vector)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $vector->name }}</h5>
                        <span class="badge bg-primary">{{ strtoupper($vector->format) }}</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Campi ({{ count($vector->data) }})</small>
                            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto; background: #fafafa;">
                                @foreach($vector->data as $key => $type)
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                    <span class="fw-medium">{{ $key }}</span>
                                    <span class="badge bg-secondary">{{ $type }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.vectors.edit', $vector) }}" class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="material-icons me-1">edit</i>Modifica
                            </a>
                            <form action="{{ route('user.vectors.destroy', $vector) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminare questo vettore?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm flex-fill" title="Elimina">
                                    <i class="material-icons me-1">delete</i>Elimina
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<!-- Modal Crea/Modifica Vettore -->
<div class="modal fade" id="vectorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="vectorForm" method="POST">
                @csrf
                <input type="hidden" name="vector_id" id="vector_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuovo Vettore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome Vettore</label>
                        <input type="text" class="form-control" name="name" id="vectorName" placeholder="es. Fatture, Ricette, Contratti" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Formato Default</label>
                        <select class="form-select" name="format" id="vectorFormat" required>
                            <option value="json">JSON</option>
                            <option value="csv">CSV (separatore ;)</option>
                            <option value="xml">XML</option>
                        </select>
                    </div>
                    <hr>
                    <label class="form-label fw-medium">Campi del Vettore <span class="text-muted small">(minimo 1)</span></label>
                    <div id="fieldsContainer">
                        <!-- Campi dinamici qui -->
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addFieldBtn">
                        <i class="material-icons me-1">add</i>Aggiungi Campo
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary" id="saveVectorBtn">
                        <i class="material-icons me-1">save</i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('vectorModal');
    const form = document.getElementById('vectorForm');
    const fieldsContainer = document.getElementById('fieldsContainer');
    const addFieldBtn = document.getElementById('addFieldBtn');
    const modalTitle = document.getElementById('modalTitle');
    const saveBtn = document.getElementById('saveVectorBtn');

    let fieldCounter = 0;

    // Tipi di dato disponibili
    const fieldTypes = [
        { value: 'string', label: 'Testo (string)' },
        { value: 'integer', label: 'Numero Intero (integer)' },
        { value: 'float', label: 'Decimale (float)' },
        { value: 'date', label: 'Data (date YYYY-MM-DD)' },
        { value: 'boolean', label: 'Booleano (true/false)' },
    ];

    function createFieldRow(key = '', type = 'string') {
        fieldCounter++;
        const div = document.createElement('div');
        div.className = 'field-row';
        div.innerHTML = `
            <input type="text" class="form-control" name="fields[${fieldCounter}][key]" placeholder="Nome campo (es. Totale)" value="${key}" required>
            <select class="form-select" name="fields[${fieldCounter}][type]" style="max-width: 200px;" required>
                ${fieldTypes.map(t => `<option value="${t.value}" ${t.value === type ? 'selected' : ''}>${t.label}</option>`).join('')}
            </select>
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.field-row').remove(); updateFieldIndexes()">
                <i class="material-icons" style="font-size: 1rem;">delete</i>
            </button>
        `;
        return div;
    }

    function updateFieldIndexes() {
        const rows = fieldsContainer.querySelectorAll('.field-row');
        rows.forEach((row, idx) => {
            const input = row.querySelector('input[name^="fields"]');
            const select = row.querySelector('select[name^="fields"]');
            if (input) input.name = `fields[${idx}][key]`;
            if (select) select.name = `fields[${idx}][type]`;
        });
    }

    // Aggiungi campo
    addFieldBtn.addEventListener('click', function() {
        fieldsContainer.appendChild(createFieldRow());
    });

    // Reset modal all'apertura
    modal.addEventListener('show.bs.modal', function(e) {
        const button = e.relatedTarget;
        if (button && button.dataset.vector) {
            // Modalità modifica
            const vector = JSON.parse(button.dataset.vector);
            modalTitle.textContent = 'Modifica Vettore';
            saveBtn.innerHTML = '<i class="material-icons me-1">save</i>Aggiorna';
            document.getElementById('vector_id').value = vector.id;
            document.getElementById('vectorName').value = vector.name;
            document.getElementById('vectorFormat').value = vector.format;

            fieldsContainer.innerHTML = '';
            Object.entries(vector.data).forEach(([key, type]) => {
                fieldsContainer.appendChild(createFieldRow(key, type));
            });
            form.action = '{{ route('user.vectors.update', ':id') }}'.replace(':id', vector.id);
            form.querySelector('input[name="_method"]').value = 'PUT';
        } else {
            // Modalità creazione
            modalTitle.textContent = 'Nuovo Vettore';
            saveBtn.innerHTML = '<i class="material-icons me-1">save</i>Salva';
            document.getElementById('vector_id').value = '';
            document.getElementById('vectorName').value = '';
            document.getElementById('vectorFormat').value = 'json';
            fieldsContainer.innerHTML = '';
            fieldsContainer.appendChild(createFieldRow());
            form.action = '{{ route('user.vectors.store') }}';
            form.querySelector('input[name="_method"]').value = '';
        }
    });

    // Aggiungi un campo all'apertura iniziale
    modal.addEventListener('shown.bs.modal', function() {
        if (fieldsContainer.children.length === 0) {
            fieldsContainer.appendChild(createFieldRow());
        }
    });
});
</script>
@endsection