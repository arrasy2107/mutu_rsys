<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'nama_lengkap',
        'nip',
        'email',
        'password',
        'role',
        'unit_id',
        'aktif',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'aktif'                 => 'boolean',
            'must_change_password'  => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function dataEntries(): HasMany
    {
        return $this->hasMany(DataEntry::class);
    }

    public function pdsaRecords(): HasMany
    {
        return $this->hasMany(PdsaRecord::class);
    }

    // ─── Role Helpers ────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isKomiteMutu(): bool
    {
        return $this->role === 'komite_mutu';
    }

    public function isKepalaUnit(): bool
    {
        return $this->role === 'kepala_unit';
    }

    public function isPicUnit(): bool
    {
        return $this->role === 'pic_unit';
    }

    public function isDireksi(): bool
    {
        return $this->role === 'direksi';
    }

    /**
     * Apakah user bisa melakukan validasi
     */
    public function canValidate(): bool
    {
        return in_array($this->role, ['superadmin', 'komite_mutu']);
    }

    /**
     * Apakah user bisa input data
     */
    public function canInputData(): bool
    {
        return in_array($this->role, ['superadmin', 'komite_mutu', 'kepala_unit', 'pic_unit']);
    }

    /**
     * Apakah user bisa isi PDSA
     */
    public function canFillPdsa(): bool
    {
        return in_array($this->role, ['superadmin', 'komite_mutu', 'kepala_unit']);
    }

    /**
     * Apakah user bisa kelola kamus indikator
     */
    public function canManageIndicators(): bool
    {
        return in_array($this->role, ['superadmin', 'komite_mutu']);
    }

    /**
     * Apakah user bisa kelola user & unit
     */
    public function canManageUsers(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Label role dalam Bahasa Indonesia
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'superadmin' => 'Super Admin',
            'komite_mutu' => 'Komite Mutu',
            'kepala_unit' => 'Kepala Unit',
            'pic_unit' => 'PIC Unit',
            'direksi' => 'Direksi',
            default => $this->role,
        };
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
