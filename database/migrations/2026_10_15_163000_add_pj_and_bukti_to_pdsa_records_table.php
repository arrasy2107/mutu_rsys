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
        Schema::table('pdsa_records', function (Blueprint $table) {
            $table->string('pj_rtl')->nullable()->after('rekomendasi_tindak_lanjut');
            $table->text('bukti_rtl')->nullable()->after('pj_rtl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdsa_records', function (Blueprint $table) {
            $table->dropColumn(['pj_rtl', 'bukti_rtl']);
        });
    }
};
