<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\PdsaRecord;
use App\Models\Unit;
use App\Services\PrivateEvidenceStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitRtlController extends Controller
{
    /**
     * Display a listing of unit RTL (Rencana Tindak Lanjut).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tahun = (int) $request->input('tahun', 2026);
        $unitId = $request->input('unit_id');
        $status = $request->input('status');

        $query = PdsaRecord::query()
            ->with(['indicator', 'unit', 'user', 'period', 'reviewedBy'])
            ->whereNotNull('rekomendasi_tindak_lanjut')
            ->when(! $user->isSuperAdmin() && ! $user->isKomiteMutu(), fn ($q) => $q->where('unit_id', $user->unit_id)
            )
            ->when($unitId, fn ($q, $u) => $q->where('unit_id', $u))
            ->when($tahun, fn ($q, $y) => $q->whereHas('period', fn ($p) => $p->where('tahun', $y)))
            ->when($status, fn ($q, $s) => $q->where('status', $s));

        $records = $query->latest()->paginate(25)->withQueryString();

        // Calculate RTL Summary Stats
        $allRtl = (clone $query)->get();
        $stats = [
            'total' => $allRtl->count(),
            'selesai' => $allRtl->where('status', 'reviewed')->count(),
            'berjalan' => $allRtl->whereIn('status', ['submitted', 'in_progress'])->count(),
            'draft' => $allRtl->where('status', 'draft')->count(),
            'ada_bukti' => $allRtl->filter(fn ($r) => ! empty($r->bukti_rtl))->count(),
        ];

        return Inertia::render('Unit/Rtl/Index', [
            'records' => $records,
            'stats' => $stats,
            'units' => Unit::aktif()->get(['id', 'nama', 'kode']),
            'filters' => [
                'unit_id' => $unitId ? (int) $unitId : '',
                'tahun' => $tahun,
                'status' => $status ?? '',
            ],
        ]);
    }

    /**
     * Upload proof file for RTL (PDF, Image).
     */
    public function uploadBukti(
        Request $request,
        PdsaRecord $pdsa,
        PrivateEvidenceStorage $evidenceStorage,
    ): RedirectResponse {
        $user = $request->user();
        if (! $user->isSuperAdmin() && ! $user->isKomiteMutu() && $pdsa->unit_id !== $user->unit_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunggah bukti RTL unit ini.');
        }

        $request->validate([
            'berkas' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx|max:10240', // Max 10MB
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('berkas')) {
            $path = $evidenceStorage->store($request->file('berkas'), 'bukti_rtl');
            $previousFilePath = $pdsa->bukti_rtl;
            $pdsa->update([
                'bukti_rtl' => $path,
            ]);
            if ($previousFilePath) {
                $evidenceStorage->delete($previousFilePath);
            }
        }

        return back()->with('success', 'Berkas bukti RTL berhasil diunggah.');
    }

    /**
     * Update status RTL.
     */
    public function updateStatus(Request $request, PdsaRecord $pdsa)
    {
        $request->validate([
            'status' => 'required|in:draft,submitted,in_progress,reviewed',
            'catatan' => 'nullable|string',
        ]);

        $pdsa->update([
            'status' => $request->status,
            'catatan_review' => $request->catatan ?? $pdsa->catatan_review,
        ]);

        return back()->with('success', 'Status RTL berhasil diperbarui.');
    }
}
