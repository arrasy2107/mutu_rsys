<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicator_id', 'period_id', 'unit_id', 'user_id',
        'tanggal', 'numerator', 'denominator', 'capaian',
        'catatan', 'file_bukti', 'status', 'submitted_at',
        'log_harian', 'form_schema_version', 'workbook_snapshot',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'numerator' => 'decimal:2',
        'denominator' => 'decimal:2',
        'capaian' => 'decimal:2',
        'submitted_at' => 'datetime',
        'log_harian' => 'array',
        'form_schema_version' => 'integer',
        'workbook_snapshot' => 'array',
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

    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class);
    }

    public function latestValidation(): HasOne
    {
        return $this->hasOne(Validation::class)->latestOfMany();
    }

    public function pdsaRecords(): HasMany
    {
        return $this->hasMany(PdsaRecord::class, 'period_id', 'period_id');
    }

    /**
     * Hitung capaian otomatis
     */
    public function hitungCapaian(): float
    {
        if ($this->denominator == 0) {
            return 0;
        }

        $multiplier = $this->indicator?->multiplier ?? 100.0;

        return round(($this->numerator / $this->denominator) * $multiplier, 2);
    }

    /**
     * Auto-compute capaian sebelum save
     */
    protected static function booted(): void
    {
        static::saving(function (DataEntry $entry) {
            if ($entry->denominator > 0) {
                $multiplier = $entry->indicator?->multiplier
                    ?? ($entry->indicator_id ? Indicator::find($entry->indicator_id)?->multiplier ?? 100.0 : 100.0);
                $entry->capaian = round(($entry->numerator / $entry->denominator) * $multiplier, 2);
            } else {
                $entry->capaian = 0;
            }
        });
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'green',
            'exempt' => 'teal',
            'rejected' => 'red',
            'revision' => 'orange',
            'submitted' => 'blue',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Menunggu Validasi',
            'approved' => 'Disetujui',
            'exempt' => 'Bebas Validasi',
            'rejected' => 'Ditolak',
            'revision' => 'Perlu Revisi',
            default => '-',
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }
}
