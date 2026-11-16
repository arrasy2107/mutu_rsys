<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    // Password default untuk semua user baru
    const DEFAULT_PASSWORD = '12345678';

    public function run(): void
    {
        // ── Super Admin ──
        User::updateOrCreate(
            ['email' => 'admin@mutu-rsys.id'],
            [
                'username'             => 'admin',
                'name'                 => 'Administrator',
                'nama_lengkap'         => 'Administrator Sistem',
                'nip'                  => 'RSYS-001',
                'password'             => Hash::make(self::DEFAULT_PASSWORD),
                'role'                 => 'superadmin',
                'unit_id'              => null,
                'aktif'                => true,
                'must_change_password' => true,
            ]
        );

        // ── Komite Mutu ──
        User::updateOrCreate(
            ['email' => 'komite.mutu@mutu-rsys.id'],
            [
                'username'             => 'komite_mutu',
                'name'                 => 'Komite Mutu',
                'nama_lengkap'         => 'dr. Hendra Setiawan, Sp.PK',
                'nip'                  => '198205142008011002',
                'password'             => Hash::make(self::DEFAULT_PASSWORD),
                'role'                 => 'komite_mutu',
                'unit_id'              => null,
                'aktif'                => true,
                'must_change_password' => true,
            ]
        );

        // ── Direksi ──
        User::updateOrCreate(
            ['email' => 'direktur@mutu-rsys.id'],
            [
                'username'             => 'direktur',
                'name'                 => 'Direktur RS',
                'nama_lengkap'         => 'dr. H. Rahmat Hidayat, MARS',
                'nip'                  => '197508202002121001',
                'password'             => Hash::make(self::DEFAULT_PASSWORD),
                'role'                 => 'direksi',
                'unit_id'              => null,
                'aktif'                => true,
                'must_change_password' => true,
            ]
        );

        // ── Kepala Unit & PIC IGD ──
        $igd = Unit::where('kode', 'IGD')->first();
        if ($igd) {
            User::updateOrCreate(
                ['email' => 'karu.igd@mutu-rsys.id'],
                [
                    'username'             => 'karu_igd',
                    'name'                 => 'Kepala Ruangan IGD',
                    'nama_lengkap'         => 'Ns. Dewi Lestari, S.Kep',
                    'nip'                  => '198811052012012004',
                    'password'             => Hash::make(self::DEFAULT_PASSWORD),
                    'role'                 => 'kepala_unit',
                    'unit_id'              => $igd->id,
                    'aktif'                => true,
                    'must_change_password' => true,
                ]
            );

            User::updateOrCreate(
                ['email' => 'pic.igd@mutu-rsys.id'],
                [
                    'username'             => 'pic_igd',
                    'name'                 => 'PIC Mutu IGD',
                    'nama_lengkap'         => 'Amd. Kep. Farhan Maulana',
                    'nip'                  => '199203182015031003',
                    'password'             => Hash::make(self::DEFAULT_PASSWORD),
                    'role'                 => 'pic_unit',
                    'unit_id'              => $igd->id,
                    'aktif'                => true,
                    'must_change_password' => true,
                ]
            );
        }

        // ── PIC Rawat Inap ──
        $riInterna = Unit::where('kode', 'RI-INTERNA')->first();
        if ($riInterna) {
            User::updateOrCreate(
                ['email' => 'pic.ri.interna@mutu-rsys.id'],
                [
                    'username'             => 'pic_interna',
                    'name'                 => 'PIC Rawat Inap Interna',
                    'nama_lengkap'         => 'Ns. Budi Prakoso, S.Kep',
                    'nip'                  => '199009122014021002',
                    'password'             => Hash::make(self::DEFAULT_PASSWORD),
                    'role'                 => 'pic_unit',
                    'unit_id'              => $riInterna->id,
                    'aktif'                => true,
                    'must_change_password' => true,
                ]
            );
        }

        // ── PIC Rawat Jalan (IRJ) ──
        $irj = Unit::where('id', 18)->orWhere('nama', 'like', '%Rawat Jalan%')->first();
        if ($irj) {
            User::updateOrCreate(
                ['username' => 'pic_irj'],
                [
                    'name'                 => 'PIC Mutu IRJ',
                    'nama_lengkap'         => 'Ns. Siti Nurhaliza, S.Kep',
                    'nip'                  => '199107252014032001',
                    'email'                => 'pic.irj@mutu-rsys.id',
                    'password'             => Hash::make(self::DEFAULT_PASSWORD),
                    'role'                 => 'pic_unit',
                    'unit_id'              => $irj->id,
                    'aktif'                => true,
                    'must_change_password' => true,
                ]
            );
        }
    }
}
