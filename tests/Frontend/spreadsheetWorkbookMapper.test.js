import assert from 'node:assert/strict';
import test from 'node:test';
import {
    clearSpreadsheetBodyValues,
    getSpreadsheetEntryRowCount,
    getSpreadsheetFixedRows,
    getSpreadsheetLogicalEntryRowCount,
    getSpreadsheetPopulatedRowCount,
    getSpreadsheetSummaryMetricRowRange,
    getSpreadsheetSummaryRowIndexes,
    isSpreadsheetAggregateFormula,
    mapSpreadsheetRows,
} from '../../resources/js/Components/FormRenderers/spreadsheetWorkbookMapper.js';
import {
    getSpreadsheetFormulaPatterns,
    translateSpreadsheetFormula,
} from '../../resources/js/Components/FormRenderers/spreadsheetRows.js';

test('clears template body values while preserving cell styles and workbook structure', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        styles: {
            centered: { ht: 'center', vt: 'middle', tb: 'wrap' },
        },
        sheets: {
            'sheet-1': {
                rowCount: 6,
                columnCount: 3,
                rowData: { 2: { h: 42 } },
                columnData: { 1: { w: 120 } },
                mergeData: [
                    {
                        startRow: 0,
                        endRow: 0,
                        startColumn: 0,
                        endColumn: 2,
                    },
                ],
                cellData: {
                    0: { 0: { v: 'Title' } },
                    1: {
                        0: { v: 'Header', s: 'centered' },
                        1: { v: 'Value' },
                    },
                    2: {
                        0: { v: 'Old value', s: 'centered' },
                        1: { v: 4 },
                        2: { f: '=SUM(B3:B5)', s: 'centered' },
                    },
                    3: { 0: { v: 'Summary', s: 'centered' } },
                },
            },
        },
    };

    const result = clearSpreadsheetBodyValues(workbook, 2, [3]);
    const sheet = result.sheets['sheet-1'];

    assert.deepEqual(sheet.cellData, {
        0: { 0: { v: 'Title' } },
        1: {
            0: { v: 'Header', s: 'centered' },
            1: { v: 'Value' },
        },
        2: {
            0: { s: 'centered' },
            2: { f: '=SUM(B3:B5)', s: 'centered' },
        },
        3: { 0: { v: 'Summary', s: 'centered' } },
    });
    assert.deepEqual(result.styles, {
        centered: { ht: 'center', vt: 'middle', tb: 'wrap' },
    });
    assert.deepEqual(sheet.rowData, { 2: { h: 42 } });
    assert.deepEqual(sheet.columnData, { 1: { w: 120 } });
    assert.equal(sheet.mergeData.length, 1);
});

test('discovers and translates template formulas for additional dynamic rows', () => {
    const patterns = getSpreadsheetFormulaPatterns(
        {
            sheetOrder: ['sheet-1'],
            sheets: {
                'sheet-1': {
                    cellData: {
                        2: {
                            2: { f: '=IF(A3=0,0,B3/A3)' },
                        },
                    },
                },
            },
        },
        2,
        [{ key: 'numerator' }, { key: 'denominator' }, { key: 'rate' }],
    );

    assert.deepEqual(patterns, {
        rate: {
            formula: '=IF(A3=0,0,B3/A3)',
            sourceRow: 3,
        },
    });
    assert.equal(
        translateSpreadsheetFormula(patterns.rate.formula, 1),
        '=IF(A4=0,0,B4/A4)',
    );
});

test('does not shift absolute or quoted formula references', () => {
    const formula = '=IF(A3="$A$3",$B$1+A3,LOG10(A3))';

    assert.equal(
        translateSpreadsheetFormula(formula, 2),
        '=IF(A5="$A$3",$B$1+A5,LOG10(A5))',
    );
});

test('keeps the active dynamic row count independent of formatted worksheet rows', () => {
    const rowCount = getSpreadsheetEntryRowCount({
        activeRowCount: 4,
        fixedRowCount: 0,
        maximumRowCount: 498,
        rowType: 'dynamic_rows',
    });

    assert.equal(rowCount, 4);
});

