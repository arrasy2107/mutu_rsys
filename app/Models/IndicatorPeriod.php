<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndicatorPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicator_id', 'unit_id', 'tahun', 'bulan',
        'status', 'deadline_input', 'tanggal_validasi', 'validated_by',
    ];

    protected $casts = [
        'deadline_input' => 'date',
        'tanggal_validasi' => 'datetime',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function dataEntries(): HasMany
    {
        return $this->hasMany(DataEntry::class, 'period_id');
    }

    public function getNamaBulanAttribute(): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $bulan[$this->bulan] ?? '-';
    }

    public function getLabelAttribute(): string
    {
        return "{$this->nama_bulan} {$this->tahun}";
    }

    /**
     * Apakah periode masih bisa diinput
     */
    public function isInputOpen(): bool
    {
        return $this->status === 'open' &&
               ($this->deadline_input === null || $this->deadline_input->isFuture());
    }
}
