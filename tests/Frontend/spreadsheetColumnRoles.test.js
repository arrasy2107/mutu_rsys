import assert from 'node:assert/strict';
import test from 'node:test';
import {
    getSpreadsheetCellAddress,
    getSpreadsheetColumnLetters,
    getSpreadsheetFormulaConfig,
    getSpreadsheetHeaderRowCount,
    getDefinedNameMetricColumns,
    getContiguousSpreadsheetHeaderIndexes,
    getMeaningfulSpreadsheetHeaderIndexes,
    getSpreadsheetHeaderColumnTitles,
    ensureSpreadsheetWorkbookColumnCount,
    ensureSpreadsheetWorkbookRowCount,
    getSpreadsheetWorkbookColumnCount,
    maximumSpreadsheetEntryColumns,
    minimumSpreadsheetEntryColumns,
    minimumSpreadsheetEntryRows,
    minimumSpreadsheetBuilderColumns,
    minimumSpreadsheetBuilderRows,
    normalizeSpreadsheetWorkbookColumns,
    reconcileSpreadsheetColumns,
    reconcileSpreadsheetColumnsWithWorkbook,
    resolveSpreadsheetCellReference,
    setSpreadsheetMetricRoleFromColumn,
    setSpreadsheetMetricRoleFromCell,
    setSpreadsheetColumnRole,
} from '../../resources/js/Components/FormRenderers/spreadsheetColumnRoles.js';
import { mapSpreadsheetRows } from '../../resources/js/Components/FormRenderers/spreadsheetWorkbookMapper.js';

test('assigns a metric role without changing the column input type', () => {
    const column = { key: 'observed', type: 'text', role: 'auxiliary' };

    setSpreadsheetColumnRole(column, 'numerator');

    assert.deepEqual(column, {
        key: 'observed',
        type: 'text',
        role: 'numerator',
    });
});

test('creates a formula configuration from stable column keys', () => {
    const formulaConfig = getSpreadsheetFormulaConfig([
        { key: 'first_num', role: 'numerator' },
        { key: 'second_num', role: 'numerator' },
        { key: 'first_den', role: 'denominator' },
        { key: 'second_den', role: 'denominator' },
        { key: 'notes', role: 'auxiliary' },
    ]);

    assert.deepEqual(formulaConfig, {
        numerator_cols: ['first_num', 'second_num'],
        denominator_cols: ['first_den', 'second_den'],
        numerator_col: 'first_num',
        denominator_col: 'first_den',
    });
});

test('resolves a spreadsheet cell address to its configured column', () => {
    const columns = [
        { key: 'name', type: 'text' },
        { key: 'date', type: 'date' },
        { key: 'numerator', type: 'number' },
        { key: 'denominator', type: 'number' },
        { key: 'notes', type: 'text' },
    ];

    assert.deepEqual(resolveSpreadsheetCellReference('e9', columns), {
        address: 'E9',
        columnIndex: 4,
        columnKey: 'notes',
        rowNumber: 9,
    });
    assert.equal(resolveSpreadsheetCellReference('E2', columns), null);
    assert.equal(resolveSpreadsheetCellReference('F9', columns), null);
    assert.equal(resolveSpreadsheetCellReference('E0', columns), null);
    assert.equal(resolveSpreadsheetCellReference('Sheet1!E9', columns), null);
});

test('assigns a selected cell role and normalizes nonnumeric input types', () => {
    const columns = [
        { key: 'first', type: 'text', role: 'numerator' },
        { key: 'second', type: 'decimal', role: 'auxiliary' },
        { key: 'third', type: 'checkbox', role: 'auxiliary' },
    ];

    const result = setSpreadsheetMetricRoleFromCell(columns, 'numerator', 'B9');

    assert.equal(result.columnKey, 'second');
    assert.deepEqual(
        columns.map(({ type, role }) => ({ type, role })),
        [
            { type: 'text', role: 'auxiliary' },
            { type: 'decimal', role: 'numerator' },
            { type: 'checkbox', role: 'auxiliary' },
        ],
    );

    const textDenominator = {
        key: 'text-denominator',
        type: 'text',
        role: 'auxiliary',
    };
    assert.equal(
        setSpreadsheetMetricRoleFromCell(
            [...columns, textDenominator],
            'denominator',
            'D9',
        ).columnKey,
        'text-denominator',
    );
    assert.equal(textDenominator.type, 'number');

    assert.equal(
        setSpreadsheetMetricRoleFromCell(columns, 'denominator', 'B5'),
        null,
    );
});

