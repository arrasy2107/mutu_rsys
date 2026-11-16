<?php

namespace Database\Seeders;

use App\Models\IndicatorCategory;
use Illuminate\Database\Seeder;

class IndicatorCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'kode'      => 'INM',
                'nama'      => 'Indikator Nasional Mutu',
                'deskripsi' => '13 indikator mutu yang wajib dilaporkan ke Kemenkes RI sesuai PMK No. 30 Tahun 2022',
                'warna'     => '#EF4444', // red
            ],
            [
                'kode'      => 'IMP-RS',
                'nama'      => 'Indikator Mutu Prioritas Rumah Sakit',
                'deskripsi' => 'Indikator mutu yang ditetapkan oleh manajemen RS sebagai prioritas perbaikan',
                'warna'     => '#F59E0B', // amber
            ],
            [
                'kode'      => 'IMP-Unit',
                'nama'      => 'Indikator Mutu Unit',
                'deskripsi' => 'Indikator mutu yang spesifik pada masing-masing unit/instalasi',
                'warna'     => '#3B82F6', // blue
            ],
        ];

        foreach ($categories as $cat) {
            IndicatorCategory::updateOrCreate(['kode' => $cat['kode']], $cat);
        }
    }
}
