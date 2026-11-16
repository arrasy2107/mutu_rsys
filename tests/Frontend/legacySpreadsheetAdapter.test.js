import assert from 'node:assert/strict';
import test from 'node:test';
import {
    createLegacySpreadsheetConfig,
    legacyRecordsToSpreadsheetRows,
    spreadsheetRowsToLegacyRecords,
} from '../../resources/js/Components/FormRenderers/legacySpreadsheetAdapter.js';

test('adapts daily grid records to a spreadsheet while preserving day values', () => {
    const config = createLegacySpreadsheetConfig({
        bulan: 1,
        daysInMonth: 31,
        formFields: [
            { key: 'num', name: 'Numerator', type: 'numerator' },
            { key: 'den', name: 'Denominator', type: 'denominator' },
        ],
        indicator: { multiplier: 100 },
        layoutType: 'daily_grid',
        tahun: 2026,
    });
    const rows = legacyRecordsToSpreadsheetRows({
        bulan: 1,
        config,
        layoutType: 'daily_grid',
        records: { 1: { num: 2, den: 4 } },
    });

    assert.equal(rows[1].day, 1);
    assert.equal(rows[1].weekday, 'Kam');
    assert.equal(rows[1].num, 2);
    assert.equal(rows[1].den, 4);
    assert.equal(rows[1].__formulas.capaian, '=IF(D3=0,0,C3/D3*100)');
    assert.deepEqual(
        spreadsheetRowsToLegacyRecords({
            bulan: 1,
            config,
            layoutType: 'daily_grid',
            previousRecords: {},
            spreadsheetRows: {
                ...rows,
                1: {
                    ...rows[1],
                    col_3: 'Catatan tambahan',
                    __formulas: {
                        ...rows[1].__formulas,
                        col_3: '=C3+D3',
                    },
                },
            },
        })[1],
        {
            num: 2,
            den: 4,
            col_3: 'Catatan tambahan',
            __formulas: { col_3: '=C3+D3' },
        },
    );
});

test('adapts inspection criteria and retains formula metadata in legacy records', () => {
    const config = createLegacySpreadsheetConfig({
        bulan: 1,
        daysInMonth: 31,
        formFields: [],
        indicator: {},
        layoutType: 'inspection_points',
        schema: {
            criteria: [
                { key: 'label_ha', label: 'Stiker High Alert' },
                { key: 'double_check', label: 'Double Check' },
            ],
            points: [{ key: 'troli_1', label: 'Troli 1' }],
        },
        tahun: 2026,
    });
    const rows = legacyRecordsToSpreadsheetRows({
        bulan: 1,
        config,
        layoutType: 'inspection_points',
        records: {
            points: {
                troli_1: {
                    checked: true,
                    criteria: { label_ha: true, double_check: false },
                    catatan: '',
                },
            },
        },
    });
    const records = spreadsheetRowsToLegacyRecords({
        bulan: 1,
        config,
        layoutType: 'inspection_points',
        previousRecords: {},
        spreadsheetRows: {
            troli_1: {
                ...rows.troli_1,
                compliant: 0,
            },
        },
    });

    assert.equal(rows.troli_1.__formulas.compliant, '=IF(AND(B3,C3,D3),1,0)');
    assert.deepEqual(records.points.troli_1.criteria, {
        label_ha: true,
        double_check: false,
    });
    assert.equal(records.points.troli_1.compliant, 0);
    assert.equal(
        records.points.troli_1.__formulas.compliant,
        '=IF(AND(B3,C3,D3),1,0)',
    );
});

test('omits deleted daily rows from the legacy records sent for saving', () => {
    const config = createLegacySpreadsheetConfig({
        bulan: 1,
        daysInMonth: 31,
        formFields: [
            { key: 'num', name: 'Numerator', type: 'numerator' },
            { key: 'den', name: 'Denominator', type: 'denominator' },
        ],
        indicator: {},
        layoutType: 'daily_grid',
        tahun: 2026,
    });
    const savedRows = spreadsheetRowsToLegacyRecords({
        bulan: 1,
        config,
        layoutType: 'daily_grid',
        previousRecords: {
            1: { num: 3, den: 4 },
            2: { num: 2, den: 3 },
        },
        spreadsheetRows: {
            2: { num: 5, den: 6 },
        },
    });

    assert.deepEqual(savedRows, {
        2: { num: 5, den: 6 },
    });
});

