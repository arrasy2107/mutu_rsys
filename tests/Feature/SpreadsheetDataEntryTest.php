<?php

use App\Models\CustomFormFormat;
use App\Models\DataEntry;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\IndicatorDailyRecord;
use App\Models\IndicatorFormField;
use App\Models\ReportLog;
use App\Models\Unit;
use App\Models\User;
use App\Models\Validation;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;

function createSpreadsheetDataEntryFixture(string $rowType, array $columns, array $rows = []): array
{
    $unit = Unit::create(['kode' => 'TEST', 'nama' => 'Unit Test']);
    $category = IndicatorCategory::create(['kode' => 'TEST', 'nama' => 'Kategori Test']);
    $user = User::factory()->create([
        'role' => 'superadmin',
        'unit_id' => $unit->id,
        'must_change_password' => false,
    ]);

    $indicator = Indicator::create([
        'kode' => 'TEST-1',
        'judul' => 'Indikator Test',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'form_layout_type' => $rowType,
        'form_schema' => [
            'renderer' => 'spreadsheet',
            'schema_version' => 1,
            'columns' => $columns,
            'rows' => $rows,
        ],
        'is_active' => true,
    ]);

    return [$user, $unit, $indicator];
}

function spreadsheetMetricMappingWorkbook(array $metricMapping): array
{
    return [
        'entry_metadata' => [
            'logical_row_count' => 2,
            'metric_mapping' => $metricMapping,
        ],
        'sheetOrder' => ['mapping-sheet'],
        'sheets' => [
            'mapping-sheet' => [
                'id' => 'mapping-sheet',
                'name' => 'Form Pengumpul Data',
                'rowCount' => 3,
                'columnCount' => 2,
                'cellData' => [
                    0 => [
                        0 => ['v' => 'Kolom A'],
                        1 => ['v' => 'Kolom B'],
                    ],
                    1 => [
                        0 => ['v' => 2],
                        1 => ['v' => 4],
                    ],
                    2 => [
                        0 => ['v' => 3],
                        1 => ['v' => 6],
                    ],
                ],
            ],
        ],
    ];
}

it('recalculates spreadsheet dynamic-row totals instead of trusting the submitted summary', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'notes', 'type' => 'text', 'role' => 'auxiliary'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['_id' => 'row-1', 'compliant' => true, 'sample' => 1, 'notes' => 'Sampel 1'],
                ['_id' => 'row-2', 'compliant' => false, 'sample' => 1, 'notes' => 'Sampel 2'],
            ],
            'summary' => ['numerator' => 99, 'denominator' => 99, 'capaian' => 100],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(1.0)
        ->and((float) $entry->denominator)->toBe(2.0)
        ->and((float) $entry->capaian)->toBe(50.0);
});

it('persists an additional dynamic spreadsheet row and includes it in monthly totals', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['compliant' => true, 'sample' => 1],
                ['compliant' => false, 'sample' => 1],
                ['compliant' => true, 'sample' => 1],
            ],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect(count($entry->log_harian))->toBe(3)
        ->and($entry->log_harian[2])->toMatchArray([
            'compliant' => true,
            'sample' => 1,
        ])
        ->and((float) $entry->numerator)->toBe(2.0)
        ->and((float) $entry->denominator)->toBe(3.0)
        ->and((float) $entry->capaian)->toBe(66.67);
});

it('persists and reloads the edited workbook structure for its period', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $workbookSnapshot = [
        'entry_metadata' => [
            'logical_row_count' => 3,
        ],
        'id' => 'period-workbook',
        'name' => 'Period Workbook',
        'appVersion' => '1.3.1',
        'locale' => 'enUS',
        'styles' => [
            'centered' => [
                'ht' => 'center',
                'vt' => 'middle',
                'tb' => 'wrap',
            ],
        ],
        'sheetOrder' => ['test-sheet'],
        'sheets' => [
            'test-sheet' => [
                'id' => 'test-sheet',
                'name' => 'Form Pengumpul Data',
                'rowCount' => 5,
                'columnCount' => 2,
                'rowData' => [
                    4 => ['h' => 40],
                ],
                'columnData' => [
                    1 => ['w' => 120],
                ],
                'mergeData' => [
                    [
                        'startRow' => 0,
                        'endRow' => 0,
                        'startColumn' => 0,
                        'endColumn' => 1,
                    ],
                ],
                'cellData' => [
                    0 => [0 => ['v' => 'Judul Form']],
                    1 => [
                        0 => ['v' => 'Numerator'],
                        1 => ['v' => 'Denominator'],
                    ],
                    4 => [
                        0 => [
                            's' => 'centered',
                            'p' => [
                                'id' => 'rich-text-cell',
                                'body' => [
                                    'dataStream' => 'Paragraf dengan format.',
                                    'paragraphs' => [
                                        [
                                            'startIndex' => 0,
                                            'paragraphId' => 'paragraph-1',
                                            'paragraphStyle' => [
                                                'horizontalAlignment' => 'center',
                                            ],
                                        ],
                                    ],
                                ],
                                'documentStyle' => [],
                            ],
                        ],
                        1 => ['v' => 4],
                    ],
                ],
            ],
        ],
        'defined_names' => [],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['numerator' => 1, 'denominator' => 2],
                ['numerator' => 1, 'denominator' => 2],
                ['numerator' => 2, 'denominator' => 4],
            ],
            'workbook_snapshot' => $workbookSnapshot,
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect($entry->workbook_snapshot)->toBe($workbookSnapshot);

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentPeriodEntry.workbook_snapshot.entry_metadata.logical_row_count', 3)
            ->where('currentPeriodEntry.workbook_snapshot.sheets.test-sheet.rowCount', 5)
            ->where('currentPeriodEntry.workbook_snapshot.sheets.test-sheet.mergeData.0.endColumn', 1)
            ->where('currentPeriodEntry.workbook_snapshot.sheets.test-sheet.rowData.4.h', 40)
            ->where('currentPeriodEntry.workbook_snapshot.sheets.test-sheet.columnData.1.w', 120)
            ->where('currentPeriodEntry.workbook_snapshot.styles.centered.ht', 'center')
            ->where('currentPeriodEntry.workbook_snapshot.sheets.test-sheet.cellData.4.0.s', 'centered')
            ->where(
                'currentPeriodEntry.workbook_snapshot.sheets.test-sheet.cellData.4.0.p.body.dataStream',
                'Paragraf dengan format.',
            )
            ->where('currentPeriodEntry.workbook_snapshot.sheets.test-sheet.cellData.4.1.v', 4));

    $this->actingAs($user)
        ->post(route('data-entry.submit-period'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
        ])
        ->assertRedirect();

    $validation = Validation::where('data_entry_id', $entry->id)->firstOrFail();
    expect($validation->unit_data_snapshot['workbook_snapshot'])->toBe($workbookSnapshot)
        ->and($validation->unit_data_snapshot['form_schema_version'])->toBe(1);
});

