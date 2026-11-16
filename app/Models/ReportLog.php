<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'jenis', 'tahun', 'bulan', 'triwulan',
        'format', 'parameter', 'file_path',
    ];

    protected $casts = [
        'parameter' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
