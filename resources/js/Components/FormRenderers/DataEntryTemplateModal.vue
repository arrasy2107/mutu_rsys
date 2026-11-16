<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { getSpreadsheetFormulaConfig } from './spreadsheetColumnRoles';
import { getSpreadsheetFormulaPatterns } from './spreadsheetRows';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    indicator: {
        type: Object,
        default: null,
    },
    unit: {
        type: Object,
        default: null,
    },
    existingFormat: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const formatName = ref('');
const tableTitle = ref('');
const instructions = ref('');
const rowType = ref('dynamic_rows');
const columns = ref([]);
const fixedRows = ref([]);
const isSubmitting = ref(false);
const errorMessage = ref('');

const rowTypeLabel = computed(() =>
    rowType.value === 'fixed_rows' ? 'Baris tetap' : 'Baris dinamis',
);
const metricColumnOptions = [
    { value: 'auxiliary', label: 'Data pendukung' },
    { value: 'numerator', label: 'Numerator (N)' },
    { value: 'denominator', label: 'Denominator (D)' },
    { value: 'label', label: 'Label (tidak dihitung)' },
];
const inputTypeOptions = [
    { value: 'text', label: 'Teks' },
    { value: 'number', label: 'Bilangan bulat' },
    { value: 'decimal', label: 'Bilangan desimal' },
    { value: 'checkbox', label: 'Ya / Tidak' },
    { value: 'date', label: 'Tanggal' },
    { value: 'time', label: 'Waktu' },
    { value: 'select', label: 'Pilihan' },
];

const canUseMetricRole = (column) =>
    ['number', 'decimal', 'checkbox'].includes(column.type);

const metricColumnsAreValid = computed(() => {
    const numerator = columns.value.filter(
        (column) => column.role === 'numerator',
    );
    const denominator = columns.value.filter(
        (column) => column.role === 'denominator',
    );

    return (
        numerator.length > 0 &&
        denominator.length > 0 &&
        numerator.every(canUseMetricRole) &&
        denominator.every(canUseMetricRole)
    );
});