it('preserves inserted spreadsheet columns without changing numerator and denominator totals', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'first', 'title' => 'First', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'numerator', 'title' => 'Numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'title' => 'Denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $workbookSnapshot = [
        'sheetOrder' => ['test-sheet'],
        'sheets' => [
            'test-sheet' => [
                'rowCount' => 6,
                'columnCount' => 4,
                'cellData' => [
                    1 => [
                        0 => ['v' => 'First'],
                        1 => ['v' => 'Numerator'],
                        2 => ['v' => 'Catatan Tambahan'],
                        3 => ['v' => 'Denominator'],
                    ],
                ],
                'mergeData' => [],
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                [
                    'first' => 1,
                    'numerator' => 99,
                    'col_3' => 'Catatan baris',
                    'denominator' => 2,
                    '__formulas' => ['numerator' => '=A3+D3'],
                ],
            ],
            'workbook_snapshot' => $workbookSnapshot,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();
    expect($entry->log_harian[0])->toMatchArray([
        'first' => 1,
        'numerator' => 3,
        'col_3' => 'Catatan baris',
        'denominator' => 2,
    ])
        ->and((float) $entry->numerator)->toBe(3.0)
        ->and((float) $entry->denominator)->toBe(2.0)
        ->and($entry->workbook_snapshot)->toBe($workbookSnapshot);
});

it('rejects spreadsheet record keys that do not match configured or inserted columns', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'title' => 'Numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'title' => 'Denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->from(route('data-entry.index', [
            'unit_id' => $unit->id,
            'indicator_id' => $indicator->id,
            'tahun' => 2026,
            'bulan' => 1,
        ]))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['numerator' => 1, 'denominator' => 2, 'unexpected' => 'Tidak dikenal'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('records');

    expect(DataEntry::count())->toBe(0);
});

it('rejects a workbook snapshot that does not contain its declared worksheet', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->from(route('data-entry.index', [
            'unit_id' => $unit->id,
            'indicator_id' => $indicator->id,
            'tahun' => 2026,
            'bulan' => 1,
        ]))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
            'workbook_snapshot' => [
                'sheetOrder' => ['missing-sheet'],
                'sheets' => [
                    'different-sheet' => [
                        'rowCount' => 3,
                        'columnCount' => 2,
                        'cellData' => [],
                        'mergeData' => [],
                    ],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('workbook_snapshot');

    expect(DataEntry::count())->toBe(0);
});

it('rejects a period workbook with more than 500 logical input rows', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->from(route('data-entry.index', [
            'unit_id' => $unit->id,
            'indicator_id' => $indicator->id,
            'tahun' => 2026,
            'bulan' => 1,
        ]))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
            'workbook_snapshot' => [
                'entry_metadata' => ['logical_row_count' => 501],
                'sheetOrder' => ['test-sheet'],
                'sheets' => [
                    'test-sheet' => [
                        'rowCount' => 3,
                        'columnCount' => 2,
                        'cellData' => [],
                        'mergeData' => [],
                    ],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('workbook_snapshot.entry_metadata.logical_row_count');

    expect(DataEntry::count())->toBe(0);
});

it('excludes a spreadsheet total row from saved observations and monthly metrics', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'label', 'type' => 'text', 'role' => 'label'],
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $records = [
        ['label' => 'Pengamatan 1', 'numerator' => 1, 'denominator' => 12],
        ['label' => 'Pengamatan 2', 'numerator' => 1, 'denominator' => 12],
        ['label' => 'Pengamatan 3', 'numerator' => 1, 'denominator' => 12],
        ['label' => 'Pengamatan 4', 'numerator' => 1, 'denominator' => 12],
        ['label' => 'Pengamatan 5', 'numerator' => 0, 'denominator' => 12],
        ['label' => 'Pengamatan 6', 'numerator' => 0, 'denominator' => 10],
        ['label' => 'Pengamatan 7', 'numerator' => 0, 'denominator' => 11],
        [
            'label' => 'Total',
            'numerator' => 4,
            'denominator' => 81,
            '__formulas' => [
                'numerator' => '=SUM(B3:B9)',
                'denominator' => '=SUM(C3:C9)',
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => $records,
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect(count($entry->log_harian))->toBe(7)
        ->and((float) $entry->numerator)->toBe(4.0)
        ->and((float) $entry->denominator)->toBe(81.0)
        ->and((float) $entry->capaian)->toBe(4.94);
});

it('excludes formula-only aggregate rows even when they have no summary label', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['numerator' => 1, 'denominator' => 3],
                ['numerator' => 0, 'denominator' => 2],
                [
                    'numerator' => 1,
                    'denominator' => 5,
                    '__formulas' => [
                        'numerator' => '=SUM(B3:B4)',
                        'denominator' => '=SUM(C3:C4)',
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect(count($entry->log_harian))->toBe(2)
        ->and((float) $entry->numerator)->toBe(1.0)
        ->and((float) $entry->denominator)->toBe(5.0);
});

it('preserves a dynamic observation whose label happens to be a summary word', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'label', 'type' => 'text', 'role' => 'label'],
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['label' => 'Total', 'numerator' => 1, 'denominator' => 2],
            ],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect(count($entry->log_harian))->toBe(1)
        ->and($entry->log_harian[0]['label'])->toBe('Total')
        ->and((float) $entry->numerator)->toBe(1.0)
        ->and((float) $entry->denominator)->toBe(2.0);
});

it('recalculates spreadsheet fixed-row totals from configured numerator and denominator columns', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('fixed_rows', [
        ['key' => 'label', 'type' => 'text', 'role' => 'label'],
        ['key' => 'compliant', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ], [
        ['key' => 'row_1', 'label' => 'Kategori 1'],
        ['key' => 'row_2', 'label' => 'Kategori 2'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                'row_1' => ['compliant' => 3, 'sample' => 4],
                'row_2' => ['compliant' => 2, 'sample' => 6],
            ],
            'summary' => ['numerator' => 100, 'denominator' => 100, 'capaian' => 100],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(5.0)
        ->and((float) $entry->denominator)->toBe(10.0)
        ->and((float) $entry->capaian)->toBe(50.0);
});

it('recalculates spreadsheet formulas on the server before calculating indicator totals', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'first', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'second', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'compliant', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                [
                    'first' => 4,
                    'second' => 5,
                    'compliant' => 999,
                    'sample' => 10,
                    '__formulas' => ['compliant' => '=A3+B3'],
                ],
            ],
            'summary' => ['numerator' => 999, 'denominator' => 1, 'capaian' => 999],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(9.0)
        ->and((float) $entry->denominator)->toBe(10.0)
        ->and((float) $entry->capaian)->toBe(90.0)
        ->and($entry->log_harian[0]['compliant'])->toBe(9)
        ->and($entry->log_harian[0]['__formulas']['compliant'])->toBe('=A3+B3');
});

it('uses workbook metric summary ranges without dropping rows outside those ranges', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'date', 'title' => 'Tanggal', 'type' => 'text', 'role' => 'auxiliary'],
        ['key' => 'denominator', 'title' => 'Denominator', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'numerator', 'title' => 'Numerator', 'type' => 'number', 'role' => 'numerator'],
    ]);
    $indicator->update([
        'form_schema' => [
            'schema_version' => 1,
            'row_type' => 'dynamic_rows',
            'columns' => $indicator->form_schema['columns'],
        ],
    ]);

    $records = [
        ['date' => '2 Mei', 'denominator' => 5, 'numerator' => 0],
        ['date' => '4 Mei', 'denominator' => 6, 'numerator' => 0],
        ['date' => '9 Mei', 'denominator' => 2, 'numerator' => 2],
        ['date' => '11 Mei', 'denominator' => 3, 'numerator' => 0],
        ['date' => '16 Mei', 'denominator' => 2, 'numerator' => 2],
        ['date' => '18 Mei', 'denominator' => 8, 'numerator' => 0],
        ['date' => '23 Mei', 'denominator' => 15, 'numerator' => 0],
        ['date' => '25 Mei', 'denominator' => 8, 'numerator' => 0],
        ['date' => '30 Mei', 'denominator' => 8, 'numerator' => 0],
    ];
    $workbookSnapshot = [
        'sheetOrder' => ['test-sheet'],
        'sheets' => [
            'test-sheet' => [
                'rowCount' => 12,
                'columnCount' => 3,
                'cellData' => array_replace(
                    [
                        0 => [
                            0 => ['v' => 'Tanggal'],
                            1 => ['v' => 'Denominator'],
                            2 => ['v' => 'Numerator'],
                        ],
                    ],
                    array_combine(
                        range(1, count($records)),
                        array_map(
                            fn (array $row): array => [
                                0 => ['v' => $row['date']],
                                1 => ['v' => $row['denominator']],
                                2 => ['v' => $row['numerator']],
                            ],
                            $records
                        )
                    ),
                    [
                        10 => [
                            1 => ['f' => '=SUM(B2:B9)', 'v' => 999],
                            2 => ['f' => '=SUM(C2:C9)', 'v' => 999],
                        ],
                    ]
                ),
                'mergeData' => [],
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 5,
            'records' => $records,
            'workbook_snapshot' => $workbookSnapshot,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();

    expect((float) $entry->numerator)->toBe(4.0)
        ->and((float) $entry->denominator)->toBe(49.0)
        ->and(count($entry->log_harian))->toBe(9);
});

it('saves formulas when data occupies a configured header row', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'denominator', 'title' => 'Configured denominator', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'numerator', 'title' => 'Configured numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'rate', 'title' => 'Configured rate', 'type' => 'number', 'role' => 'auxiliary'],
    ]);

    $workbookSnapshot = [
        'sheetOrder' => ['test-sheet'],
        'sheets' => [
            'test-sheet' => [
                'rowCount' => 4,
                'columnCount' => 3,
                'cellData' => [
                    0 => [
                        0 => ['v' => 'Denominator'],
                        1 => ['v' => 'Numerator'],
                        2 => ['v' => 'Rate'],
                    ],
                    1 => [
                        0 => ['v' => 5],
                        1 => ['v' => 4],
                        2 => ['f' => '=(B2/A2)*100', 'v' => 80],
                    ],
                ],
                'mergeData' => [],
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                [
                    'denominator' => 5,
                    'numerator' => 4,
                    'rate' => 80,
                    '__formulas' => ['rate' => '=(B2/A2)*100'],
                ],
            ],
            'workbook_snapshot' => $workbookSnapshot,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(4.0)
        ->and((float) $entry->denominator)->toBe(5.0)
        ->and((float) $entry->capaian)->toBe(80.0)
        ->and((float) $entry->log_harian[0]['rate'])->toBe(80.0)
        ->and($entry->log_harian[0]['__formulas']['rate'])->toBe('=(B2/A2)*100')
        ->and($entry->workbook_snapshot)->toBe($workbookSnapshot);
});

it('recalculates formula columns for newly added rows using their translated row references', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'first', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'second', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'total', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                [
                    'first' => 4,
                    'second' => 5,
                    'total' => '',
                    'sample' => 10,
                    '__formulas' => ['total' => '=A3+B3'],
                ],
                [
                    'first' => 8,
                    'second' => 2,
                    'total' => '',
                    'sample' => 10,
                    '__formulas' => ['total' => '=A4+B4'],
                ],
            ],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect($entry->log_harian[0]['total'])->toBe(9)
        ->and($entry->log_harian[1]['total'])->toBe(10)
        ->and((float) $entry->numerator)->toBe(19.0)
        ->and((float) $entry->denominator)->toBe(20.0)
        ->and((float) $entry->capaian)->toBe(95.0);
});

it('recalculates formulas for legacy category layouts and preserves their stored shape', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('grouped_category', []);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                'categories' => [
                    'category_1' => ['sample' => 10, 'compliant' => 99, 'total_minutes' => 0],
                ],
                '__spreadsheet' => [
                    'layout_type' => 'grouped_category',
                    'row_type' => 'fixed_rows',
                    'header_rows' => 2,
                    'columns' => [
                        ['key' => 'label', 'type' => 'text', 'role' => 'label'],
                        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
                        ['key' => 'compliant', 'type' => 'number', 'role' => 'numerator'],
                    ],
                    'rows' => [
                        ['key' => 'category_1', 'label' => 'Kategori 1'],
                    ],
                    'records' => [
                        'category_1' => [
                            'sample' => 10,
                            'compliant' => 99,
                            '__formulas' => ['compliant' => '=B3*2'],
                        ],
                    ],
                ],
            ],
            'summary' => ['numerator' => 99, 'denominator' => 1, 'capaian' => 999],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(20.0)
        ->and((float) $entry->denominator)->toBe(10.0)
        ->and($entry->log_harian['categories']['category_1']['compliant'])->toBe(20)
        ->and($entry->log_harian['categories']['category_1']['__formulas']['compliant'])->toBe('=B3*2')
        ->and(array_key_exists('__spreadsheet', $entry->log_harian))->toBeFalse();
});

