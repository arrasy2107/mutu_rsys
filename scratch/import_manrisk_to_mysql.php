<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RiskRegister;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

echo "=== STARTING MANRISK REAL DATA IMPORT ===" . PHP_EOL;

$jsonFile = __DIR__ . '/manrisk_parsed_data.json';
if (!file_exists($jsonFile)) {
    die("JSON file not found: $jsonFile\n");
}

$data = json_decode(file_get_contents($jsonFile), true);
echo "Loaded " . count($data) . " items from JSON." . PHP_EOL;

// Fetch all units
$units = Unit::all()->keyBy('kode');
echo "Found " . $units->count() . " units in database." . PHP_EOL;

// Truncate existing risk_registers
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
RiskRegister::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo "Truncated table risk_registers." . PHP_EOL;

$inserted = 0;
$rsPriorityCount = 0;
$karsCount = 0;

foreach ($data as $index => $item) {
    $seq = $index + 1;
    $nomorRegister = sprintf("MR-2026-%04d", $seq);
    
    $unitKode = $item['unit_kode'] ?? 'IRJ';
    $unit = $units->get($unitKode) ?? $units->first();
    
    $isRsPrio = (bool) ($item['is_rs_priority'] ?? false);
    if ($isRsPrio) $rsPriorityCount++;
    if (!empty($item['kategori_kars'])) $karsCount++;
    
    $record = new RiskRegister();
    $record->nomor_register = $nomorRegister;
    $record->nomor_urut = $item['nomor_urut'] ?? $seq;
    $record->kode_risiko = $item['kode_risiko'] ?? 'RO';
    $record->tahun = 2026;
    $record->unit_id = $unit->id;
    $record->ruang_lingkup = str_contains(strtolower($item['ruang_lingkup'] ?? ''), 'manajemen') ? 'unit' : 'unit';
    $record->is_rs_priority = $isRsPrio;
    $record->kategori_kars = $item['kategori_kars'] ?? null;
    $record->nomor_prioritas_rs = $item['nomor_prioritas_rs'] ?? null;
    $record->kategori_risiko = $item['kategori_risiko'] ?? 'Risiko Operasional';
    $record->kategori_mfk = null;
    
    // Identifikasi
    $record->kegiatan = $item['kegiatan'];
    $record->tujuan_kegiatan = $item['tujuan_kegiatan'];
    $record->pernyataan_risiko = $item['pernyataan_risiko'];
    $record->penyebab = $item['penyebab'];
    $record->kontrolabilitas = $item['kontrolabilitas'] ?? 'C';
    $record->dampak = $item['dampak'];
    
    // Pengendalian & Analisis
    $record->pengendalian_ada = $item['pengendalian_ada'];
    $record->pengendalian_desain = $item['pengendalian_desain'];
    $record->pengendalian_efektifitas_text = $item['pengendalian_efektifitas_text'];
    $record->efektivitas_pengendalian = $item['efektivitas_pengendalian'] ?? 'cukup';
    $record->inherent_probabilitas = $item['inherent_probabilitas'] ?? 1;
    $record->inherent_dampak = $item['inherent_dampak'] ?? 1;
    $record->bobot = $item['bobot'] ?? 1.0;
    $record->skoring_akhir = $item['skoring_akhir'] ?? 1.0;
    $record->inherent_skor = $item['inherent_skor'] ?? 1;
    $record->inherent_band = $item['inherent_band'] ?? 'rendah';
    $record->peringkat_risiko = $item['peringkat_risiko'];
    $record->pemilik_risiko = $item['pemilik_risiko'] ?? $unit->nama;
    
    // Rencana Penanganan
    $record->alternatif_penanganan = $item['alternatif_penanganan'];
    $record->pengendalian_harus_ada = $item['pengendalian_harus_ada'];
    $record->opsi_penanganan = 'mitigasi';
    $record->rencana_penanganan = $item['rencana_penanganan'];
    $record->rencana_kegiatan = $item['rencana_kegiatan'];
    $record->waktu_jadwal = $item['waktu_jadwal'];
    $record->jenis_alternatif = $item['jenis_alternatif'];
    $record->penanggung_jawab = $item['penanggung_jawab'];
    
    // Pemantauan
    $record->pemantauan_rencana = $item['pemantauan_rencana'];
    $record->pemantauan_realisasi = $item['pemantauan_realisasi'];
    $record->pemantauan_belum_tertangani = $item['pemantauan_belum_tertangani'];
    $record->pemantauan_usulan_perbaikan = $item['pemantauan_usulan_perbaikan'];
    $record->waktu_pemantauan_rencana = $item['waktu_pemantauan_rencana'];
    $record->waktu_pemantauan_realisasi = $item['waktu_pemantauan_realisasi'];
    $record->pj_pemantauan = $item['pj_pemantauan'];
    
    // Residu
    $record->residual_probabilitas = $item['residual_probabilitas'];
    $record->residual_dampak = $item['residual_dampak'];
    $record->residual_bobot = $item['residual_bobot'];
    $record->residual_skor = $item['residual_skor'];
    $record->residual_band = $item['residual_band'];
    $record->penurunan_risiko_persen = $item['penurunan_risiko_persen'];
    
    // Pelaporan
    $record->pelaporan_output = $item['pelaporan_output'];
    $record->pelaporan_target = $item['pelaporan_target'];
    $record->pelaporan_realisasi = $item['pelaporan_realisasi'];
    $record->waktu_implementasi = $item['waktu_implementasi'];
    $record->status_tren = $item['status_tren'];
    $record->level_risiko_evaluasi = $item['level_risiko_evaluasi'];
    $record->status_pemantauan = !empty($item['pelaporan_realisasi']) ? 'terkendali' : 'dalam_proses';
    
    $record->save();
    $inserted++;
}

echo "SUCCESS: Inserted {$inserted} risk registers." . PHP_EOL;
echo "- RS Priorities: {$rsPriorityCount}" . PHP_EOL;
echo "- KARS 8 Pillars: {$karsCount}" . PHP_EOL;

$bandDist = RiskRegister::select('inherent_band', DB::raw('count(*) as total'))
    ->groupBy('inherent_band')
    ->pluck('total', 'inherent_band');

echo "Distribution by Inherent Band:" . PHP_EOL;
foreach ($bandDist as $b => $t) {
    echo "  * {$b}: {$t}" . PHP_EOL;
}