test('does not count template formula-only rows as populated input rows', () => {
    const rowCount = getSpreadsheetPopulatedRowCount(
        [
            [null, 0],
            [null, 0],
            [0, 4],
            ['', 0],
        ],
        [
            ['', '=SUM(A1:A1)'],
            ['', '=SUM(A2:A2)'],
            ['', '=SUM(A3:A3)'],
            ['', '=SUM(A4:A4)'],
        ],
    );

    assert.equal(rowCount, 3);
});

test('identifies summary rows without classifying same-row formulas as totals', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                cellData: {
                    2: { 2: { f: '=SUM(A3:B3)' } },
                    3: { 0: { v: 'Total' }, 1: { f: '=SUM(B3:B8)' } },
                },
            },
        },
    };

    assert.equal(isSpreadsheetAggregateFormula('=SUM(A3:B3)'), false);
    assert.equal(isSpreadsheetAggregateFormula('=SUM(B3:B8)'), true);
    assert.deepEqual(getSpreadsheetSummaryRowIndexes(workbook, 2), [3]);
});

test('uses a metric summary formula range to exclude populated rows outside its range', () => {
    const workbook = {
        sheetOrder: ['sheet-1'],
        sheets: {
            'sheet-1': {
                cellData: {
                    10: {
                        3: { f: '=SUM(D2:D9)', v: 49 },
                        4: { f: '=SUM(E2:E9)', v: 4 },
                    },
                },
            },
        },
    };

    assert.deepEqual(getSpreadsheetSummaryMetricRowRange(workbook, 1, 3), {
        startRow: 0,
        endRow: 7,
    });
    assert.deepEqual(getSpreadsheetSummaryMetricRowRange(workbook, 1, 4), {
        startRow: 0,
        endRow: 7,
    });
    assert.equal(getSpreadsheetSummaryMetricRowRange(workbook, 1, 2), null);
});

test('skips summary formulas when discovering formulas for new input rows', () => {
    const patterns = getSpreadsheetFormulaPatterns(
        {
            sheetOrder: ['sheet-1'],
            sheets: {
                'sheet-1': {
                    cellData: {
                        2: { 2: { f: '=A3+B3' } },
                        3: { 2: { f: '=SUM(C3:C8)' } },
                    },
                },
            },
        },
        2,
        [{ key: 'first' }, { key: 'second' }, { key: 'total' }],
    );

    assert.deepEqual(patterns, {
        total: {
            formula: '=A3+B3',
            sourceRow: 3,
        },
    });
});

test('adds dynamic rows up to the available worksheet capacity', () => {
    const rowCount = getSpreadsheetEntryRowCount({
        activeRowCount: 499,
        fixedRowCount: 0,
        maximumRowCount: 498,
        rowType: 'dynamic_rows',
    });

    assert.equal(rowCount, 498);
});

test('keeps fixed row counts defined by the form format', () => {
    const rowCount = getSpreadsheetEntryRowCount({
        activeRowCount: 7,
        fixedRowCount: 3,
        maximumRowCount: 498,
        rowType: 'fixed_rows',
    });

    assert.equal(rowCount, 3);
});

test('restores edited fixed row counts and tracks structural row changes', () => {
    const restoredRowCount = getSpreadsheetLogicalEntryRowCount({
        baselineRowCount: 31,
        currentWorksheetRowCount: 510,
        initialWorksheetRowCount: 510,
        rowType: 'fixed_rows',
        savedRowCount: 29,
    });
    const insertedRowCount = getSpreadsheetLogicalEntryRowCount({
        baselineRowCount: restoredRowCount,
        currentWorksheetRowCount: 511,
        initialWorksheetRowCount: 510,
        rowType: 'fixed_rows',
    });
    const deletedRowCount = getSpreadsheetLogicalEntryRowCount({
        baselineRowCount: restoredRowCount,
        currentWorksheetRowCount: 509,
        initialWorksheetRowCount: 510,
        rowType: 'fixed_rows',
    });

    assert.equal(restoredRowCount, 29);
    assert.equal(insertedRowCount, 30);
    assert.equal(deletedRowCount, 28);
    assert.equal(
        getSpreadsheetLogicalEntryRowCount({
            baselineRowCount: 31,
            currentWorksheetRowCount: 510,
            initialWorksheetRowCount: 510,
            rowType: 'fixed_rows',
            savedRowCount: null,
        }),
        31,
    );
});