it('recalculates spreadsheet formulas for daily-grid records before tally rollup', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('daily_grid', []);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                '1' => ['num' => 99, 'den' => 4],
                '__spreadsheet' => [
                    'layout_type' => 'daily_grid',
                    'row_type' => 'fixed_rows',
                    'header_rows' => 2,
                    'columns' => [
                        ['key' => 'day', 'type' => 'text', 'role' => 'label'],
                        ['key' => 'weekday', 'type' => 'text', 'role' => 'label'],
                        ['key' => 'num', 'type' => 'number', 'role' => 'numerator'],
                        ['key' => 'den', 'type' => 'number', 'role' => 'denominator'],
                        ['key' => 'capaian', 'type' => 'number', 'role' => 'auxiliary'],
                    ],
                    'rows' => [
                        ['key' => '1', 'label' => '1', 'values' => ['day' => 1, 'weekday' => 'Kam']],
                    ],
                    'records' => [
                        '1' => [
                            'num' => 99,
                            'den' => 4,
                            '__formulas' => ['num' => '=D3'],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(4.0)
        ->and((float) $entry->denominator)->toBe(4.0)
        ->and($entry->log_harian[1]['data']['num'])->toBe(4)
        ->and(array_key_exists('__spreadsheet', $entry->log_harian))->toBeFalse();
});

it('saves text-only daily-grid formula errors without blocking the period', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('daily_grid', []);
    $columns = [
        ['key' => 'day', 'type' => 'text', 'role' => 'label'],
        ['key' => 'weekday', 'type' => 'text', 'role' => 'label'],
        ['key' => 'date', 'type' => 'text', 'role' => 'label'],
        ['key' => 'den', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'num', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'col_6', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'col_7', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'col_8', 'type' => 'text', 'role' => 'auxiliary'],
    ];
    $row = [
        'den' => 0,
        'num' => 0,
        'col_6' => 0,
        'col_7' => 0,
        'col_8' => 0,
        '__formulas' => ['col_8' => '=(E3/D3)*100'],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 3,
            'records' => [
                '1' => $row,
                '__spreadsheet' => [
                    'layout_type' => 'daily_grid',
                    'row_type' => 'fixed_rows',
                    'header_rows' => 2,
                    'columns' => $columns,
                    'rows' => [
                        ['key' => '1', 'label' => '1', 'values' => ['day' => 1, 'weekday' => 'Kam']],
                    ],
                    'records' => ['1' => $row],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();

    expect($entry->log_harian[1]['data']['col_8'])->toBe('#DIV/0!')
        ->and($entry->log_harian[1]['data']['__formulas']['col_8'])->toBe('=(E3/D3)*100');
});

it('rejects daily-grid formula errors in numeric metrics with a cell-specific message', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('daily_grid', []);
    $columns = [
        ['key' => 'day', 'type' => 'text', 'role' => 'label'],
        ['key' => 'weekday', 'type' => 'text', 'role' => 'label'],
        ['key' => 'date', 'type' => 'text', 'role' => 'label'],
        ['key' => 'den', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'num', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'col_6', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'col_7', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'col_8', 'type' => 'text', 'role' => 'auxiliary'],
    ];
    $row = [
        'den' => 0,
        'num' => 0,
        'col_6' => 0,
        'col_7' => 0,
        'col_8' => 0,
        '__formulas' => ['num' => '=1/0'],
    ];

    $this->actingAs($user)
        ->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 3,
            'records' => [
                '1' => $row,
                '__spreadsheet' => [
                    'layout_type' => 'daily_grid',
                    'row_type' => 'fixed_rows',
                    'header_rows' => 2,
                    'columns' => $columns,
                    'rows' => [
                        ['key' => '1', 'label' => '1', 'values' => ['day' => 1, 'weekday' => 'Kam']],
                    ],
                    'records' => ['1' => $row],
                ],
            ],
        ])
        ->assertSessionHasErrors([
            'records' => 'Rumus pada sel E3 menghasilkan #DIV/0!. Periksa referensi sel dan nilai yang digunakan.',
        ]);

    expect(IndicatorDailyRecord::count())->toBe(0)
        ->and(DataEntry::count())->toBe(0);
});

it('persists edited additional columns in daily-grid spreadsheet records', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('daily_grid', []);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => ['1' => ['num' => 1, 'den' => 2]],
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                '1' => [
                    'num' => 99,
                    'den' => 4,
                    'col_5' => 99,
                    '__formulas' => [
                        'num' => '=D3',
                        'col_5' => '=D3+1',
                    ],
                ],
                '__spreadsheet' => [
                    'layout_type' => 'daily_grid',
                    'row_type' => 'fixed_rows',
                    'header_rows' => 2,
                    'columns' => [
                        ['key' => 'day', 'type' => 'text', 'role' => 'label'],
                        ['key' => 'weekday', 'type' => 'text', 'role' => 'label'],
                        ['key' => 'num', 'type' => 'number', 'role' => 'numerator'],
                        ['key' => 'den', 'type' => 'number', 'role' => 'denominator'],
                        ['key' => 'capaian', 'type' => 'number', 'role' => 'auxiliary'],
                        ['key' => 'col_5', 'type' => 'number', 'role' => 'auxiliary'],
                    ],
                    'rows' => [
                        ['key' => '1', 'label' => '1', 'values' => ['day' => 1, 'weekday' => 'Kam']],
                    ],
                    'records' => [
                        '1' => [
                            'num' => 99,
                            'den' => 4,
                            'col_5' => 99,
                            '__formulas' => [
                                'num' => '=D3',
                                'col_5' => '=D3+1',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $dailyRecord = IndicatorDailyRecord::where('indicator_id', $indicator->id)
        ->where('unit_id', $unit->id)
        ->where('hari', 1)
        ->firstOrFail();
    $entry = DataEntry::firstOrFail();

    expect($dailyRecord->data)->toMatchArray([
        'num' => 4,
        'den' => 4,
        'col_5' => 5,
    ])
        ->and($dailyRecord->data['__formulas']['col_5'])->toBe('=D3+1')
        ->and((float) $entry->numerator)->toBe(4.0)
        ->and((float) $entry->denominator)->toBe(4.0)
        ->and(DataEntry::count())->toBe(1);

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dailyRecords.1.col_5', 5)
            ->where('dailyRecords.1.__formulas.col_5', '=D3+1'));
});

it('removes a deleted daily-grid row from the saved period records', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('daily_grid', []);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                '1' => ['num' => 1, 'den' => 2],
                '2' => ['num' => 2, 'den' => 3],
            ],
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                '2' => ['num' => 5, 'den' => 6],
                '__spreadsheet' => [
                    'layout_type' => 'daily_grid',
                    'row_type' => 'fixed_rows',
                    'header_rows' => 2,
                    'columns' => [
                        ['key' => 'day', 'type' => 'text', 'role' => 'label'],
                        ['key' => 'weekday', 'type' => 'text', 'role' => 'label'],
                        ['key' => 'num', 'type' => 'number', 'role' => 'numerator'],
                        ['key' => 'den', 'type' => 'number', 'role' => 'denominator'],
                    ],
                    'rows' => [
                        ['key' => '2', 'label' => '2', 'values' => ['day' => 2, 'weekday' => 'Jum']],
                    ],
                    'records' => [
                        '2' => ['num' => 5, 'den' => 6],
                    ],
                ],
            ],
            'workbook_snapshot' => [
                'entry_metadata' => ['logical_row_count' => 1],
                'sheetOrder' => ['test-sheet'],
                'sheets' => [
                    'test-sheet' => [
                        'rowCount' => 510,
                        'columnCount' => 4,
                        'cellData' => [
                            1 => [
                                0 => ['v' => 'Tanggal'],
                                1 => ['v' => 'Hari'],
                                2 => ['v' => 'Numerator'],
                                3 => ['v' => 'Denominator'],
                            ],
                            2 => [
                                0 => ['v' => 2],
                                1 => ['v' => 'Jum'],
                                2 => ['v' => 5],
                                3 => ['v' => 6],
                            ],
                        ],
                        'mergeData' => [],
                    ],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();

    expect(array_keys($entry->log_harian))->toBe([2])
        ->and((float) $entry->numerator)->toBe(5.0)
        ->and((float) $entry->denominator)->toBe(6.0);
    $this->assertDatabaseMissing('indicator_daily_records', [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'hari' => 1,
    ]);
});

it('recalculates formulas for legacy dynamic patient-register rows', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('patient_register', []);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                [
                    'num' => 99,
                    'den' => 4,
                    '__formulas' => ['num' => '=B3'],
                ],
                '__spreadsheet' => [
                    'layout_type' => 'patient_register',
                    'row_type' => 'dynamic_rows',
                    'header_rows' => 2,
                    'columns' => [
                        ['key' => 'num', 'type' => 'number', 'role' => 'numerator'],
                        ['key' => 'den', 'type' => 'number', 'role' => 'denominator'],
                    ],
                    'rows' => [],
                    'records' => [
                        [
                            'num' => 99,
                            'den' => 4,
                            '__formulas' => ['num' => '=B3'],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(4.0)
        ->and((float) $entry->denominator)->toBe(1.0)
        ->and($entry->log_harian[0]['num'])->toBe(4)
        ->and($entry->log_harian[0]['__formulas']['num'])->toBe('=B3')
        ->and(array_key_exists('__spreadsheet', $entry->log_harian))->toBeFalse();
});

it('rejects spreadsheet formulas that use functions outside the supported safe set', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                [
                    'compliant' => 1,
                    'sample' => 1,
                    '__formulas' => ['compliant' => '=INDIRECT(A3)'],
                ],
            ],
        ])
        ->assertSessionHasErrors('records');

    expect(DataEntry::count())->toBe(0);
});

it('rejects spreadsheet cells that violate their configured input type', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['compliant' => true, 'sample' => -1],
            ],
        ])
        ->assertSessionHasErrors('records');

    expect(DataEntry::count())->toBe(0);
});

it('rejects non-numeric text in a legacy text-typed metric column', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'text', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['numerator' => 'Sesuai', 'denominator' => 1],
            ],
        ])
        ->assertSessionHasErrors('records');

    expect(DataEntry::count())->toBe(0);
});

it('rejects saving a spreadsheet format with text-typed numerator columns', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->from(route('data-entry.index'))
        ->post(route('custom-form-formats.store'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'nama_format' => 'Format Spreadsheet Test',
            'row_type' => 'dynamic_rows',
            'schema' => [
                'renderer' => 'spreadsheet',
                'columns' => [
                    ['key' => 'numerator', 'type' => 'text', 'role' => 'numerator'],
                    ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
                ],
            ],
        ])
        ->assertSessionHasErrors('schema');

    expect(CustomFormFormat::count())->toBe(0);
});

it('does not save spreadsheet data when the indicator belongs to another unit', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $otherUnit = Unit::create(['kode' => 'OTHER', 'nama' => 'Unit Lain']);

    $this->actingAs($user)
        ->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $otherUnit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 1]],
        ])
        ->assertSessionHas('error', 'Indikator tidak terdaftar pada unit yang dipilih.');

    expect(DataEntry::count())->toBe(0);
});

it('keeps rejection status, period status, and validation history consistent', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ])->assertRedirect();

    $this->post(route('validation.reject', $entry->id), [
        'catatan' => 'Data perlu dikumpulkan kembali.',
        'validator_num' => 1,
        'validator_den' => 2,
    ])->assertRedirect();

    $rejectedEntry = $entry->fresh();
    expect($rejectedEntry->status)->toBe('rejected')
        ->and($rejectedEntry->period->status)->toBe('open')
        ->and($rejectedEntry->latestValidation->status)->toBe('rejected')
        ->and($rejectedEntry->latestValidation->catatan_validator)->toBe('Data perlu dikumpulkan kembali.');
});

it('does not allow validation decisions on a draft entry', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $invalidDecisions = [
        ['validation.approve', ['validator_num' => 1, 'validator_den' => 2]],
        ['validation.reject', ['catatan' => 'Tidak sesuai.', 'validator_num' => 1, 'validator_den' => 2]],
        ['validation.revision', ['catatan' => 'Mohon diperbaiki.']],
    ];

    foreach ($invalidDecisions as [$routeName, $payload]) {
        $this->from(route('validation.show', $entry))
            ->post(route($routeName, $entry), $payload)
            ->assertRedirect(route('validation.show', $entry))
            ->assertSessionHas('error', 'Data tidak lagi menunggu validasi. Muat ulang halaman untuk melihat status terbaru.');
    }

    expect($entry->fresh()->status)->toBe('draft')
        ->and($entry->period->fresh()->status)->toBe('open')
        ->and(Validation::where('data_entry_id', $entry->id)->count())->toBe(0);
});

it('allows quality administrators to update an approved validation record', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $entry->update(['status' => 'approved']);
    $entry->period->update(['status' => 'validated']);
    Validation::create([
        'data_entry_id' => $entry->id,
        'validator_id' => $user->id,
        'status' => 'approved',
        'validator_numerator' => 1,
        'validator_denominator' => 2,
        'is_valid' => true,
    ]);

    $this->post(route('validation.approve', $entry), [
        'validator_num' => 2,
        'validator_den' => 2,
    ])->assertRedirect(route('validation.show', $entry));

    expect($entry->fresh()->status)->toBe('approved')
        ->and(Validation::where('data_entry_id', $entry->id)->count())->toBe(2)
        ->and((float) $entry->latestValidation->fresh()->validator_numerator)->toBe(2.0);
});

it('blocks edits to approved data in the current period', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $year = (int) now()->format('Y');
    $month = (int) now()->format('n');

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => $year,
            'bulan' => $month,
            'records' => [['numerator' => 1, 'denominator' => 1]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $entry->update(['status' => 'approved']);

    $this->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => $year,
            'bulan' => $month,
            'records' => [['numerator' => 0, 'denominator' => 1]],
        ])
        ->assertSessionHas('error', 'Data yang sudah disetujui pada periode berjalan atau mendatang tidak dapat diubah.');

    expect((float) $entry->fresh()->numerator)->toBe(1.0);
});

