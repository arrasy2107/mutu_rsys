<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HvaAssessment extends Model
{
    use HasFactory;

    protected $table = 'hva_assessments';

    protected $fillable = [
        'tahun',
        'ruang_lingkup',
        'unit_id',
        'kategori',
        'nama_kejadian',
        'urutan',
        'probabilitas',
        'dampak_manusia',
        'dampak_properti',
        'dampak_bisnis',
        'kesiapan',
        'respon_internal',
        'respon_eksternal',
        'skor_keparahan',
        'skor_risiko_relatif',
        'peringkat_prioritas',
        'rencana_tindak_lanjut',
        'pj_mitigasi',
        'target_waktu',
        'status_mitigasi',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'urutan' => 'integer',
        'probabilitas' => 'integer',
        'dampak_manusia' => 'integer',
        'dampak_properti' => 'integer',
        'dampak_bisnis' => 'integer',
        'kesiapan' => 'integer',
        'respon_internal' => 'integer',
        'respon_eksternal' => 'integer',
        'skor_keparahan' => 'integer',
        'skor_risiko_relatif' => 'float',
        'peringkat_prioritas' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Hitung skor keparahan dan skor risiko relatif berdasarkan rumus baku Kaiser Permanente
     */
    public function computeScores(): void
    {
        $severity = (int)$this->dampak_manusia
            + (int)$this->dampak_properti
            + (int)$this->dampak_bisnis
            + (int)$this->kesiapan
            + (int)$this->respon_internal
            + (int)$this->respon_eksternal;

        $this->skor_keparahan = $severity;

        $p = (int)$this->probabilitas;
        if ($p > 0) {
            $this->skor_risiko_relatif = round(($p / 3) * ($severity / 18) * 100, 2);
        } else {
            $this->skor_risiko_relatif = 0.00;
        }
    }

    public function scopeTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    public function scopeRuangLingkup($query, $scope)
    {
        return $query->where('ruang_lingkup', $scope);
    }

    public function scopeKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }
}
