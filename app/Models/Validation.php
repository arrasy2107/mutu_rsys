<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validation extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_entry_id', 'validator_id', 'actor_id', 'status',
        'catatan_validator', 'tanggal_validasi',
        'unit_numerator', 'unit_denominator', 'unit_capaian', 'unit_data_snapshot',
        'validator_numerator', 'validator_denominator', 'validator_capaian',
        'tingkat_kesesuaian', 'is_valid',
        'jumlah_sampel_validasi', 'justifikasi_perlu_validasi',
        'metode_validasi', 'hasil_validasi', 'hasil_analisa',
        'kesimpulan', 'rencana_tindak_lanjut', 'lampiran_file',
    ];

    protected $casts = [
        'tanggal_validasi' => 'datetime',
        'unit_numerator' => 'float',
        'unit_denominator' => 'float',
        'unit_capaian' => 'float',
        'unit_data_snapshot' => 'array',
        'validator_numerator' => 'float',
        'validator_denominator' => 'float',
        'validator_capaian' => 'float',
        'tingkat_kesesuaian' => 'float',
        'is_valid' => 'boolean',
    ];

    /**
     * Hitung status kepatuhan validasi suatu indikator pada unit dan tahun tertentu
     */
    public static function getIndicatorValidationStatus(int $unitId, int $indicatorId, int $tahun, int $currentBulan): array
    {
        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $yearlyValidations = self::query()
            ->whereHas('dataEntry', function ($query) use ($unitId, $indicatorId, $tahun) {
                $query->where('unit_id', $unitId)
                    ->where('indicator_id', $indicatorId)
                    ->whereHas('period', fn ($period) => $period->where('tahun', $tahun));
            })
            ->with('dataEntry.period')
            ->orderByDesc('tanggal_validasi')
            ->orderByDesc('id')
            ->get();

        $validValidation = $yearlyValidations->first(function (self $validation) use ($yearlyValidations): bool {
            $isValid = $validation->status === 'approved'
                && ($validation->is_valid === true
                    || ($validation->is_valid === null
                        && $validation->tingkat_kesesuaian !== null
                        && $validation->tingkat_kesesuaian >= 90));

            if (! $isValid) {
                return false;
            }

            return ! $yearlyValidations->contains(
                fn (self $candidate): bool => $candidate->data_entry_id === $validation->data_entry_id
                    && $candidate->status === 'revision'
                    && $candidate->id > $validation->id
            );
        });

        if ($validValidation && $validValidation->dataEntry?->period) {
            $passedMonth = (int) $validValidation->dataEntry->period->bulan;

            return [
                'is_exempt' => true,
                'passed_month' => $passedMonth,
                'passed_month_name' => $bulanNames[$passedMonth] ?? "Bulan {$passedMonth}",
                'passed_validation_id' => $validValidation->id,
                'is_mandatory_retry' => false,
                'previous_failed_month' => null,
                'previous_failed_month_name' => null,
                'message' => "Profil indikator telah dinyatakan VALID pada {$bulanNames[$passedMonth]} {$tahun}. Tidak perlu mengajukan validasi lagi untuk profil ini pada tahun tersebut.",
            ];
        }

        $failedValidation = $yearlyValidations->first(fn (self $validation): bool => $validation->status === 'rejected'
            || $validation->status === 'revision'
            || ($validation->status === 'approved' && (
                $validation->is_valid === false
                || ($validation->is_valid === null
                    && $validation->tingkat_kesesuaian !== null
                    && $validation->tingkat_kesesuaian < 90)
            )));
        $failedMonth = $failedValidation?->dataEntry?->period?->bulan
            ? (int) $failedValidation->dataEntry->period->bulan
            : null;
        $failedMonthName = $failedMonth ? ($bulanNames[$failedMonth] ?? "Bulan {$failedMonth}") : null;
        $hasFailedValidation = $failedValidation !== null;

        return [
            'is_exempt' => false,
            'passed_month' => null,
            'passed_month_name' => null,
            'passed_validation_id' => null,
            'is_mandatory_retry' => $hasFailedValidation,
            'previous_failed_month' => $failedMonth,
            'previous_failed_month_name' => $failedMonthName,
            'message' => $hasFailedValidation
                ? "Validasi profil sebelumnya pada {$failedMonthName} {$tahun} dinyatakan TIDAK VALID. Unit dapat memilih bulan mana pun yang datanya siap untuk mengajukan validasi ulang."
                : "Pilih satu bulan dengan data siap untuk validasi profil indikator tahun {$tahun}.",
        ];
    }

    public function dataEntry(): BelongsTo
    {
        return $this->belongsTo(DataEntry::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