it('prevents unit staff from editing approved data in a historical period', function () {
    [$admin, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $previousPeriod = now()->subMonth();
    $year = (int) $previousPeriod->format('Y');
    $month = (int) $previousPeriod->format('n');

    $this->actingAs($admin)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => $year,
            'bulan' => $month,
            'records' => [['numerator' => 1, 'denominator' => 1]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $entry->update(['status' => 'approved']);
    $pic = User::factory()->create([
        'role' => 'pic_unit',
        'unit_id' => $unit->id,
        'must_change_password' => false,
    ]);

    $this->actingAs($pic)
        ->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => $year,
            'bulan' => $month,
            'records' => [['numerator' => 0, 'denominator' => 1]],
        ])
        ->assertSessionHas(
            'error',
            'Data yang sudah disetujui hanya dapat dibuka kembali oleh Administrator.'
        );

    expect((float) $entry->fresh()->numerator)->toBe(1.0)
        ->and($entry->fresh()->status)->toBe('approved')
        ->and(Validation::where('data_entry_id', $entry->id)->exists())->toBeFalse();
});

it('allows edits to approved data in a historical period', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $previousPeriod = now()->subMonth();
    $year = (int) $previousPeriod->format('Y');
    $month = (int) $previousPeriod->format('n');

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => $year,
            'bulan' => $month,
            'records' => [['numerator' => 1, 'denominator' => 1]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $entry->update(['status' => 'approved']);
    $entry->period->update([
        'status' => 'validated',
        'tanggal_validasi' => now(),
        'validated_by' => $user->id,
    ]);
    Validation::create([
        'data_entry_id' => $entry->id,
        'validator_id' => $user->id,
        'status' => 'approved',
        'unit_numerator' => 1,
        'unit_denominator' => 1,
        'unit_capaian' => 100,
        'is_valid' => true,
    ]);

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => $year,
        'bulan' => $month,
        'records' => [['numerator' => 0, 'denominator' => 1]],
    ])->assertRedirect();

    $updatedEntry = $entry->fresh();
    expect((float) $updatedEntry->numerator)->toBe(0.0)
        ->and($updatedEntry->status)->toBe('revision')
        ->and($updatedEntry->period->status)->toBe('open')
        ->and($updatedEntry->latestValidation->status)->toBe('revision')
        ->and($updatedEntry->latestValidation->validator_id)->toBeNull()
        ->and($updatedEntry->latestValidation->actor_id)->toBe($user->id)
        ->and($updatedEntry->latestValidation->is_valid)->toBeFalse();

    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => $year,
        'bulan' => $month,
    ])->assertRedirect();

    $resubmittedEntry = $entry->fresh();
    expect($resubmittedEntry->status)->toBe('submitted')
        ->and($resubmittedEntry->latestValidation->status)->toBe('submitted')
        ->and(Validation::getIndicatorValidationStatus(
            $unit->id,
            $indicator->id,
            $year,
            (int) now()->format('n'),
        )['is_exempt'])->toBeFalse();

    $indicator->update(['target_capaian' => 0]);
    $this->post(route('validation.approve', $entry->id), [
        'validator_num' => 0,
        'validator_den' => 1,
    ])->assertRedirect();

    $approvedEntry = $entry->fresh();
    expect($approvedEntry->status)->toBe('approved')
        ->and($approvedEntry->latestValidation->status)->toBe('approved')
        ->and($approvedEntry->period->status)->toBe('validated')
        ->and(Validation::where('data_entry_id', $entry->id)->count())->toBe(4);
});

it('enforces the 500-row spreadsheet limit without replacing saved rows', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $records = array_fill(0, 500, ['numerator' => 0, 'denominator' => 1]);
    $requestData = [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'records' => $records,
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), $requestData)
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect(count($entry->log_harian))->toBe(500)
        ->and((float) $entry->denominator)->toBe(500.0);

    $requestData['records'][] = ['numerator' => 0, 'denominator' => 1];
    $this->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), $requestData)
        ->assertSessionHasErrors('records');

    expect(count($entry->fresh()->log_harian))->toBe(500);
});

it('persists the selected spreadsheet renderer and schema version with a custom form', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'nama_format' => 'Format Spreadsheet Test',
            'table_title' => 'Data Pengamatan',
            'row_type' => 'dynamic_rows',
            'schema' => [
                'renderer' => 'spreadsheet',
                'engine' => 'univer',
                'header_rows' => 2,
                'schema_version' => 1,
                'columns' => [
                    ['key' => 'unit_name', 'type' => 'text', 'role' => 'auxiliary'],
                    ['key' => 'date', 'type' => 'date', 'role' => 'auxiliary'],
                    ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
                    ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
                    ['key' => 'additional_compliant', 'type' => 'number', 'role' => 'numerator'],
                    ['key' => 'additional_sample', 'type' => 'number', 'role' => 'denominator'],
                ],
                'rows' => [],
                'formula_config' => [
                    'numerator_cols' => ['compliant', 'additional_compliant'],
                    'denominator_cols' => ['sample', 'additional_sample'],
                    'numerator_col' => 'compliant',
                    'denominator_col' => 'sample',
                    'numerator_cell' => 'E9',
                    'denominator_cell' => 'F9',
                ],
                'workbook_template' => [
                    'id' => 'test-workbook',
                    'sheetOrder' => ['test-sheet'],
                    'sheets' => [
                        'test-sheet' => [
                            'mergeData' => [
                                ['startRow' => 0, 'endRow' => 0, 'startColumn' => 0, 'endColumn' => 1],
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    expect($indicator->fresh()->form_schema['renderer'])->toBe('spreadsheet')
        ->and($indicator->fresh()->form_schema['engine'])->toBe('univer')
        ->and($indicator->fresh()->form_schema['header_rows'])->toBe(2)
        ->and($indicator->fresh()->form_schema['formula_config']['numerator_cols'])->toBe(['compliant', 'additional_compliant'])
        ->and($indicator->fresh()->form_schema['formula_config']['denominator_cols'])->toBe(['sample', 'additional_sample'])
        ->and($indicator->fresh()->form_schema['formula_config']['numerator_col'])->toBe('compliant')
        ->and($indicator->fresh()->form_schema['formula_config']['denominator_col'])->toBe('sample')
        ->and($indicator->fresh()->form_schema['formula_config']['numerator_cell'])->toBe('E9')
        ->and($indicator->fresh()->form_schema['formula_config']['denominator_cell'])->toBe('F9')
        ->and($indicator->fresh()->form_schema['workbook_template']['sheets']['test-sheet']['mergeData'][0]['endColumn'])->toBe(1)
        ->and($indicator->fresh()->form_schema['schema_version'])->toBe(1)
        ->and(CustomFormFormat::where('indicator_id', $indicator->id)->firstOrFail()->schema['columns'])
        ->toBe($indicator->fresh()->form_schema['columns']);
});

it('generates or preserves the table title when a custom form payload omits it', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $formPayload = [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'nama_format' => 'Format Spreadsheet Test',
        'row_type' => 'dynamic_rows',
        'schema' => [
            'renderer' => 'spreadsheet',
            'engine' => 'univer',
            'header_rows' => 2,
            'columns' => [
                ['key' => 'compliant', 'type' => 'number', 'role' => 'numerator'],
                ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), $formPayload)
        ->assertRedirect();

    $expectedTitle = "Tabel Pengumpulan Data: {$indicator->judul}";
    expect($indicator->fresh()->form_schema['table_title'])->toBe($expectedTitle);

    $formPayload['nama_format'] = 'Format Spreadsheet Updated';
    $this->post(route('custom-form-formats.store'), $formPayload)
        ->assertRedirect();

    expect($indicator->fresh()->form_schema['table_title'])->toBe($expectedTitle);
});

it('keeps old period data editable with the spreadsheet schema version it was entered against', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $formPayload = [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'nama_format' => 'Format Spreadsheet Test',
        'table_title' => 'Data Pengamatan',
        'row_type' => 'dynamic_rows',
        'schema' => [
            'renderer' => 'spreadsheet',
            'columns' => [
                ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
                ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
            ],
            'rows' => [],
        ],
    ];

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), $formPayload)
        ->assertRedirect();

    $formPayload['schema']['columns'][1]['key'] = 'total_sample';
    $formPayload['table_title'] = 'Format Pengamatan Terbaru';
    $this->post(route('custom-form-formats.store'), $formPayload)
        ->assertRedirect();

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'schema_version' => 1,
        'records' => [
            ['compliant' => true, 'sample' => 1],
            ['compliant' => false, 'sample' => 1],
        ],
        'summary' => ['numerator' => 100, 'denominator' => 100],
    ])->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect($indicator->fresh()->form_schema['schema_version'])->toBe(2)
        ->and($indicator->fresh()->form_schema['schema_history']['1']['columns'][1]['key'])->toBe('sample')
        ->and($entry->form_schema_version)->toBe(1)
        ->and((float) $entry->numerator)->toBe(1.0)
        ->and((float) $entry->denominator)->toBe(2.0);

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('entrySchema.schema_version', 1)
            ->where('entrySchema.columns.1.key', 'sample'));
});

it('preserves and serves historical schema versions from the indicator profile after schema edits', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $formPayload = [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'nama_format' => 'Format Spreadsheet Test',
        'table_title' => 'Data Pengamatan',
        'row_type' => 'dynamic_rows',
        'schema' => [
            'renderer' => 'spreadsheet',
            'columns' => [
                ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
                ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
            ],
            'rows' => [],
        ],
    ];

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), $formPayload)
        ->assertRedirect();

    $formPayload['schema']['columns'][1]['key'] = 'total_sample';

    $this->post(route('custom-form-formats.store'), $formPayload)
        ->assertRedirect();

    $format = CustomFormFormat::where('indicator_id', $indicator->id)->firstOrFail();
    expect($format->schema['schema_version'])->toBe(2)
        ->and($format->schema['schema_history']['1']['columns'][1]['key'])->toBe('sample');

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'schema_version' => 1,
        'records' => [
            ['compliant' => true, 'sample' => 1],
            ['compliant' => false, 'sample' => 1],
        ],
    ])->assertRedirect();

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('entrySchema.schema_version', 1)
            ->where('entrySchema.columns.1.key', 'sample'));
});

it('does not use a legacy custom format when the indicator profile has no spreadsheet schema', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'nama_format' => 'Format Lama',
            'row_type' => 'dynamic_rows',
            'schema' => [
                'renderer' => 'spreadsheet',
                'columns' => [
                    ['key' => 'legacy_n', 'type' => 'number', 'role' => 'numerator'],
                    ['key' => 'legacy_d', 'type' => 'number', 'role' => 'denominator'],
                ],
            ],
        ])
        ->assertRedirect();

    $indicator->update(['form_schema' => null]);

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('entrySchema', null)
            ->missing('currentFormat'));
});

it('rejects deleting a template referenced by existing period data and preserves both records', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'nama_format' => 'Format Spreadsheet Test',
            'row_type' => 'dynamic_rows',
            'schema' => [
                'renderer' => 'spreadsheet',
                'columns' => [
                    ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
                    ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
                ],
            ],
        ])
        ->assertRedirect();

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'records' => [
            ['compliant' => true, 'sample' => 1],
        ],
    ])->assertRedirect();

    $format = CustomFormFormat::where('indicator_id', $indicator->id)->firstOrFail();
    $entry = DataEntry::firstOrFail();

    $this->delete(route('custom-form-formats.destroy', $format))
        ->assertRedirect()
        ->assertSessionHas(
            'error',
            'Format tidak dapat dihapus karena sudah digunakan oleh data periode. Pertahankan format agar riwayat data tetap dapat dibuka.'
        );

    $this->assertModelExists($format);
    $this->assertModelExists($entry);
    expect($indicator->fresh()->form_schema['schema_version'])->toBe(1);
});

