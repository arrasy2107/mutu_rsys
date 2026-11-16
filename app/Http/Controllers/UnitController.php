<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $units = Unit::withCount('users')
            ->when($request->search, fn($q, $s) =>
                $q->where('nama', 'like', "%{$s}%")->orWhere('kode', 'like', "%{$s}%")
            )
            ->orderBy('kode')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Units/Index', [
            'units'   => $units,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Units/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode'      => 'required|string|max:20|unique:units',
            'nama'      => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'aktif'     => 'boolean',
        ]);

        Unit::create($validated);

        return redirect()->route('units.index')
            ->with('success', 'Unit berhasil ditambahkan.');
    }

    public function show(Unit $unit): Response
    {
        $unit->load('users');
        return Inertia::render('Units/Show', ['unit' => $unit]);
    }

    public function edit(Unit $unit): Response
    {
        return Inertia::render('Units/Edit', ['unit' => $unit]);
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'kode'      => 'required|string|max:20|unique:units,kode,' . $unit->id,
            'nama'      => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'aktif'     => 'boolean',
        ]);

        $unit->update($validated);

        return redirect()->route('units.index')
            ->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return redirect()->route('units.index')
            ->with('success', 'Unit berhasil dihapus.');
    }
}
