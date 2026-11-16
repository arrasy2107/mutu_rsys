<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'nama', 'deskripsi', 'aktif'];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }

    public function dataEntries(): HasMany
    {
        return $this->hasMany(DataEntry::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(IndicatorPeriod::class);
    }

    public function customFormFormats(): HasMany
    {
        return $this->hasMany(CustomFormFormat::class);
    }

    public function patientSafetyIncidents(): HasMany
    {
        return $this->hasMany(PatientSafetyIncident::class, 'unit_pelapor_id');
    }

    public function riskRegisters(): HasMany
    {
        return $this->hasMany(RiskRegister::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
