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
            $table->dropUnique('indicators_kode_unique');
            $table->unique(['unit_id', 'kode'], 'indicators_unit_id_kode_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->dropUnique('indicators_unit_id_kode_unique');
            $table->unique('kode', 'indicators_kode_unique');
        });
    }
};
