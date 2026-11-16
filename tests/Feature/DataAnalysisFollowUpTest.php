<?php

use App\Models\DataEntry;
use App\Models\Indicator;
use App\Models\IndicatorPeriod;
use App\Models\Unit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createDataAnalysisEntry(
    User $user,
    Indicator $indicator,
    Unit $unit,
    int $year,
    int $month,
    float $numerator,
    float $denominator
): DataEntry {
    $period = IndicatorPeriod::create([
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => $year,
        'bulan' => $month,
    ]);

    return DataEntry::create([
        'indicator_id' => $indicator->id,
        'period_id' => $period->id,
        'unit_id' => $unit->id,
        'user_id' => $user->id,
        'numerator' => $numerator,
        'denominator' => $denominator,
        'status' => 'approved',
    ]);
}

function createDataAnalysisFixture(): array
{
    $unit = Unit::create(['kode' => 'TEST', 'nama' => 'Unit Test']);
    $user = User::factory()->create([
        'role' => 'superadmin',
        'unit_id' => $unit->id,
        'must_change_password' => false,
    ]);

    $indicator = Indicator::create([
        'kode' => 'TEST-1',
        'judul' => 'Indikator Test',
        'jenis' => ['IMP-RS'],
        'target_capaian' => 80,
        'target_operator' => '>=',
        'unit_id' => $unit->id,
        'is_active' => true,
    ]);

    return [$user, $unit, $indicator];
}

it('provides monthly RTL and PDSA actions including the previous December for January', function () {
    [$user, $unit, $indicator] = createDataAnalysisFixture();
    createDataAnalysisEntry($user, $indicator, $unit, 2025, 12, 60, 100);
    createDataAnalysisEntry($user, $indicator, $unit, 2026, 1, 80, 100);
    createDataAnalysisEntry($user, $indicator, $unit, 2026, 2, 60, 100);
    createDataAnalysisEntry($user, $indicator, $unit, 2026, 12, 90, 100);

    $this->actingAs($user)
        ->get(route('data-analysis.index', ['unit_id' => $unit->id, 'tahun' => 2026]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('DataAnalysis/Index')
            ->where('analysisData.0.months.1.action_type', 'pdsa')
            ->where('analysisData.0.months.1.previous_capaian', 60)
            ->where('analysisData.0.months.2.action_type', 'rtl')
            ->where('analysisData.0.months.2.warna', 'yellow')
            ->where('analysisData.0.months.3.action_type', null)
            ->where('analysisData.0.months.12.capaian', 90)
            ->where('analysisData.0.months.12.action_type', null)
        );
});

it('persists RTL as a distinct follow-up type without PDSA phase fields', function () {
    [$user, $unit, $indicator] = createDataAnalysisFixture();
    $period = IndicatorPeriod::create([
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 2,
    ]);

    $this->actingAs($user)
        ->post(route('pdsa.store'), [
            'jenis_tindak_lanjut' => 'rtl',
            'indicator_id' => $indicator->id,
            'period_id' => $period->id,
            'unit_id' => $unit->id,
            'capaian_aktual' => 60,
            'target_capaian' => 80,
            'masalah' => 'Capaian belum memenuhi target.',
            'rekomendasi_tindak_lanjut' => 'Lakukan perbaikan proses.',
            'plan' => 'Tidak disimpan untuk RTL.',
        ])
        ->assertRedirect(route('pdsa.index'));

    $this->assertDatabaseHas('pdsa_records', [
        'indicator_id' => $indicator->id,
        'period_id' => $period->id,
        'jenis_tindak_lanjut' => 'rtl',
        'plan' => null,
    ]);
});
