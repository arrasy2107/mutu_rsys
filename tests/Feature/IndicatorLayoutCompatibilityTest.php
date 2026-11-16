<?php

use App\Models\Indicator;
use App\Models\Unit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('allows legacy spreadsheet layouts to be retained when updating an indicator profile', function () {
    $unit = Unit::create(['kode' => 'TEST', 'nama' => 'Unit Test']);
    $user = User::factory()->create([
        'role' => 'superadmin',
        'unit_id' => $unit->id,
        'must_change_password' => false,
    ]);
    $indicator = Indicator::create([
        'kode' => 'TEST-1',
        'judul' => 'Indikator Test',
        'jenis' => ['IMU'],
        'target_capaian' => 80,
        'target_operator' => '>=',
        'unit_id' => $unit->id,
        'form_layout_type' => 'dynamic_rows',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->put(route('indicators.update', $indicator), [
            'kode' => 'TEST-1',
            'judul' => 'Indikator Test',
            'jenis' => ['IMU'],
            'dimensi_mutu' => ['Efektif'],
            'satuan_pengukuran' => 'Persentase',
            'jenis_indikator' => ['Proses'],
            'target_capaian' => 80,
            'target_operator' => '>=',
            'frekuensi' => 'Bulanan',
            'form_layout_type' => 'dynamic_rows',
            'is_active' => true,
        ])
        ->assertRedirect(route('indicators.show', $indicator))
        ->assertSessionHasNoErrors();

    expect($indicator->fresh()->form_layout_type)->toBe('dynamic_rows');
});

it('keeps spreadsheet editing in data entry and omits the separate format builder', function (
    string $role,
    bool $indicatorIsUnitSpecific,
    bool $canSaveMetricMappingDefault
) {
    $unit = Unit::create(['kode' => 'TEST', 'nama' => 'Unit Test']);
    $user = User::factory()->create([
        'role' => $role,
        'unit_id' => $unit->id,
        'must_change_password' => false,
    ]);
    $indicator = Indicator::create([
        'kode' => 'TEST-1',
        'judul' => 'Indikator Test',
        'jenis' => ['IMU'],
        'unit_id' => $indicatorIsUnitSpecific ? $unit->id : null,
        'form_layout_type' => 'dynamic_rows',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('indicators.show', $indicator))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Indicators/Show')
            ->missing('canManageFormats')
            ->missing('unitIndicators'));

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('DataEntry/Index')
            ->where('selectedIndicator.id', $indicator->id)
            ->where('permissions.canSaveMetricMappingDefault', $canSaveMetricMappingDefault)
            ->missing('allUnitIndicators')
            ->missing('unitCustomFormats')
            ->missing('currentFormat'));
})->with([
    'hospital administrator' => ['superadmin', true, true],
    'unit PIC manages their own format' => ['pic_unit', true, true],
    'unit PIC cannot manage hospital format' => ['pic_unit', false, false],
    'director remains read-only' => ['direksi', true, false],
]);