test('assigns a metric role by stable column key', () => {
    const columns = [
        { key: 'date', type: 'date', role: 'numerator' },
        { key: 'compliant', type: 'checkbox', role: 'auxiliary' },
        { key: 'sample', type: 'number', role: 'denominator' },
    ];

    assert.equal(
        setSpreadsheetMetricRoleFromColumn(columns, 'numerator', 'compliant'),
        true,
    );
    assert.equal(
        setSpreadsheetMetricRoleFromColumn(columns, 'numerator', 'sample'),
        false,
    );
    assert.equal(
        setSpreadsheetMetricRoleFromColumn(columns, 'numerator', 'date'),
        true,
    );
    assert.deepEqual(
        columns.map(({ key, role }) => ({ key, role })),
        [
            { key: 'date', role: 'numerator' },
            { key: 'compliant', role: 'auxiliary' },
            { key: 'sample', role: 'denominator' },
        ],
    );
    assert.equal(columns[0].type, 'number');
});

test('normalizes saved numerator and denominator roles from text to numbers', () => {
    const columns = [
        { key: 'numerator', type: 'text', role: 'numerator' },
        { key: 'additional-numerator', type: 'decimal', role: 'numerator' },
        { key: 'denominator', type: 'text', role: 'denominator' },
    ];

    assert.equal(
        setSpreadsheetMetricRoleFromColumn(columns, 'numerator', 'numerator'),
        true,
    );
    assert.equal(
        setSpreadsheetMetricRoleFromColumn(
            columns,
            'denominator',
            'denominator',
        ),
        true,
    );

    assert.deepEqual(
        columns.map(({ type, role }) => ({ type, role })),
        [
            { type: 'number', role: 'numerator' },
            { type: 'decimal', role: 'numerator' },
            { type: 'number', role: 'denominator' },
        ],
    );
});

test('converts zero-based column indexes to spreadsheet references', () => {
    assert.equal(getSpreadsheetColumnLetters(0), 'A');
    assert.equal(getSpreadsheetColumnLetters(25), 'Z');
    assert.equal(getSpreadsheetColumnLetters(26), 'AA');
    assert.equal(getSpreadsheetCellAddress(4, 9), 'E9');
});

test('ignores empty default spreadsheet header cells when discovering columns', () => {
    const headerCells = {
        0: { v: 'Tanggal' },
        1: { v: 'Uraian' },
        2: { v: '   ' },
        3: {},
        4: { v: null },
        5: { v: 'Numerator' },
        6: { f: '=A1' },
        7: { v: 2026 },
        8: { v: true },
        9: { v: '' },
    };

    assert.deepEqual(
        getMeaningfulSpreadsheetHeaderIndexes(headerCells),
        [0, 1, 5],
    );
    assert.deepEqual(getMeaningfulSpreadsheetHeaderIndexes(null), []);
});

test('does not expand a table to a distant stray header cell', () => {
    const headerCells = {
        0: { v: 'Tanggal' },
        1: { v: 'Keterangan' },
        2: { v: 'Numerator' },
        3: { v: 'Denominator' },
        4: { v: 'Catatan' },
        139: { v: 'Data lama' },
    };

    assert.deepEqual(
        getContiguousSpreadsheetHeaderIndexes(headerCells, 5, 100),
        [],
    );
    assert.deepEqual(
        getContiguousSpreadsheetHeaderIndexes(
            { 5: { v: 'Tambahan' }, 6: { v: 'Rincian' }, 8: { v: 'Jauh' } },
            5,
            100,
        ),
        [5, 6],
    );
});

test('discovers columns across header rows and fills gaps without losing order', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                cellData: {
                    0: {
                        0: { v: 'Identitas' },
                        2: { v: 'Hasil Pemeriksaan' },
                    },
                    1: {
                        0: { v: 'Tanggal' },
                        1: { v: 'Nama Pasien' },
                        2: { v: 'Sesuai' },
                        4: { v: 'Total Sampel' },
                    },
                },
                mergeData: [
                    {
                        startRow: 0,
                        endRow: 0,
                        startColumn: 0,
                        endColumn: 1,
                    },
                    {
                        startRow: 0,
                        endRow: 0,
                        startColumn: 2,
                        endColumn: 3,
                    },
                ],
            },
        },
    };

    assert.deepEqual(getSpreadsheetHeaderColumnTitles(workbook, 2, 2), [
        'Tanggal',
        'Nama Pasien',
        'Sesuai',
        null,
        'Total Sampel',
    ]);
});

