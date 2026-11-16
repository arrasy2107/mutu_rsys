<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndicatorSelection extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'tipe',
        'tanggal',
        'jam',
        'tempat',
        'notulen',
        'berkas_pendukung',
        'status',
        'pemimpin_rapat_id',
        'pemimpin_rapat_nama',
        'imp_item_id',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pemimpinRapat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemimpin_rapat_id');
    }

    public function impItem(): BelongsTo
    {
        return $this->belongsTo(IndicatorSelectionItem::class, 'imp_item_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(IndicatorSelectionItem::class, 'selection_id')->orderByDesc('total_skor');
    }
}
