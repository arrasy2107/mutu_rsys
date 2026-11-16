import assert from 'node:assert/strict';
import test from 'node:test';
import {
    getFormatForIndicator,
    getIndicatorById,
} from '../../resources/js/Pages/DataEntry/formFormatTarget.js';

test('uses the persisted format only when it belongs to the selected indicator', () => {
    const indicator = { id: 88 };
    const format = { id: 30, indicator_id: '88', schema: { columns: [] } };

    assert.equal(getFormatForIndicator(indicator, format), format);
    assert.equal(
        getFormatForIndicator(indicator, {
            id: 31,
            indicator_id: 89,
            schema: { columns: [{ key: 'wrong' }] },
        }),
        null,
    );
});

test('uses the selected indicator schema when its persisted format is missing', () => {
    const indicator = {
        id: 88,
        kode: 'IMP-U1',
        judul: 'Indikator Test',
        form_layout_type: 'dynamic_rows',
        form_schema: {
            format_id: 30,
            nama_format: 'Format Triase',
            row_type: 'dynamic_rows',
            columns: [{ key: 'compliant' }, { key: 'sample' }],
        },
    };

    assert.deepEqual(getFormatForIndicator(indicator, null), {
        id: null,
        indicator_id: 88,
        nama_format: 'Format Triase',
        table_title: '',
        row_type: 'dynamic_rows',
        petunjuk_pengisian: '',
        schema: indicator.form_schema,
    });
});

test('does not open an editor from an indicator without a saved format schema', () => {
    assert.equal(
        getFormatForIndicator({ id: 88, form_schema: null }, null),
        null,
    );
    assert.equal(
        getFormatForIndicator(
            { id: 88, form_schema: { format_id: 30, columns: [] } },
            null,
        ),
        null,
    );
});

test('resolves the edited format indicator from selected data regardless of ID type', () => {
    const selectedIndicator = { id: 88, judul: 'Indikator terpilih' };
    const unrelatedIndicator = { id: 89, judul: 'Indikator lain' };

    assert.equal(
        getIndicatorById('88', [unrelatedIndicator, selectedIndicator]),
        selectedIndicator,
    );
    assert.equal(
        getIndicatorById(88, [unrelatedIndicator], selectedIndicator),
        selectedIndicator,
    );
    assert.equal(
        getIndicatorById(88, [unrelatedIndicator], {
            id: 89,
            judul: 'Bukan indikator terpilih',
        }),
        null,
    );
    assert.equal(getIndicatorById(null, [selectedIndicator]), null);
});
