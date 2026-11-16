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
        Schema::table('validations', function (Blueprint $table) {
            $table->integer('jumlah_sampel_validasi')->nullable()->after('catatan_validator');
            $table->string('justifikasi_perlu_validasi')->nullable()->after('jumlah_sampel_validasi');
            $table->string('metode_validasi')->nullable()->after('justifikasi_perlu_validasi');
            $table->string('hasil_validasi')->nullable()->after('metode_validasi');
            $table->text('hasil_analisa')->nullable()->after('hasil_validasi');
            $table->text('kesimpulan')->nullable()->after('hasil_analisa');
            $table->text('rencana_tindak_lanjut')->nullable()->after('kesimpulan');
            $table->string('lampiran_file')->nullable()->after('rencana_tindak_lanjut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validations', function (Blueprint $table) {
            $table->dropColumn([
                'jumlah_sampel_validasi',
                'justifikasi_perlu_validasi',
                'metode_validasi',
                'hasil_validasi',
                'hasil_analisa',
                'kesimpulan',
                'rencana_tindak_lanjut',
                'lampiran_file'
            ]);
        });
    }
};
