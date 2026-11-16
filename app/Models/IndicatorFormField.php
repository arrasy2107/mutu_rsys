<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorFormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicator_id',
        'name',
        'key',
        'type', // 'numerator', 'denominator', 'auxiliary'
        'order',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
}
