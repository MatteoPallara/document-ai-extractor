@extends('layouts.app')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">Documenti</h1>
                        <p class="text-muted mb-0">Carica e gestisci i tuoi documenti per l'elaborazione OCR</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="material-icons me-1">cloud_upload</i>Carica Documento
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

        @if($documents->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="material-icons" style="font-size: 4rem; color: #ddd;">description</i>
                <h4 class="mt-3 text-muted">Nessun documento caricato</h4>
                <p class="text-muted">Carica il tuo primo documento PDF o immagine per iniziare</p>
                <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="material-icons me-1">cloud_upload</i>Carica Documento
                </button>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;"></th>
                                <th>Nome File</th>
                                <th>Tipo</th>
                                <th>Dimensione</th>
                                <th>OCR Cache</th>
                                <th>Caricato</th>
                                <th style="width: 120px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $doc)
                            <tr>
                                <td>
                                    <i class="material-icons {{ $doc->mime_type === 'application/pdf' ? 'text-danger' : 'text-success' }}">
                                        {{ $doc->mime_type === 'application/pdf' ? 'picture_as_pdf' : 'image' }}
                                    </i>
                                </td>
                                <td>
                                    <strong>{{ $doc->original_name }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $doc->mime_type }}</span>
                                </td>
                                <td>{{ $doc->size_in_kb >= 1024 ? number_format($doc->size_in_kb / 1024, 1) . ' MB' : $doc->size_in_kb . ' KB' }}</td>
                                <td>
                                    @if($doc->content && $doc->content !== '')
                                    <span class="badge bg-success">In cache</span>
                                    @else
                                    <span class="badge bg-warning text-dark">Non processato</span>
                                    @endif
                                </td>
                                <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('user.documents.download', $doc) }}" class="btn btn-outline-primary" title="Scarica">
                                            <i class="material-icons" style="font-size: 1rem;">download</i>
                                        </a>
                                        <form action="{{ route('user.documents.destroy', $doc) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminare questo documento?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Elimina">
                                                <i class="material-icons" style="font-size: 1rem;">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginazione -->
                {{ $documents->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('user.documents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Carica Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" class="form-control" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                        <div class="form-text">Formati supportati: PDF, JPG, PNG (max 10MB)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons me-1">cloud_upload</i>Carica
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection