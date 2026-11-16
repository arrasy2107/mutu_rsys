<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('risk_registers', function (Blueprint $table) {
            // Drop unique constraint on kode_risiko if it exists (since in hospital data it's category code like 'RK', 'RO')
            $table->dropUnique(['kode_risiko']);
            
            // Register Number Unique (e.g. MR-2026-001)
            $table->string('nomor_register', 100)->nullable()->unique()->after('id');
            $table->integer('nomor_urut')->nullable()->after('nomor_register');
            
            // Tahap 1: Identifikasi Risiko Spesifik RSYS
            $table->text('kegiatan')->nullable()->after('ruang_lingkup');
            $table->text('tujuan_kegiatan')->nullable()->after('kegiatan'); // Format SMART
            $table->string('kontrolabilitas', 10)->default('C')->after('penyebab'); // C (Controllable) / UC (Uncontrollable)
            
            // Tahap 2 & 3: Pengendalian Eksisting & Analisis Bobot
            $table->text('pengendalian_desain')->nullable()->after('pengendalian_ada'); // Ada / Tidak
            $table->string('pengendalian_efektifitas_text', 100)->nullable()->after('pengendalian_desain'); // Efektif / Kurang Efektif / Tidak Efektif
            $table->decimal('bobot', 5, 2)->default(1.00)->after('inherent_dampak'); // Bobot RSYS (1.00 s.d 2.00)
            $table->decimal('skoring_akhir', 6, 2)->default(1.00)->after('bobot'); // P x D x Bobot
            $table->integer('peringkat_risiko')->nullable()->after('inherent_band'); // Peringkat Prioritas Unit/RS
            $table->string('pemilik_risiko')->nullable()->after('peringkat_risiko'); // Nama Unit/Jabatan Pemilik
            
            // Tahap 4: Rencana Penanganan & Pengendalian
            $table->string('alternatif_penanganan')->nullable()->after('opsi_penanganan'); // Menerima, Mengurangi kemungkinan, Mengurangi dampak, Berbagi, Menghindari
            $table->text('pengendalian_harus_ada')->nullable()->after('alternatif_penanganan'); // RTL Pengendalian
            $table->text('rencana_kegiatan')->nullable()->after('rencana_penanganan'); // Uraian Kegiatan Mitigasi
            $table->string('waktu_jadwal')->nullable()->after('target_waktu');
            $table->string('jenis_alternatif')->nullable()->after('waktu_jadwal');
            
            // Tahap 5: Pemantauan
            $table->text('pemantauan_rencana')->nullable()->after('catatan_pemantauan');
            $table->text('pemantauan_realisasi')->nullable()->after('pemantauan_rencana');
            $table->text('pemantauan_belum_tertangani')->nullable()->after('pemantauan_realisasi');
            $table->text('pemantauan_usulan_perbaikan')->nullable()->after('pemantauan_belum_tertangani');
            $table->string('waktu_pemantauan_rencana')->nullable()->after('pemantauan_usulan_perbaikan');
            $table->string('waktu_pemantauan_realisasi')->nullable()->after('waktu_pemantauan_rencana');
            $table->string('pj_pemantauan')->nullable()->after('waktu_pemantauan_realisasi');
            
            // Tahap 6: Evaluasi Risiko Residu
            $table->decimal('residual_bobot', 5, 2)->nullable()->after('residual_dampak');
            $table->decimal('penurunan_risiko_persen', 6, 2)->nullable()->after('residual_band');
            
            // Tahap 7: Pelaporan Risiko
            $table->text('pelaporan_output')->nullable()->after('penurunan_risiko_persen');
            $table->text('pelaporan_target')->nullable()->after('pelaporan_output');
            $table->text('pelaporan_realisasi')->nullable()->after('pelaporan_target');
            $table->string('waktu_implementasi')->nullable()->after('pelaporan_realisasi');
            $table->string('status_tren')->nullable()->after('waktu_implementasi'); // Tren: meningkat/menurun/stabil
            $table->string('level_risiko_evaluasi')->nullable()->after('status_tren'); // Level risiko saat evaluasi
            
            // Prioritas Rumah Sakit & KARS 2026
            $table->string('kategori_kars', 100)->nullable()->after('is_rs_priority'); // 8 Pilar KARS
            $table->string('nomor_prioritas_rs', 50)->nullable()->after('kategori_kars'); // No prioritas di sheet DAFTAR RISIKO RS
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risk_registers', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_register',
                'nomor_urut',
                'kegiatan',
                'tujuan_kegiatan',
                'kontrolabilitas',
                'pengendalian_desain',
                'pengendalian_efektifitas_text',
                'bobot',
                'skoring_akhir',
                'peringkat_risiko',
                'pemilik_risiko',
                'alternatif_penanganan',
                'pengendalian_harus_ada',
                'rencana_kegiatan',
                'waktu_jadwal',
                'jenis_alternatif',
                'pemantauan_rencana',
                'pemantauan_realisasi',
                'pemantauan_belum_tertangani',
                'pemantauan_usulan_perbaikan',
                'waktu_pemantauan_rencana',
                'waktu_pemantauan_realisasi',
                'pj_pemantauan',
                'residual_bobot',
                'penurunan_risiko_persen',
                'pelaporan_output',
                'pelaporan_target',
                'pelaporan_realisasi',
                'waktu_implementasi',
                'status_tren',
                'level_risiko_evaluasi',
                'kategori_kars',
                'nomor_prioritas_rs',
            ]);
            $table->unique('kode_risiko');
        });
    }
};

