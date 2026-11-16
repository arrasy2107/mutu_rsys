<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndicatorCategory extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'nama', 'deskripsi', 'warna'];

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class, 'category_id');
    }
}
