<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientSafetyIncident extends Model
{
    use HasFactory;

    protected $table = 'patient_safety_incidents';

    protected $fillable = [
        'nomor_laporan',
        'tanggal_laporan',
        'tanggal_kejadian',
        'tipe_insiden',
        'insiden',
        'kronologi',
        'jenis_insiden',
        'grading',
        'unit_pelapor_id',
        'unit_terkait_id',
        'tipe_penyelesaian',
        'tanggal_selesai_penyelesaian',
        'kriteria_waktu_penyelesaian',
        'rencana_tindak_lanjut',
        'penanggung_jawab_rtl',
        'bukti_rtl_path',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_laporan'             => 'date:Y-m-d',
        'tanggal_kejadian'            => 'datetime:Y-m-d H:i',
        'tanggal_selesai_penyelesaian' => 'date:Y-m-d',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function unitPelapor(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_pelapor_id');
    }

    public function unitTerkait(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_terkait_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes & Helpers ─────────────────────────────────────────────────────

    /**
     * Generate format nomor laporan otomatis: IKP/YYYY/MM/0001
     */
    public static function generateNomorLaporan(?Carbon $date = null): string
    {
        $targetDate = $date ?? now();
        $year = $targetDate->format('Y');
        $month = $targetDate->format('m');
        $prefix = "IKP/{$year}/{$month}/";

        $last = self::where('nomor_laporan', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->nomor_laporan, strrpos($last->nomor_laporan, '/') + 1);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }

    /**
     * Hitung kepatuhan waktu penyelesaian otomatis berdasarkan grading dan tanggal kejadian
     * Biru / Hijau = Investigasi Sederhana (Maks 1-2 minggu)
     * Kuning / Merah = RCA Komprehensif (Maks 45 hari)
     */
    public function evaluateKriteriaWaktu(): string
    {
        if (!$this->tanggal_selesai_penyelesaian || !$this->tanggal_laporan) {
            return 'dalam_proses';
        }

        $start = Carbon::parse($this->tanggal_laporan);
        $finish = Carbon::parse($this->tanggal_selesai_penyelesaian);
        $diffDays = $start->diffInDays($finish);

        if (in_array(strtolower($this->grading), ['kuning', 'merah']) || $this->tipe_penyelesaian === 'rca') {
            // Standar RCA Kemenkes: maksimal 45 hari
            return $diffDays <= 45 ? 'sesuai' : 'tidak_sesuai';
        } else {
            // Standar Investigasi Sederhana: maksimal 14 hari
            return $diffDays <= 14 ? 'sesuai' : 'tidak_sesuai';
        }
    }
}

