<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_entries', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'revision', 'exempt'])
                ->default('draft')
                ->change();
        });

        DB::table('data_entries')
            ->where('status', 'draft')
            ->where('denominator', '>', 0)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('validations as validation')
                    ->join('data_entries as passed_entry', 'passed_entry.id', '=', 'validation.data_entry_id')
                    ->join('indicator_periods as passed_period', 'passed_period.id', '=', 'passed_entry.period_id')
                    ->join('indicator_periods as target_period', 'target_period.id', '=', 'data_entries.period_id')
                    ->whereColumn('passed_entry.unit_id', 'data_entries.unit_id')
                    ->whereColumn('passed_entry.indicator_id', 'data_entries.indicator_id')
                    ->whereColumn('passed_period.tahun', 'target_period.tahun')
                    ->where('validation.status', 'approved')
                    ->where(function ($validityQuery): void {
                        $validityQuery->where('validation.is_valid', true)
                            ->orWhere(function ($legacyQuery): void {
                                $legacyQuery->whereNull('validation.is_valid')
                                    ->where('validation.tingkat_kesesuaian', '>=', 90);
                            });
                    })
                    ->whereNotExists(function ($revisionQuery): void {
                        $revisionQuery->selectRaw('1')
                            ->from('validations as later_revision')
                            ->whereColumn('later_revision.data_entry_id', 'validation.data_entry_id')
                            ->where('later_revision.status', 'revision')
                            ->whereColumn('later_revision.id', '>', 'validation.id');
                    });
            })
            ->update(['status' => 'exempt']);

        Schema::table('validations', function (Blueprint $table) {
            $table->foreignId('actor_id')->nullable()->after('validator_id')->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('validator_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('data_entries')
            ->where('status', 'exempt')
            ->update(['status' => 'draft']);

        DB::table('validations')
            ->whereNull('validator_id')
            ->whereNotNull('actor_id')
            ->update(['validator_id' => DB::raw('actor_id')]);

        Schema::table('validations', function (Blueprint $table) {
            $table->dropForeign(['actor_id']);
            $table->dropColumn('actor_id');
            $table->unsignedBigInteger('validator_id')->nullable(false)->change();
        });

        Schema::table('data_entries', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'revision'])
                ->default('draft')
                ->change();
        });
    }
};
