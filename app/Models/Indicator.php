<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Indicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode', 'judul', 'jenis', 'dasar_pemikiran', 'dimensi_mutu', 'tujuan',
        'kriteria_inklusi', 'kriteria_eksklusi', 'formula', 'cara_pengumpulan',
        'jenis_indikator', 'target_capaian', 'target_operator', 'frekuensi', 'sumber_data',
        'penanggung_jawab', 'validator', 'pengumpul_data', 'unit_id', 'is_active', 'created_by',
        'definisi_operasional', 'satuan_pengukuran', 'numerator_keterangan',
        'denominator_keterangan', 'metode_pengumpulan', 'instrumen_pengambilan',
        'besar_sampel', 'cara_pengambilan_sampel',
        'periode_analisis', 'publikasi_data',
        'form_layout_type', 'form_schema',
    ];

    protected $casts = [
        'target_capaian' => 'decimal:2',
        'is_active' => 'boolean',
        'jenis' => 'array',
        'dimensi_mutu' => 'array',
        'jenis_indikator' => 'array',
        'form_schema' => 'array',
    ];

    protected $appends = ['multiplier'];

    public function getMultiplierAttribute(): float
    {
        if (!empty($this->formula)) {
            if (preg_match('/(?:x|\*|×)\s*(\d+(?:\.\d+)?)/iu', $this->formula, $matches)) {
                $val = (float) $matches[1];
                if ($val > 0) {
                    return $val;
                }
            }
        }
        return 100.0;
    }

    public function getJenisIndikatorAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        return is_string($value) ? [$value] : (array) $value;
    }



    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(IndicatorPeriod::class);
    }

    public function dataEntries(): HasMany
    {
        return $this->hasMany(DataEntry::class);
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(IndicatorFormField::class)->orderBy('order');
    }

    public function dailyRecords(): HasMany
    {
        return $this->hasMany(IndicatorDailyRecord::class);
    }

    public function pdsaRecords(): HasMany
    {
        return $this->hasMany(PdsaRecord::class);
    }

    public function customFormFormat(): HasOne
    {
        return $this->hasOne(CustomFormFormat::class);
    }

    /**
     * Cek apakah capaian memenuhi target
     */
    public function isTargetMet(float $capaian): bool
    {
        return match($this->target_operator) {
            '>=' => $capaian >= $this->target_capaian,
            '<=' => $capaian <= $this->target_capaian,
            '='  => $capaian == $this->target_capaian,
            default => $capaian >= $this->target_capaian,
        };
    }

    /**
     * Hitung status warna indikator berdasarkan capaian
     */
    public function getStatusColor(float $capaian): string
    {
        if ($this->isTargetMet($capaian)) {
            return 'green';
        }

        $gap = abs($capaian - $this->target_capaian);

        if ($gap <= 10) {
            return 'yellow'; // dalam 10% dari target = kuning
        }

        return 'red';
    }

    /**
     * Return the normalized IMUT RSYS workbook color band for a measured value.
     */
    public function getImutPerformanceColor(float $capaian): string
    {
        if ($this->target_operator !== '>=') {
            return $this->isTargetMet($capaian) ? 'none' : 'orange';
        }

        if ($this->isTargetMet($capaian)) {
            return 'none';
        }

        if ($this->target_capaian > 80 && $capaian >= 81) {
            return 'blue';
        }

        if ($capaian >= 71) {
            return 'green';
        }

        if ($capaian >= 51) {
            return 'yellow';
        }

        return 'orange';
    }

    public function getMonthlyFollowUpType(?float $capaian, ?float $previousCapaian): ?string
    {
        if ($capaian === null) {
            return null;
        }

        if (!$this->isTargetMet($capaian)) {
            return 'rtl';
        }

        return $previousCapaian !== null && !$this->isTargetMet($previousCapaian)
            ? 'pdsa'
            : null;
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }
}
