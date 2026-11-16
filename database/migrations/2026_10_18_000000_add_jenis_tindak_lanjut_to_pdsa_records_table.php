<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdsa_records', function (Blueprint $table) {
            $table->string('jenis_tindak_lanjut')->default('pdsa')->after('siklus');
        });

        DB::table('pdsa_records')
            ->whereNotNull('rekomendasi_tindak_lanjut')
            ->whereNull('plan')
            ->whereNull('do_action')
            ->whereNull('study')
            ->whereNull('act')
            ->update(['jenis_tindak_lanjut' => 'rtl']);
    }

    public function down(): void
    {
        Schema::table('pdsa_records', function (Blueprint $table) {
            $table->dropColumn('jenis_tindak_lanjut');
        });
    }
};