it('allows deleting a template when saved entries do not reference its schema version', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'nama_format' => 'Format Spreadsheet Test',
            'row_type' => 'dynamic_rows',
            'schema' => [
                'renderer' => 'spreadsheet',
                'columns' => [
                    ['key' => 'compliant', 'type' => 'checkbox', 'role' => 'numerator'],
                    ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
                ],
            ],
        ])
        ->assertRedirect();

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'records' => [['compliant' => true, 'sample' => 1]],
    ])->assertRedirect();

    $format = CustomFormFormat::where('indicator_id', $indicator->id)->firstOrFail();
    $entry = DataEntry::firstOrFail();
    $entry->update(['form_schema_version' => null]);

    $this->delete(route('custom-form-formats.destroy', $format))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(CustomFormFormat::whereKey($format->id)->exists())->toBeFalse()
        ->and($entry->fresh())->not->toBeNull()
        ->and($indicator->fresh()->form_schema)->toBeNull();
});

it('prevents users from another unit from accessing legacy data-entry routes and exports', function () {
    [$owner, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($owner)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $otherUnit = Unit::create(['kode' => 'OTHER', 'nama' => 'Unit Lain']);
    $otherUnitUser = User::factory()->create([
        'role' => 'pic_unit',
        'unit_id' => $otherUnit->id,
        'must_change_password' => false,
    ]);

    $this->actingAs($otherUnitUser)
        ->get(route('data-entry.show', $entry))
        ->assertNotFound();

    $this->get(route('data-entry.edit', $entry))
        ->assertNotFound();

    $this->put(route('data-entry.update', $entry), [
        'numerator' => 0,
        'denominator' => 2,
    ])->assertNotFound();

    $this->delete(route('data-entry.destroy', $entry))
        ->assertNotFound();

    $this->get(route('data-entry.export-excel', [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))->assertNotFound();

    expect($entry->fresh()->status)->toBe('draft')
        ->and((float) $entry->fresh()->numerator)->toBe(1.0);
});

it('allows authorized unit users to export their unit data', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $user->update(['role' => 'pic_unit']);

    $this->actingAs($user)
        ->get(route('data-entry.export-excel', [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('prevents changes to submitted data until validation returns it for revision', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ])->assertRedirect();

    $submittedEntry = $entry->fresh();
    $snapshot = $submittedEntry->latestValidation->unit_data_snapshot;

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'records' => [['numerator' => 0, 'denominator' => 2]],
    ])->assertSessionHas(
        'error',
        'Data yang sedang menunggu validasi tidak dapat diubah. Minta Administrator membuka revisi terlebih dahulu.'
    );

    expect((float) $submittedEntry->fresh()->numerator)->toBe(1.0)
        ->and($submittedEntry->fresh()->status)->toBe('submitted')
        ->and($submittedEntry->fresh()->latestValidation->unit_data_snapshot)->toBe($snapshot);
});

it('allows IGD staff to correct submitted data within the current year', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $this->travelTo(now()->setDate(2026, 11, 12)->setTime(12, 0));
    $unit->update(['kode' => 'IGD']);
    $user->update(['role' => 'pic_unit']);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 11,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 11,
    ])->assertRedirect();
    $submittedSnapshot = $entry->fresh()->latestValidation->unit_data_snapshot;

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 11,
        'records' => [['numerator' => 2, 'denominator' => 5]],
    ])->assertRedirectBackWithoutErrors();

    $correctedEntry = $entry->fresh();
    expect((float) $correctedEntry->numerator)->toBe(2.0)
        ->and((float) $correctedEntry->denominator)->toBe(5.0)
        ->and($correctedEntry->status)->toBe('revision')
        ->and($correctedEntry->period->status)->toBe('open')
        ->and($correctedEntry->latestValidation->status)->toBe('revision')
        ->and($correctedEntry->latestValidation->actor_id)->toBe($user->id)
        ->and($correctedEntry->validations()->count())->toBe(2)
        ->and($correctedEntry->validations()->where('status', 'submitted')->first()->unit_data_snapshot)
        ->toBe($submittedSnapshot);
});

it('allows IGD staff to correct an approved January entry and saves spreadsheet formatting', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $this->travelTo(now()->setDate(2026, 11, 12)->setTime(12, 0));
    $unit->update(['kode' => 'IGD']);
    $user->update(['role' => 'pic_unit']);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $entry->update(['status' => 'approved']);
    $entry->period->update(['status' => 'validated']);
    Validation::create([
        'data_entry_id' => $entry->id,
        'validator_id' => $user->id,
        'status' => 'approved',
        'unit_numerator' => 1,
        'unit_denominator' => 2,
        'unit_capaian' => 50,
        'is_valid' => true,
    ]);
    $workbookSnapshot = [
        'styles' => [
            'centered' => [
                'ht' => 'center',
                'vt' => 'middle',
                'tb' => 'wrap',
            ],
        ],
        'sheetOrder' => ['igd-sheet'],
        'sheets' => [
            'igd-sheet' => [
                'rowCount' => 3,
                'columnCount' => 2,
                'rowData' => [1 => ['h' => 36]],
                'columnData' => [0 => ['w' => 125]],
                'mergeData' => [[
                    'startRow' => 0,
                    'endRow' => 0,
                    'startColumn' => 0,
                    'endColumn' => 1,
                ]],
                'cellData' => [
                    0 => [
                        0 => ['v' => 'Numerator', 's' => 'centered'],
                        1 => ['v' => 'Denominator', 's' => 'centered'],
                    ],
                    1 => [
                        0 => ['v' => 2],
                        1 => ['v' => 5],
                    ],
                ],
            ],
        ],
    ];

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'records' => [['numerator' => 2, 'denominator' => 5]],
        'workbook_snapshot' => $workbookSnapshot,
    ])->assertRedirectBackWithoutErrors();

    $correctedEntry = $entry->fresh();
    expect((float) $correctedEntry->numerator)->toBe(2.0)
        ->and((float) $correctedEntry->denominator)->toBe(5.0)
        ->and($correctedEntry->status)->toBe('revision')
        ->and($correctedEntry->period->status)->toBe('open')
        ->and($correctedEntry->latestValidation->status)->toBe('revision')
        ->and($correctedEntry->latestValidation->actor_id)->toBe($user->id)
        ->and($correctedEntry->workbook_snapshot)->toBe($workbookSnapshot)
        ->and($correctedEntry->validations()->where('status', 'approved')->exists())->toBeTrue();
});

it('exposes temporary IGD correction access only from January through the current month', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $this->travelTo(now()->setDate(2026, 11, 12)->setTime(12, 0));
    $unit->update(['kode' => 'IGD']);
    $user->update(['role' => 'pic_unit']);

    $this->actingAs($user)
        ->get(route('data-entry.index', [
            'unit_id' => $unit->id,
            'indicator_id' => $indicator->id,
            'tahun' => 2026,
            'bulan' => 1,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('permissions.canTemporarilyCorrectPeriod', true));

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 12,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('permissions.canTemporarilyCorrectPeriod', false));

    $unit->update(['kode' => 'TEST']);

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('permissions.canTemporarilyCorrectPeriod', false));
});

it('does not reopen submitted IGD data for a future period', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $this->travelTo(now()->setDate(2026, 11, 12)->setTime(12, 0));
    $unit->update(['kode' => 'IGD']);
    $user->update(['role' => 'pic_unit']);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 12,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 12,
    ])->assertRedirect();

    $this->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 12,
            'records' => [['numerator' => 0, 'denominator' => 2]],
        ])
        ->assertSessionHas(
            'error',
            'Data yang sedang menunggu validasi tidak dapat diubah. Minta Administrator membuka revisi terlebih dahulu.'
        );

    expect((float) $entry->fresh()->numerator)->toBe(1.0)
        ->and($entry->fresh()->status)->toBe('submitted');
});

it('requires saved denominator data before a period can be submitted', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 0, 'denominator' => 0]],
        ])
        ->assertRedirect();

    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ])->assertSessionHas(
        'error',
        'Data indikator belum memiliki denominator. Lengkapi dan simpan data sebelum mengajukan validasi.'
    );

    expect(DataEntry::firstOrFail()->status)->toBe('draft')
        ->and(Validation::count())->toBe(0);
});

it('calculates daily-grid totals from configured numerator and denominator field keys', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('daily_grid', []);
    IndicatorFormField::create([
        'indicator_id' => $indicator->id,
        'name' => 'Jumlah Patuh',
        'key' => 'compliant_count',
        'type' => 'numerator',
        'order' => 1,
    ]);
    IndicatorFormField::create([
        'indicator_id' => $indicator->id,
        'name' => 'Jumlah Sampel',
        'key' => 'sample_count',
        'type' => 'denominator',
        'order' => 2,
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                1 => ['compliant_count' => 3, 'sample_count' => 4],
                2 => ['compliant_count' => 2, 'sample_count' => 6],
            ],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(5.0)
        ->and((float) $entry->denominator)->toBe(10.0)
        ->and((float) $entry->capaian)->toBe(50.0)
        ->and($entry->log_harian[1]['data']['compliant_count'])->toBe(3)
        ->and($entry->log_harian[1]['data']['sample_count'])->toBe(4);
});

it('rejects invalid daily-grid metrics without saving any of the submitted month', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('daily_grid', []);

    $this->actingAs($user)
        ->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                1 => ['num' => -1, 'den' => 2],
            ],
        ])
        ->assertSessionHasErrors('records');

    expect(DataEntry::count())->toBe(0)
        ->and(IndicatorDailyRecord::count())->toBe(0);
});

it('loads historical data using its saved layout after the indicator format changes', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'compliant', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $baseFormat = [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'nama_format' => 'Format Baris Dinamis',
        'row_type' => 'dynamic_rows',
        'schema' => [
            'renderer' => 'spreadsheet',
            'engine' => 'univer',
            'columns' => [
                ['key' => 'compliant', 'type' => 'number', 'role' => 'numerator'],
                ['key' => 'sample', 'type' => 'number', 'role' => 'denominator'],
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), $baseFormat)
        ->assertRedirect();

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'schema_version' => 1,
        'records' => [
            ['compliant' => 2, 'sample' => 4],
        ],
    ])->assertRedirect();

    $newFormat = $baseFormat;
    $newFormat['row_type'] = 'daily_grid';
    $this->post(route('custom-form-formats.store'), $newFormat)
        ->assertRedirect();

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('entrySchema.row_type', 'dynamic_rows')
            ->where('dailyRecords.0.compliant', 2)
            ->where('dailyRecords.0.sample', 4));
});

it('does not render a historical entry with a missing schema version', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $entry->update(['form_schema_version' => 99]);

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertStatus(409)
        ->assertSee('Versi format yang digunakan untuk data periode ini tidak tersedia.');
});

