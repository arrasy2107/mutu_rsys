<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['kode' => 'IGD',       'nama' => 'Instalasi Gawat Darurat',          'deskripsi' => 'Unit pelayanan gawat darurat 24 jam'],
            ['kode' => 'ICU',       'nama' => 'Intensive Care Unit',              'deskripsi' => 'Unit perawatan intensif'],
            ['kode' => 'ICCU',      'nama' => 'Intensive Coronary Care Unit',     'deskripsi' => 'Unit perawatan intensif jantung'],
            ['kode' => 'NICU',      'nama' => 'Neonatal Intensive Care Unit',     'deskripsi' => 'Unit perawatan intensif neonatal'],
            ['kode' => 'OK',        'nama' => 'Instalasi Kamar Operasi',          'deskripsi' => 'Ruang operasi dan tindakan bedah'],
            ['kode' => 'VK',        'nama' => 'Kamar Bersalin (VK)',              'deskripsi' => 'Ruang persalinan'],
            ['kode' => 'RI-INTERNA','nama' => 'Rawat Inap Interna',               'deskripsi' => 'Ruang rawat inap penyakit dalam'],
            ['kode' => 'RI-BEDAH',  'nama' => 'Rawat Inap Bedah',                'deskripsi' => 'Ruang rawat inap bedah'],
            ['kode' => 'RI-ANAK',   'nama' => 'Rawat Inap Anak',                 'deskripsi' => 'Ruang rawat inap pediatri'],
            ['kode' => 'RI-KEBID',  'nama' => 'Rawat Inap Kebidanan',             'deskripsi' => 'Ruang rawat inap kebidanan'],
            ['kode' => 'POLI-UMUM', 'nama' => 'Poliklinik Umum',                  'deskripsi' => 'Rawat jalan umum'],
            ['kode' => 'FARMASI',   'nama' => 'Instalasi Farmasi',                'deskripsi' => 'Apotek dan pelayanan obat'],
            ['kode' => 'LAB',       'nama' => 'Laboratorium Klinik',              'deskripsi' => 'Pemeriksaan laboratorium'],
            ['kode' => 'RADIOLOGI', 'nama' => 'Instalasi Radiologi',              'deskripsi' => 'Pemeriksaan radiologi dan pencitraan'],
            ['kode' => 'GIZI',      'nama' => 'Instalasi Gizi',                   'deskripsi' => 'Pelayanan gizi dan dietetik'],
            ['kode' => 'CSSD',      'nama' => 'CSSD / Sterilisasi',               'deskripsi' => 'Central Sterile Supply Department'],
            ['kode' => 'REKAM-MED', 'nama' => 'Rekam Medis',                      'deskripsi' => 'Pengelolaan rekam medis pasien'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['kode' => $unit['kode']], array_merge($unit, ['aktif' => true]));
        }
    }
}
