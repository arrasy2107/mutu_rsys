<?php

namespace App\Http\Controllers;

use App\Models\DataEntry;
use App\Models\IndicatorSelection;
use App\Models\PatientSafetyIncident;
use App\Models\PdsaRecord;
use App\Models\User;
use App\Models\Validation;
use App\Services\PrivateEvidenceStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function show(
        Request $request,
        string $type,
        int $record,
        PrivateEvidenceStorage $storage,
    ): StreamedResponse {
        $user = $request->user();
        $path = match ($type) {
            'data-entry' => $this->dataEntryPath($user, $record),
            'validation' => $this->validationPath($user, $record),
            'indicator-selection' => $this->indicatorSelectionPath($user, $record),
            'pdsa' => $this->pdsaPath($user, $record),
            'ikp' => $this->incidentPath($user, $record),
            default => abort(404),
        };

        abort_if(! $path, 404);

        return $storage->response($path, $request->boolean('download'));
    }

    private function dataEntryPath(User $user, int $record): ?string
    {
        $entry = DataEntry::with(['unit', 'indicator'])->findOrFail($record);

        abort_if(
            ! $entry->unit ||
                ! $entry->indicator ||
                ($entry->indicator->unit_id !== null && (int) $entry->indicator->unit_id !== (int) $entry->unit_id),
            404
        );
        $this->authorizeUnitEvidence($user, (int) $entry->unit_id);

        return $entry->file_bukti;
    }

    private function validationPath(User $user, int $record): ?string
    {
        $validation = Validation::with('dataEntry.unit', 'dataEntry.indicator')->findOrFail($record);
        $entry = $validation->dataEntry;

        abort_if(
            ! $entry ||
                ! $entry->unit ||
                ! $entry->indicator ||
                ($entry->indicator->unit_id !== null && (int) $entry->indicator->unit_id !== (int) $entry->unit_id),
            404
        );
        $this->authorizeUnitEvidence($user, (int) $entry->unit_id);

        return $validation->lampiran_file;
    }

    private function indicatorSelectionPath(User $user, int $record): ?string
    {
        $selection = IndicatorSelection::findOrFail($record);

        if ($user->isPicUnit() || $user->isKepalaUnit()) {
            abort_if(
                $selection->tipe !== 'unit' ||
                    (int) $selection->unit_id !== (int) $user->unit_id,
                404
            );
        } else {
            abort_unless($this->canViewAllUnits($user), 404);
        }

        return $selection->berkas_pendukung;
    }

    private function pdsaPath(User $user, int $record): ?string
    {
        $pdsa = PdsaRecord::findOrFail($record);
        $this->authorizeUnitEvidence($user, $pdsa->unit_id ? (int) $pdsa->unit_id : null);

        return $pdsa->bukti_rtl;
    }

    private function incidentPath(User $user, int $record): ?string
    {
        $incident = PatientSafetyIncident::findOrFail($record);

        if (! $this->canViewAllUnits($user)) {
            $canViewUnitIncident = ($user->isPicUnit() || $user->isKepalaUnit()) &&
                $user->unit_id !== null &&
                in_array((int) $user->unit_id, [
                    (int) $incident->unit_pelapor_id,
                    (int) $incident->unit_terkait_id,
                ], true);

            abort_unless($canViewUnitIncident, 404);
        }

        return $incident->bukti_rtl_path;
    }

    private function authorizeUnitEvidence(User $user, ?int $unitId): void
    {
        abort_unless(
            $this->canViewAllUnits($user) ||
                (($user->isPicUnit() || $user->isKepalaUnit()) &&
                    $unitId !== null &&
                    (int) $user->unit_id === $unitId),
            404
        );
    }

    private function canViewAllUnits(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isKomiteMutu() || $user->isDireksi();
    }
}
