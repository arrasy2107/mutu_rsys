<?php

namespace App\Http\Controllers;

use App\Models\CustomFormFormat;
use App\Models\Indicator;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomFormFormatController extends Controller
{
    /**
     * Simpan / Perbarui Konfigurasi Format Form Kustom
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isDireksi()) {
            return redirect()->back()->with('error', 'Akses ditolak: Direksi hanya memiliki hak peninjauan (Read-Only).');
        }

        $validated = $request->validate([
            'id' => 'nullable|exists:custom_form_formats,id',
            'indicator_id' => 'required|exists:indicators,id',
            'unit_id' => 'nullable|exists:units,id',
            'nama_format' => 'required|string|max:255',
            'table_title' => 'nullable|string|max:255',
            'row_type' => 'required|in:daily_grid,fixed_rows,dynamic_rows,entity_rows,monthly_summary',
            'schema' => 'required|array',
            'schema.columns' => 'required|array|min:1',
            'schema.renderer' => 'nullable|in:classic,spreadsheet',
            'schema.engine' => 'nullable|in:univer,tabulator',
            'schema.header_rows' => 'nullable|integer|min:1|max:10',
            'schema.schema_version' => 'nullable|integer|min:1',
            'schema.rows' => 'nullable|array',
            'schema.formula_config' => 'nullable|array',
            'schema.workbook_template' => 'nullable|array',
            'petunjuk_pengisian' => 'nullable|string',
        ], [
            'nama_format.required' => 'Nama format form wajib diisi.',
            'indicator_id.required' => 'Pilih Profil Indikator Mutu yang berkaitan.',
            'schema.columns.required' => 'Minimal harus ada 1 kolom pada tabel.',
        ]);

        if (($validated['schema']['renderer'] ?? null) === 'spreadsheet') {
            foreach ($validated['schema']['columns'] as $column) {
                if (
                    is_array($column) &&
                    in_array($column['role'] ?? null, ['numerator', 'denominator'], true) &&
                    ! in_array($column['type'] ?? null, ['number', 'decimal', 'checkbox'], true)
                ) {
                    throw ValidationException::withMessages([
                        'schema' => 'Kolom Numerator dan Denominator harus bertipe angka atau checkbox.',
                    ]);
                }
            }
        }

        $indicator = Indicator::findOrFail($validated['indicator_id']);
        if (
            in_array($user->role, ['kepala_unit', 'pic_unit'], true) &&
            $indicator->unit_id === null
        ) {
            return redirect()->back()->with(
                'error',
                'Format indikator lintas unit hanya dapat diatur oleh Administrator atau Komite Mutu.'
            );
        }
        $existingFormat = CustomFormFormat::where('indicator_id', $indicator->id)->first();
        $validated['table_title'] = trim((string) ($validated['table_title'] ?? ''))
            ?: $existingFormat?->table_title
            ?: "Tabel Pengumpulan Data: {$indicator->judul}";
        $currentSchema = $indicator->form_schema;
        if (! is_array($currentSchema) || $currentSchema === []) {
            $currentSchema = $existingFormat?->schema ?? [];
        }
        $schemaVersion = max(1, (int) ($currentSchema['schema_version'] ?? 1));
        $schemaHistory = $currentSchema['schema_history'] ?? [];

        if ($existingFormat) {
            $storedFormatSchema = $existingFormat->schema ?? [];
            $previousSchema = [
                'nama_format' => $existingFormat->nama_format,
                'table_title' => $existingFormat->table_title,
                'row_type' => $existingFormat->row_type,
                'renderer' => $currentSchema['renderer'] ?? $storedFormatSchema['renderer'] ?? 'classic',
                'engine' => $currentSchema['engine'] ?? $storedFormatSchema['engine'] ?? null,
                'header_rows' => $currentSchema['header_rows'] ?? $storedFormatSchema['header_rows'] ?? null,
                'columns' => $currentSchema['columns'] ?? $storedFormatSchema['columns'] ?? [],
                'rows' => $currentSchema['rows'] ?? $storedFormatSchema['rows'] ?? [],
                'formula_config' => $currentSchema['formula_config'] ?? $storedFormatSchema['formula_config'] ?? [],
                'workbook_template' => $currentSchema['workbook_template'] ?? $storedFormatSchema['workbook_template'] ?? null,
                'petunjuk_pengisian' => $currentSchema['petunjuk_pengisian'] ?? $existingFormat->petunjuk_pengisian,
            ];
            $nextSchema = [
                'nama_format' => $validated['nama_format'],
                'table_title' => $validated['table_title'],
                'row_type' => $validated['row_type'],
                'renderer' => $validated['schema']['renderer'] ?? 'classic',
                'engine' => $validated['schema']['engine'] ?? null,
                'header_rows' => $validated['schema']['header_rows'] ?? null,
                'columns' => $validated['schema']['columns'],
                'rows' => $validated['schema']['rows'] ?? [],
                'formula_config' => $validated['schema']['formula_config'] ?? [],
                'workbook_template' => $validated['schema']['workbook_template'] ?? null,
                'petunjuk_pengisian' => $validated['petunjuk_pengisian'] ?? null,
            ];

            if ($previousSchema !== $nextSchema) {
                $schemaHistory[(string) $schemaVersion] = [
                    ...$previousSchema,
                    'schema_version' => $schemaVersion,
                ];
                $schemaVersion++;
            }
        }

        $validated['schema']['schema_version'] = $schemaVersion;
        $validated['schema']['schema_history'] = $schemaHistory;

        // Verifikasi kepemilikan unit jika user adalah staf unit
        if (in_array($user->role, ['kepala_unit', 'pic_unit'])) {
            if ($indicator->unit_id && $indicator->unit_id !== $user->unit_id) {
                return redirect()->back()->with('error', 'Akses ditolak: Anda hanya dapat mengatur format form untuk unit Anda sendiri.');
            }
            $validated['unit_id'] = $user->unit_id;
        } else {
            $validated['unit_id'] = $indicator->unit_id === null
                ? null
                : ($validated['unit_id'] ?: $indicator->unit_id);
        }

        $validated['created_by'] = $user->id;
        $validated['is_active'] = true;

        $format = DB::transaction(function () use ($indicator, $validated, $schemaVersion, $schemaHistory): CustomFormFormat {
            $format = CustomFormFormat::updateOrCreate(
                ['indicator_id' => $indicator->id],
                $validated
            );

            $indicator->update([
                'form_layout_type' => $validated['row_type'],
                'form_schema' => [
                    'format_id' => $format->id,
                    'nama_format' => $validated['nama_format'],
                    'table_title' => $validated['table_title'],
                    'row_type' => $validated['row_type'],
                    'renderer' => $validated['schema']['renderer'] ?? 'classic',
                    'schema_version' => $schemaVersion,
                    'schema_history' => $schemaHistory,
                    'engine' => $validated['schema']['engine'] ?? null,
                    'header_rows' => $validated['schema']['header_rows'] ?? 2,
                    'columns' => $validated['schema']['columns'] ?? [],
                    'rows' => $validated['schema']['rows'] ?? [],
                    'formula_config' => $validated['schema']['formula_config'] ?? [],
                    'workbook_template' => $validated['schema']['workbook_template'] ?? null,
                    'petunjuk_pengisian' => $validated['petunjuk_pengisian'] ?? null,
                ],
            ]);

            return $format;
        });

        return redirect()->back()->with('success', "Format form '{$validated['nama_format']}' berhasil disimpan untuk indikator {$indicator->kode}.");
    }

    /**
     * Hapus Format Kustom dan Kembalikan ke Grid Kalender Standar
     */
    public function destroy(Request $request, CustomFormFormat $customFormFormat): RedirectResponse
    {
        $user = $request->user();

        if ($user->isDireksi()) {
            return redirect()->back()->with('error', 'Akses ditolak: Direksi hanya memiliki hak peninjauan (Read-Only).');
        }

        if (in_array($user->role, ['kepala_unit', 'pic_unit']) && $customFormFormat->unit_id !== $user->unit_id) {
            return redirect()->back()->with('error', 'Akses ditolak: Anda hanya dapat menghapus format milik unit Anda.');
        }

        $indicator = $customFormFormat->indicator;
        if (
            in_array($user->role, ['kepala_unit', 'pic_unit'], true) &&
            ($indicator?->unit_id === null || $customFormFormat->unit_id !== $user->unit_id)
        ) {
            return redirect()->back()->with('error', 'Akses ditolak: format indikator lintas unit hanya dapat dihapus oleh Administrator atau Komite Mutu.');
        }

        if ($indicator?->dataEntries()->where('form_schema_version', '>', 0)->exists()) {
            return redirect()->back()->with(
                'error',
                'Format tidak dapat dihapus karena sudah digunakan oleh data periode. Pertahankan format agar riwayat data tetap dapat dibuka.'
            );
        }

        if ($indicator) {
            DB::transaction(function () use ($indicator, $customFormFormat): void {
                $indicator->update([
                    'form_layout_type' => 'daily_grid',
                    'form_schema' => null,
                ]);
                $customFormFormat->delete();
            });
        } else {
            $customFormFormat->delete();
        }

        $nama = $customFormFormat->nama_format;

        return redirect()->back()->with('success', "Format form '{$nama}' berhasil dihapus. Indikator dikembalikan ke format kalender standar.");
    }

    /**
     * Ambil Skema Format dalam Format JSON
     */
    public function getSchema(Indicator $indicator): JsonResponse
    {
        $format = CustomFormFormat::where('indicator_id', $indicator->id)->first();

        return response()->json([
            'format' => $format,
            'indicator' => [
                'id' => $indicator->id,
                'kode' => $indicator->kode,
                'judul' => $indicator->judul,
                'unit_id' => $indicator->unit_id,
                'numerator_keterangan' => $indicator->numerator_keterangan,
                'denominator_keterangan' => $indicator->denominator_keterangan,
                'form_layout_type' => $indicator->form_layout_type,
            ],
        ]);
    }
}
