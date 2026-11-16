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
            $table->text('numerator_keterangan')->nullable()->change();
            $table->text('denominator_keterangan')->nullable()->change();
            $table->text('metode_pengumpulan')->nullable()->change();
            $table->text('instrumen_pengambilan')->nullable()->change();
            $table->text('besar_sampel')->nullable()->change();
            $table->text('cara_pengambilan_sampel')->nullable()->change();
            $table->text('periode_analisis')->nullable()->change();
            $table->text('publikasi_data')->nullable()->change();
            $table->text('sumber_data')->nullable()->change();
            $table->text('dimensi_mutu')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->string('numerator_keterangan', 255)->nullable()->change();
            $table->string('denominator_keterangan', 255)->nullable()->change();
            $table->string('metode_pengumpulan', 255)->nullable()->change();
            $table->string('instrumen_pengambilan', 255)->nullable()->change();
            $table->string('besar_sampel', 255)->nullable()->change();
            $table->string('cara_pengambilan_sampel', 255)->nullable()->change();
            $table->string('periode_analisis', 255)->nullable()->change();
            $table->string('publikasi_data', 255)->nullable()->change();
            $table->string('sumber_data', 255)->nullable()->change();
            $table->string('dimensi_mutu', 255)->nullable()->change();
        });
    }
};