test('discovers workbook headers beyond a stale saved column count', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                columnCount: 2,
                cellData: {
                    0: {
                        0: { v: 'Bulan' },
                        1: { v: 'Hari' },
                        2: { v: 'Tanggal' },
                        3: { v: 'Jumlah Pasien' },
                    },
                },
            },
        },
    };

    assert.deepEqual(getSpreadsheetHeaderColumnTitles(workbook, 2, 2), [
        'Bulan',
        'Hari',
        'Tanggal',
        'Jumlah Pasien',
    ]);
    assert.equal(getSpreadsheetWorkbookColumnCount(workbook, 2), 4);
});

test('keeps metric roles attached to their headers after inserting a column', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                cellData: {
                    1: {
                        0: { v: 'Numerator' },
                        1: { v: 'Catatan Tambahan' },
                        2: { v: 'Denominator' },
                    },
                },
            },
        },
    };
    const columns = reconcileSpreadsheetColumnsWithWorkbook(
        [
            {
                key: 'numerator',
                title: 'Numerator',
                type: 'number',
                role: 'numerator',
            },
            {
                key: 'denominator',
                title: 'Denominator',
                type: 'number',
                role: 'denominator',
            },
        ],
        workbook,
        2,
        maximumSpreadsheetEntryColumns,
        1,
        { preserveMetricRolesForPositionallyMatchedColumns: true },
    );

    assert.deepEqual(
        columns.map(({ key, role }) => ({ key, role })),
        [
            { key: 'numerator', role: 'numerator' },
            { key: 'col_2', role: 'auxiliary' },
            { key: 'denominator', role: 'denominator' },
        ],
    );
    assert.deepEqual(
        mapSpreadsheetRows({
            columns,
            formulas: [[]],
            rowType: 'dynamic_rows',
            values: [[1, 'Catatan', 2]],
        }),
        [
            {
                numerator: 1,
                col_2: 'Catatan',
                denominator: 2,
            },
        ],
    );
});

test('expands the entry canvas while respecting the supported size limits', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                rowCount: 6,
                columnCount: 2,
            },
        },
    };

    ensureSpreadsheetWorkbookRowCount(workbook, minimumSpreadsheetEntryRows);
    ensureSpreadsheetWorkbookColumnCount(
        workbook,
        minimumSpreadsheetEntryColumns,
        maximumSpreadsheetEntryColumns,
    );

    assert.equal(workbook.sheets['sheet-1'].rowCount, 510);
    assert.equal(workbook.sheets['sheet-1'].columnCount, 100);
});

test('restores every workbook header before applying a stale saved schema', () => {
    const headers = [
        'Tanggal',
        'Nama Pasien',
        'Diagnosis',
        'Dokter',
        'Poli',
        'Hasil',
        'Numerator',
        'Denominator',
        'Keterangan',
        'Petugas',
        'Bulan',
    ];
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                columnCount: 2,
                cellData: {
                    1: Object.fromEntries(
                        headers.map((title, index) => [index, { v: title }]),
                    ),
                },
            },
        },
    };

    const columns = reconcileSpreadsheetColumnsWithWorkbook(
        [
            { key: 'date', title: 'Tanggal', role: 'auxiliary' },
            { key: 'patient', title: 'Nama Pasien', role: 'auxiliary' },
        ],
        workbook,
        2,
    );

    assert.equal(columns.length, 11);
    assert.deepEqual(
        columns.map(({ title }) => title),
        headers,
    );
    assert.deepEqual(
        columns.slice(0, 2).map(({ key }) => key),
        ['date', 'patient'],
    );
    assert.equal(new Set(columns.map(({ key }) => key)).size, 11);
});

