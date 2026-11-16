<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['superadmin', 'komite_mutu', 'kepala_unit', 'pic_unit', 'direksi'])
                  ->default('pic_unit')
                  ->after('email');
            $table->foreignId('unit_id')->nullable()->after('role')
                  ->constrained('units')->nullOnDelete();
            $table->boolean('aktif')->default(true)->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['role', 'unit_id', 'aktif']);
        });
    }
};
