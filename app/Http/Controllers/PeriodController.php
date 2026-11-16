<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndicatorPeriod;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodController extends Controller
{
    public function index(Request $request): Response
    {
        $tahun = $request->integer('tahun', now()->year);

        $indicators = Indicator::aktif()
            ->with(['unit'])
            ->get();

        // Ambil semua periode tahun ini
        $periods = IndicatorPeriod::whereYear('created_at', $tahun)
            ->orWhere('tahun', $tahun)
            ->with(['indicator', 'unit'])
            ->get()
            ->groupBy('indicator_id');

        $units = Unit::aktif()->get();

        return Inertia::render('Periods/Index', [
            'indicators' => $indicators,
            'periods'    => $periods,
            'units'      => $units,
            'tahun'      => $tahun,
        ]);
    }

    public function openPeriod(Request $request)
    {
        $validated = $request->validate([
            'indicator_id' => 'required|exists:indicators,id',
            'unit_id'      => 'required|exists:units,id',
            'tahun'        => 'required|integer|min:2020|max:2099',
            'bulan'        => 'required|integer|min:1|max:12',
            'deadline_input'=> 'nullable|date',
        ]);

        IndicatorPeriod::updateOrCreate(
            [
                'indicator_id' => $validated['indicator_id'],
                'unit_id'      => $validated['unit_id'],
                'tahun'        => $validated['tahun'],
                'bulan'        => $validated['bulan'],
            ],
            [
                'status'         => 'open',
                'deadline_input' => $validated['deadline_input'] ?? null,
            ]
        );

        return back()->with('success', 'Periode berhasil dibuka.');
    }

    public function closePeriod(Request $request, IndicatorPeriod $period)
    {
        $period->update(['status' => 'closed']);
        return back()->with('success', 'Periode berhasil ditutup.');
    }

    public function bulkOpen(Request $request)
    {
        $validated = $request->validate([
            'tahun'          => 'required|integer',
            'bulan'          => 'required|integer|min:1|max:12',
            'deadline_input' => 'nullable|date',
        ]);

        $indicators = Indicator::aktif()->get();
        $units = Unit::aktif()->get();

        foreach ($indicators as $indicator) {
            $targetUnits = $indicator->unit_id
                ? $units->where('id', $indicator->unit_id)
                : $units;

            foreach ($targetUnits as $unit) {
                IndicatorPeriod::updateOrCreate(
                    [
                        'indicator_id' => $indicator->id,
                        'unit_id'      => $unit->id,
                        'tahun'        => $validated['tahun'],
                        'bulan'        => $validated['bulan'],
                    ],
                    [
                        'status'         => 'open',
                        'deadline_input' => $validated['deadline_input'] ?? null,
                    ]
                );
            }
        }

        return back()->with('success', 'Semua periode berhasil dibuka untuk bulan yang dipilih.');
    }
}