test('removes deleted workbook columns from the period data mapping', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                cellData: {
                    1: {
                        0: { v: 'Numerator' },
                    },
                },
            },
        },
    };
    const columns = reconcileSpreadsheetColumnsWithWorkbook(
        [
            {
                key: 'numerator',
                title: 'Numerator',
                type: 'number',
                role: 'numerator',
            },
            {
                key: 'denominator',
                title: 'Denominator',
                type: 'number',
                role: 'denominator',
            },
        ],
        workbook,
        2,
        maximumSpreadsheetEntryColumns,
        1,
    );

    assert.deepEqual(columns, [
        {
            key: 'numerator',
            title: 'Numerator',
            type: 'number',
            role: 'numerator',
        },
    ]);
});

test('expands a saved workbook to include template cells outside its row count', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                rowCount: 5,
                cellData: {
                    9: {
                        0: { v: 'Data row' },
                    },
                },
            },
        },
    };

    assert.equal(ensureSpreadsheetWorkbookRowCount(workbook, 7), workbook);
    assert.equal(workbook.sheets['sheet-1'].rowCount, 10);
});

test('expands a saved workbook to include merges outside its row count', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                rowCount: 5,
                mergeData: [{ startRow: 8, endRow: 9 }],
            },
        },
    };

    ensureSpreadsheetWorkbookRowCount(workbook, 7);

    assert.equal(workbook.sheets['sheet-1'].rowCount, 10);
});

test('keeps an existing workbook row count when it already fits the template', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                rowCount: 20,
            },
        },
    };

    ensureSpreadsheetWorkbookRowCount(workbook, 12);

    assert.equal(workbook.sheets['sheet-1'].rowCount, 20);
});

test('preserves a 500 by 500 builder canvas and its populated cells', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                rowCount: 500,
                columnCount: 500,
                cellData: {
                    450: {
                        450: { v: 'Template content' },
                    },
                },
                mergeData: [
                    {
                        startRow: 480,
                        endRow: 481,
                        startColumn: 480,
                        endColumn: 481,
                    },
                ],
            },
        },
    };

    ensureSpreadsheetWorkbookRowCount(workbook, minimumSpreadsheetBuilderRows);
    ensureSpreadsheetWorkbookColumnCount(
        workbook,
        minimumSpreadsheetBuilderColumns,
    );

    assert.equal(workbook.sheets['sheet-1'].rowCount, 500);
    assert.equal(workbook.sheets['sheet-1'].columnCount, 500);
    assert.equal(
        getSpreadsheetWorkbookColumnCount(workbook),
        minimumSpreadsheetBuilderColumns,
    );
    assert.equal(
        workbook.sheets['sheet-1'].cellData[450][450].v,
        'Template content',
    );
});

test('expands smaller workbook dimensions to the 500 by 500 builder canvas', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                rowCount: 20,
                columnCount: 12,
            },
        },
    };

    ensureSpreadsheetWorkbookRowCount(workbook, minimumSpreadsheetBuilderRows);
    ensureSpreadsheetWorkbookColumnCount(
        workbook,
        minimumSpreadsheetBuilderColumns,
    );

    assert.equal(workbook.sheets['sheet-1'].rowCount, 500);
    assert.equal(workbook.sheets['sheet-1'].columnCount, 500);
});

test('expands builder dimensions when existing content exceeds 500 rows or columns', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                rowCount: 100,
                columnCount: 100,
                cellData: {
                    501: {
                        501: { v: 'Beyond builder minimum' },
                    },
                },
            },
        },
    };

    ensureSpreadsheetWorkbookRowCount(workbook, minimumSpreadsheetBuilderRows);
    ensureSpreadsheetWorkbookColumnCount(
        workbook,
        minimumSpreadsheetBuilderColumns,
    );

    assert.equal(workbook.sheets['sheet-1'].rowCount, 502);
    assert.equal(workbook.sheets['sheet-1'].columnCount, 502);
});

test('maps Numerator and Denumerator defined names to their referenced columns', () => {
    const columns = [
        { key: 'date' },
        { key: 'numerator' },
        { key: 'denominator' },
        { key: 'notes' },
    ];

    assert.deepEqual(
        getDefinedNameMetricColumns(
            [
                {
                    name: 'Numerator',
                    formulaOrRefString: "'Sheet 1'!$B$3",
                },
                {
                    name: 'Denumerator',
                    formulaOrRefString: '=Sheet1!$C$3:$C$30',
                },
            ],
            columns,
        ),
        {
            numeratorColumnKey: 'numerator',
            denominatorColumnKey: 'denominator',
        },
    );
});

