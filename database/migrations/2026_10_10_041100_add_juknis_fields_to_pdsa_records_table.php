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
            $table->text('analisa_penyebab_manusia')->nullable()->after('masalah');
            $table->text('analisa_penyebab_metode')->nullable()->after('analisa_penyebab_manusia');
            $table->text('analisa_penyebab_alat')->nullable()->after('analisa_penyebab_metode');
            $table->text('analisa_penyebab_material')->nullable()->after('analisa_penyebab_alat');
            $table->text('analisa_penyebab_lingkungan')->nullable()->after('analisa_penyebab_material');
            
            $table->text('rekomendasi_tindak_lanjut')->nullable()->after('akar_masalah');

            // Convert existing short strings to text if possible (using change)
            $table->text('akar_masalah')->nullable()->change();
            $table->text('plan')->nullable()->change();
            $table->text('do_action')->nullable()->change();
            $table->text('study')->nullable()->change();
            $table->text('act')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdsa_records', function (Blueprint $table) {
            $table->dropColumn([
                'analisa_penyebab_manusia',
                'analisa_penyebab_metode',
                'analisa_penyebab_alat',
                'analisa_penyebab_material',
                'analisa_penyebab_lingkungan',
                'rekomendasi_tindak_lanjut'
            ]);
            
            // Revert back to string is not strictly required but good practice
            $table->string('akar_masalah')->nullable()->change();
            $table->string('plan')->nullable()->change();
            $table->string('do_action')->nullable()->change();
            $table->string('study')->nullable()->change();
            $table->string('act')->nullable()->change();
        });
    }
};
