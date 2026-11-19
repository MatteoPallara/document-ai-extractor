@extends('layouts.app')

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-1">Risultati</h1>
                <p class="text-muted mb-0">Storico delle elaborazioni effettuate</p>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($results->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="material-icons" style="font-size: 4rem; color: #ddd;">assessment</i>
                <h4 class="mt-3 text-muted">Nessun risultato</h4>
                <p class="text-muted">Le elaborazioni completate appariranno qui</p>
                <a href="{{ route('user.elaborate') }}" class="btn btn-primary mt-2">
                    <i class="material-icons me-1">play_arrow</i>Nuova Elaborazione
                </a>
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
                                <th>Documento</th>
                                <th>Vettore</th>
                                <th>Modello</th>
                                <th>Stato</th>
                                <th>Data</th>
                                <th style="width: 200px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                            <tr>
                                <td>
                                    <i class="material-icons {{ $result->document->mime_type === 'application/pdf' ? 'text-danger' : 'text-success' }}">
                                        {{ $result->document->mime_type === 'application/pdf' ? 'picture_as_pdf' : 'image' }}
                                    </i>
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
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('user.results.show', $result) }}" class="btn btn-outline-primary" title="Visualizza">
                                            <i class="material-icons" style="font-size: 1rem;">visibility</i>
                                        </a>
                                        <div class="btn-group">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="material-icons" style="font-size: 1rem;">download</i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="{{ route('user.results.download', ['result' => $result->id, 'format' => 'json']) }}">JSON</a></li>
                                                <li><a class="dropdown-item" href="{{ route('user.results.download', ['result' => $result->id, 'format' => 'csv']) }}">CSV</a></li>
                                                <li><a class="dropdown-item" href="{{ route('user.results.download', ['result' => $result->id, 'format' => 'xml']) }}">XML</a></li>
                                            </ul>
                                        </div>
                                        <form action="{{ route('user.results.destroy', $result) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminare questo risultato?')">
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
                {{ $results->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection