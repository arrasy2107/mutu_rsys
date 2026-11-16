<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->string('frekuensi', 50)->default('Bulanan')->change();
            $table->string('sumber_data', 255)->nullable()->change();
            $table->text('jenis_indikator')->nullable()->change();
        });

        // Normalisasi data existing: frekuensi
        DB::table('indicators')->where('frekuensi', 'bulanan')->update(['frekuensi' => 'Bulanan']);
        DB::table('indicators')->where('frekuensi', 'harian')->update(['frekuensi' => 'Bulanan']);

        // Normalisasi data existing: jenis_indikator ke JSON array jika masih string tunggal
        $indicators = DB::table('indicators')->whereNotNull('jenis_indikator')->get();
        foreach ($indicators as $ind) {
            $val = trim($ind->jenis_indikator);
            if (!empty($val) && !str_starts_with($val, '[')) {
                DB::table('indicators')->where('id', $ind->id)->update([
                    'jenis_indikator' => json_encode([$val]),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->enum('frekuensi', ['harian', 'bulanan'])->default('bulanan')->change();
            $table->enum('sumber_data', ['manual', 'simrs'])->default('manual')->change();
            $table->string('jenis_indikator')->nullable()->change();
        });
    }
};
