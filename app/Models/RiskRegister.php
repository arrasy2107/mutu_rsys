<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskRegister extends Model
{
    use HasFactory;

    protected $table = 'risk_registers';

    protected $fillable = [
        'nomor_register',
        'nomor_urut',
        'kode_risiko',
        'tahun',
        'unit_id',
        'ruang_lingkup',
        'is_rs_priority',
        'kategori_kars',
        'nomor_prioritas_rs',
        'kategori_risiko',
        'kategori_mfk',
        'kegiatan',
        'tujuan_kegiatan',
        'pernyataan_risiko',
        'deskripsi',
        'penyebab',
        'kontrolabilitas',
        'dampak',
        'pengendalian_ada',
        'pengendalian_desain',
        'pengendalian_efektifitas_text',
        'efektivitas_pengendalian',
        'inherent_probabilitas',
        'inherent_dampak',
        'bobot',
        'skoring_akhir',
        'inherent_skor',
        'inherent_band',
        'peringkat_risiko',
        'pemilik_risiko',
        'alternatif_penanganan',
        'pengendalian_harus_ada',
        'opsi_penanganan',
        'rencana_penanganan',
        'rencana_kegiatan',
        'target_waktu',
        'waktu_jadwal',
        'jenis_alternatif',
        'penanggung_jawab',
        'pemantauan_rencana',
        'pemantauan_realisasi',
        'pemantauan_belum_tertangani',
        'pemantauan_usulan_perbaikan',
        'waktu_pemantauan_rencana',
        'waktu_pemantauan_realisasi',
        'pj_pemantauan',
        'residual_probabilitas',
        'residual_dampak',
        'residual_bobot',
        'residual_skor',
        'residual_band',
        'penurunan_risiko_persen',
        'pelaporan_output',
        'pelaporan_target',
        'pelaporan_realisasi',
        'waktu_implementasi',
        'status_tren',
        'level_risiko_evaluasi',
        'status_pemantauan',
        'catatan_pemantauan',
        'tanggal_review',
        'created_by',
    ];

    protected $casts = [
        'tahun'                    => 'integer',
        'nomor_urut'               => 'integer',
        'is_rs_priority'           => 'boolean',
        'inherent_probabilitas'    => 'integer',
        'inherent_dampak'          => 'integer',
        'inherent_skor'            => 'integer',
        'bobot'                    => 'decimal:2',
        'skoring_akhir'            => 'decimal:2',
        'peringkat_risiko'         => 'integer',
        'residual_probabilitas'    => 'integer',
        'residual_dampak'          => 'integer',
        'residual_bobot'           => 'decimal:2',
        'residual_skor'            => 'decimal:2',
        'penurunan_risiko_persen'  => 'decimal:2',
        'target_waktu'             => 'date:Y-m-d',
        'tanggal_review'           => 'date:Y-m-d',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    public function scopeRsPriority($query)
    {
        return $query->where('is_rs_priority', true);
    }

    public function scopeMfk($query)
    {
        return $query->where('ruang_lingkup', 'mfk');
    }

    public function scopeUnitScope($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopeKarsScope($query, $kategoriKars)
    {
        return $query->where('kategori_kars', $kategoriKars);
    }

    // ─── Matriks Penilaian Risiko Standar RSYS & JUKNIS 2026 ─────────────────

    /**
     * Hitung Skor Akhir RSYS: P x D x Bobot
     */
    public static function calculateSkor(int $probabilitas, int $dampak, float $bobot = 1.0): float
    {
        return round($probabilitas * $dampak * $bobot, 2);
    }

    /**
     * Tentukan Band / Tingkat Risiko berdasarkan Panduan RSYS:
     * - Skor > 19 : Sangat Tinggi (Merah)
     * - Skor > 14 : Tinggi (Kuning Tua / Oranye)
     * - Skor > 9  : Sedang (Kuning Muda)
     * - Skor > 4  : Rendah (Hijau)
     * - Skor <= 4 : Sangat Rendah (Biru)
     */
    public static function determineRiskBand(float $skor): string
    {
        if ($skor > 19) {
            return 'sangat_tinggi';
        } elseif ($skor > 14) {
            return 'tinggi';
        } elseif ($skor > 9) {
            return 'sedang';
        } elseif ($skor > 4) {
            return 'rendah';
        } else {
            return 'sangat_rendah';
        }
    }

    /**
     * Label Tingkat Risiko dalam Bahasa Indonesia yang formal
     */
    public static function getRiskBandLabel(string $band): string
    {
        return match ($band) {
            'sangat_tinggi' => 'Sangat Tinggi',
            'tinggi'        => 'Tinggi',
            'sedang'        => 'Sedang',
            'rendah'        => 'Rendah',
            'sangat_rendah' => 'Sangat Rendah',
            default         => ucfirst($band),
        };
    }

    /**
     * Hex Color untuk Tingkat Risiko RSYS
     */
    public static function getRiskBandColor(string $band): string
    {
        return match ($band) {
            'sangat_tinggi' => '#EF4444', // Merah
            'tinggi'        => '#F97316', // Oranye / Kuning Tua
            'sedang'        => '#FBBF24', // Kuning Muda
            'rendah'        => '#10B981', // Hijau
            'sangat_rendah' => '#3B82F6', // Biru
            default         => '#6B7280',
        };
    }

    /**
     * Generate format nomor register unik: MR-YYYY-XXXX (e.g. MR-2026-0001)
     */
    public static function generateNomorRegister(?int $tahun = null): string
    {
        $targetYear = $tahun ?? 2026;
        $prefix = "MR-{$targetYear}-";

        $last = self::where('nomor_register', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($last && preg_match('/MR-\d+-(\d+)/', $last->nomor_register, $m)) {
            $nextNumber = str_pad(((int) $m[1]) + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }
}
