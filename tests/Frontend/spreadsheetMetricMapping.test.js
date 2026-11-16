import assert from 'node:assert/strict';
import test from 'node:test';
import {
    calculateSpreadsheetMetricSource,
    isCompleteSpreadsheetMetricMapping,
    parseSpreadsheetMetricRange,
    resolveSpreadsheetMetricSelection,
    supportsSpreadsheetMetricMapping,
} from '../../resources/js/Components/FormRenderers/spreadsheetMetricMapping.js';

test('enables metric mapping on spreadsheet renderers without a layout restriction', () => {
    assert.equal(
        supportsSpreadsheetMetricMapping({ renderer: 'spreadsheet' }),
        true,
    );
    assert.equal(
        supportsSpreadsheetMetricMapping({ renderer: 'custom' }),
        false,
    );
});

test('enables metric mapping for legacy dynamic and fixed spreadsheet layouts', () => {
    assert.equal(
        supportsSpreadsheetMetricMapping({}, 'dynamic_rows'),
        true,
    );
    assert.equal(
        supportsSpreadsheetMetricMapping({ row_type: 'fixed_rows' }),
        true,
    );
    assert.equal(
        supportsSpreadsheetMetricMapping(
            { renderer: 'custom' },
            'dynamic_rows',
        ),
        false,
    );
});

test('calculates selected metric ranges using the configured aggregation', () => {
    const values = [
        [4, null],
        [3, ''],
    ];

    assert.equal(calculateSpreadsheetMetricSource(values, 'sum'), 7);
    assert.equal(calculateSpreadsheetMetricSource(values, 'count'), 2);
    assert.equal(calculateSpreadsheetMetricSource(values, 'counta'), 2);
});

test('sums checked values while ignoring unrelated text', () => {
    assert.equal(
        calculateSpreadsheetMetricSource(
            [
                [true, 'Sesuai'],
                [false, 'catatan'],
                [2, null],
            ],
            'sum',
        ),
        4,
    );
});

test('requires a valid source for both numerator and denominator', () => {
    assert.equal(
        isCompleteSpreadsheetMetricMapping({
            numerator: { range: 'E2:E8', aggregation: 'sum' },
            denominator: { range: 'D2:D8', aggregation: 'counta' },
        }),
        true,
    );
    assert.equal(
        isCompleteSpreadsheetMetricMapping({
            numerator: { range: 'E2:E8', aggregation: 'average' },
            denominator: { range: '', aggregation: 'sum' },
        }),
        false,
    );
});

test('parses valid cell ranges and rejects reversed ranges', () => {
    assert.deepEqual(parseSpreadsheetMetricRange('$D$2:E9'), {
        startColumn: 4,
        startRow: 2,
        endColumn: 5,
        endRow: 9,
    });
    assert.equal(parseSpreadsheetMetricRange('E9:D2'), null);
});

test('allows populated data ranges and keeps the selected aggregation', () => {
    assert.deepEqual(
        resolveSpreadsheetMetricSelection({
            selectedRange: 'D2:D9',
            workbookSnapshot: {
                sheetOrder: ['sheet'],
                sheets: {
                    sheet: { rowCount: 10, columnCount: 5 },
                },
            },
            headerRowCount: 1,
            summaryRowIndexes: [9],
        }),
        { range: 'D2:D9', aggregation: null },
    );
});

test('uses the data range and aggregation behind one selected total formula', () => {
    assert.deepEqual(
        resolveSpreadsheetMetricSelection({
            selectedRange: 'D10',
            workbookSnapshot: {
                sheetOrder: ['sheet'],
                sheets: {
                    sheet: {
                        rowCount: 10,
                        columnCount: 5,
                        cellData: {
                            9: { 3: { f: '=SUM(D2:D9)', v: 54 } },
                        },
                    },
                },
            },
            headerRowCount: 1,
            summaryRowIndexes: [9],
        }),
        { range: 'D2:D9', aggregation: 'sum' },
    );
});

test('rejects multi-cell total selections and summary formula ranges', () => {
    const workbookSnapshot = {
        sheetOrder: ['sheet'],
        sheets: {
            sheet: {
                rowCount: 10,
                columnCount: 5,
                cellData: {
                    9: {
                        3: { f: '=SUM(D2:D9)', v: 54 },
                        4: { f: '=SUM(E2:E9)', v: 11 },
                    },
                },
            },
        },
    };

    assert.equal(
        resolveSpreadsheetMetricSelection({
            selectedRange: 'D10:E10',
            workbookSnapshot,
            headerRowCount: 1,
            summaryRowIndexes: [9],
        }),
        null,
    );
    assert.equal(
        resolveSpreadsheetMetricSelection({
            selectedRange: 'D2:D10',
            workbookSnapshot,
            headerRowCount: 1,
            summaryRowIndexes: [9],
        }),
        null,
    );
});