const isFormulaValid = (value) => {
    const formula = String(value || '').trim();

    return formula === '' || /^=[A-Z0-9_$ .,+\-*\x2f^():<>="]+$/i.test(formula);
};
const formulasAreValid = computed(() =>
    columns.value.every((column) => isFormulaValid(column.formula)),
);

const isValid = computed(
    () =>
        Boolean(props.indicator?.id) &&
        formatName.value.trim().length > 0 &&
        tableTitle.value.trim().length > 0 &&
        columns.value.length >= 2 &&
        metricColumnsAreValid.value &&
        formulasAreValid.value &&
        (rowType.value !== 'fixed_rows' || fixedRows.value.length > 0),
);

const createColumn = (title, type, role) => {
    const key = `column_${Date.now()}_${columns.value.length + 1}`;

    return {
        key,
        title,
        type,
        role,
        group: '',
        options: [],
        formula: '',
    };
};

const defaultColumns = () => [
    createColumn('Tanggal', 'date', 'auxiliary'),
    createColumn('Uraian / Nama Pasien', 'text', 'auxiliary'),
    createColumn('Sesuai Standar Mutu', 'checkbox', 'numerator'),
    createColumn('Jumlah Sampel', 'number', 'denominator'),
    createColumn('Keterangan', 'text', 'auxiliary'),
];

const initialize = () => {
    errorMessage.value = '';
    const indicator = props.indicator;
    const format = props.existingFormat;
    const schema = format?.schema || indicator?.form_schema || {};

    formatName.value =
        format?.nama_format ||
        schema.nama_format ||
        `Form ${indicator?.kode || ''} ${indicator?.judul || ''}`.trim();
    tableTitle.value =
        format?.table_title ||
        schema.table_title ||
        `Tabel Pengumpulan Data: ${indicator?.judul || 'Indikator Mutu'}`;
    instructions.value =
        format?.petunjuk_pengisian || schema.petunjuk_pengisian || '';
    rowType.value =
        format?.row_type ||
        schema.row_type ||
        (indicator?.form_layout_type === 'fixed_rows'
            ? 'fixed_rows'
            : 'dynamic_rows');

    const savedColumns = Array.isArray(schema.columns) ? schema.columns : [];
    if (savedColumns.length) {
        const formulaPatterns = getSpreadsheetFormulaPatterns(
            schema.workbook_template,
            Math.max(1, Number(schema.header_rows) || 2),
            savedColumns,
        );

        columns.value = savedColumns.map((column) => ({
            ...column,
            group: column.group || '',
            options: Array.isArray(column.options) ? [...column.options] : [],
            formula:
                column.formula || formulaPatterns[column.key]?.formula || '',
        }));
    } else {
        columns.value = defaultColumns();
    }

    fixedRows.value = Array.isArray(schema.rows)
        ? schema.rows.map((row) => ({ ...row }))
        : [];
    if (rowType.value === 'fixed_rows' && fixedRows.value.length === 0) {
        fixedRows.value = [{ key: 'row_1', label: 'Baris 1', values: {} }];
    }

    if (rowType.value === 'fixed_rows' && columns.value.length) {
        columns.value[0].role = 'label';
    }
};

const addColumn = () => {
    columns.value.push(
        createColumn(`Kolom ${columns.value.length + 1}`, 'text', 'auxiliary'),
    );
};

const removeColumn = (index) => {
    const column = columns.value[index];
    if (!column || columns.value.length <= 2) {
        return;
    }

    columns.value.splice(index, 1);
    if (rowType.value === 'fixed_rows' && index === 0 && columns.value[0]) {
        columns.value[0].role = 'label';
    }
};

const moveColumn = (index, direction) => {
    const targetIndex = index + direction;
    if (targetIndex < 0 || targetIndex >= columns.value.length) {
        return;
    }

    [columns.value[index], columns.value[targetIndex]] = [
        columns.value[targetIndex],
        columns.value[index],
    ];
    if (rowType.value === 'fixed_rows') {
        columns.value[0].role = 'label';
        if (targetIndex === 0) {
            columns.value[index].role = 'label';
        }
    }
};

const addFixedRow = () => {
    const nextNumber = fixedRows.value.length + 1;
    fixedRows.value.push({
        key: `row_${Date.now()}_${nextNumber}`,
        label: `Baris ${nextNumber}`,
        values: {},
    });
};

const removeFixedRow = (index) => {
    if (fixedRows.value.length <= 1) {
        return;
    }

    fixedRows.value.splice(index, 1);
};

const createWorkbookTemplate = () => {
    const sheetId = 'form-template';
    const cellData = {
        0: { 0: { v: tableTitle.value.trim() } },
        1: Object.fromEntries(
            columns.value.map((column, index) => [
                index,
                { v: column.title.trim() || column.key },
            ]),
        ),
    };

    columns.value.forEach((column, index) => {
        const formula = String(column.formula || '').trim();
        if (formula) {
            cellData[2] ||= {};
            cellData[2][index] = { f: formula };
        }
    });

    return {
        id: sheetId,
        name: tableTitle.value.trim(),
        appVersion: '1.0.0',
        locale: 'enUS',
        styles: [],
        sheetOrder: [sheetId],
        sheets: {
            [sheetId]: {
                id: sheetId,
                name: tableTitle.value.trim(),
                rowCount: 3,
                columnCount: columns.value.length,
                cellData,
                mergeData: [],
            },
        },
        defined_names: [],
    };
};

const saveTemplate = () => {
    errorMessage.value = '';
    if (!isValid.value || isSubmitting.value) {
        errorMessage.value =
            'Lengkapi nama dan judul form, kolom Numerator/Denominator, serta rumus yang valid.';
        return;
    }

    isSubmitting.value = true;
    router.post(
        route('custom-form-formats.store'),
        {
            id: props.existingFormat?.id || null,
            indicator_id: props.indicator.id,
            unit_id: props.unit?.id || props.indicator.unit_id || null,
            nama_format: formatName.value.trim(),
            table_title: tableTitle.value.trim(),
            row_type: rowType.value,
            petunjuk_pengisian: instructions.value.trim() || null,
            schema: {
                renderer: 'spreadsheet',
                engine: 'univer',
                header_rows: 2,
                columns: columns.value.map((column, index) => ({
                    ...column,
                    role:
                        rowType.value === 'fixed_rows' && index === 0
                            ? 'label'
                            : column.role,
                })),
                rows: rowType.value === 'fixed_rows' ? fixedRows.value : [],
                formula_config: getSpreadsheetFormulaConfig(columns.value),
                workbook_template: createWorkbookTemplate(),
            },
        },
        {
            preserveScroll: true,
            onSuccess: (page) => {
                const serverError = page.props.flash?.error;
                if (typeof serverError === 'string' && serverError.trim()) {
                    errorMessage.value = serverError;
                    window.notify?.toast?.error(
                        'Template Gagal Disimpan',
                        serverError,
                    );
                    return;
                }

                window.notify?.toast?.success(
                    'Template Tersimpan',
                    'Struktur form baru akan digunakan pada entri berikutnya. Data periode lama tetap memakai versi templatenya.',
                );
                emit('saved');
                emit('close');
            },
            onError: (errors) => {
                errorMessage.value = Object.values(errors)
                    .flatMap((error) =>
                        Array.isArray(error) ? error : [error],
                    )
                    .filter(Boolean)
                    .join(' ');
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

watch(
    () => props.show,
    (show) => {
        if (show) {
            initialize();
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6"
            @click.self="emit('close')"
        >
            <section
                class="my-auto flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="data-entry-template-title"
            >
                <header
                    class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-7"
                >
                    <div class="space-y-1">
                        <p
                            class="text-xs font-bold uppercase tracking-wider text-indigo-600"
                        >
                            Pengaturan form · {{ indicator?.kode }}
                        </p>
                        <h2
                            id="data-entry-template-title"
                            class="text-lg font-bold text-slate-900"
                        >
                            Atur Template Pengumpulan Data
                        </h2>
                        <p class="max-w-2xl text-sm text-slate-600">
                            Template mengatur kolom dan cara hitung. Perubahan
                            tidak mengubah data periode yang sudah tersimpan.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        @click="emit('close')"
                    >
                        Tutup
                    </button>
                </header>

                <div class="space-y-6 overflow-y-auto p-5 sm:p-7">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-1.5">
                            <span class="text-sm font-semibold text-slate-700"
                                >Nama template</span
                            >
                            <input
                                v-model="formatName"
                                maxlength="255"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Contoh: Form Observasi Pasien"
                            />
                        </label>
                        <label class="space-y-1.5">
                            <span class="text-sm font-semibold text-slate-700"
                                >Judul tabel</span
                            >
                            <input
                                v-model="tableTitle"
                                maxlength="255"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Judul yang tampil di atas tabel"
                            />
                        </label>
                        <label class="space-y-1.5">
                            <span class="text-sm font-semibold text-slate-700"
                                >Model baris</span
                            >
                            <select
                                v-model="rowType"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="dynamic_rows">
                                    Baris dinamis — dapat ditambah saat input
                                </option>
                                <option value="fixed_rows">
                                    Baris tetap — daftar baris ditetapkan di
                                    template
                                </option>
                            </select>
                        </label>
                        <label class="space-y-1.5">
                            <span class="text-sm font-semibold text-slate-700"
                                >Petunjuk pengisian</span
                            >
                            <input
                                v-model="instructions"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Opsional"
                            />
                        </label>
                    </div>

                    <section class="space-y-3">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <h3 class="font-bold text-slate-900">
                                    Kolom template
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Pilih tipe input dan tandai kolom N/D.
                                    Header berkelompok dapat memakai nama grup
                                    yang sama.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                                @click="addColumn"
                            >
                                Tambah kolom
                            </button>
                        </div>

                        <div class="grid gap-3">
                            <article
                                v-for="(column, index) in columns"
                                :key="column.key"
                                class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-12 md:items-end"
                            >
                                <label class="space-y-1 md:col-span-3">
                                    <span
                                        class="text-xs font-semibold text-slate-600"
                                        >Nama kolom</span
                                    >
                                    <input
                                        v-model="column.title"
                                        class="w-full rounded-lg border-slate-300 text-sm"
                                        placeholder="Nama kolom"
                                    />
                                </label>
                                <label class="space-y-1 md:col-span-2">
                                    <span
                                        class="text-xs font-semibold text-slate-600"
                                        >Tipe input</span
                                    >
                                    <select
                                        v-model="column.type"
                                        :disabled="
                                            rowType === 'fixed_rows' &&
                                            index === 0
                                        "
                                        class="w-full rounded-lg border-slate-300 text-sm"
                                    >
                                        <option
                                            v-for="option in inputTypeOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </label>
                                <label class="space-y-1 md:col-span-2">
                                    <span
                                        class="text-xs font-semibold text-slate-600"
                                        >Peran</span
                                    >
                                    <select
                                        v-model="column.role"
                                        :disabled="
                                            rowType === 'fixed_rows' &&
                                            index === 0
                                        "
                                        class="w-full rounded-lg border-slate-300 text-sm"
                                    >
                                        <option
                                            v-for="option in metricColumnOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </label>
                                <label class="space-y-1 md:col-span-2">
                                    <span
                                        class="text-xs font-semibold text-slate-600"
                                        >Grup header</span
                                    >
                                    <input
                                        v-model="column.group"
                                        class="w-full rounded-lg border-slate-300 text-sm"
                                        placeholder="Opsional"
                                    />
                                </label>
                                <label class="space-y-1 md:col-span-3">
                                    <span
                                        class="text-xs font-semibold text-slate-600"
                                        >Rumus baris (opsional)</span
                                    >
                                    <input
                                        v-model="column.formula"
                                        :aria-invalid="
                                            column.formula &&
                                            !isFormulaValid(column.formula)
                                        "
                                        class="w-full rounded-lg border-slate-300 font-mono text-xs"
                                        placeholder="=IF(C3=0,0,B3/C3*100)"
                                    />
                                </label>
                                <div
                                    class="flex items-center justify-between gap-2 md:col-span-12"
                                >
                                    <span
                                        class="font-mono text-[11px] text-slate-500"
                                    >
                                        {{ column.key }}
                                    </span>
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            class="rounded border border-slate-300 bg-white px-2 py-1 text-xs disabled:opacity-40"
                                            :disabled="index === 0"
                                            @click="moveColumn(index, -1)"
                                        >
                                            Geser kiri
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded border border-slate-300 bg-white px-2 py-1 text-xs disabled:opacity-40"
                                            :disabled="
                                                index === columns.length - 1
                                            "
                                            @click="moveColumn(index, 1)"
                                        >
                                            Geser kanan
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded border border-rose-200 bg-white px-2 py-1 text-xs font-semibold text-rose-700 disabled:opacity-40"
                                            :disabled="columns.length <= 2"
                                            @click="removeColumn(index)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>

                    <section
                        v-if="rowType === 'fixed_rows'"
                        class="space-y-3 rounded-xl border border-slate-200 p-4"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <h3 class="font-bold text-slate-900">
                                    Baris tetap
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Kolom pertama menjadi label yang tidak
                                    dihitung sebagai N atau D.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                @click="addFixedRow"
                            >
                                Tambah baris tetap
                            </button>
                        </div>
                        <div
                            v-for="(row, index) in fixedRows"
                            :key="row.key"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="w-16 shrink-0 text-xs font-semibold text-slate-500"
                                >Baris {{ index + 1 }}</span
                            >
                            <input
                                v-model="row.label"
                                class="w-full rounded-lg border-slate-300 text-sm"
                                placeholder="Nama kategori/baris"
                            />
                            <button
                                type="button"
                                class="rounded border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 disabled:opacity-40"
                                :disabled="fixedRows.length <= 1"
                                @click="removeFixedRow(index)"
                            >
                                Hapus
                            </button>
                        </div>
                    </section>

                    <div
                        class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900"
                    >
                        <strong>Ringkasan:</strong> {{ rowTypeLabel }} ·
                        {{ columns.length }} kolom · N/D dihitung dari nilai
                        yang disimpan · versi template baru tidak mengubah
                        periode yang sudah ada.
                    </div>

                    <p
                        v-if="!metricColumnsAreValid"
                        class="text-sm text-amber-700"
                    >
                        Tetapkan minimal satu kolom Numerator dan satu kolom
                        Denominator. Keduanya harus berupa angka atau checkbox.
                    </p>
                    <p v-if="!formulasAreValid" class="text-sm text-rose-700">
                        Rumus harus diawali tanda = dan hanya berisi operasi
                        spreadsheet yang didukung.
                    </p>
                    <p
                        v-if="errorMessage"
                        class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800"
                        role="alert"
                    >
                        {{ errorMessage }}
                    </p>
                </div>

                <footer
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-7"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                        @click="emit('close')"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="!isValid || isSubmitting"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="saveTemplate"
                    >
                        {{ isSubmitting ? 'Menyimpan...' : 'Simpan template' }}
                    </button>
                </footer>
            </section>
        </div>
    </Teleport>
</template>
