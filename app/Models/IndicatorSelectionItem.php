<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorSelectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'selection_id',
        'usulan_imut',
        'data_pendukung',
        'ssi_visi_misi',
        'problem_prone',
        'high_volume',
        'high_risk_cost',
        'ssi_aturan',
        'dpt_dikendalikan_unit',
        'tdk_dpt_dikendalikan_rs',
        'dpt_diukur',
        'is_lolos_skrining',
        'skor_masalah_banyak',
        'skor_high_volume',
        'skor_high_proses',
        'skor_ketidakpuasan',
        'skor_kemudahan_ukur',
        'skor_ketentuan_pemerintah',
        'skor_sesuai_renstra',
        'skor_patient_experience',
        'total_skor',
        'is_ditetapkan',
        'is_imp',
        'indicator_id',
    ];

    protected $casts = [
        'ssi_visi_misi' => 'boolean',
        'problem_prone' => 'boolean',
        'high_volume' => 'boolean',
        'high_risk_cost' => 'boolean',
        'ssi_aturan' => 'boolean',
        'dpt_dikendalikan_unit' => 'boolean',
        'tdk_dpt_dikendalikan_rs' => 'boolean',
        'dpt_diukur' => 'boolean',
        'is_lolos_skrining' => 'boolean',
        'is_ditetapkan' => 'boolean',
        'is_imp' => 'boolean',
        'skor_masalah_banyak' => 'integer',
        'skor_high_volume' => 'integer',
        'skor_high_proses' => 'integer',
        'skor_ketidakpuasan' => 'integer',
        'skor_kemudahan_ukur' => 'integer',
        'skor_ketentuan_pemerintah' => 'integer',
        'skor_sesuai_renstra' => 'integer',
        'skor_patient_experience' => 'integer',
        'total_skor' => 'integer',
    ];

    public function selection(): BelongsTo
    {
        return $this->belongsTo(IndicatorSelection::class, 'selection_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class, 'indicator_id');
    }

    /**
     * Helper to recalculate total score
     */
    public function hitungTotalSkor(): int
    {
        return $this->skor_masalah_banyak +
            $this->skor_high_volume +
            $this->skor_high_proses +
            $this->skor_ketidakpuasan +
            $this->skor_kemudahan_ukur +
            $this->skor_ketentuan_pemerintah +
            $this->skor_sesuai_renstra +
            $this->skor_patient_experience;
    }
}
