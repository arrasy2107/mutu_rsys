<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import {
    cloneSpreadsheetRows,
    getSpreadsheetFormulaPatterns,
    translateSpreadsheetFormula,
} from './spreadsheetRows';
import 'tabulator-tables/dist/css/tabulator.min.css';

const props = defineProps({
    modelValue: [Object, Array],
    indicator: Object,
    schema: Object,
    formFields: Array,
    tahun: Number,
    bulan: Number,
    permissions: Object,
    isPeriodApproved: Boolean,
    readOnly: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'update:summary', 'change']);

const tableElement = ref(null);
const rowCount = ref(0);
const summary = ref({ numerator: 0, denominator: 0, capaian: 0 });
const isEditable = computed(
    () =>
        !props.readOnly &&
        props.permissions?.canEditDaily &&
        !props.isPeriodApproved,
);
const currentSchema = computed(
    () => props.schema || props.indicator?.form_schema || {},
);
const rowType = computed(
    () =>
        currentSchema.value.row_type ||
        props.indicator?.form_layout_type ||
        'dynamic_rows',
);
const columns = computed(() => currentSchema.value.columns || []);
const rows = computed(() => currentSchema.value.rows || []);
const formulaPatterns = computed(() =>
    getSpreadsheetFormulaPatterns(
        currentSchema.value.workbook_template,
        Number(currentSchema.value.header_rows) || 2,
        columns.value,
    ),
);
const tableTitle = computed(
    () =>
        currentSchema.value.table_title ||
        `Tabel Pencatatan ${props.indicator?.judul || 'Indikator'}`,
);
const instructions = computed(
    () =>
        currentSchema.value.petunjuk_pengisian ||
        props.indicator?.definisi_operasional ||
        '',
);

let table = null;

const isChecked = (value) => {
    return (
        value === true ||
        value === 1 ||
        value === '1' ||
        value === 'true' ||
        value === 'v' ||
        value === 'V' ||
        value === '√' ||
        value === 'ya' ||
        value === 'Ya' ||
        value === 'Sesuai'
    );
};