test('ignores invalid defined name references and duplicate N/D assignments', () => {
    const columns = [{ key: 'same' }, { key: 'other' }];

    assert.deepEqual(
        getDefinedNameMetricColumns(
            [
                { name: 'Numerator', formulaOrRefString: 'SUM(A1:A4)' },
                { name: 'Denumerator', formulaOrRefString: 'Sheet1!A3' },
            ],
            columns,
        ),
        { numeratorColumnKey: '', denominatorColumnKey: 'same' },
    );
    assert.deepEqual(
        getDefinedNameMetricColumns(
            [
                { name: 'Numerator', formulaOrRefString: 'Sheet1!A3' },
                { name: 'Denumerator', formulaOrRefString: 'Sheet1!A9' },
            ],
            columns,
        ),
        { numeratorColumnKey: '', denominatorColumnKey: '' },
    );
    assert.deepEqual(
        getDefinedNameMetricColumns(
            [{ name: 'Numerator', formulaOrRefString: 'Sheet1!Z3' }],
            columns,
        ),
        { numeratorColumnKey: '', denominatorColumnKey: '' },
    );
});

test('reads Univer array-shaped cell data and headers under a merged title', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                columnCount: 2,
                cellData: [
                    [{ v: 'Bulan' }],
                    [{ v: 'Jumlah N' }, { v: 'Jumlah D' }, { v: 'Catatan' }],
                ],
                mergeData: [
                    {
                        startRow: 0,
                        endRow: 0,
                        startColumn: 0,
                        endColumn: 1,
                    },
                ],
            },
        },
    };

    assert.deepEqual(getSpreadsheetHeaderColumnTitles(workbook, 2, 2), [
        'Jumlah N',
        'Jumlah D',
        'Catatan',
    ]);
    assert.equal(getSpreadsheetWorkbookColumnCount(workbook, 2), 3);
});

test('uses the first populated metric row when a saved header row was removed', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                cellData: [
                    [
                        { v: 'Bulan' },
                        { v: 'Hari' },
                        { v: 'Tanggal' },
                        { v: 'Jumlah Pasien' },
                        { v: 'Pasien Ditriase' },
                    ],
                    [
                        { v: 'Januari' },
                        { v: 'Senin' },
                        { v: 1 },
                        { v: 65 },
                        { v: 24 },
                        { f: '=E2/D2', v: 0.37 },
                    ],
                ],
            },
        },
    };
    const columns = [
        { key: 'month', role: 'auxiliary', type: 'text' },
        { key: 'day', role: 'auxiliary', type: 'text' },
        { key: 'date', role: 'auxiliary', type: 'number' },
        { key: 'denominator', role: 'denominator', type: 'number' },
        { key: 'numerator', role: 'numerator', type: 'number' },
    ];

    assert.equal(getSpreadsheetHeaderRowCount(workbook, 2, columns), 1);
});

test('keeps a configured two-row header when its last row contains only titles', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                cellData: [
                    [{ v: 'Form Pengumpul Data' }],
                    [{ v: 'Numerator' }, { v: 'Denominator' }],
                ],
            },
        },
    };
    const columns = [
        { key: 'numerator', role: 'numerator', type: 'number' },
        { key: 'denominator', role: 'denominator', type: 'number' },
    ];

    assert.equal(getSpreadsheetHeaderRowCount(workbook, 2, columns), 2);
});

test('preserves metric roles for renamed headers that remain in their columns', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                cellData: {
                    1: {
                        0: { v: 'Jumlah Pasien (Jam 12.00-14.00)' },
                        1: { v: 'Jumlah Pasien Yang Ditriase' },
                    },
                },
            },
        },
    };
    const columns = reconcileSpreadsheetColumnsWithWorkbook(
        [
            {
                key: 'denominator',
                title: 'Jumlah pasien 12-14',
                role: 'denominator',
            },
            {
                key: 'numerator',
                title: 'RM pasien yg dilakukan triase',
                role: 'numerator',
            },
        ],
        workbook,
        2,
        maximumSpreadsheetEntryColumns,
        1,
        { preserveMetricRolesForPositionallyMatchedColumns: true },
    );

    assert.deepEqual(
        columns.map(({ key, role }) => ({ key, role })),
        [
            { key: 'denominator', role: 'denominator' },
            { key: 'numerator', role: 'numerator' },
        ],
    );
});