it('limits unit staff indicator choices to profiles owned by their unit', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $otherUnit = Unit::create(['kode' => 'OTHER', 'nama' => 'Unit Lain']);
    $category = IndicatorCategory::firstOrCreate([
        'kode' => 'OTHER',
    ], [
        'nama' => 'Kategori Lain',
    ]);

    Indicator::create([
        'kode' => 'OTHER-1',
        'judul' => 'Indikator Unit Lain',
        'category_id' => $category->id,
        'unit_id' => $otherUnit->id,
        'form_layout_type' => 'dynamic_rows',
        'is_active' => true,
    ]);
    Indicator::create([
        'kode' => 'RS-1',
        'judul' => 'Indikator Rumah Sakit',
        'category_id' => $category->id,
        'unit_id' => null,
        'form_layout_type' => 'dynamic_rows',
        'is_active' => true,
    ]);

    foreach (['pic_unit', 'kepala_unit'] as $role) {
        $user->update(['role' => $role]);

        $this->actingAs($user)
            ->get(route('data-entry.index', [
                'unit_id' => $unit->id,
                'indicator_id' => $indicator->id,
                'tahun' => 2026,
                'bulan' => 1,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('unitIndicators', fn ($indicators) => count($indicators) === 1
                    && $indicators[0]['id'] === $indicator->id)
                ->where('selectedIndicator.id', $indicator->id));
    }
});

it('provides the current validation decision even when a prior pass exempts the month', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    foreach ([1, 2] as $month) {
        $this->actingAs($user)
            ->post(route('data-entry.save-daily-records'), [
                'indicator_id' => $indicator->id,
                'unit_id' => $unit->id,
                'tahun' => 2026,
                'bulan' => $month,
                'records' => [['numerator' => 1, 'denominator' => 2]],
            ])
            ->assertRedirect();
    }

    $januaryEntry = DataEntry::whereHas('period', fn ($query) => $query->where('bulan', 1))->firstOrFail();
    $januaryEntry->update(['status' => 'approved']);
    Validation::create([
        'data_entry_id' => $januaryEntry->id,
        'validator_id' => $user->id,
        'status' => 'approved',
        'is_valid' => true,
    ]);

    $februaryEntry = DataEntry::whereHas('period', fn ($query) => $query->where('bulan', 2))->firstOrFail();
    $februaryEntry->update(['status' => 'rejected']);
    Validation::create([
        'data_entry_id' => $februaryEntry->id,
        'validator_id' => $user->id,
        'status' => 'rejected',
        'catatan_validator' => 'Dokumen pendukung belum lengkap.',
    ]);

    $this->get(route('data-entry.index', [
        'unit_id' => $unit->id,
        'indicator_id' => $indicator->id,
        'tahun' => 2026,
        'bulan' => 2,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('validationStatus.is_exempt', true)
            ->where('currentPeriodEntry.status', 'rejected')
            ->where('currentPeriodEntry.latest_validation.catatan_validator', 'Dokumen pendukung belum lengkap.'));
});

it('exports configured spreadsheet columns and historical row values', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $formPayload = [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'nama_format' => 'Form Observasi Unit',
        'row_type' => 'dynamic_rows',
        'schema' => [
            'renderer' => 'spreadsheet',
            'engine' => 'univer',
            'columns' => [
                ['key' => 'observed_item', 'title' => 'Item Observasi', 'type' => 'text', 'role' => 'label'],
                ['key' => 'compliant', 'title' => 'Sesuai Standar', 'type' => 'number', 'role' => 'numerator'],
                ['key' => 'sample', 'title' => 'Jumlah Sampel', 'type' => 'number', 'role' => 'denominator'],
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), $formPayload)
        ->assertRedirect();

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'records' => [
            ['observed_item' => 'Pemeriksaan A', 'compliant' => 2, 'sample' => 3],
        ],
    ])->assertRedirect();

    $response = $this->get(route('data-entry.export-excel', [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))->assertOk()->assertDownload();

    $temporaryPath = tempnam(sys_get_temp_dir(), 'form-export-');
    $xlsxPath = $temporaryPath.'.xlsx';
    unlink($temporaryPath);
    file_put_contents($xlsxPath, $response->streamedContent());

    try {
        $export = IOFactory::load($xlsxPath);
        $sheet = $export->getActiveSheet();

        expect($sheet->getCell('A6')->getValue())->toBe('Item Observasi')
            ->and($sheet->getCell('B6')->getValue())->toBe('Sesuai Standar (N)')
            ->and($sheet->getCell('C6')->getValue())->toBe('Jumlah Sampel (D)')
            ->and($sheet->getCell('A7')->getValue())->toBe('Pemeriksaan A')
            ->and($sheet->getCell('B7')->getValue())->toBe(2)
            ->and($sheet->getCell('C7')->getValue())->toBe(3);

        $export->disconnectWorksheets();
    } finally {
        unlink($xlsxPath);
    }
});

it('exports an empty custom spreadsheet from its saved schema before the first entry', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('custom-form-formats.store'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'nama_format' => 'Form Observasi Unit',
            'row_type' => 'dynamic_rows',
            'schema' => [
                'renderer' => 'spreadsheet',
                'engine' => 'univer',
                'columns' => [
                    ['key' => 'item', 'title' => 'Item Observasi', 'type' => 'text', 'role' => 'label'],
                    ['key' => 'compliant', 'title' => 'Sesuai Standar', 'type' => 'number', 'role' => 'numerator'],
                    ['key' => 'sample', 'title' => 'Jumlah Sampel', 'type' => 'number', 'role' => 'denominator'],
                ],
            ],
        ])
        ->assertRedirect();

    $response = $this->get(route('data-entry.export-excel', [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))->assertOk()->assertDownload();

    $temporaryPath = tempnam(sys_get_temp_dir(), 'form-export-');
    $xlsxPath = $temporaryPath.'.xlsx';
    unlink($temporaryPath);
    file_put_contents($xlsxPath, $response->streamedContent());

    try {
        $export = IOFactory::load($xlsxPath);
        $sheet = $export->getActiveSheet();

        expect($sheet->getCell('A6')->getValue())->toBe('Item Observasi')
            ->and($sheet->getCell('B6')->getValue())->toBe('Sesuai Standar (N)')
            ->and($sheet->getCell('C6')->getValue())->toBe('Jumlah Sampel (D)')
            ->and($sheet->getCell('A7')->getValue())->toBeNull();

        $export->disconnectWorksheets();
    } finally {
        unlink($xlsxPath);
    }
});

