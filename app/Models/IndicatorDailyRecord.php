<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorDailyRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicator_id',
        'unit_id',
        'tahun',
        'bulan',
        'hari',
        'tanggal',
        'data',
        'created_by',
    ];

    protected $casts = [
        'tahun'   => 'integer',
        'bulan'   => 'integer',
        'hari'    => 'integer',
        'tanggal' => 'date',
        'data'    => 'array',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
