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
            // Data awal snapshot dari Form Pengumpul Data (Unit)
            $table->decimal('unit_numerator', 10, 2)->nullable()->after('catatan_validator');
            $table->decimal('unit_denominator', 10, 2)->nullable()->after('unit_numerator');
            $table->decimal('unit_capaian', 10, 2)->nullable()->after('unit_denominator');
            $table->json('unit_data_snapshot')->nullable()->after('unit_capaian');

            // Data hasil uji petik telaah independen oleh Validator (Komite Mutu)
            $table->decimal('validator_numerator', 10, 2)->nullable()->after('unit_data_snapshot');
            $table->decimal('validator_denominator', 10, 2)->nullable()->after('validator_numerator');
            $table->decimal('validator_capaian', 10, 2)->nullable()->after('validator_denominator');

            // Metrik kesesuaian dan kelulusan validasi
            $table->decimal('tingkat_kesesuaian', 5, 2)->nullable()->after('validator_capaian');
            $table->boolean('is_valid')->default(false)->after('tingkat_kesesuaian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validations', function (Blueprint $table) {
            $table->dropColumn([
                'unit_numerator',
                'unit_denominator',
                'unit_capaian',
                'unit_data_snapshot',
                'validator_numerator',
                'validator_denominator',
                'validator_capaian',
                'tingkat_kesesuaian',
                'is_valid',
            ]);
        });
    }
};