test('reconciles reordered and added headers to stable column keys', () => {
    const columns = reconcileSpreadsheetColumns(
        [
            { key: 'numerator', title: 'Numerator', role: 'numerator' },
            { key: 'denominator', title: 'Denominator', role: 'denominator' },
            { key: 'weekday', title: 'Hari', role: 'auxiliary' },
        ],
        ['Hari', 'Numerator', 'Denominator', 'Catatan'],
    );

    assert.deepEqual(
        columns.map(({ key, title, role }) => ({ key, title, role })),
        [
            { key: 'weekday', title: 'Hari', role: 'auxiliary' },
            { key: 'numerator', title: 'Numerator', role: 'numerator' },
            { key: 'denominator', title: 'Denominator', role: 'denominator' },
            { key: 'col_4', title: 'Catatan', role: 'auxiliary' },
        ],
    );
});

test('does not reassign an existing key to a new column inserted in the middle', () => {
    const columns = reconcileSpreadsheetColumns(
        [
            { key: 'col_1', title: 'Nama', role: 'auxiliary' },
            { key: 'col_2', title: 'Jumlah N', role: 'numerator' },
            { key: 'col_3', title: 'Jumlah D', role: 'denominator' },
        ],
        ['Nama', 'Keterangan', 'Jumlah N', 'Jumlah D'],
    );

    assert.deepEqual(
        columns.map(({ key, title, role }) => ({ key, title, role })),
        [
            { key: 'col_1', title: 'Nama', role: 'auxiliary' },
            { key: 'col_4', title: 'Keterangan', role: 'auxiliary' },
            { key: 'col_2', title: 'Jumlah N', role: 'numerator' },
            { key: 'col_3', title: 'Jumlah D', role: 'denominator' },
        ],
    );
});

test('clears metric roles when the header identity changes at that position', () => {
    const columns = reconcileSpreadsheetColumns(
        [
            { key: 'old_num', title: 'Old numerator', role: 'numerator' },
            { key: 'old_den', title: 'Old denominator', role: 'denominator' },
        ],
        ['Bulan', 'Hari'],
    );

    assert.deepEqual(
        columns.map(({ key, title, role }) => ({ key, title, role })),
        [
            { key: 'old_num', title: 'Bulan', role: 'auxiliary' },
            { key: 'old_den', title: 'Hari', role: 'auxiliary' },
        ],
    );
});

test('does not keep a stale metric title for a column with an empty header', () => {
    const columns = reconcileSpreadsheetColumns(
        [{ key: 'old_num', title: 'Numerator', role: 'numerator' }],
        [null],
    );

    assert.deepEqual(columns, [
        {
            key: 'old_num',
            title: 'Kolom A',
            role: 'auxiliary',
        },
    ]);
});

test('trims stale workbook cells and merges to the configured column count', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                columnCount: 140,
                cellData: {
                    0: { 0: { v: 'Title' }, 139: { v: 'Old header' } },
                    1: { 0: { v: 'Date' }, 4: { v: 'Notes' } },
                    2: { 139: { v: 'Old value' } },
                },
                mergeData: [
                    {
                        startRow: 0,
                        endRow: 0,
                        startColumn: 0,
                        endColumn: 139,
                    },
                    {
                        startRow: 0,
                        endRow: 0,
                        startColumn: 139,
                        endColumn: 139,
                    },
                ],
            },
        },
    };

    normalizeSpreadsheetWorkbookColumns(workbook, 5);

    assert.equal(workbook.sheets['sheet-1'].columnCount, 5);
    assert.deepEqual(workbook.sheets['sheet-1'].cellData, {
        0: { 0: { v: 'Title' } },
        1: { 0: { v: 'Date' }, 4: { v: 'Notes' } },
    });
    assert.deepEqual(workbook.sheets['sheet-1'].mergeData, [
        {
            startRow: 0,
            endRow: 0,
            startColumn: 0,
            endColumn: 4,
        },
    ]);
});

test('rejects unknown metric roles', () => {
    const column = { key: 'observed', type: 'text', role: 'auxiliary' };

    assert.throws(
        () => setSpreadsheetColumnRole(column, 'unknown'),
        RangeError,
    );
    assert.equal(column.role, 'auxiliary');
});
