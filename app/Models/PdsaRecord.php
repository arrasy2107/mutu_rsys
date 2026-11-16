<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdsaRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'siklus', 'jenis_tindak_lanjut', 'indicator_id', 'period_id', 'unit_id', 'user_id',
        'capaian_aktual', 'target_capaian',
        'masalah', 'analisa_penyebab_manusia', 'analisa_penyebab_metode',
        'analisa_penyebab_alat', 'analisa_penyebab_material', 'analisa_penyebab_lingkungan',
        'akar_masalah', 'rekomendasi_tindak_lanjut', 'pj_rtl', 'bukti_rtl',
        'plan', 'do_action', 'study', 'act',
        'rencana_selesai', 'status',
        'reviewed_by', 'reviewed_at', 'catatan_review',
    ];

    protected $casts = [
        'rencana_selesai' => 'date',
        'reviewed_at' => 'datetime',
        'capaian_aktual' => 'decimal:2',
        'target_capaian' => 'decimal:2',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(IndicatorPeriod::class, 'period_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getGapCapaianAttribute(): float
    {
        return abs($this->capaian_aktual - $this->target_capaian);
    }
}
