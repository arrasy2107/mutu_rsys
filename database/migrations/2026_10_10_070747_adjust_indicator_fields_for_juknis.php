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
            // Drop category_id constraint and column
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');

            // Change jenis to string to hold multiple values (JSON or comma separated)
            $table->string('jenis')->nullable()->change();

            // Rename or replace tipe with jenis_indikator
            $table->dropColumn('tipe');
            $table->string('jenis_indikator')->nullable()->after('cara_pengumpulan');

            // Dimensi mutu to text/string so it can hold multiple values
            $table->string('dimensi_mutu')->nullable()->change();

            // Change target_operator to string to easily add '='
            $table->string('target_operator', 10)->default('>=')->change();

            // Drop penyajian_data if it's strictly auto-generated now
            $table->dropColumn('penyajian_data');

            // Add validator and pengumpul_data
            $table->string('validator')->nullable()->after('penanggung_jawab');
            $table->string('pengumpul_data')->nullable()->after('validator');
        });
    }

    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('indicator_categories');
            $table->enum('tipe', ['struktur', 'proses', 'outcome'])->default('proses');
            $table->dropColumn(['jenis_indikator', 'validator', 'pengumpul_data']);
            $table->string('penyajian_data')->nullable();
        });
    }
};