it('lets a unit collect and submit a hospital-wide indicator without changing its shared format', function () {
    [$admin, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $indicator->update(['unit_id' => null]);
    $this->actingAs($admin)
        ->post(route('custom-form-formats.store'), [
            'indicator_id' => $indicator->id,
            'nama_format' => 'Format Bersama',
            'row_type' => 'dynamic_rows',
            'schema' => [
                'renderer' => 'spreadsheet',
                'columns' => [
                    ['key' => 'numerator', 'title' => 'Numerator', 'type' => 'number', 'role' => 'numerator'],
                    ['key' => 'denominator', 'title' => 'Denominator', 'type' => 'number', 'role' => 'denominator'],
                ],
            ],
        ])
        ->assertRedirect();

    $format = CustomFormFormat::where('indicator_id', $indicator->id)->firstOrFail();
    $pic = User::factory()->create([
        'role' => 'pic_unit',
        'unit_id' => $unit->id,
        'must_change_password' => false,
    ]);

    $this->actingAs($pic)
        ->post(route('custom-form-formats.store'), [
            'id' => $format->id,
            'indicator_id' => $indicator->id,
            'nama_format' => 'Format Unit',
            'row_type' => 'dynamic_rows',
            'schema' => [
                'renderer' => 'spreadsheet',
                'columns' => [
                    ['key' => 'numerator', 'title' => 'Numerator', 'type' => 'number', 'role' => 'numerator'],
                    ['key' => 'denominator', 'title' => 'Denominator', 'type' => 'number', 'role' => 'denominator'],
                ],
            ],
        ])
        ->assertSessionHas(
            'error',
            'Format indikator lintas unit hanya dapat diatur oleh Administrator atau Komite Mutu.'
        );

    expect($format->fresh()->nama_format)->toBe('Format Bersama');

    $this->actingAs($pic)
        ->get(route('data-entry.index', [
            'unit_id' => $unit->id,
            'indicator_id' => $indicator->id,
            'tahun' => 2026,
            'bulan' => 1,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedIndicator.id', $indicator->id)
            ->where('permissions.canSaveMetricMappingDefault', false));

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'records' => [['numerator' => 1, 'denominator' => 2]],
    ])->assertRedirect();

    $entry = DataEntry::firstOrFail();
    expect($entry->unit_id)->toBe($unit->id)
        ->and($entry->status)->toBe('draft');

    $this->get(route('validation.indicator-periods', [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertJsonPath('months.0.can_submit', true);

    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ])->assertRedirect();

    expect($entry->fresh()->status)->toBe('submitted')
        ->and($entry->fresh()->latestValidation->status)->toBe('submitted');

    $secondIndicator = Indicator::create([
        'kode' => 'TEST-2',
        'judul' => 'Indikator Lintas Unit Kedua',
        'category_id' => $indicator->category_id,
        'unit_id' => null,
        'form_layout_type' => 'dynamic_rows',
        'form_schema' => $indicator->form_schema,
        'is_active' => true,
    ]);

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $secondIndicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
        'records' => [['numerator' => 1, 'denominator' => 2]],
    ])->assertRedirect();

    $this->post(route('validation.submit-entry'), [
        'indicator_id' => $secondIndicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ])->assertRedirect();

    expect(DataEntry::where('indicator_id', $secondIndicator->id)->firstOrFail()->status)->toBe('submitted');

    $this->get(route('validation.index', ['unit_id' => $unit->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('modalIndicators.0.id', $indicator->id)
            ->where('modalIndicators.1.id', $secondIndicator->id));

    $this->get(route('data-entry.export-excel', [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))->assertOk()->assertDownload();

    $otherUnit = Unit::create([
        'kode' => 'OTHER',
        'nama' => 'Unit Lain',
        'aktif' => true,
    ]);

    $this->from(route('data-entry.index'))
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $otherUnit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertSessionHas(
            'error',
            'Akses dibatasi: Anda hanya dapat menyimpan data untuk unit Anda sendiri.'
        );

    expect(DataEntry::where('unit_id', $otherUnit->id)->exists())->toBeFalse();
});

it('allows a unit PIC to validate and exempts the profile for the rest of the year after a valid result', function () {
    [$admin, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    foreach ([1, 2] as $month) {
        $this->actingAs($admin)
            ->post(route('data-entry.save-daily-records'), [
                'indicator_id' => $indicator->id,
                'unit_id' => $unit->id,
                'tahun' => 2026,
                'bulan' => $month,
                'records' => [['numerator' => 1, 'denominator' => 2]],
            ])
            ->assertRedirect();
    }

    $januaryEntry = DataEntry::whereHas('period', fn ($query) => $query->where('bulan', 1))->firstOrFail();
    $this->actingAs($admin)
        ->post(route('data-entry.submit-period'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
        ])
        ->assertRedirect();

    $pic = User::factory()->create([
        'role' => 'pic_unit',
        'unit_id' => $unit->id,
        'must_change_password' => false,
    ]);
    $this->actingAs($pic)
        ->post(route('validation.approve', $januaryEntry), [
            'validator_num' => 1,
            'validator_den' => 2,
        ])
        ->assertRedirect(route('validation.show', $januaryEntry));

    $this->actingAs($admin)
        ->from(route('data-entry.index'))
        ->post(route('data-entry.submit-period'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 2,
        ])
        ->assertSessionHas('error', 'Profil indikator telah dinyatakan VALID pada Januari 2026. Tidak perlu mengajukan validasi lagi untuk profil ini pada tahun tersebut.');

    expect($januaryEntry->fresh()->latestValidation->is_valid)->toBeTrue()
        ->and(DataEntry::whereHas('period', fn ($query) => $query->where('bulan', 2))->firstOrFail()->status)->toBe('exempt')
        ->and(Validation::getIndicatorValidationStatus($unit->id, $indicator->id, 2026, 2)['is_exempt'])->toBeTrue();

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 3,
        'records' => [['numerator' => 1, 'denominator' => 2]],
    ])->assertRedirect();

    expect(DataEntry::whereHas('period', fn ($query) => $query->where('bulan', 3))->firstOrFail()->status)->toBe('exempt');

    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 4,
        'records' => [['numerator' => 0, 'denominator' => 0]],
    ])->assertRedirect();

    expect(DataEntry::whereHas('period', fn ($query) => $query->where('bulan', 4))->firstOrFail()->status)->toBe('draft');

    $this->get(route('validation.index', [
        'category' => 'valid',
        'unit_id' => $unit->id,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.valid', 1)
            ->has('entries.data', 1)
            ->where('entries.data.0.validation_event_id', $januaryEntry->fresh()->latestValidation->id));
});

it('reports exempt entries separately from approved data on the hospital dashboard', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $unit->update(['aktif' => true]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    DataEntry::firstOrFail()->update(['status' => 'exempt']);

    $this->get(route('dashboard', ['tahun' => 2026, 'bulan' => 1]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.unitExempt', 1)
            ->has('pieKepatuhanUnit.series', 4)
            ->where('unitSubmissions.0.exempt', 1)
            ->where('unitSubmissions.0.status', 'exempt'));
});

it('includes exempt entries in PDF reports and excludes rejected entries', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    foreach ([
        ['bulan' => 1, 'numerator' => 1, 'denominator' => 2, 'status' => 'approved'],
        ['bulan' => 2, 'numerator' => 2, 'denominator' => 2, 'status' => 'exempt'],
        ['bulan' => 3, 'numerator' => 0, 'denominator' => 2, 'status' => 'rejected'],
    ] as $record) {
        $this->actingAs($user)
            ->post(route('data-entry.save-daily-records'), [
                'indicator_id' => $indicator->id,
                'unit_id' => $unit->id,
                'tahun' => 2026,
                'bulan' => $record['bulan'],
                'records' => [[
                    'numerator' => $record['numerator'],
                    'denominator' => $record['denominator'],
                ]],
            ])
            ->assertRedirect();

        DataEntry::whereHas('period', fn ($query) => $query->where('bulan', $record['bulan']))
            ->firstOrFail()
            ->update(['status' => $record['status']]);
    }

    Storage::fake('private');
    $pdf = Mockery::mock(PDF::class);
    $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
    $pdf->shouldReceive('output')->once()->andReturn('test-pdf');
    $reportData = null;
    DomPdf::shouldReceive('loadView')
        ->once()
        ->withArgs(function (string $view, array $viewData) use (&$reportData): bool {
            $reportData = $viewData['data'];

            return $view === 'reports.laporan-mutu';
        })
        ->andReturn($pdf);

    $response = $this->actingAs($user)->post(route('reports.generate'), [
        'jenis' => 'tahunan',
        'tahun' => 2026,
        'format' => 'pdf',
        'unit_id' => $unit->id,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();
    $log = ReportLog::firstOrFail();
    $response->assertRedirect(route('reports.download', $log));
    Storage::disk('private')->assertExists($log->file_path);
    expect($reportData)->toHaveCount(1)
        ->and($reportData[0]['capaian'])->toBe(75.0);
});

it('includes exempt entries in hospital capaian and internal benchmark totals', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);
    $unit->update(['aktif' => true]);
    $indicator->update([
        'unit_id' => null,
        'jenis' => ['IMN'],
        'target_capaian' => 80,
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 3, 'denominator' => 4]],
        ])
        ->assertRedirect();

    DataEntry::firstOrFail()->update(['status' => 'exempt']);

    $this->get(route('hospital.data-capaian.index', [
        'tahun' => 2026,
        'kategori' => 'IMN',
        'indicator_id' => $indicator->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('analysisData.bulanan.0.num', 3)
            ->where('analysisData.bulanan.0.den', 4)
            ->where('analysisData.bulanan.0.capaian', 75));

    $this->get(route('hospital.benchmark.index', [
        'tahun' => 2026,
        'indicator_id' => $indicator->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('internalComparison.0.unit_id', $unit->id)
            ->where('internalComparison.0.total_patuh', 3)
            ->where('internalComparison.0.total_sampel', 4)
            ->where('internalComparison.0.capaian', 75));
});

it('allows a different ready month to be submitted after an invalid decision and preserves both decisions', function () {
    [$admin, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    foreach ([1, 2] as $month) {
        $this->actingAs($admin)
            ->post(route('data-entry.save-daily-records'), [
                'indicator_id' => $indicator->id,
                'unit_id' => $unit->id,
                'tahun' => 2026,
                'bulan' => $month,
                'records' => [['numerator' => 1, 'denominator' => 2]],
            ])
            ->assertRedirect();
    }

    $januaryEntry = DataEntry::whereHas('period', fn ($query) => $query->where('bulan', 1))->firstOrFail();
    $this->actingAs($admin)
        ->post(route('data-entry.submit-period'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
        ])
        ->assertRedirect();

    $pic = User::factory()->create([
        'role' => 'kepala_unit',
        'unit_id' => $unit->id,
        'must_change_password' => false,
    ]);
    $this->actingAs($pic)
        ->post(route('validation.approve', $januaryEntry), [
            'validator_num' => 0,
            'validator_den' => 1,
        ])
        ->assertRedirect(route('validation.show', $januaryEntry));

    $this->post(route('validation.submit-entry'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 2,
    ])->assertRedirect();

    $februaryEntry = DataEntry::whereHas('period', fn ($query) => $query->where('bulan', 2))->firstOrFail();
    expect($januaryEntry->fresh()->latestValidation->is_valid)->toBeFalse()
        ->and($februaryEntry->status)->toBe('submitted')
        ->and(Validation::where('data_entry_id', $januaryEntry->id)->count())->toBe(2)
        ->and(Validation::where('data_entry_id', $februaryEntry->id)->count())->toBe(1)
        ->and(Validation::getIndicatorValidationStatus($unit->id, $indicator->id, 2026, 2)['is_mandatory_retry'])->toBeTrue();
});

it('blocks another month while a profile validation is pending', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    foreach ([1, 2] as $month) {
        $this->actingAs($user)
            ->post(route('data-entry.save-daily-records'), [
                'indicator_id' => $indicator->id,
                'unit_id' => $unit->id,
                'tahun' => 2026,
                'bulan' => $month,
                'records' => [['numerator' => 1, 'denominator' => 2]],
            ])
            ->assertRedirect();
    }

    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ])->assertRedirect();

    $this->from(route('data-entry.index'))
        ->post(route('validation.submit-entry'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 2,
        ])
        ->assertSessionHas('error', 'Masih ada pengajuan validasi profil ini yang menunggu keputusan.');

    expect(Validation::count())->toBe(1)
        ->and(DataEntry::whereHas('period', fn ($query) => $query->where('bulan', 2))->firstOrFail()->status)->toBe('draft');
});

it('shows validation decisions as separate filtered history events and opens their snapshots', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();

    $entry = DataEntry::firstOrFail();
    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ])->assertRedirect();
    $submittedEvent = Validation::where('data_entry_id', $entry->id)->firstOrFail();

    $this->post(route('validation.reject', $entry), [
        'catatan' => 'Perlu diperiksa ulang.',
    ])->assertRedirect();
    $rejectedEvent = Validation::where('data_entry_id', $entry->id)->latest('id')->firstOrFail();

    $this->get(route('validation.index', [
        'category' => 'history',
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Validation/Index')
            ->where('filters.tahun', 2026)
            ->where('filters.bulan', 1)
            ->has('entries.data', 2)
            ->where('entries.data.0.validation_event_id', $rejectedEvent->id)
            ->where('entries.data.1.validation_event_id', $submittedEvent->id));

    $this->get(route('validation.index', [
        'category' => 'tidak_valid',
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.tidak_valid', 1)
            ->has('entries.data', 1)
            ->where('entries.data.0.validation_event_id', $rejectedEvent->id));

    $this->get(route('validation.show', [
        'dataEntry' => $entry->id,
        'validation_id' => $submittedEvent->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Validation/Show')
            ->where('validation.id', $submittedEvent->id)
            ->where('validation.status', 'submitted')
            ->where('entry.status', 'submitted')
            ->where('permissions.canValidate', false));
});

it('does not allow the quality committee to assign a validation result', function () {
    [$admin, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'numerator', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'denominator', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $this->actingAs($admin)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [['numerator' => 1, 'denominator' => 2]],
        ])
        ->assertRedirect();
    $entry = DataEntry::firstOrFail();
    $this->post(route('data-entry.submit-period'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 1,
    ])->assertRedirect();

    $committee = User::factory()->create([
        'role' => 'komite_mutu',
        'must_change_password' => false,
    ]);
    $this->actingAs($committee)
        ->post(route('validation.approve', $entry), [
            'validator_num' => 1,
            'validator_den' => 2,
        ])
        ->assertForbidden();

    expect($entry->fresh()->status)->toBe('submitted')
        ->and(Validation::where('data_entry_id', $entry->id)->count())->toBe(1);
});

it('uses the saved numerator and denominator ranges instead of column roles', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'first', 'title' => 'Kolom A', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'second', 'title' => 'Kolom B', 'type' => 'number', 'role' => 'numerator'],
    ]);

    $defaultMapping = [
        'version' => 1,
        'numerator' => ['range' => 'A2:A3', 'aggregation' => 'sum'],
        'denominator' => ['range' => 'B2:B3', 'aggregation' => 'sum'],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['first' => 2, 'second' => 4],
                ['first' => 3, 'second' => 6],
            ],
            'workbook_snapshot' => spreadsheetMetricMappingWorkbook($defaultMapping),
            'save_metric_mapping_as_default' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $firstEntry = DataEntry::where('indicator_id', $indicator->id)
        ->where('unit_id', $unit->id)
        ->firstOrFail();
    expect((float) $firstEntry->numerator)->toBe(5.0)
        ->and((float) $firstEntry->denominator)->toBe(10.0)
        ->and($firstEntry->workbook_snapshot['entry_metadata']['metric_mapping'])
        ->toBe($defaultMapping)
        ->and($indicator->fresh()->form_schema['metric_mapping_default'])
        ->toBe($defaultMapping);

    $periodOverride = [
        'version' => 1,
        'numerator' => ['range' => 'B2:B3', 'aggregation' => 'sum'],
        'denominator' => ['range' => 'A2:A3', 'aggregation' => 'sum'],
    ];
    $this->post(route('data-entry.save-daily-records'), [
        'indicator_id' => $indicator->id,
        'unit_id' => $unit->id,
        'tahun' => 2026,
        'bulan' => 2,
        'records' => [
            ['first' => 2, 'second' => 4],
            ['first' => 3, 'second' => 6],
        ],
        'workbook_snapshot' => spreadsheetMetricMappingWorkbook($periodOverride),
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $secondEntry = DataEntry::where('indicator_id', $indicator->id)
        ->where('unit_id', $unit->id)
        ->where('id', '!=', $firstEntry->id)
        ->firstOrFail();
    expect((float) $secondEntry->numerator)->toBe(10.0)
        ->and((float) $secondEntry->denominator)->toBe(5.0)
        ->and($secondEntry->workbook_snapshot['entry_metadata']['metric_mapping'])
        ->toBe($periodOverride);
});

it('calculates spreadsheet metric ranges from workbook cells when row keys cannot be reconciled', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'first', 'title' => 'Kolom A', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'second', 'title' => 'Kolom B', 'type' => 'number', 'role' => 'denominator'],
    ]);

    $metricMapping = [
        'version' => 1,
        'numerator' => ['range' => 'A2:A3', 'aggregation' => 'sum'],
        'denominator' => ['range' => 'B2:B3', 'aggregation' => 'sum'],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [[], []],
            'workbook_snapshot' => spreadsheetMetricMappingWorkbook($metricMapping),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(5.0)
        ->and((float) $entry->denominator)->toBe(10.0)
        ->and((float) $entry->capaian)->toBe(50.0);
});