test('maps spreadsheet values and formula metadata to dynamic rows', () => {
    const rows = mapSpreadsheetRows({
        columns: [
            { key: 'num', role: 'numerator' },
            { key: 'den', role: 'denominator' },
            { key: 'rate', role: 'auxiliary' },
        ],
        formulas: [['', '', 'A1/B1']],
        rowType: 'dynamic_rows',
        values: [[2, 4, 0.5]],
    });

    assert.deepEqual(rows, [
        {
            num: 2,
            den: 4,
            rate: 0.5,
            __formulas: { rate: '=A1/B1' },
        },
    ]);
});

test('maps fixed spreadsheet rows by configured row key and excludes label cells', () => {
    const rows = mapSpreadsheetRows({
        columns: [
            { key: 'label', role: 'label' },
            { key: 'value', role: 'numerator' },
        ],
        fixedRows: [{ key: 'morning' }, { key: 'evening' }],
        formulas: [
            ['', 'B1*2'],
            ['', ''],
        ],
        rowType: 'fixed_rows',
        values: [
            ['Pagi', 12],
            ['Malam', 8],
        ],
    });

    assert.deepEqual(rows, {
        morning: { value: 12, __formulas: { value: '=B1*2' } },
        evening: { value: 8 },
    });
});

test('preserves a column inserted before the fixed-row label column', () => {
    const columns = [
        { key: 'col_1', role: 'auxiliary' },
        { key: 'day', role: 'label' },
        { key: 'num', role: 'numerator' },
    ];
    const values = [['Catatan', 2, 5]];
    const rows = mapSpreadsheetRows({
        columns,
        fixedRows: getSpreadsheetFixedRows({
            columns,
            fixedRows: [{ key: '1' }],
            values,
        }),
        formulas: [['', '', '']],
        rowType: 'fixed_rows',
        values,
    });

    assert.deepEqual(rows, {
        2: { col_1: 'Catatan', num: 5 },
    });
});

test('keeps shifted fixed rows distinct when a blank row is inserted', () => {
    const columns = [
        { key: 'day', role: 'label' },
        { key: 'num', role: 'numerator' },
    ];
    const values = [
        [1, 4],
        ['', ''],
        [2, 5],
    ];
    const rows = mapSpreadsheetRows({
        columns,
        fixedRows: getSpreadsheetFixedRows({
            columns,
            fixedRows: [
                { key: '1', label: '1' },
                { key: '2', label: '2' },
            ],
            values,
        }),
        formulas: [
            ['', ''],
            ['', ''],
            ['', ''],
        ],
        rowType: 'fixed_rows',
        values,
    });

    assert.deepEqual(rows, {
        1: { num: 4 },
        row_2: { num: '' },
        2: { num: 5 },
    });
});

test('includes a newly added dynamic row in the save payload shape', () => {
    const rows = mapSpreadsheetRows({
        columns: [
            { key: 'numerator', role: 'numerator' },
            { key: 'denominator', role: 'denominator' },
        ],
        formulas: [
            ['', ''],
            ['', ''],
            ['', ''],
        ],
        rowType: 'dynamic_rows',
        values: [
            [1, 1],
            [0, 1],
            [1, 1],
        ],
    });

    assert.deepEqual(rows, [
        { numerator: 1, denominator: 1 },
        { numerator: 0, denominator: 1 },
        { numerator: 1, denominator: 1 },
    ]);
});
