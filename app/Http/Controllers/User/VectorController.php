<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Vector;

class VectorController extends Controller
{
    public function index()
    {
        $vectors = Auth::user()->vectors()->latest()->get();
        return view('user.vectors', compact('vectors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'format' => 'required|in:json,csv,xml',
            'fields' => 'required|array|min:1',
            'fields.*.key' => 'required|string|max:100|distinct',
            'fields.*.type' => 'required|in:string,integer,float,date,boolean',
        ]);

        $schema = [];
        foreach ($request->fields as $field) {
            $schema[$field['key']] = $field['type'];
        }

        Auth::user()->vectors()->create([
            'name' => $request->name,
            'format' => $request->format,
            'data' => $schema,
        ]);

        return redirect()->route('user.vectors')
            ->with('success', 'Vettore creato con successo');
    }

    public function edit(Vector $vector)
    {
        if ($vector->user_id !== Auth::id()) {
            abort(403);
        }

        return view('user.vectors.edit', compact('vector'));
    }

    public function update(Request $request, Vector $vector)
    {
        if ($vector->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'format' => 'required|in:json,csv,xml',
            'fields' => 'required|array|min:1',
            'fields.*.key' => 'required|string|max:100|distinct',
            'fields.*.type' => 'required|in:string,integer,float,date,boolean',
        ]);

        $schema = [];
        foreach ($request->fields as $field) {
            $schema[$field['key']] = $field['type'];
        }

        $vector->update([
            'name' => $request->name,
            'format' => $request->format,
            'data' => $schema,
        ]);

        return redirect()->route('user.vectors')
            ->with('success', 'Vettore aggiornato');
    }

    public function destroy(Vector $vector)
    {
        if ($vector->user_id !== Auth::id()) {
            abort(403);
        }

        $vector->delete();

        return redirect()->route('user.vectors')
            ->with('success', 'Vettore eliminato');
    }
}