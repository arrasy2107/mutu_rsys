<?php

use App\Models\PatientSafetyIncident;
use App\Models\ReportLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('unit users can only view evidence for incidents connected to their unit', function () {
    Storage::fake('private');
    Storage::fake('public');

    $assignedUnit = Unit::create([
        'kode' => 'UNIT-A',
        'nama' => 'Unit A',
    ]);
    $otherUnit = Unit::create([
        'kode' => 'UNIT-B',
        'nama' => 'Unit B',
    ]);
    $user = User::factory()->create([
        'username' => 'pic_unit_a',
        'role' => 'pic_unit',
        'unit_id' => $assignedUnit->id,
    ]);

    $ownIncident = PatientSafetyIncident::create([
        'nomor_laporan' => 'IKP/2026/01/OWN',
        'tanggal_laporan' => '2026-01-01',
        'tanggal_kejadian' => '2026-01-01 08:00:00',
        'tipe_insiden' => 'Keselamatan Pasien',
        'insiden' => 'Insiden unit sendiri',
        'kronologi' => 'Catatan insiden untuk pengujian.',
        'jenis_insiden' => 'KTC',
        'grading' => 'biru',
        'unit_pelapor_id' => $assignedUnit->id,
        'bukti_rtl_path' => 'legacy/own-evidence.pdf',
    ]);
    $otherIncident = PatientSafetyIncident::create([
        'nomor_laporan' => 'IKP/2026/01/OTHER',
        'tanggal_laporan' => '2026-01-01',
        'tanggal_kejadian' => '2026-01-01 09:00:00',
        'tipe_insiden' => 'Keselamatan Pasien',
        'insiden' => 'Insiden unit lain',
        'kronologi' => 'Catatan insiden unit lain untuk pengujian.',
        'jenis_insiden' => 'KTC',
        'grading' => 'biru',
        'unit_pelapor_id' => $otherUnit->id,
        'bukti_rtl_path' => 'legacy/other-evidence.pdf',
    ]);

    Storage::disk('public')->put('legacy/own-evidence.pdf', 'own proof');
    Storage::disk('public')->put('legacy/other-evidence.pdf', 'other proof');

    $this->actingAs($user)
        ->get(route('evidence.show', ['type' => 'ikp', 'record' => $ownIncident->id]))
        ->assertOk()
        ->assertStreamedContent('own proof');

    $this->actingAs($user)
        ->get(route('evidence.show', ['type' => 'ikp', 'record' => $otherIncident->id]))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('ikp.show', $otherIncident))
        ->assertNotFound();

    $this->actingAs($user)
        ->post(route('ikp.upload-bukti', $otherIncident))
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('ikp.destroy', $otherIncident))
        ->assertNotFound();
});

test('report files are downloadable only by their owner', function () {
    Storage::fake('private');

    $owner = User::factory()->create([
        'username' => 'report_owner',
        'role' => 'superadmin',
    ]);
    $otherUser = User::factory()->create([
        'username' => 'report_other',
        'role' => 'superadmin',
    ]);
    $report = ReportLog::create([
        'user_id' => $owner->id,
        'jenis' => 'bulanan',
        'tahun' => 2026,
        'bulan' => 1,
        'format' => 'pdf',
        'file_path' => 'reports/report.pdf',
    ]);
    Storage::disk('private')->put($report->file_path, 'private report contents');

    $this->actingAs($owner)
        ->get(route('reports.download', $report))
        ->assertDownload('report.pdf')
        ->assertStreamedContent('private report contents');

    $this->actingAs($otherUser)
        ->get(route('reports.download', $report))
        ->assertNotFound();
});

test('new incident evidence is stored on the private disk', function () {
    Storage::fake('private');

    $unit = Unit::create([
        'kode' => 'IKP-UPLOAD',
        'nama' => 'Unit Upload',
    ]);
    $user = User::factory()->create([
        'username' => 'ikp_uploader',
        'role' => 'superadmin',
    ]);

    $response = $this->actingAs($user)->post(route('ikp.store'), [
        'nomor_laporan' => 'IKP/2026/01/UPLOAD',
        'tanggal_laporan' => '2026-01-01',
        'tanggal_kejadian' => '2026-01-01 08:00:00',
        'tipe_insiden' => 'Keselamatan Pasien',
        'insiden' => 'Insiden dengan lampiran',
        'kronologi' => 'Catatan insiden untuk pengujian.',
        'jenis_insiden' => 'KTC',
        'grading' => 'biru',
        'unit_pelapor_id' => $unit->id,
        'bukti_rtl' => UploadedFile::fake()->create('evidence.pdf', 10, 'application/pdf'),
    ]);

    $response->assertRedirect(route('ikp.index'));
    $incident = PatientSafetyIncident::where('nomor_laporan', 'IKP/2026/01/UPLOAD')->firstOrFail();

    expect($incident->bukti_rtl_path)->toStartWith('ikp_bukti/');
    Storage::disk('private')->assertExists($incident->bukti_rtl_path);
});

test('evidence downloads require authentication', function () {
    $this->get(route('evidence.show', ['type' => 'ikp', 'record' => 1]))
        ->assertRedirect(route('login'));
});