it('uses explicit workbook mappings for legacy spreadsheet layouts without a renderer schema', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', []);
    $indicator->update([
        'form_schema' => [
            'schema_version' => 1,
            'header_rows' => 1,
            'columns' => [],
            'rows' => [],
        ],
    ]);
    $metricMapping = [
        'version' => 1,
        'numerator' => ['range' => 'A2:A3', 'aggregation' => 'sum'],
        'denominator' => ['range' => 'B2:B3', 'aggregation' => 'sum'],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [[], []],
            'workbook_snapshot' => spreadsheetMetricMappingWorkbook($metricMapping),
            'save_metric_mapping_as_default' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(5.0)
        ->and((float) $entry->denominator)->toBe(10.0)
        ->and((float) $entry->capaian)->toBe(50.0)
        ->and($entry->workbook_snapshot['entry_metadata']['metric_mapping'])
        ->toBe($metricMapping)
        ->and($indicator->fresh()->form_schema['metric_mapping_default'])
        ->toBe($metricMapping);
});

it('reads legacy mapped metrics from workbook cells when row keys are missing', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('daily_grid', []);
    $indicator->update(['form_schema' => null]);

    $metricMapping = [
        'version' => 1,
        'numerator' => ['range' => 'F3:F4', 'aggregation' => 'sum'],
        'denominator' => ['range' => 'B3:B4', 'aggregation' => 'sum'],
    ];
    $columns = [
        ['key' => 'day', 'title' => 'Tanggal', 'type' => 'text', 'role' => 'label'],
        ['key' => 'col_1', 'title' => 'D Terpilih', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'weekday', 'title' => 'Hari', 'type' => 'text', 'role' => 'label'],
        ['key' => 'num', 'title' => 'N Lama', 'type' => 'number', 'role' => 'numerator'],
        ['key' => 'den', 'title' => 'D Lama', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'col_3', 'title' => 'N Terpilih', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'col_4', 'title' => 'D Terpilih', 'type' => 'number', 'role' => 'auxiliary'],
        ['key' => 'capaian', 'title' => 'Capaian (%)', 'type' => 'number', 'role' => 'auxiliary'],
    ];
    $dailyRows = [
        '1' => [
            'day' => 1,
            'weekday' => 'Sen',
            'num' => 1,
            'den' => 2,
            'col_3' => 4,
            'col_4' => 10,
            'capaian' => 50,
        ],
        '2' => [
            'day' => 2,
            'weekday' => 'Sel',
            'num' => 1,
            'den' => 2,
            'col_3' => 5,
            'col_4' => 20,
            'capaian' => 50,
        ],
    ];
    $records = [
        '1' => ['num' => 1, 'den' => 2, 'col_3' => 4, 'col_4' => 10],
        '2' => ['num' => 1, 'den' => 2, 'col_3' => 5, 'col_4' => 20],
        '__spreadsheet' => [
            'layout_type' => 'daily_grid',
            'row_type' => 'fixed_rows',
            'header_rows' => 2,
            'columns' => $columns,
            'rows' => [
                ['key' => '1', 'label' => '1', 'values' => ['day' => 1]],
                ['key' => '2', 'label' => '2', 'values' => ['day' => 2]],
            ],
            'records' => $dailyRows,
        ],
    ];
    $workbookSnapshot = [
        'entry_metadata' => [
            'metric_mapping' => $metricMapping,
        ],
        'sheetOrder' => ['daily-grid'],
        'sheets' => [
            'daily-grid' => [
                'id' => 'daily-grid',
                'name' => 'Form Pengumpul Data',
                'rowCount' => 4,
                'columnCount' => 8,
                'cellData' => [
                    0 => [0 => ['v' => 'Tally Sheet']],
                    1 => [
                        0 => ['v' => 'Tanggal'],
                        1 => ['v' => 'D Terpilih'],
                        2 => ['v' => 'Hari'],
                        3 => ['v' => 'N Lama'],
                        4 => ['v' => 'D Lama'],
                        5 => ['v' => 'N Terpilih'],
                        6 => ['v' => 'D Terpilih'],
                        7 => ['v' => 'Capaian (%)'],
                    ],
                    2 => [
                        0 => ['v' => 1],
                        1 => ['v' => 10],
                        2 => ['v' => 'Sen'],
                        3 => ['v' => 1],
                        4 => ['v' => 2],
                        5 => ['v' => 4],
                        6 => ['v' => 10],
                        7 => ['v' => 50],
                    ],
                    3 => [
                        0 => ['v' => 2],
                        1 => ['v' => 20],
                        2 => ['v' => 'Sel'],
                        3 => ['v' => 1],
                        4 => ['v' => 2],
                        5 => ['v' => 5],
                        6 => ['v' => 20],
                        7 => ['v' => 50],
                    ],
                ],
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => $records,
            'workbook_snapshot' => $workbookSnapshot,
            'save_metric_mapping_as_default' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(9.0)
        ->and((float) $entry->denominator)->toBe(30.0)
        ->and((float) $entry->capaian)->toBe(30.0)
        ->and((float) $entry->log_harian[1]['n'])->toBe(4.0)
        ->and((float) $entry->log_harian[1]['d'])->toBe(10.0)
        ->and((float) $entry->log_harian[2]['n'])->toBe(5.0)
        ->and((float) $entry->log_harian[2]['d'])->toBe(20.0)
        ->and($indicator->fresh()->form_schema['metric_mapping_default'])
        ->toBe($metricMapping);
    expect(array_key_exists('col_1', $entry->log_harian[1]['data']))->toBeFalse()
        ->and($entry->workbook_snapshot['sheets']['daily-grid']['cellData'][2][1]['v'])
        ->toBe(10);
});

it('uses workbook cell mappings for legacy monthly-summary forms', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('monthly_summary', []);
    $metricMapping = [
        'version' => 1,
        'numerator' => ['range' => 'C3:C3', 'aggregation' => 'sum'],
        'denominator' => ['range' => 'B3:B3', 'aggregation' => 'sum'],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                '1' => ['num' => 0, 'den' => 0],
                '__spreadsheet' => [
                    'layout_type' => 'monthly_summary',
                    'row_type' => 'fixed_rows',
                    'header_rows' => 2,
                    'columns' => [
                        ['key' => 'month', 'title' => 'Bulan', 'type' => 'text', 'role' => 'label'],
                        ['key' => 'billing_total', 'title' => 'Jumlah Billing', 'type' => 'number', 'role' => 'auxiliary'],
                        ['key' => 'billing_complete', 'title' => 'Billing Lengkap', 'type' => 'number', 'role' => 'auxiliary'],
                    ],
                    'rows' => [
                        ['key' => 'january', 'label' => 'Januari'],
                    ],
                    'records' => [
                        'january' => [],
                    ],
                ],
            ],
            'workbook_snapshot' => [
                'entry_metadata' => [
                    'metric_mapping' => $metricMapping,
                ],
                'sheetOrder' => ['monthly-summary'],
                'sheets' => [
                    'monthly-summary' => [
                        'id' => 'monthly-summary',
                        'rowCount' => 3,
                        'columnCount' => 3,
                        'cellData' => [
                            0 => [0 => ['v' => 'Rekapitulasi Bulanan']],
                            1 => [
                                0 => ['v' => 'Bulan'],
                                1 => ['v' => 'Jumlah Billing'],
                                2 => ['v' => 'Billing Lengkap'],
                            ],
                            2 => [
                                0 => ['v' => 'Januari'],
                                1 => ['v' => 12],
                                2 => ['v' => 8],
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = DataEntry::firstOrFail();
    expect((float) $entry->numerator)->toBe(8.0)
        ->and((float) $entry->denominator)->toBe(12.0)
        ->and((float) $entry->capaian)->toBe(66.67);
});

it('rejects metric ranges that include header rows without saving the entry', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'first', 'title' => 'Kolom A', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'second', 'title' => 'Kolom B', 'type' => 'number', 'role' => 'numerator'],
    ]);

    $invalidMapping = [
        'version' => 1,
        'numerator' => ['range' => 'A1:A2', 'aggregation' => 'sum'],
        'denominator' => ['range' => 'B2:B3', 'aggregation' => 'sum'],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['first' => 2, 'second' => 4],
                ['first' => 3, 'second' => 6],
            ],
            'workbook_snapshot' => spreadsheetMetricMappingWorkbook($invalidMapping),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors([
            'workbook_snapshot.entry_metadata.metric_mapping' => 'Rentang sumber numerator harus berupa sel data yang valid dan tidak lebih dari 10.000 sel.',
        ]);

    expect(DataEntry::count())->toBe(0);
});

it('does not let unit staff change the default mapping for a hospital indicator', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'first', 'title' => 'Kolom A', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'second', 'title' => 'Kolom B', 'type' => 'number', 'role' => 'numerator'],
    ]);
    $indicator->update(['unit_id' => null]);
    $user->update(['role' => 'pic_unit']);

    $metricMapping = [
        'version' => 1,
        'numerator' => ['range' => 'A2:A3', 'aggregation' => 'sum'],
        'denominator' => ['range' => 'B2:B3', 'aggregation' => 'sum'],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['first' => 2, 'second' => 4],
                ['first' => 3, 'second' => 6],
            ],
            'workbook_snapshot' => spreadsheetMetricMappingWorkbook($metricMapping),
            'save_metric_mapping_as_default' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas(
            'error',
            'Anda tidak memiliki izin untuk mengubah pemetaan default indikator ini.'
        );

    expect(DataEntry::count())->toBe(0)
        ->and($indicator->fresh()->form_schema['metric_mapping_default'] ?? null)
        ->toBeNull();
});

it('rejects unexpected fields in a submitted metric mapping', function () {
    [$user, $unit, $indicator] = createSpreadsheetDataEntryFixture('dynamic_rows', [
        ['key' => 'first', 'title' => 'Kolom A', 'type' => 'number', 'role' => 'denominator'],
        ['key' => 'second', 'title' => 'Kolom B', 'type' => 'number', 'role' => 'numerator'],
    ]);

    $metricMapping = [
        'version' => 1,
        'numerator' => [
            'range' => 'A2:A3',
            'aggregation' => 'sum',
            'unexpected' => 'ignored input',
        ],
        'denominator' => ['range' => 'B2:B3', 'aggregation' => 'sum'],
    ];

    $this->actingAs($user)
        ->post(route('data-entry.save-daily-records'), [
            'indicator_id' => $indicator->id,
            'unit_id' => $unit->id,
            'tahun' => 2026,
            'bulan' => 1,
            'records' => [
                ['first' => 2, 'second' => 4],
                ['first' => 3, 'second' => 6],
            ],
            'workbook_snapshot' => spreadsheetMetricMappingWorkbook($metricMapping),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(
            'workbook_snapshot.entry_metadata.metric_mapping.numerator'
        );

    expect(DataEntry::count())->toBe(0);
});
