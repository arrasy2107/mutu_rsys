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
            $table->text('pernyataan_risiko')->change();
            $table->text('penanggung_jawab')->nullable()->change();
            $table->text('alternatif_penanganan')->nullable()->change();
            $table->text('jenis_alternatif')->nullable()->change();
            $table->text('pemilik_risiko')->nullable()->change();
            $table->text('status_tren')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risk_registers', function (Blueprint $table) {
            $table->string('pernyataan_risiko')->change();
        });
    }
};

