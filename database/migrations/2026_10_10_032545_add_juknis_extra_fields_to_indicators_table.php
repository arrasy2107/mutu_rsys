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
        Schema::table('indicators', function (Blueprint $table) {
            $table->string('numerator_keterangan')->nullable()->after('formula');
            $table->string('denominator_keterangan')->nullable()->after('numerator_keterangan');
            $table->string('metode_pengumpulan')->nullable()->after('denominator_keterangan');
            $table->string('instrumen_pengambilan')->nullable()->after('sumber_data');
            $table->string('besar_sampel')->nullable()->after('instrumen_pengambilan');
            $table->string('cara_pengambilan_sampel')->nullable()->after('besar_sampel');
            $table->string('penyajian_data')->nullable()->after('frekuensi');
            $table->string('periode_analisis')->nullable()->after('penyajian_data');
            $table->string('publikasi_data')->nullable()->after('penanggung_jawab');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->dropColumn([
                'numerator_keterangan',
                'denominator_keterangan',
                'metode_pengumpulan',
                'instrumen_pengambilan',
                'besar_sampel',
                'cara_pengambilan_sampel',
                'penyajian_data',
                'periode_analisis',
                'publikasi_data',
            ]);
        });
    }
};
