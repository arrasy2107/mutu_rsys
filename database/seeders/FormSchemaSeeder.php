<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class FormSchemaSeeder extends Seeder
{
    public function run(): void
    {
        $farmasi = Unit::where('kode', 'like', '%FAR%')->orWhere('nama', 'like', '%Farmasi%')->first();
        if (!$farmasi) return;

        // 1. IMU-2: Waktu Tunggu Pelayanan Farmasi (grouped_category)
        $imu2 = Indicator::where('unit_id', $farmasi->id)->where('kode', 'IMU-2')->first();
        if ($imu2) {
            $imu2->update([
                'form_layout_type' => 'grouped_category',
                'form_schema' => [
                    'type' => 'grouped_category',
                    'title' => 'Tally Sheet Waktu Tunggu Pelayanan Resep Farmasi',
                    'categories' => [
                        [
                            'key' => 'obat_jadi',
                            'label' => 'Obat Jadi (1 Minggu & 1 Bulan)',
                            'standard_label' => '≤ 30 Menit',
                            'standard_minutes' => 30,
                            'default_sample' => 778,
                            'default_compliant' => 670,
                        ],
                        [
                            'key' => 'obat_umum',
                            'label' => 'Obat Umum / Jaminan Perusahaan',
                            'standard_label' => '≤ 30 Menit',
                            'standard_minutes' => 30,
                            'default_sample' => 219,
                            'default_compliant' => 198,
                        ],
                        [
                            'key' => 'obat_racikan',
                            'label' => 'Obat Racikan',
                            'standard_label' => '≤ 60 Menit',
                            'standard_minutes' => 60,
                            'default_sample' => 134,
                            'default_compliant' => 130,
                        ],
                    ],
                    'columns' => [
                        ['key' => 'sample', 'label' => 'Jumlah Sampel Resep', 'is_denominator' => true],
                        ['key' => 'compliant', 'label' => 'Sesuai Standar Waktu', 'is_numerator' => true],
                        ['key' => 'total_minutes', 'label' => 'Akumulasi Waktu (Menit)', 'optional' => true],
                    ],
                    'rollup' => [
                        'numerator' => 'sum(compliant)',
                        'denominator' => 'sum(sample)',
                    ]
                ]
            ]);
        }

        // 2. IMP-RS4: Ketepatan Pengisian Aturan Pakai Obat di EMR (entity_checklist)
        $imprs4 = Indicator::where('unit_id', $farmasi->id)->where('kode', 'IMP-RS4')->first();
        if ($imprs4) {
            $doctors = [
                'IGD dan Poli Umum',
                'dr. Alex Chandra',
                'dr. Oea Khairsyaf',
                'dr. Kornelis Aribowo',
                'dr. Novi Arius',
                'dr. Riski Ilona',
                'dr. Ivanny K',
                'dr. Rina Gustia',
                'dr. Akmal M',
                'dr. Irvan Medison',
                'dr. Deasy Natalia',
                'dr. Raveinal',
                'dr. Yuni Handayani',
                'dr. Daan Khambri',
                'dr. AlHafiz',
                'dr. Putri Handayani',
                'dr. Agung Pratama',
                'dr. Mayetti',
                'dr. Eka Agustia',
                'dr. Miranda Azhar',
                'dr. Fauzar',
                'dr. Aris Albirru',
                'dr. Dian Budianti',
                'dr. Harmen',
                'dr. Syarif Indra',
                'dr. Najirman',
                'dr. Kurniawan',
                'dr. Kemala Sayuti',
                'dr. Ivan Mahendra',
                'dr. Hendra Maska',
                'dr. Benni Raymond',
                'dr. Alexander Cahyadi',
                'dr. Piet Iskandar',
                'dr. Yose Ramda',
                'dr. Edi Leonardo',
            ];

            $imprs4->update([
                'form_layout_type' => 'entity_checklist',
                'form_schema' => [
                    'type' => 'entity_checklist',
                    'title' => 'Audit Ketepatan Pengisian Aturan Pakai Obat di EMR oleh Dokter DPJP',
                    'entity_label' => 'Dokter DPJP / Poli',
                    'entities' => array_map(fn($name, $idx) => [
                        'id' => $idx + 1,
                        'name' => $name,
                    ], $doctors, array_keys($doctors)),
                    'columns' => [
                        ['key' => 'sample', 'label' => 'Jml Sampel', 'type' => 'number', 'is_denominator' => true],
                        ['key' => 'pasien', 'label' => 'Nama Pasien', 'type' => 'number', 'auxiliary' => true],
                        ['key' => 'tgl_lahir', 'label' => 'Tgl Lahir', 'type' => 'number', 'auxiliary' => true],
                        ['key' => 'no_rm', 'label' => 'No. RM', 'type' => 'number', 'auxiliary' => true],
                        ['key' => 'nama_obat', 'label' => 'Nama Obat', 'type' => 'number', 'auxiliary' => true],
                        ['key' => 'dosis', 'label' => 'Dosis Obat', 'type' => 'number', 'auxiliary' => true],
                        ['key' => 'aturan_pakai', 'label' => 'Aturan Pakai', 'type' => 'number', 'is_numerator' => true],
                    ],
                    'rollup' => [
                        'numerator' => 'sum(aturan_pakai)',
                        'denominator' => 'sum(sample)',
                    ]
                ]
            ]);
        }

        // 3. IMU-5: Kepatuhan Penggunaan Formularium Nasional (location_matrix)
        $imu5 = Indicator::where('unit_id', $farmasi->id)->where('kode', 'IMU-5')->first();
        if ($imu5) {
            $imu5->update([
                'form_layout_type' => 'location_matrix',
                'form_schema' => [
                    'type' => 'location_matrix',
                    'title' => 'Matriks Rekapitulasi Kepatuhan Formularium Nasional per Depo Layanan',
                    'location_label' => 'Depo Pelayanan Farmasi',
                    'locations' => [
                        ['key' => 'depo_igd', 'label' => 'Depo Gawat Darurat (IGD)'],
                        ['key' => 'depo_ranap', 'label' => 'Depo Rawat Inap'],
                        ['key' => 'depo_rajal', 'label' => 'Depo Rawat Jalan'],
                    ],
                    'columns' => [
                        ['key' => 'fornas', 'label' => 'Resep FORNAS', 'type' => 'number', 'is_numerator' => true],
                        ['key' => 'generik', 'label' => 'Resep Generik', 'type' => 'number', 'auxiliary' => true],
                        ['key' => 'total_resep', 'label' => 'Total Resep Keseluruhan', 'type' => 'number', 'is_denominator' => true],
                    ],
                    'rollup' => [
                        'numerator' => 'sum(fornas)',
                        'denominator' => 'sum(total_resep)',
                    ]
                ]
            ]);
        }

        // 4. IMP-RS7: Kepatuhan Penyimpanan Obat High Alert di IBS (inspection_points)
        $imprs7 = Indicator::where('unit_id', $farmasi->id)->where('kode', 'IMP-RS7')->first();
        if ($imprs7) {
            $imprs7->update([
                'form_layout_type' => 'inspection_points',
                'form_schema' => [
                    'type' => 'inspection_points',
                    'title' => 'Checklist Titik Inspeksi Penyimpanan Obat High Alert di IBS',
                    'point_label' => 'Tempat / Titik Penyimpanan di IBS',
                    'points' => [
                        ['key' => 'lemari_ha_ibs', 'label' => 'Lemari Khusus High Alert Farmasi IBS'],
                        ['key' => 'troli_ok_1', 'label' => 'Troli Emergensi Kamar Bedah 1'],
                        ['key' => 'troli_ok_2', 'label' => 'Troli Emergensi Kamar Bedah 2'],
                        ['key' => 'troli_ok_3', 'label' => 'Troli Emergensi Kamar Bedah 3 / Minor'],
                        ['key' => 'lemari_narkotika', 'label' => 'Lemari Narkotika & Psikotropika IBS (Double Lock)'],
                        ['key' => 'kotak_lasa', 'label' => 'Penyimpanan Obat LASA / NORUM IBS'],
                        ['key' => 'kulkas_farmasi', 'label' => 'Kulkas Obat IBS (Suhu 2°C - 8°C)'],
                    ],
                    'criteria' => [
                        ['key' => 'label_ha', 'label' => 'Stiker High Alert Merah Terpasang'],
                        ['key' => 'pemisahan_lasa', 'label' => 'Pemisahan & Penandaan LASA/NORUM'],
                        ['key' => 'double_check', 'label' => 'Daftar Obat & Kartu Stok Sesuai'],
                        ['key' => 'suhu_terjaga', 'label' => 'Suhu Penyimpanan Sesuai & Dicatat'],
                    ],
                    'rollup' => [
                        'numerator' => 'count(all_criteria_met)',
                        'denominator' => 'count(points)',
                    ]
                ]
            ]);
        }

        // 5. Pastikan semua indikator lainnya memiliki 'daily_grid' secara eksplisit
        Indicator::whereNull('form_layout_type')->orWhere('form_layout_type', '')->update([
            'form_layout_type' => 'daily_grid',
            'form_schema' => null,
        ]);
    }
}
