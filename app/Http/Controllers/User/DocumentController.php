<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;

class DocumentController extends Controller
{
    protected array $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
    ];

    protected int $maxSizeKb = 10240; // 10MB

    public function index()
    {
        $documents = Auth::user()->documents()->latest()->paginate(15);
        return view('user.documents', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:' . $this->maxSizeKb . '|mimes:pdf,jpg,jpeg,png',
        ]);

        $file = $request->file('file');
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, $this->allowedMimes)) {
            return back()->withErrors(['file' => 'Tipo file non supportato. Solo PDF, JPG, PNG.']);
        }

        $path = $file->store('documents', 'private');
        $sizeKb = round($file->getSize() / 1024);

        $document = Auth::user()->documents()->create([
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $mimeType,
            'size_in_kb' => $sizeKb,
            'content' => null,
        ]);

        return redirect()->route('user.documents')
            ->with('success', 'Documento caricato con successo');
    }

    public function destroy(Document $document)
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        Storage::disk('private')->delete($document->path);
        $document->delete();

        return redirect()->route('user.documents')
            ->with('success', 'Documento eliminato');
    }

    public function download(Document $document)
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        return Storage::disk('private')->download($document->path, $document->original_name);
    }
}