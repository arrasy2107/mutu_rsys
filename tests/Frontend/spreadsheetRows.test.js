import assert from 'node:assert/strict';
import test from 'node:test';
import { reactive, toRaw } from 'vue';
import { cloneSpreadsheetRows } from '../../resources/js/Components/FormRenderers/spreadsheetRows.js';

test('clones rows from a reactive array without retaining Vue proxies', () => {
    const source = reactive([
        { tanggal: '2026-11-01', detail: { compliant: true } },
    ]);

    const rows = cloneSpreadsheetRows(source);

    assert.deepEqual(rows, [
        { tanggal: '2026-11-01', detail: { compliant: true } },
    ]);
    assert.notStrictEqual(rows[0], toRaw(source[0]));
    assert.notStrictEqual(rows[0].detail, toRaw(source[0].detail));

    rows[0].detail.compliant = false;
    assert.equal(source[0].detail.compliant, true);
});

test('clones rows from reactive items and object-shaped data', () => {
    const source = reactive({
        items: [{ value: 1 }],
        first: { value: 2 },
        second: { value: 3 },
    });

    const itemRows = cloneSpreadsheetRows(source.items);
    const objectRows = cloneSpreadsheetRows(
        Object.values(source).filter(
            (row) => row && typeof row === 'object' && !Array.isArray(row),
        ),
    );

    assert.deepEqual(itemRows, [{ value: 1 }]);
    assert.deepEqual(objectRows, [{ value: 2 }, { value: 3 }]);
});
