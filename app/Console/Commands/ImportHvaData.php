<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HvaAssessment;
use Illuminate\Support\Facades\DB;

class ImportHvaData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hva:import-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import real Hazard Vulnerability Analysis (HVA) data from 35 hospital units and RS aggregation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting HVA Real Data Import...");

        $jsonFile = base_path('scratch/hva_parsed_data.json');
        if (!file_exists($jsonFile)) {
            $this->error("File not found: {$jsonFile}");
            return 1;
        }

        $data = json_decode(file_get_contents($jsonFile), true);
        if (!$data) {
            $this->error("Invalid JSON data in {$jsonFile}");
            return 1;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        HvaAssessment::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $unitRecords = $data['unit_assessments'] ?? [];
        $rsRecords = $data['rs_assessments'] ?? [];

        $this->info("Importing " . count($unitRecords) . " unit assessments...");
        $now = now();
        $batch = [];
        $batchSize = 250;

        foreach ($unitRecords as $item) {
            $batch[] = [
                'tahun' => $item['tahun'] ?? 2026,
                'ruang_lingkup' => 'unit',
                'unit_id' => $item['unit_id'],
                'kategori' => $item['kategori'],
                'nama_kejadian' => $item['nama_kejadian'],
                'urutan' => $item['urutan'],
                'probabilitas' => $item['probabilitas'],
                'dampak_manusia' => $item['dampak_manusia'],
                'dampak_properti' => $item['dampak_properti'],
                'dampak_bisnis' => $item['dampak_bisnis'],
                'kesiapan' => $item['kesiapan'],
                'respon_internal' => $item['respon_internal'],
                'respon_eksternal' => $item['respon_eksternal'],
                'skor_keparahan' => $item['skor_keparahan'],
                'skor_risiko_relatif' => $item['skor_risiko_relatif'],
                'peringkat_prioritas' => $item['peringkat_prioritas'] ?? null,
                'rencana_tindak_lanjut' => $item['rencana_tindak_lanjut'] ?? null,
                'pj_mitigasi' => $item['pj_mitigasi'] ?? null,
                'target_waktu' => $item['target_waktu'] ?? null,
                'status_mitigasi' => $item['status_mitigasi'] ?? 'belum_mulai',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                HvaAssessment::insert($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            HvaAssessment::insert($batch);
            $batch = [];
        }

        $this->info("Importing " . count($rsRecords) . " hospital aggregated assessments...");
        foreach ($rsRecords as $item) {
            $batch[] = [
                'tahun' => $item['tahun'] ?? 2026,
                'ruang_lingkup' => 'rs',
                'unit_id' => null,
                'kategori' => $item['kategori'],
                'nama_kejadian' => $item['nama_kejadian'],
                'urutan' => $item['urutan'],
                'probabilitas' => $item['probabilitas'],
                'dampak_manusia' => $item['dampak_manusia'],
                'dampak_properti' => $item['dampak_properti'],
                'dampak_bisnis' => $item['dampak_bisnis'],
                'kesiapan' => $item['kesiapan'],
                'respon_internal' => $item['respon_internal'],
                'respon_eksternal' => $item['respon_eksternal'],
                'skor_keparahan' => $item['skor_keparahan'],
                'skor_risiko_relatif' => $item['skor_risiko_relatif'],
                'peringkat_prioritas' => $item['peringkat_prioritas'] ?? null,
                'rencana_tindak_lanjut' => $item['rencana_tindak_lanjut'] ?? null,
                'pj_mitigasi' => $item['pj_mitigasi'] ?? null,
                'target_waktu' => $item['target_waktu'] ?? null,
                'status_mitigasi' => $item['status_mitigasi'] ?? 'belum_mulai',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (count($batch) > 0) {
            HvaAssessment::insert($batch);
        }

        $total = HvaAssessment::count();
        $this->info("SUCCESS! Total records in hva_assessments table: {$total}");
        $this->info(" - Unit records: " . HvaAssessment::where('ruang_lingkup', 'unit')->count());
        $this->info(" - Hospital (RS) records: " . HvaAssessment::where('ruang_lingkup', 'rs')->count());

        return 0;
    }
}