test('preserves category, entity, and location records through spreadsheet mapping', () => {
    const categoryConfig = createLegacySpreadsheetConfig({
        bulan: 1,
        daysInMonth: 31,
        formFields: [],
        indicator: {},
        layoutType: 'grouped_category',
        schema: {
            categories: [{ key: 'pharmacy', label: 'Farmasi' }],
        },
        tahun: 2026,
    });
    const categoryRows = legacyRecordsToSpreadsheetRows({
        bulan: 1,
        config: categoryConfig,
        layoutType: 'grouped_category',
        records: { categories: { pharmacy: { sample: 10, compliant: 8 } } },
    });
    assert.deepEqual(
        spreadsheetRowsToLegacyRecords({
            bulan: 1,
            config: categoryConfig,
            layoutType: 'grouped_category',
            previousRecords: {},
            spreadsheetRows: categoryRows,
        }).categories.pharmacy,
        { sample: 10, compliant: 8, total_minutes: 0 },
    );

    const entityConfig = createLegacySpreadsheetConfig({
        bulan: 1,
        daysInMonth: 31,
        formFields: [],
        indicator: {},
        layoutType: 'entity_checklist',
        schema: {
            columns: [
                {
                    key: 'sample',
                    label: 'Sampel',
                    type: 'number',
                    is_denominator: true,
                },
                {
                    key: 'aturan_pakai',
                    label: 'Aturan Pakai',
                    type: 'number',
                    is_numerator: true,
                },
            ],
            entities: [{ id: 'doctor_1', name: 'Dokter 1' }],
        },
        tahun: 2026,
    });
    const entityRows = legacyRecordsToSpreadsheetRows({
        bulan: 1,
        config: entityConfig,
        layoutType: 'entity_checklist',
        records: {
            entities: { doctor_1: { sample: 4, aturan_pakai: 3 } },
        },
    });
    assert.deepEqual(
        spreadsheetRowsToLegacyRecords({
            bulan: 1,
            config: entityConfig,
            layoutType: 'entity_checklist',
            previousRecords: {},
            spreadsheetRows: entityRows,
        }).entities.doctor_1,
        { sample: 4, aturan_pakai: 3 },
    );

    const locationConfig = createLegacySpreadsheetConfig({
        bulan: 1,
        daysInMonth: 31,
        formFields: [],
        indicator: {},
        layoutType: 'location_matrix',
        schema: { locations: [{ key: 'igd', label: 'IGD' }] },
        tahun: 2026,
    });
    const locationRows = legacyRecordsToSpreadsheetRows({
        bulan: 1,
        config: locationConfig,
        layoutType: 'location_matrix',
        records: {
            locations: { igd: { fornas: 12, generik: 3, total_resep: 15 } },
        },
    });
    assert.deepEqual(
        spreadsheetRowsToLegacyRecords({
            bulan: 1,
            config: locationConfig,
            layoutType: 'location_matrix',
            previousRecords: {},
            spreadsheetRows: locationRows,
        }).locations.igd,
        { fornas: 12, generik: 3, total_resep: 15 },
    );
});

test('adapts a monthly summary without changing other months', () => {
    const config = createLegacySpreadsheetConfig({
        bulan: 4,
        daysInMonth: 30,
        formFields: [],
        indicator: { multiplier: 100 },
        layoutType: 'monthly_summary',
        tahun: 2026,
    });
    const previousRecords = {
        3: { num: 8, den: 10 },
        4: { num: 5, den: 10, notes: 'Catatan lama' },
    };
    const rows = legacyRecordsToSpreadsheetRows({
        bulan: 4,
        config,
        layoutType: 'monthly_summary',
        records: previousRecords,
    });
    const records = spreadsheetRowsToLegacyRecords({
        bulan: 4,
        config,
        layoutType: 'monthly_summary',
        previousRecords,
        spreadsheetRows: { 4: { ...rows[4], num: 6 } },
    });

    assert.equal(rows[4].__formulas.capaian, '=IF(C3=0,0,B3/C3*100)');
    assert.deepEqual(records[3], previousRecords[3]);
    assert.equal(records[4].num, 6);
});