const createEmptyRow = () => {
    const row = {
        _id: `row_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
    };
    columns.value.forEach((column) => {
        if (formulaPatterns.value[column.key]) {
            row[column.key] = '';
        } else if (column.type === 'date') {
            row[column.key] = '';
        } else if (column.type === 'number' || column.type === 'decimal') {
            row[column.key] = 0;
        } else if (column.type === 'checkbox') {
            row[column.key] = false;
        } else {
            row[column.key] = '';
        }
    });

    const formulas = getRowFormulas(0);
    if (Object.keys(formulas).length) {
        row.__formulas = formulas;
    }

    return row;
};

const getRowFormulas = (rowIndex, existingFormulas = {}) => {
    const formulas = { ...existingFormulas };
    const headerRows = Number(currentSchema.value.header_rows) || 2;
    const targetRow = headerRows + rowIndex + 1;

    Object.entries(formulaPatterns.value).forEach(([key, pattern]) => {
        formulas[key] = translateSpreadsheetFormula(
            pattern.formula,
            targetRow - pattern.sourceRow,
        );
    });

    return formulas;
};

const toTableData = (value = props.modelValue) => {
    if (rowType.value === 'dynamic_rows') {
        if (Array.isArray(value)) {
            return value.length
                ? cloneSpreadsheetRows(value)
                : [createEmptyRow()];
        }

        if (Array.isArray(value?.items)) {
            return value.items.length
                ? cloneSpreadsheetRows(value.items)
                : [createEmptyRow()];
        }

        if (value && typeof value === 'object') {
            const values = Object.values(value).filter(
                (row) => row && typeof row === 'object',
            );
            return values.length
                ? cloneSpreadsheetRows(values)
                : [createEmptyRow()];
        }

        return [createEmptyRow()];
    }

    const fixedRows = rows.value.length
        ? rows.value
        : [{ key: 'item_1', label: 'Baris Pengamatan' }];

    return fixedRows.map((row) => {
        const values = {
            ...(row.values || {}),
            ...(value?.[row.key] || {}),
        };
        const record = {
            __gridKey: row.key,
            __gridLabel: row.label || '',
        };

        columns.value.forEach((column, index) => {
            if (index === 0) {
                return;
            }

            record[column.key] =
                values[column.key] ?? (column.type === 'number' ? 0 : '');
        });

        return record;
    });
};

const meaningfulValue = (value) =>
    value !== null &&
    value !== undefined &&
    value !== '' &&
    value !== false &&
    value !== 0 &&
    value !== '0';

const getPayload = (tableData) => {
    if (rowType.value === 'dynamic_rows') {
        const fields = new Set(columns.value.map((column) => column.key));
        return tableData.flatMap((row, rowIndex) => {
            const hasValue = Object.entries(row).some(
                ([key, value]) =>
                    key !== '_id' &&
                    !key.startsWith('__') &&
                    meaningfulValue(value),
            );
            if (!hasValue) {
                return [];
            }

            const payload = Object.fromEntries(
                [...fields].map((field) => [field, row[field] ?? '']),
            );
            if (row._id !== undefined) {
                payload._id = row._id;
            }
            const formulas = getRowFormulas(rowIndex, row.__formulas || {});
            if (Object.keys(formulas).length) {
                payload.__formulas = formulas;
            }
            return [payload];
        });
    }

    const result = {};
    tableData.forEach((row) => {
        result[row.__gridKey] = {};
        columns.value.forEach((column, index) => {
            if (index !== 0) {
                result[row.__gridKey][column.key] = row[column.key] ?? '';
            }
        });
    });

    return result;
};

const metricValue = (value, type) => {
    if (type === 'checkbox') {
        return isChecked(value) ? 1 : 0;
    }

    if (
        typeof value === 'number' ||
        (typeof value === 'string' &&
            value.trim() !== '' &&
            !Number.isNaN(Number(value)))
    ) {
        return Number(value) || 0;
    }

    return isChecked(value) ? 1 : 0;
};

const summarize = (tableData) => {
    const metricRows =
        rowType.value === 'fixed_rows'
            ? tableData.map((record) => {
                  const row = { ...record };
                  delete row.__gridKey;
                  delete row.__gridLabel;
                  return row;
              })
            : tableData;
    const numeratorColumns = columns.value.filter(
        (column) => column.role === 'numerator',
    );
    const denominatorColumns = columns.value.filter(
        (column) => column.role === 'denominator',
    );
    const compositeCriteria =
        currentSchema.value.formula_config?.composite_criteria || [];

    const validRows = metricRows.filter((row) =>
        Object.entries(row).some(
            ([key, value]) => key !== '_id' && meaningfulValue(value),
        ),
    );

    let numerator = 0;
    if (rowType.value === 'dynamic_rows' && compositeCriteria.length) {
        numerator = validRows.filter((row) =>
            compositeCriteria.every((key) => isChecked(row[key])),
        ).length;
    } else {
        metricRows.forEach((row) => {
            numeratorColumns.forEach((column) => {
                numerator += metricValue(row[column.key], column.type);
            });
        });
    }

    let denominator = 0;
    if (denominatorColumns.length) {
        metricRows.forEach((row) => {
            denominatorColumns.forEach((column) => {
                denominator += metricValue(row[column.key], column.type);
            });
        });
    } else if (rowType.value === 'dynamic_rows') {
        denominator = validRows.length;
    }

    const multiplier = Number(props.indicator?.multiplier) || 100;
    summary.value = {
        numerator,
        denominator,
        capaian:
            denominator > 0
                ? Number(((numerator / denominator) * multiplier).toFixed(2))
                : 0,
    };
    rowCount.value = tableData.length;
};

const emitTableChange = () => {
    if (!table) {
        return;
    }

    const tableData = table.getData();
    summarize(tableData);
    emit('update:modelValue', getPayload(tableData));
    emit('update:summary', summary.value);
    emit('change');
};

const pasteTableRange = (data) => {
    if (!table || !isEditable.value || !data.length) {
        return [];
    }

    const activeRange = table.modules.selectRange.activeRange;
    const bounds = activeRange?.getBounds();
    const startCell = bounds?.start;
    const tableRows = table.getRows();
    const startRow = startCell ? tableRows.indexOf(startCell.row) : -1;

    if (startRow < 0) {
        return [];
    }

    const isSingleCell = bounds.start === bounds.end;
    const selectedRowCount = isSingleCell
        ? data.length
        : tableRows.indexOf(bounds.end.row) - startRow + 1;
    const pasteRowCount =
        rowType.value === 'dynamic_rows'
            ? data.length
            : Math.min(selectedRowCount, data.length);
    const rowsToPaste = data
        .slice(0, pasteRowCount)
        .map((row) =>
            Object.fromEntries(
                Object.entries(row).filter(
                    ([key]) => !formulaPatterns.value[key],
                ),
            ),
        );
    const additionalRows = Math.max(
        0,
        startRow + rowsToPaste.length - tableRows.length,
    );

    const applyPaste = async () => {
        const targetRows = table
            .getRows()
            .slice(startRow, startRow + rowsToPaste.length);
        await Promise.all(
            targetRows.map((row, index) =>
                row.update(rowsToPaste[index % rowsToPaste.length]),
            ),
        );
        emitTableChange();
    };

    void (async () => {
        try {
            if (additionalRows > 0) {
                await table.addData(
                    Array.from({ length: additionalRows }, createEmptyRow),
                );
            }
            await applyPaste();
        } catch (error) {
            console.error('Gagal menempelkan data spreadsheet.', error);
            window.notify?.toast?.error(
                'Data Tidak Ditempel',
                'Terjadi kesalahan saat menambahkan data dari clipboard.',
            );
        }
    })();

    return [];
};

const flushChanges = () => {
    emitTableChange();
};

const getEditor = (column) => {
    if (!isEditable.value) {
        return false;
    }

    switch (column.type) {
        case 'number':
        case 'decimal':
        case 'date':
        case 'time':
            return column.type === 'decimal' ? 'number' : column.type;
        case 'checkbox':
            return true;
        case 'select':
            return 'list';
        default:
            return 'input';
    }
};

const buildColumn = (column, index) => {
    const isFixedLabel =
        rowType.value === 'fixed_rows' &&
        (column.role === 'label' || index === 0);
    const isCalculatedColumn = Boolean(formulaPatterns.value[column.key]);
    const definition = {
        title: column.title || column.key,
        field: column.key,
        minWidth: 110,
        width: Number.parseInt(column.width, 10) || undefined,
        hozAlign: ['number', 'decimal', 'checkbox', 'date', 'time'].includes(
            column.type,
        )
            ? 'center'
            : 'left',
        headerHozAlign: 'center',
        cssClass:
            column.role === 'numerator'
                ? 'spreadsheet-numerator'
                : column.role === 'denominator'
                  ? 'spreadsheet-denominator'
                  : '',
        editor: isFixedLabel || isCalculatedColumn ? false : getEditor(column),
        validator: ['number', 'decimal'].includes(column.type)
            ? ['numeric', 'min:0']
            : undefined,
    };

    if (isFixedLabel) {
        definition.field = index === 0 ? '__gridLabel' : column.key;
        definition.editor = false;
        definition.cssClass = 'spreadsheet-row-label';
    } else if (column.type === 'checkbox') {
        definition.formatter = 'tickCross';
    } else if (column.type === 'select') {
        const options = Array.isArray(column.options) ? column.options : [];
        definition.editorParams = {
            values: Object.fromEntries(
                options.map((option) => [option, option]),
            ),
            autocomplete: true,
            allowEmpty: true,
            listOnEmpty: true,
        };
    }

    return definition;
};

const buildColumns = () => {
    const tableColumns = columns.value.map(buildColumn);
    const grouped = [];
    let currentGroup = null;
    let currentColumns = [];

    const flushGroup = () => {
        if (currentGroup) {
            grouped.push({ title: currentGroup, columns: currentColumns });
            currentGroup = null;
            currentColumns = [];
        } else {
            grouped.push(...currentColumns);
            currentColumns = [];
        }
    };

    columns.value.forEach((column, index) => {
        const group = String(column.group || '').trim();
        const tableColumn = tableColumns[index];

        if (!group) {
            flushGroup();
            grouped.push(tableColumn);
            return;
        }

        if (currentGroup && currentGroup !== group) {
            flushGroup();
        }
        currentGroup = group;
        currentColumns.push(tableColumn);
    });
    flushGroup();

    if (rowType.value === 'dynamic_rows' && isEditable.value) {
        grouped.push({
            title: 'Aksi',
            field: '__rowActions',
            headerSort: false,
            hozAlign: 'center',
            width: 76,
            clipboard: false,
            formatter: (cell) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = 'Hapus';
                button.setAttribute('aria-label', 'Hapus baris');
                button.className =
                    'rounded px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50';
                button.addEventListener('click', () =>
                    removeRow(cell.getRow()),
                );
                return button;
            },
        });
    }

    return grouped;
};

const removeRow = (row) => {
    if (table.getDataCount() <= 1) {
        window.notify?.alert?.warning(
            'Baris Terakhir',
            'Tabel harus menyisakan minimal satu baris.',
        );
        return;
    }

    row.delete().then(emitTableChange);
};

const mountTable = () => {
    if (!tableElement.value || !columns.value.length) {
        return;
    }

    table?.destroy();
    table = new Tabulator(tableElement.value, {
        data: toTableData(),
        columns: buildColumns(),
        layout: 'fitDataStretch',
        height: 'min(60vh, 620px)',
        rowHeader: {
            formatter: 'rownum',
            headerSort: false,
            resizable: false,
            frozen: true,
            width: 48,
            hozAlign: 'center',
        },
        columnDefaults: {
            headerSort: false,
            resizable: true,
            vertAlign: 'middle',
        },
        movableColumns: false,
        clipboard: isEditable.value,
        clipboardPasteAction: pasteTableRange,
        clipboardCopyConfig: { columnHeaders: false, rowHeaders: false },
        selectableRange: true,
        validationMode: 'highlight',
        history: true,
    });
    table.on('cellEdited', emitTableChange);
    summarize(table.getData());
    emit('update:summary', summary.value);
};

const addRow = () => {
    if (!table || rowType.value !== 'dynamic_rows') {
        return;
    }

    table.addRow(createEmptyRow()).then(emitTableChange);
};

const copyHeaders = async () => {
    const headerText = columns.value
        .map((column) => column.title || column.key)
        .join('\t');

    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(headerText);
        } else {
            const input = document.createElement('textarea');
            input.value = headerText;
            input.setAttribute('readonly', '');
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            const copied = document.execCommand('copy');
            input.remove();
            if (!copied) {
                throw new Error('Browser menolak penyalinan header.');
            }
        }

        window.notify?.toast?.success(
            'Header Disalin',
            'Nama kolom form berhasil disalin. Tempelkan ke spreadsheet tujuan.',
        );
    } catch (error) {
        console.error('Gagal menyalin header spreadsheet.', error);
        window.notify?.toast?.error(
            'Header Tidak Tersalin',
            'Browser tidak mengizinkan penyalinan otomatis. Coba kembali melalui koneksi aman.',
        );
    }
};

const valuesAreEqual = (left, right) => {
    if (Object.is(left, right)) {
        return true;
    }

    if (Array.isArray(left) || Array.isArray(right)) {
        return (
            Array.isArray(left) &&
            Array.isArray(right) &&
            left.length === right.length &&
            left.every((value, index) => valuesAreEqual(value, right[index]))
        );
    }

    if (
        left === null ||
        right === null ||
        typeof left !== 'object' ||
        typeof right !== 'object'
    ) {
        return false;
    }

    const leftKeys = Object.keys(left).sort();
    const rightKeys = Object.keys(right).sort();
    return (
        leftKeys.length === rightKeys.length &&
        leftKeys.every(
            (key, index) =>
                key === rightKeys[index] &&
                valuesAreEqual(left[key], right[key]),
        )
    );
};

watch(
    () => props.modelValue,
    (value) => {
        if (!table) {
            return;
        }

        const nextData = toTableData(value);
        if (!valuesAreEqual(table.getData(), nextData)) {
            table.replaceData(nextData).then(() => {
                summarize(table.getData());
                emit('update:summary', summary.value);
            });
        }
    },
    { deep: true },
);

watch(
    () => [
        props.schema,
        props.indicator?.form_schema,
        props.permissions?.canEditDaily,
        props.isPeriodApproved,
        props.readOnly,
    ],
    () => mountTable(),
    { deep: true },
);

onMounted(mountTable);
onBeforeUnmount(() => table?.destroy());

defineExpose({ flushChanges });
</script>

<template>
    <section
        class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
    >
        <header
            class="flex flex-col gap-3 border-b border-slate-100 pb-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3 class="font-bold text-slate-800">{{ tableTitle }}</h3>
                <p v-if="instructions" class="mt-1 text-xs text-slate-500">
                    {{ instructions }}
                </p>
                <p v-else-if="readOnly" class="mt-1 text-xs text-slate-500">
                    Mode baca saja. Pilih Edit Isi Data untuk mengubah data.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    v-if="columns.length"
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    @click="copyHeaders"
                >
                    Salin Header
                </button>
                <button
                    v-if="rowType === 'dynamic_rows' && isEditable"
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                    @click="addRow"
                >
                    Tambah Baris
                </button>
            </div>
        </header>

        <div
            class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600"
        >
            <span v-if="readOnly"
                >{{ rowCount }} baris · Tabel dalam mode baca saja.</span
            >
            <span v-else
                >{{ rowCount }} baris · Klik sel untuk mengedit, gunakan
                Ctrl/Cmd+C dan Ctrl/Cmd+V untuk salin-tempel.</span
            >
            <span class="flex flex-wrap gap-3">
                <span
                    >Numerator:
                    <strong class="text-emerald-700">{{
                        summary.numerator
                    }}</strong></span
                >
                <span
                    >Denominator:
                    <strong class="text-sky-700">{{
                        summary.denominator
                    }}</strong></span
                >
                <span
                    >Capaian: <strong>{{ summary.capaian }}%</strong></span
                >
            </span>
        </div>

        <div
            ref="tableElement"
            class="spreadsheet-grid min-w-0 overflow-x-auto"
        />
    </section>
</template>

<style>
.spreadsheet-grid .tabulator {
    border-color: #cbd5e1;
    font-size: 12px;
}

.spreadsheet-grid .tabulator-header .tabulator-col-group {
    background: #eef2ff;
    color: #1e1b4b;
}

.spreadsheet-grid .tabulator-cell.spreadsheet-numerator {
    background-color: #f0fdf4;
}

.spreadsheet-grid .tabulator-cell.spreadsheet-denominator {
    background-color: #f0f9ff;
}

.spreadsheet-grid .tabulator-cell.spreadsheet-row-label {
    font-weight: 600;
}
</style>
