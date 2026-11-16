<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import UniverSpreadsheetRenderer from './UniverSpreadsheetRenderer.vue';
import {
    getSpreadsheetFormulaConfig,
    getSpreadsheetColumnLetters,
    getDefinedNameMetricColumns,
    getSpreadsheetWorkbookColumnCount,
    minimumSpreadsheetBuilderColumns,
    reconcileSpreadsheetColumnsWithWorkbook,
    resolveSpreadsheetCellReference,
    setSpreadsheetMetricRoleFromColumn,
} from './spreadsheetColumnRoles';
import { getIndicatorById } from '../../Pages/DataEntry/formFormatTarget';

const props = defineProps({
    show: Boolean,
    mode: {
        type: String,
        default: 'create', // 'create' | 'edit'
    },
    indicator: Object,
    unit: Object,
    indicatorsList: {
        type: Array,
        default: () => [],
    },
    existingFormat: Object,
});

const emit = defineEmits(['close', 'saved']);

// Mode: 'create' | 'edit'
const currentMode = ref(props.mode || 'create');

// General form settings
const namaFormat = ref('');
const tableTitle = ref('');
const numeratorColumnKey = ref('');
const denominatorColumnKey = ref('');
const selectedIndicatorId = ref(null);
const rowType = ref('dynamic_rows'); // 'dynamic_rows', 'daily_grid', 'fixed_rows', 'monthly_summary'
const petunjukPengisian = ref('');
const isSubmitting = ref(false);
const saveErrorMessage = ref('');
const spreadsheetTemplate = ref(null);
const workflowStep = ref('structure');
const spreadsheetRenderer = ref(null);

// Active column settings popover / modal index (null if closed)
const activeColIndex = ref(null);

// Number of visual blank rows in the spreadsheet canvas
const visualRowCount = ref(3);
const maximumBuilderColumns = 16384;

// Columns & Rows builder
const columns = ref([]);
const rows = ref([]);

// Target indicator details
const targetIndicator = computed(() => {
    return getIndicatorById(
        selectedIndicatorId.value,
        props.indicatorsList,
        props.indicator,
    );
});
const builderSchema = computed(() => ({
    renderer: 'spreadsheet',
    engine: 'univer',
    row_type: rowType.value,
    header_rows: 2,
    table_title:
        tableTitle.value.trim() ||
        `Tabel Pengumpulan Data: ${targetIndicator.value?.judul || 'Indikator Mutu'}`,
    columns: columns.value,
    rows: rows.value,
    workbook_template: spreadsheetTemplate.value,
}));

const reconcileMetricColumnSelections = () => {
    const selectedNumerator = columns.value.find(
        (column) =>
            column.key === numeratorColumnKey.value &&
            column.role === 'numerator',
    );
    const selectedDenominator = columns.value.find(
        (column) =>
            column.key === denominatorColumnKey.value &&
            column.role === 'denominator',
    );

    numeratorColumnKey.value =
        selectedNumerator?.key ||
        columns.value.find((column) => column.role === 'numerator')?.key ||
        '';
    denominatorColumnKey.value =
        selectedDenominator?.key ||
        columns.value.find((column) => column.role === 'denominator')?.key ||
        '';
};

const updateSpreadsheetTemplate = (workbook) => {
    spreadsheetTemplate.value = workbook;

    const reconciledColumns = reconcileSpreadsheetColumnsWithWorkbook(
        columns.value,
        workbook,
        builderSchema.value.header_rows,
        maximumBuilderColumns,
    );
    if (JSON.stringify(reconciledColumns) !== JSON.stringify(columns.value)) {
        columns.value = reconciledColumns;
        reconcileMetricColumnSelections();
    }

    const definedMetricColumns = getDefinedNameMetricColumns(
        workbook?.defined_names,
        columns.value,
    );
    if (
        definedMetricColumns.numeratorColumnKey ||
        definedMetricColumns.denominatorColumnKey
    ) {
        const numeratorKey =
            definedMetricColumns.numeratorColumnKey ||
            columns.value.find((column) => column.role === 'numerator')?.key ||
            '';
        const denominatorKey =
            definedMetricColumns.denominatorColumnKey ||
            columns.value.find((column) => column.role === 'denominator')
                ?.key ||
            '';

        columns.value.forEach((column) => {
            if (['numerator', 'denominator'].includes(column.role)) {
                column.role = 'auxiliary';
            }
        });

        if (numeratorKey && numeratorKey !== denominatorKey) {
            setSpreadsheetMetricRoleFromColumn(
                columns.value,
                'numerator',
                numeratorKey,
            );
        }
        if (denominatorKey && denominatorKey !== numeratorKey) {
            setSpreadsheetMetricRoleFromColumn(
                columns.value,
                'denominator',
                denominatorKey,
            );
        }

        numeratorColumnKey.value =
            numeratorKey !== denominatorKey ? numeratorKey : '';
        denominatorColumnKey.value =
            denominatorKey !== numeratorKey ? denominatorKey : '';
    }

    const sheetId = workbook?.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;
    if (sheet) {
        sheet.columnCount = getSpreadsheetWorkbookColumnCount(
            workbook,
            Math.max(columns.value.length, minimumSpreadsheetBuilderColumns),
            maximumBuilderColumns,
        );
    }
};

const handleSpreadsheetSyncError = (message) => {
    window.notify?.toast?.error(
        'Perubahan spreadsheet gagal disiapkan',
        message,
    );
};

// Currently active column object for the settings modal
const activeCol = computed(() => {
    if (activeColIndex.value === null || !columns.value[activeColIndex.value])
        return null;
    return columns.value[activeColIndex.value];
});

// Unique list of currently used group names across all columns
const existingGroups = computed(() => {
    const set = new Set();
    columns.value.forEach((c) => {
        if (c.group && String(c.group).trim() !== '') {
            set.add(String(c.group).trim());
        }
    });
    return Array.from(set);
});

// Checks if any column has a group defined
const hasGroups = computed(() => {
    return columns.value.some((c) => c.group && String(c.group).trim() !== '');
});

const numeratorColumn = computed(() =>
    columns.value.find((column) => column.key === numeratorColumnKey.value),
);
const denominatorColumn = computed(() =>
    columns.value.find((column) => column.key === denominatorColumnKey.value),
);

// 2-Tier Header Structure calculation
const headerStructure = computed(() => {
    if (!hasGroups.value) {
        return {
            tier1: columns.value.map((c) => ({
                isGroup: false,
                title: c.title,
                colspan: 1,
                rowspan: 1,
                col: c,
            })),
            tier2: [],
        };
    }

    const tier1 = [];
    const tier2 = [];
    let currentGroup = null;
    let currentGroupCols = [];

    const flushGroup = () => {
        if (currentGroup) {
            tier1.push({
                isGroup: true,
                title: currentGroup,
                colspan: currentGroupCols.length,
                rowspan: 1,
                columns: currentGroupCols,
            });
            currentGroupCols.forEach((col) => tier2.push(col));
            currentGroup = null;
            currentGroupCols = [];
        }
    };

    columns.value.forEach((col) => {
        const grp =
            col.group && String(col.group).trim() !== ''
                ? String(col.group).trim()
                : null;
        if (grp) {
            if (currentGroup && currentGroup !== grp) {
                flushGroup();
            }
            currentGroup = grp;
            currentGroupCols.push(col);
        } else {
            flushGroup();
            tier1.push({
                isGroup: false,
                title: col.title,
                colspan: 1,
                rowspan: 2,
                col: col,
            });
        }
    });
    flushGroup();

    return { tier1, tier2 };
});

// ==========================================
// COLUMN OPERATIONS
// ==========================================
const addColumnAtEnd = () => {
    insertColumnAt(columns.value.length);
};

const insertColumnAt = (index) => {
    const nextNum = columns.value.length + 1;
    const newCol = {
        id: `c_${Date.now()}_${nextNum}`,
        key: `col_${nextNum}`,
        title: `Kolom ${nextNum}`,
        group: '',
        type: 'text',
        role: 'auxiliary',
        width: '130px',
    };
    columns.value.splice(index, 0, newCol);
};

const removeColumn = (index) => {
    if (columns.value.length <= 2) {
        window.notify?.alert?.warning(
            'Peringatan Kolom',
            'Minimal harus ada 2 kolom pada tabel.',
        );
        return;
    }
    const [removedColumn] = columns.value.splice(index, 1);
    if (numeratorColumnKey.value === removedColumn.key) {
        numeratorColumnKey.value =
            columns.value.find((column) => column.role === 'numerator')?.key ||
            '';
    }
    if (denominatorColumnKey.value === removedColumn.key) {
        denominatorColumnKey.value =
            columns.value.find((column) => column.role === 'denominator')
                ?.key || '';
    }
    if (activeColIndex.value === index) {
        activeColIndex.value = null;
    } else if (activeColIndex.value > index) {
        activeColIndex.value--;
    }
};

const moveColumn = (index, direction) => {
    const target = index + direction;
    if (target < 0 || target >= columns.value.length) return;
    const temp = columns.value[index];
    columns.value[index] = columns.value[target];
    columns.value[target] = temp;
    if (activeColIndex.value === index) {
        activeColIndex.value = target;
    }
};

const duplicateColumn = (index) => {
    const source = columns.value[index];
    const nextNum = columns.value.length + 1;
    const cloned = {
        ...JSON.parse(JSON.stringify(source)),
        id: `c_${Date.now()}_${nextNum}`,
        key: `col_${nextNum}`,
        title: `${source.title} (Salinan)`,
    };
    columns.value.splice(index + 1, 0, cloned);
    activeColIndex.value = index + 1;
};

// ==========================================
// ROW OPERATIONS
// ==========================================
const addRowAtEnd = () => {
    insertRowAt(visualRowCount.value);
};

const insertRowAt = (index) => {
    visualRowCount.value++;
    if (rowType.value === 'fixed_rows') {
        const nextNum = rows.value.length + 1;
        rows.value.splice(index, 0, {
            id: `r_${Date.now()}_${nextNum}`,
            key: `row_${nextNum}`,
            label: `Kategori / Item ${nextNum}`,
        });
    }
};

const removeRowAt = (index) => {
    if (visualRowCount.value <= 1) {
        window.notify?.alert?.warning(
            'Peringatan Baris',
            'Minimal harus ada 1 baris pada tabel.',
        );
        return;
    }
    visualRowCount.value--;
    if (rowType.value === 'fixed_rows' && rows.value.length > index) {
        rows.value.splice(index, 1);
    }
};

// ==========================================
// COLUMN SETTINGS HELPER
// ==========================================
const openColSetting = (colOrIndex) => {
    if (typeof colOrIndex === 'number') {
        activeColIndex.value = colOrIndex;
    } else {
        const idx = columns.value.findIndex(
            (c) => c.id === colOrIndex.id || c.key === colOrIndex.key,
        );
        activeColIndex.value = idx !== -1 ? idx : null;
    }
};

const closeColSetting = () => {
    activeColIndex.value = null;
};

const setColGroup = (groupName) => {
    if (!activeCol.value) return;
    activeCol.value.group = groupName;
};

const setDropdownOptions = (event) => {
    if (!activeCol.value) return;
    activeCol.value.options = event.target.value
        .split(/\r?\n/)
        .map((option) => option.trim())
        .filter(Boolean);
};

// ==========================================
// FORMULA VALIDATION
// ==========================================
const hasNumeratorCol = computed(
    () => numeratorColumn.value?.role === 'numerator',
);
const hasDenominatorCol = computed(
    () => denominatorColumn.value?.role === 'denominator',
);
const hasDistinctMetricColumns = computed(
    () =>
        numeratorColumnKey.value &&
        denominatorColumnKey.value &&
        numeratorColumnKey.value !== denominatorColumnKey.value,
);
const hasValidMetricColumnTypes = computed(() =>
    [numeratorColumn.value, denominatorColumn.value].every((column) =>
        ['number', 'decimal', 'checkbox'].includes(column?.type),
    ),
);

const isStructureValid = computed(
    () =>
        namaFormat.value.trim() !== '' &&
        selectedIndicatorId.value !== null &&
        selectedIndicatorId.value !== undefined &&
        targetIndicator.value?.id !== null &&
        targetIndicator.value?.id !== undefined &&
        Number(targetIndicator.value?.id) ===
            Number(selectedIndicatorId.value) &&
        columns.value.length >= 2,
);
const structureValidationMessage = computed(() => {
    const issues = [];

    if (!namaFormat.value.trim()) {
        issues.push('isi nama format');
    }
    if (
        selectedIndicatorId.value === null ||
        selectedIndicatorId.value === undefined ||
        targetIndicator.value?.id === null ||
        targetIndicator.value?.id === undefined ||
        Number(targetIndicator.value.id) !== Number(selectedIndicatorId.value)
    ) {
        issues.push(
            'profil indikator format tidak ditemukan atau tidak sesuai; muat ulang halaman',
        );
    }
    if (columns.value.length < 2) {
        issues.push(
            `pastikan minimal dua kolom terdeteksi (saat ini ${columns.value.length})`,
        );
    }

    return issues.length
        ? `Belum bisa lanjut: ${issues.join(', ')}.`
        : 'Struktur tabel siap ditinjau.';
});
const metricValidationMessage = computed(() => {
    if (!hasNumeratorCol.value || !numeratorColumnKey.value) {
        return 'Pilih kolom Numerator [N].';
    }
    if (!hasDenominatorCol.value || !denominatorColumnKey.value) {
        return 'Pilih kolom Denominator [D].';
    }
    if (!hasDistinctMetricColumns.value) {
        return 'Numerator [N] dan Denominator [D] harus menggunakan kolom yang berbeda.';
    }
    if (!hasValidMetricColumnTypes.value) {
        return 'Kolom Numerator dan Denominator harus bertipe angka atau checkbox.';
    }

    return 'Kolom Numerator dan Denominator siap disimpan.';
});
const isValid = computed(() => {
    return (
        isStructureValid.value &&
        hasNumeratorCol.value &&
        hasDenominatorCol.value &&
        hasDistinctMetricColumns.value &&
        hasValidMetricColumnTypes.value
    );
});

const setMetricColumn = (role, columnKey) => {
    const selectedColumnKey = typeof columnKey === 'string' ? columnKey : '';

    if (
        !selectedColumnKey ||
        !setSpreadsheetMetricRoleFromColumn(
            columns.value,
            role,
            selectedColumnKey,
        )
    ) {
        const existingRoleColumn = columns.value.find(
            (column) => column.role === role,
        );
        if (role === 'numerator') {
            numeratorColumnKey.value = existingRoleColumn?.key || '';
        } else {
            denominatorColumnKey.value = existingRoleColumn?.key || '';
        }
        return;
    }

    if (role === 'numerator') {
        numeratorColumnKey.value = selectedColumnKey;
    } else {
        denominatorColumnKey.value = selectedColumnKey;
    }
};

const getSavedMetricColumnKey = (schema, role) => {
    const formulaConfig = schema.formula_config || {};
    const savedColumns = formulaConfig[`${role}_cols`];
    const savedColumnKey = Array.isArray(savedColumns)
        ? savedColumns.find((key) =>
              columns.value.some((column) => column.key === key),
          )
        : null;
    if (savedColumnKey) {
        return savedColumnKey;
    }

    const savedCell = formulaConfig[`${role}_cell`];
    const savedCellResolution = resolveSpreadsheetCellReference(
        savedCell,
        columns.value,
    );
    if (savedCellResolution) {
        return savedCellResolution.columnKey;
    }

    return columns.value.find((column) => column.role === role)?.key || '';
};

const initializeMetricColumnKeys = (schema = {}) => {
    numeratorColumnKey.value = getSavedMetricColumnKey(schema, 'numerator');
    denominatorColumnKey.value = getSavedMetricColumnKey(schema, 'denominator');

    [
        ['numerator', numeratorColumnKey.value],
        ['denominator', denominatorColumnKey.value],
    ].forEach(([role, key]) => {
        if (key) {
            setSpreadsheetMetricRoleFromColumn(columns.value, role, key);
        }
    });
};

// Initialize form
const initForm = () => {
    currentMode.value =
        props.mode || (props.existingFormat ? 'edit' : 'create');
    const requestedIndicatorId =
        currentMode.value === 'edit'
            ? (props.existingFormat?.indicator_id ?? props.indicator?.id)
            : (props.indicator?.id ?? props.indicatorsList?.[0]?.id ?? null);
    const resolvedIndicator = getIndicatorById(
        requestedIndicatorId,
        props.indicatorsList,
        props.indicator,
    );
    selectedIndicatorId.value =
        resolvedIndicator?.id ?? requestedIndicatorId ?? null;
    activeColIndex.value = null;
    saveErrorMessage.value = '';
    workflowStep.value = 'structure';
    namaFormat.value = '';
    tableTitle.value = '';
    numeratorColumnKey.value = '';
    denominatorColumnKey.value = '';
    columns.value = [];
    rows.value = [];
    spreadsheetTemplate.value = null;
    visualRowCount.value = 3;

    if (
        currentMode.value === 'edit' &&
        (!props.existingFormat ||
            Number(targetIndicator.value?.id) !==
                Number(props.existingFormat.indicator_id) ||
            Number(props.existingFormat.indicator_id) !==
                Number(selectedIndicatorId.value))
    ) {
        saveErrorMessage.value =
            'Profil indikator untuk format ini tidak cocok dengan data yang tersedia. Tutup modal, muat ulang halaman, lalu coba kembali.';
        return;
    }

    if (currentMode.value === 'edit' && props.existingFormat) {
        namaFormat.value = props.existingFormat.nama_format || '';
        tableTitle.value =
            props.existingFormat.table_title ||
            `Tabel Pengumpulan Data: ${targetIndicator.value?.judul || 'Indikator Mutu'}`;
        rowType.value =
            props.existingFormat.row_type ||
            props.existingFormat.schema?.row_type ||
            'dynamic_rows';
        petunjukPengisian.value = props.existingFormat.petunjuk_pengisian || '';

        const s = props.existingFormat.schema || {};
        if (!Array.isArray(s.columns) || s.columns.length === 0) {
            saveErrorMessage.value =
                'Struktur kolom format ini tidak tersedia. Periksa format indikator sebelum mengedit.';
            return;
        }

        spreadsheetTemplate.value = s.workbook_template || null;
        columns.value = reconcileSpreadsheetColumnsWithWorkbook(
            JSON.parse(JSON.stringify(s.columns)),
            spreadsheetTemplate.value,
            Number(s.header_rows) || 2,
            maximumBuilderColumns,
        );
        if (Array.isArray(s.rows) && s.rows.length > 0) {
            rows.value = JSON.parse(JSON.stringify(s.rows));
            visualRowCount.value = rows.value.length;
        } else {
            visualRowCount.value = 3;
        }
        initializeMetricColumnKeys(s);
    } else {
        // Create Mode - Mulai dengan kanvas tabel murni
        const indJudul = targetIndicator.value?.judul || 'Indikator Mutu';
        const indKode = targetIndicator.value?.kode || '';
        namaFormat.value = `Format Form: ${indKode ? `[${indKode}] ` : ''}${indJudul}`;
        tableTitle.value = `Tabel Pengumpulan Data: ${indJudul}`;
        rowType.value = ['dynamic_rows', 'fixed_rows'].includes(
            targetIndicator.value?.form_layout_type,
        )
            ? targetIndicator.value.form_layout_type
            : 'dynamic_rows';
        spreadsheetTemplate.value = null;
        petunjukPengisian.value = '';
        initDefaultColumns();
        visualRowCount.value = 3;
        initializeMetricColumnKeys();
    }
};

const initDefaultColumns = () => {
    const numName =
        targetIndicator.value?.numerator_keterangan || 'Sesuai Standar Mutu';
    const denName =
        targetIndicator.value?.denominator_keterangan ||
        'Total Sampel / Pasien';

    columns.value = [
        {
            id: 'c1',
            key: 'tanggal',
            title: 'Tanggal',
            type: 'date',
            role: 'auxiliary',
        },
        {
            id: 'c2',
            key: 'uraian',
            title: 'Uraian / Nama Pasien',
            type: 'text',
            role: 'auxiliary',
        },
        {
            id: 'c3',
            key: 'num',
            title: numName,
            type: 'checkbox',
            role: 'numerator',
        },
        {
            id: 'c4',
            key: 'den',
            title: denName,
            type: 'number',
            role: 'denominator',
        },
        {
            id: 'c5',
            key: 'notes',
            title: 'Keterangan',
            type: 'text',
            role: 'auxiliary',
        },
    ];
};

watch(
    () => props.show,
    (val) => {
        if (val) initForm();
    },
    { immediate: true },
);

const onIndicatorChanged = () => {
    if (targetIndicator.value) {
        const indJudul = targetIndicator.value.judul;
        const indKode = targetIndicator.value.kode;
        if (currentMode.value === 'create') {
            namaFormat.value = `Format Form: [${indKode}] ${indJudul}`;
            tableTitle.value = `Tabel Pengumpulan Data: ${indJudul}`;
            // Update default numerator & denominator column titles if unchanged
            const nCol = columns.value.find((c) => c.role === 'numerator');
            if (nCol && targetIndicator.value.numerator_keterangan) {
                nCol.title = targetIndicator.value.numerator_keterangan;
            }
            const dCol = columns.value.find((c) => c.role === 'denominator');
            if (dCol && targetIndicator.value.denominator_keterangan) {
                dCol.title = targetIndicator.value.denominator_keterangan;
            }
        }
    }
};

const saveFormat = () => {
    saveErrorMessage.value = '';

    const workbookSnapshot = spreadsheetRenderer.value?.getWorkbookSnapshot?.();
    if (workbookSnapshot) {
        updateSpreadsheetTemplate(workbookSnapshot);
    }

    if (
        workflowStep.value === 'structure'
            ? !isStructureValid.value
            : !isValid.value
    ) {
        window.notify?.alert?.warning(
            'Formulir Belum Lengkap',
            workflowStep.value === 'structure'
                ? structureValidationMessage.value
                : metricValidationMessage.value,
        );
        return;
    }

    if (
        currentMode.value === 'edit' &&
        (!props.existingFormat ||
            Number(props.existingFormat.indicator_id) !==
                Number(selectedIndicatorId.value))
    ) {
        saveErrorMessage.value =
            'Format tidak sesuai dengan indikator yang dipilih. Muat ulang halaman sebelum mencoba kembali.';
        return;
    }

    if (workflowStep.value === 'structure') {
        workflowStep.value = 'metrics';
        window.notify?.toast?.info(
            'Struktur Tabel Siap',
            'Pilih kolom Numerator dan Denominator. Perubahan akan disimpan setelah Anda menekan Simpan N/D.',
        );
        return;
    }

    isSubmitting.value = true;
    router.post(
        route('custom-form-formats.store'),
        {
            id:
                currentMode.value === 'edit' &&
                props.existingFormat?.id &&
                Number(props.existingFormat.indicator_id) ===
                    Number(selectedIndicatorId.value)
                    ? props.existingFormat.id
                    : null,
            indicator_id: selectedIndicatorId.value,
            unit_id: props.unit?.id || targetIndicator.value?.unit_id || null,
            nama_format: namaFormat.value,
            table_title:
                tableTitle.value.trim() ||
                `Tabel Pengumpulan Data: ${targetIndicator.value?.judul || 'Indikator Mutu'}`,
            row_type: rowType.value,
            petunjuk_pengisian: petunjukPengisian.value,
            schema: {
                renderer: 'spreadsheet',
                engine: 'univer',
                header_rows: 2,
                workbook_template: spreadsheetTemplate.value,
                columns: columns.value,
                rows: rows.value,
                formula_config: getSpreadsheetFormulaConfig(columns.value),
            },
        },
        {
            preserveScroll: true,
            onSuccess: (page) => {
                const serverError = page.props.flash?.error;

                if (typeof serverError === 'string' && serverError.trim()) {
                    saveErrorMessage.value = serverError;
                    window.notify?.toast?.error('Gagal Menyimpan', serverError);
                    return;
                }

                window.notify?.toast?.success(
                    currentMode.value === 'create'
                        ? 'Format Baru Berhasil Dibuat'
                        : 'Format Berhasil Diperbarui',
                    `Format '${namaFormat.value}' berhasil diterapkan untuk unit.`,
                );
                emit('saved');
                emit('close');
            },
            onError: (errors) => {
                const errorMessages = Object.values(errors)
                    .flatMap((error) =>
                        Array.isArray(error) ? error : [error],
                    )
                    .filter(
                        (error) =>
                            typeof error === 'string' && error.trim() !== '',
                    );
                const errorMessage =
                    errorMessages.join(' ') ||
                    'Server menolak penyimpanan tanpa memberikan detail validasi.';
                saveErrorMessage.value = errorMessage;
                window.notify?.toast?.error('Gagal Menyimpan', errorMessage);
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

const showRequestFailure = (message) => {
    if (!isSubmitting.value || saveErrorMessage.value) {
        return;
    }

    saveErrorMessage.value = message;
    window.notify?.toast?.error('Gagal Menyimpan', message);
};

const removeInvalidResponseListener = router.on('invalid', (event) => {
    const response = event.detail.response;
    const responseMessage =
        response.data &&
        typeof response.data === 'object' &&
        typeof response.data.message === 'string'
            ? response.data.message
            : `Server mengembalikan kesalahan HTTP ${response.status}.`;
    showRequestFailure(responseMessage);
});

const removeRequestExceptionListener = router.on('exception', (event) => {
    const exception = event.detail.exception;
    showRequestFailure(
        exception.message ||
            'Koneksi ke server gagal sebelum format tersimpan.',
    );
});

onBeforeUnmount(() => {
    removeInvalidResponseListener();
    removeRequestExceptionListener();
});
</script>

<template>
    <div
        v-if="show"
        class="backdrop-blur-xs fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/60 p-2 sm:p-4"
    >
        <div
            class="my-2 flex max-h-[96vh] w-full max-w-7xl flex-col space-y-4 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl sm:p-6"
        >
            <!-- 1. HEADER MODAL (RINGKAS & BERSIH) -->
            <div
                class="flex shrink-0 items-center justify-between border-b border-slate-200 pb-3"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm"
                    >
                        <NotionIcon name="table" size="22" class="text-white" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900">
                                {{
                                    currentMode === 'create'
                                        ? 'Rancang Format Form Pengumpul Data'
                                        : 'Edit Format Tabel Form'
                                }}
                            </h3>
                            <span
                                class="rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700"
                            >
                                Interaktif Spreadsheet
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{
                                workflowStep === 'structure'
                                    ? 'Atur struktur tabel dan header, lalu lanjutkan untuk menentukan kolom N/D.'
                                    : 'Tabel tetap terlihat. Tetapkan kolom Numerator dan Denominator sebelum menyimpan format.'
                            }}
                        </p>
                        <p
                            v-if="currentMode === 'edit'"
                            class="mt-1 text-[11px] font-medium text-indigo-700"
                        >
                            Format ini adalah template untuk profil indikator
                            terpilih dan berlaku lintas bulan. Data bulanan
                            tersimpan tidak diubah oleh edit struktur ini.
                        </p>
                    </div>
                </div>

                <button
                    @click="$emit('close')"
                    class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                >
                    <NotionIcon name="x" size="18" />
                </button>
            </div>

            <!-- 2. IDENTITAS FORM -->
            <div
                class="grid shrink-0 grid-cols-1 gap-2.5 rounded-xl border border-slate-200 bg-slate-50/90 p-3 text-xs sm:grid-cols-12"
            >
                <!-- Profil Indikator -->
                <div class="sm:col-span-4">
                    <label
                        class="mb-1 block text-[11px] font-bold text-slate-700"
                    >
                        {{
                            currentMode === 'edit'
                                ? 'Indikator Mutu'
                                : 'Profil Indikator Mutu'
                        }}
                        <span class="text-rose-500">*</span>
                    </label>
                    <div
                        v-if="currentMode === 'edit'"
                        class="flex min-h-9 items-center rounded-lg border border-slate-200 bg-slate-100 px-3 text-xs font-semibold text-slate-600"
                    >
                        [{{ targetIndicator?.kode }}]
                        {{ targetIndicator?.judul }}
                    </div>
                    <select
                        v-else
                        v-model="selectedIndicatorId"
                        @change="onIndicatorChanged"
                        class="w-full rounded-lg border-slate-300 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option :value="null" disabled>
                            -- Pilih Profil Indikator Mutu --
                        </option>
                        <option
                            v-for="ind in indicatorsList"
                            :key="ind.id"
                            :value="ind.id"
                        >
                            [{{ ind.kode }}] {{ ind.judul }}
                        </option>
                    </select>
                </div>

                <!-- Nama Format Formulir -->
                <div class="sm:col-span-3">
                    <label
                        class="mb-1 block text-[11px] font-bold text-slate-700"
                    >
                        Nama Format Form <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="namaFormat"
                        type="text"
                        placeholder="Contoh: Form Mutu IRJ 2026"
                        class="w-full rounded-lg border-slate-300 text-xs font-medium focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <!-- 3. SPREADSHEET CANVAS -->
            <div
                class="flex flex-1 flex-col overflow-hidden rounded-xl border border-slate-300 bg-slate-100/50 shadow-inner"
            >
                <!-- Toolbar Canvas Atas -->
                <div
                    class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-300 bg-slate-200/80 px-3.5 py-2 text-xs"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="flex items-center gap-1.5 font-bold text-slate-800"
                        >
                            <NotionIcon
                                name="table"
                                size="14"
                                class="text-slate-600"
                            />
                            Struktur Tabel:
                        </span>
                        <span
                            class="rounded border border-slate-300 bg-white px-2 py-0.5 font-mono font-bold text-slate-700"
                        >
                            {{ columns.length }} Kolom
                        </span>
                        <span
                            class="rounded border border-slate-300 bg-white px-2 py-0.5 font-mono font-bold text-slate-700"
                        >
                            {{ visualRowCount }} Baris Data
                        </span>
                    </div>
                </div>

                <div
                    class="flex min-h-0 flex-1 flex-col gap-2 overflow-hidden p-2"
                >
                    <div v-if="false" class="flex flex-wrap items-center gap-2">
                        <div
                            class="w-full rounded-xl border border-indigo-200 bg-white p-3"
                        >
                            <div
                                class="mb-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <h4
                                        class="text-xs font-bold text-slate-800"
                                    >
                                        Tentukan Kolom Numerator dan Denominator
                                    </h4>
                                    <p class="text-[11px] text-slate-500">
                                        Peran menentukan nilai yang dijumlahkan
                                        untuk capaian. Tipe input sel diatur
                                        terpisah melalui tombol pengaturan.
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1.5 text-[10px]">
                                    <span
                                        class="rounded-md bg-emerald-50 px-2 py-1 font-semibold text-emerald-800"
                                    >
                                        N:
                                        {{
                                            numeratorCols
                                                .map(
                                                    (column) =>
                                                        column.title ||
                                                        column.key,
                                                )
                                                .join(', ') || 'Belum dipilih'
                                        }}
                                    </span>
                                    <span
                                        class="rounded-md bg-sky-50 px-2 py-1 font-semibold text-sky-800"
                                    >
                                        D:
                                        {{
                                            denominatorCols
                                                .map(
                                                    (column) =>
                                                        column.title ||
                                                        column.key,
                                                )
                                                .join(', ') || 'Belum dipilih'
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div
                                    v-for="(column, index) in columns"
                                    :key="column.id || column.key"
                                    class="grid grid-cols-1 items-center gap-2 rounded-lg border border-slate-100 px-2 py-1.5 sm:grid-cols-[minmax(0,1fr)_minmax(150px,220px)_auto]"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-xs font-semibold text-slate-800"
                                        >
                                            {{ column.title || column.key }}
                                        </p>
                                        <p
                                            class="truncate font-mono text-[10px] text-slate-400"
                                        >
                                            {{ column.key }} · tipe:
                                            {{ column.type || 'text' }}
                                        </p>
                                    </div>
                                    <select
                                        :value="column.role || 'auxiliary'"
                                        :aria-label="`Peran mutu untuk kolom ${column.title || column.key}`"
                                        class="w-full rounded-lg border-slate-300 bg-white px-2 py-1.5 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                                        @change="
                                            setColumnRole(
                                                column,
                                                $event.target.value,
                                            )
                                        "
                                    >
                                        <option value="auxiliary">
                                            Tidak dihitung / pendukung
                                        </option>
                                        <option value="label">
                                            Label baris
                                        </option>
                                        <option value="numerator">
                                            Numerator [N]
                                        </option>
                                        <option value="denominator">
                                            Denominator [D]
                                        </option>
                                    </select>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                                        :aria-label="`Atur tipe dan judul kolom ${column.title || column.key}`"
                                        title="Atur tipe input, judul, dan grup header"
                                        @click="openColSetting(index)"
                                    >
                                        <NotionIcon name="settings" size="14" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <UniverSpreadsheetRenderer
                        ref="spreadsheetRenderer"
                        mode="builder"
                        :read-only="workflowStep === 'metrics'"
                        :schema="builderSchema"
                        :design-row-count="visualRowCount"
                        @update:workbook="updateSpreadsheetTemplate"
                        @sync-error="handleSpreadsheetSyncError"
                    />
                </div>

                <!-- Legacy table renderer is disabled. -->
                <div v-if="false" class="flex-1 overflow-auto bg-white p-3">
                    <table
                        class="w-full min-w-max border-collapse border border-slate-300 text-left font-sans text-xs"
                    >
                        <!-- THEAD DENGAN TOMBOL SETTING PADA TIAP KOLOM -->
                        <thead
                            class="shadow-2xs sticky top-0 z-10 select-none bg-slate-100 text-slate-800"
                        >
                            <!-- Tier 1 Header (Grup Induk / Standalone) -->
                            <tr class="border-b border-slate-300">
                                <th
                                    :rowspan="hasGroups ? 2 : 1"
                                    class="w-12 border-r border-slate-300 bg-slate-200/90 px-3 py-2 text-center font-bold text-slate-700"
                                >
                                    No
                                </th>

                                <template
                                    v-for="(item, idx) in headerStructure.tier1"
                                    :key="'h1-' + idx"
                                >
                                    <!-- Merged Header (Grup Induk) -->
                                    <th
                                        v-if="item.isGroup"
                                        :colspan="item.colspan"
                                        :rowspan="item.rowspan"
                                        class="border-b border-r border-indigo-200 bg-indigo-100 px-3 py-2 text-center text-[11px] font-bold uppercase tracking-wide text-indigo-950"
                                    >
                                        <div
                                            class="flex items-center justify-center gap-1.5"
                                        >
                                            <NotionIcon
                                                name="layers"
                                                size="13"
                                                class="text-indigo-600"
                                            />
                                            <span>{{ item.title }}</span>
                                            <span
                                                class="py-0.2 rounded bg-indigo-200 px-1.5 font-mono text-[9px] font-bold text-indigo-800"
                                            >
                                                Merged ({{ item.colspan }}
                                                Kolom)
                                            </span>
                                        </div>
                                    </th>

                                    <!-- Standalone Column (Tanpa Grup) -->
                                    <th
                                        v-else
                                        :rowspan="item.rowspan"
                                        :colspan="item.colspan"
                                        class="group border-r border-slate-300 px-3 py-2 font-bold transition-colors"
                                        :class="{
                                            'bg-emerald-50 text-emerald-950':
                                                item.col.role === 'numerator',
                                            'bg-sky-50 text-sky-950':
                                                item.col.role === 'denominator',
                                            'bg-slate-100 text-slate-800':
                                                item.col.role !== 'numerator' &&
                                                item.col.role !== 'denominator',
                                        }"
                                    >
                                        <div
                                            class="flex min-w-[130px] items-center justify-between gap-2"
                                        >
                                            <div
                                                class="flex items-center gap-1.5 truncate"
                                            >
                                                <span
                                                    class="truncate font-bold"
                                                    >{{ item.col.title }}</span
                                                >
                                                <span
                                                    v-if="
                                                        item.col.role ===
                                                        'numerator'
                                                    "
                                                    class="py-0.2 shrink-0 rounded bg-emerald-200 px-1.5 font-mono text-[10px] font-bold text-emerald-800"
                                                    >[N]</span
                                                >
                                                <span
                                                    v-else-if="
                                                        item.col.role ===
                                                        'denominator'
                                                    "
                                                    class="py-0.2 shrink-0 rounded bg-sky-200 px-1.5 font-mono text-[10px] font-bold text-sky-800"
                                                    >[D]</span
                                                >
                                            </div>

                                            <!-- Tombol Setting & Tambah Kolom Sebelah -->
                                            <div
                                                class="flex shrink-0 items-center gap-1"
                                            >
                                                <button
                                                    @click.stop="
                                                        openColSetting(item.col)
                                                    "
                                                    type="button"
                                                    class="shadow-2xs rounded border border-slate-300 bg-white p-1 text-slate-600 transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                                                    title="Pengaturan Kolom: Ubah nama, merge header, peran N/D, tipe data"
                                                >
                                                    <NotionIcon
                                                        name="settings"
                                                        size="13"
                                                    />
                                                </button>
                                                <button
                                                    @click.stop="
                                                        insertColumnAt(
                                                            columns.indexOf(
                                                                item.col,
                                                            ) + 1,
                                                        )
                                                    "
                                                    type="button"
                                                    class="shadow-2xs rounded border border-slate-300 bg-white p-1 font-bold text-slate-600 transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                                                    title="Sisipkan kolom baru di sebelah kanan"
                                                >
                                                    <NotionIcon
                                                        name="plus"
                                                        size="13"
                                                    />
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                </template>

                                <!-- Kolom Aksi Tambah di Ujung Kanan Header -->
                                <th
                                    :rowspan="hasGroups ? 2 : 1"
                                    class="w-28 border-l border-slate-300 bg-slate-200/60 px-3 py-2 text-center"
                                >
                                    <button
                                        @click="addColumnAtEnd"
                                        type="button"
                                        class="shadow-2xs flex w-full items-center justify-center gap-1 rounded border border-emerald-300 bg-white px-2 py-1 text-[11px] font-bold text-emerald-700 transition-all hover:bg-emerald-600 hover:text-white"
                                    >
                                        <NotionIcon name="plus" size="12" />
                                        <span>Kolom</span>
                                    </button>
                                </th>
                            </tr>

                            <!-- Tier 2 Header (Sub-Columns jika ada multi-header) -->
                            <tr
                                v-if="hasGroups"
                                class="border-b border-slate-300 bg-slate-100/90 text-slate-700"
                            >
                                <th
                                    v-for="(
                                        subCol, sIdx
                                    ) in headerStructure.tier2"
                                    :key="'h2-' + sIdx"
                                    class="group border-r border-slate-300 px-3 py-1.5 font-semibold"
                                    :class="{
                                        'bg-emerald-50/70 text-emerald-950':
                                            subCol.role === 'numerator',
                                        'bg-sky-50/70 text-sky-950':
                                            subCol.role === 'denominator',
                                    }"
                                >
                                    <div
                                        class="flex min-w-[120px] items-center justify-between gap-2"
                                    >
                                        <div
                                            class="flex items-center gap-1 truncate"
                                        >
                                            <span
                                                class="truncate text-[11px] font-bold"
                                                >{{ subCol.title }}</span
                                            >
                                            <span
                                                v-if="
                                                    subCol.role === 'numerator'
                                                "
                                                class="py-0.2 shrink-0 rounded bg-emerald-200 px-1 font-mono text-[9px] font-bold text-emerald-800"
                                                >[N]</span
                                            >
                                            <span
                                                v-else-if="
                                                    subCol.role ===
                                                    'denominator'
                                                "
                                                class="py-0.2 shrink-0 rounded bg-sky-200 px-1 font-mono text-[9px] font-bold text-sky-800"
                                                >[D]</span
                                            >
                                        </div>

                                        <div
                                            class="flex shrink-0 items-center gap-1"
                                        >
                                            <button
                                                @click.stop="
                                                    openColSetting(subCol)
                                                "
                                                type="button"
                                                class="shadow-2xs rounded border border-slate-300 bg-white p-1 text-slate-600 transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                                                title="Pengaturan Kolom: Ubah nama, merge header, peran N/D, tipe data"
                                            >
                                                <NotionIcon
                                                    name="settings"
                                                    size="13"
                                                />
                                            </button>
                                            <button
                                                @click.stop="
                                                    insertColumnAt(
                                                        columns.indexOf(
                                                            subCol,
                                                        ) + 1,
                                                    )
                                                "
                                                type="button"
                                                class="shadow-2xs rounded border border-slate-300 bg-white p-1 font-bold text-slate-600 transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                                                title="Sisipkan kolom baru di sebelah kanan"
                                            >
                                                <NotionIcon
                                                    name="plus"
                                                    size="13"
                                                />
                                            </button>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <!-- TBODY SPREADSHEET (BERSIH DENGAN TOMBOL TAMBAH/HAPUS BARIS DI TIAP ROW) -->
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr
                                v-for="rIdx in visualRowCount"
                                :key="'row-' + rIdx"
                                class="group transition-colors hover:bg-slate-50"
                            >
                                <!-- Kolom No & Sisip Baris Cepat -->
                                <td
                                    class="border-r border-slate-300 bg-slate-50/50 px-3 py-2.5 text-center font-mono font-bold text-slate-400"
                                >
                                    {{ rIdx }}
                                </td>

                                <!-- Sel Data Kolom (Kosong Bersih Tanpa Teks Isi) -->
                                <td
                                    v-for="(col, cIdx) in columns"
                                    :key="'cell-' + cIdx"
                                    class="border-r border-slate-200 px-3 py-2.5"
                                    :class="{
                                        'bg-emerald-50/10':
                                            col.role === 'numerator',
                                        'bg-sky-50/10':
                                            col.role === 'denominator',
                                    }"
                                >
                                    <!-- Jika kolom fixed rows pertama (Nama Kategori) -->
                                    <div
                                        v-if="
                                            cIdx === 0 &&
                                            rowType === 'fixed_rows'
                                        "
                                        class="flex items-center"
                                    >
                                        <input
                                            v-if="rows[rIdx - 1]"
                                            v-model="rows[rIdx - 1].label"
                                            type="text"
                                            class="w-full rounded border-slate-200 px-1.5 py-0.5 text-xs font-bold text-slate-800 focus:ring-purple-500"
                                            placeholder="Nama Kategori / Prosedur"
                                        />
                                        <span
                                            v-else
                                            class="font-bold text-slate-400"
                                            >Kategori {{ rIdx }}</span
                                        >
                                    </div>

                                    <!-- Jika kolom checkbox -->
                                    <div
                                        v-else-if="col.type === 'checkbox'"
                                        class="flex justify-center"
                                    >
                                        <div
                                            class="h-4 w-4 rounded border-2 border-slate-300 bg-slate-50"
                                        ></div>
                                    </div>

                                    <!-- Jika kolom angka / teks lainnya (grid bersih) -->
                                    <div
                                        v-else
                                        class="h-4.5 flex items-center rounded border border-dashed border-slate-200 bg-slate-50/30 px-1.5"
                                    >
                                        <span
                                            class="font-mono text-[10px] text-slate-300"
                                            >{{
                                                col.type === 'number'
                                                    ? '0'
                                                    : col.type === 'date'
                                                      ? 'YYYY-MM-DD'
                                                      : col.type === 'time'
                                                        ? 'HH:MM'
                                                        : ''
                                            }}</span
                                        >
                                    </div>
                                </td>

                                <!-- Kolom Aksi di Tiap Baris (+ Tambah Baris di Bawah / Hapus Baris) -->
                                <td
                                    class="border-l border-slate-300 bg-slate-50/40 px-2 py-2 text-center"
                                >
                                    <div
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <button
                                            @click="insertRowAt(rIdx)"
                                            type="button"
                                            class="rounded border border-transparent p-1 font-bold text-indigo-700 transition-colors hover:border-indigo-300 hover:bg-indigo-100"
                                            title="Sisipkan baris baru di bawah baris ini"
                                        >
                                            <NotionIcon name="plus" size="13" />
                                        </button>
                                        <button
                                            @click="removeRowAt(rIdx - 1)"
                                            type="button"
                                            class="rounded border border-transparent p-1 text-slate-400 transition-colors hover:border-rose-300 hover:bg-rose-100 hover:text-rose-600"
                                            title="Hapus baris ini"
                                        >
                                            <NotionIcon
                                                name="trash"
                                                size="13"
                                            />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <!-- TFOOT TOTAL FORMULA -->
                        <tfoot
                            class="select-none border-t-2 border-slate-300 bg-slate-100 font-bold text-slate-800"
                        >
                            <tr>
                                <td
                                    class="border-r border-slate-300 px-3 py-2 text-right text-[10px] uppercase tracking-wider"
                                >
                                    Rumus
                                </td>
                                <td
                                    v-for="(col, cIdx) in columns"
                                    :key="'tf-' + cIdx"
                                    class="border-r border-slate-300 px-3 py-2 text-center font-mono text-xs"
                                    :class="{
                                        'bg-emerald-100 font-extrabold text-emerald-950':
                                            col.role === 'numerator',
                                        'bg-sky-100 font-extrabold text-sky-950':
                                            col.role === 'denominator',
                                    }"
                                >
                                    <span v-if="col.role === 'numerator'"
                                        >SUM(N)</span
                                    >
                                    <span v-else-if="col.role === 'denominator'"
                                        >SUM(D)</span
                                    >
                                    <span
                                        v-else
                                        class="font-normal text-slate-400"
                                        >-</span
                                    >
                                </td>
                                <td
                                    class="border-l border-slate-300 bg-slate-100"
                                ></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Tombol Tambah Baris di Bawah Tabel -->
                    <div class="flex items-center justify-between pt-3">
                        <button
                            @click="addRowAtEnd"
                            type="button"
                            class="shadow-2xs flex items-center gap-1.5 rounded-lg border border-slate-300 bg-slate-100 px-3.5 py-1.5 text-xs font-bold text-indigo-700 transition-all hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <NotionIcon name="plus" size="14" />
                            <span>Tambah Baris Baru di Bawah</span>
                        </button>
                        <span
                            class="flex items-center gap-1 text-[11px] text-slate-400"
                        >
                            <NotionIcon
                                name="lightbulb"
                                size="13"
                                class="text-amber-500"
                            />
                            <span
                                >Gunakan tombol
                                <NotionIcon
                                    name="settings"
                                    size="11"
                                    class="inline align-baseline text-slate-600"
                                />
                                pada kolom untuk mengatur judul, merge header
                                bertingkat, dan rumus [N]/[D].</span
                            >
                        </span>
                    </div>
                </div>
            </div>

            <!-- 4. METRIC COLUMN SELECTION -->
            <div
                v-if="workflowStep === 'metrics'"
                class="shrink-0 rounded-xl border border-slate-200 bg-slate-50 p-3"
            >
                <div
                    class="mb-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h4 class="text-xs font-bold text-slate-800">
                            Tentukan Kolom Numerator [N] dan Denominator [D]
                        </h4>
                        <p class="text-[11px] text-slate-500">
                            Kolom N/D otomatis mengikuti Defined Name workbook
                            <span class="font-mono font-semibold"
                                >Numerator</span
                            >
                            dan
                            <span class="font-mono font-semibold"
                                >Denumerator</span
                            >
                            bila tersedia. Pilihan manual tetap dapat digunakan;
                            huruf dan judul kolom membantu pencocokan.
                        </p>
                    </div>
                    <p class="font-mono text-[11px] text-slate-500">
                        Capaian = (Total N / Total D) ×
                        {{ targetIndicator?.multiplier || 100 }}%
                    </p>
                </div>
                <div
                    class="mb-2 flex flex-wrap items-center justify-between gap-2"
                >
                    <p class="text-[11px] text-emerald-700">
                        {{ columns.length }} kolom tersinkron dan tersedia untuk
                        dipilih. Perubahan belum disimpan.
                    </p>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100"
                        @click="workflowStep = 'structure'"
                    >
                        Kembali ke Struktur
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <div
                        class="rounded-lg border border-emerald-200 bg-white p-2"
                    >
                        <label
                            for="numerator-column"
                            class="mb-1 block text-[11px] font-bold text-emerald-800"
                        >
                            Numerator [N]
                        </label>
                        <div class="flex items-center gap-2">
                            <select
                                id="numerator-column"
                                :value="numeratorColumnKey"
                                @change="
                                    setMetricColumn(
                                        'numerator',
                                        $event.target.value,
                                    )
                                "
                                class="min-w-0 flex-1 rounded-lg border-slate-300 px-2 py-1.5 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="" disabled>Pilih kolom</option>
                                <option
                                    v-for="(column, index) in columns"
                                    :key="column.key"
                                    :value="column.key"
                                    :disabled="
                                        column.role === 'denominator' &&
                                        column.key !== numeratorColumnKey
                                    "
                                >
                                    {{ getSpreadsheetColumnLetters(index) }} ·
                                    {{ column.title || column.key }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="rounded-lg border border-sky-200 bg-white p-2">
                        <label
                            for="denominator-column"
                            class="mb-1 block text-[11px] font-bold text-sky-800"
                        >
                            Denominator [D]
                        </label>
                        <div class="flex items-center gap-2">
                            <select
                                id="denominator-column"
                                :value="denominatorColumnKey"
                                @change="
                                    setMetricColumn(
                                        'denominator',
                                        $event.target.value,
                                    )
                                "
                                class="min-w-0 flex-1 rounded-lg border-slate-300 px-2 py-1.5 text-xs focus:border-sky-500 focus:ring-sky-500"
                            >
                                <option value="" disabled>Pilih kolom</option>
                                <option
                                    v-for="(column, index) in columns"
                                    :key="column.key"
                                    :value="column.key"
                                    :disabled="
                                        column.role === 'numerator' &&
                                        column.key !== denominatorColumnKey
                                    "
                                >
                                    {{ getSpreadsheetColumnLetters(index) }} ·
                                    {{ column.title || column.key }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <p
                    v-if="
                        numeratorColumnKey &&
                        denominatorColumnKey &&
                        !hasDistinctMetricColumns
                    "
                    class="mt-1.5 text-[11px] font-semibold text-rose-600"
                >
                    Numerator dan Denominator harus menunjuk kolom yang berbeda.
                </p>
            </div>

            <div
                v-if="saveErrorMessage"
                role="alert"
                class="shrink-0 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-800"
            >
                {{ saveErrorMessage }}
            </div>

            <!-- 5. FOOTER ACTIONS -->
            <div
                class="flex shrink-0 flex-col justify-between gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:items-center"
            >
                <div
                    class="flex items-center gap-1.5 text-xs font-bold text-slate-600"
                >
                    <span
                        v-if="
                            workflowStep === 'structure'
                                ? isStructureValid
                                : isValid
                        "
                        class="flex items-center gap-1.5 text-emerald-700"
                    >
                        <NotionIcon
                            name="check-circle"
                            size="16"
                            class="text-emerald-600"
                        />
                        <span>{{
                            workflowStep === 'structure'
                                ? structureValidationMessage
                                : isValid
                                  ? 'Format siap diterapkan untuk unit'
                                  : metricValidationMessage
                        }}</span>
                    </span>
                    <span
                        v-else
                        class="flex items-center gap-1.5 text-amber-700"
                    >
                        <NotionIcon
                            name="alert"
                            size="15"
                            class="text-amber-600"
                        />
                        <span>{{
                            workflowStep === 'structure'
                                ? structureValidationMessage
                                : metricValidationMessage
                        }}</span>
                    </span>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button
                        @click="$emit('close')"
                        type="button"
                        class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                    >
                        Batal
                    </button>

                    <button
                        @click="saveFormat"
                        :disabled="
                            (workflowStep === 'structure'
                                ? !isStructureValid
                                : !isValid) || isSubmitting
                        "
                        type="button"
                        class="flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition-all hover:bg-indigo-700 active:scale-95 disabled:opacity-50"
                    >
                        <svg
                            v-if="isSubmitting"
                            class="h-3.5 w-3.5 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            />
                        </svg>
                        <NotionIcon v-else name="check" size="14" />
                        <span>{{
                            isSubmitting
                                ? 'Menyimpan...'
                                : workflowStep === 'structure'
                                  ? 'Lanjut ke Penentuan N/D'
                                  : 'Simpan N/D & Terapkan Format'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- 6. MODAL / DIALOG SETTING KOLOM INTERAKTIF (TELEPORTED) -->
        <!-- ======================================================== -->
        <Teleport to="body">
            <div
                v-if="show && activeColIndex !== null && activeCol"
                class="backdrop-blur-xs fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/70 p-3 sm:p-4"
                @click.self="closeColSetting"
            >
                <div
                    class="animate-in fade-in zoom-in-95 w-full max-w-lg space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl duration-150"
                >
                    <!-- Header Setting Kolom -->
                    <div
                        class="flex items-center justify-between border-b border-slate-200 pb-3"
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-sm font-bold text-indigo-700"
                            >
                                <NotionIcon
                                    name="settings"
                                    size="18"
                                    class="text-indigo-600"
                                />
                            </span>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">
                                    Pengaturan Kolom #{{ activeColIndex + 1 }}:
                                    {{ activeCol.title }}
                                </h4>
                                <p class="text-[11px] text-slate-500">
                                    Atur judul kolom, merge header induk, peran
                                    mutu, dan tipe data.
                                </p>
                            </div>
                        </div>
                        <button
                            @click="closeColSetting"
                            class="rounded-lg p-1 text-slate-400 hover:text-slate-600"
                        >
                            <NotionIcon name="x" size="16" />
                        </button>
                    </div>

                    <div class="space-y-3.5 text-xs">
                        <!-- Judul Kolom -->
                        <div>
                            <label class="mb-1 block font-bold text-slate-700"
                                >Judul Kolom (Header)</label
                            >
                            <input
                                v-model="activeCol.title"
                                type="text"
                                placeholder="Contoh: No. RM / Pasien"
                                class="w-full rounded-lg border-slate-300 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Grup Header / Multi-Header (Merge) -->
                        <div
                            class="space-y-2 rounded-xl border border-indigo-100 bg-indigo-50/60 p-3"
                        >
                            <div class="flex items-center justify-between">
                                <label
                                    class="flex items-center gap-1.5 font-bold text-indigo-950"
                                >
                                    <NotionIcon
                                        name="layers"
                                        size="14"
                                        class="text-indigo-600"
                                    />
                                    <span
                                        >Gabung Header / Multi-Header
                                        (Merge)</span
                                    >
                                </label>
                                <span
                                    class="text-[10px] font-medium text-indigo-600"
                                    >Opsional (Excel Colspan)</span
                                >
                            </div>
                            <input
                                v-model="activeCol.group"
                                type="text"
                                placeholder="Ketik nama grup header induk (misal: Data Pasien, Komponen SBAR)"
                                class="w-full rounded-lg border-indigo-200 bg-white text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <div
                                v-if="existingGroups.length > 0"
                                class="flex flex-wrap items-center gap-1.5 pt-1"
                            >
                                <span
                                    class="text-[10px] font-semibold text-slate-500"
                                    >Pilih grup yang ada:</span
                                >
                                <button
                                    v-for="grp in existingGroups"
                                    :key="grp"
                                    @click="setColGroup(grp)"
                                    type="button"
                                    class="flex items-center gap-1 rounded border border-indigo-200 bg-white px-2 py-0.5 text-[10px] font-bold text-indigo-700 transition-colors hover:bg-indigo-100"
                                >
                                    <NotionIcon name="plus" size="10" />
                                    <span>{{ grp }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Tipe Data Input -->
                        <div>
                            <label class="mb-1 block font-bold text-slate-700"
                                >Tipe Data Input Sel</label
                            >
                            <select
                                v-model="activeCol.type"
                                class="w-full rounded-lg border-slate-300 text-xs font-medium focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="text">
                                    Teks Bebas / Catatan
                                </option>
                                <option value="number">
                                    Angka Bulat (Count)
                                </option>
                                <option value="decimal">Angka Desimal</option>
                                <option value="checkbox">
                                    Kotak Centang (Checkbox Ya/Tidak)
                                </option>
                                <option value="select">
                                    Daftar Pilihan (Dropdown)
                                </option>
                                <option value="date">
                                    Tanggal (YYYY-MM-DD)
                                </option>
                                <option value="time">Waktu (Jam:Menit)</option>
                            </select>
                        </div>

                        <div v-if="activeCol.type === 'select'">
                            <label class="mb-1 block font-bold text-slate-700"
                                >Pilihan Dropdown</label
                            >
                            <textarea
                                :value="(activeCol.options || []).join('\n')"
                                rows="4"
                                placeholder="Sesuai&#10;Tidak sesuai&#10;Tidak berlaku"
                                class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                @input="setDropdownOptions"
                            />
                            <p class="mt-1 text-[10px] text-slate-500">
                                Masukkan satu pilihan per baris.
                            </p>
                        </div>

                        <!-- Urutan & Aksi Kolom -->
                        <div
                            class="flex items-center justify-between gap-2 border-t border-slate-200 pt-2"
                        >
                            <div class="flex items-center gap-1.5">
                                <button
                                    @click="moveColumn(activeColIndex, -1)"
                                    :disabled="activeColIndex === 0"
                                    type="button"
                                    class="flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold hover:bg-slate-100 disabled:opacity-40"
                                    title="Geser ke kiri"
                                >
                                    <NotionIcon name="arrow-left" size="12" />
                                    <span>Geser Kiri</span>
                                </button>
                                <button
                                    @click="moveColumn(activeColIndex, 1)"
                                    :disabled="
                                        activeColIndex === columns.length - 1
                                    "
                                    type="button"
                                    class="flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold hover:bg-slate-100 disabled:opacity-40"
                                    title="Geser ke kanan"
                                >
                                    <span>Geser Kanan</span>
                                    <NotionIcon name="arrow-right" size="12" />
                                </button>
                                <button
                                    @click="duplicateColumn(activeColIndex)"
                                    type="button"
                                    class="flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold hover:bg-slate-100"
                                    title="Duplikasi kolom ini"
                                >
                                    <NotionIcon name="copy" size="12" />
                                    <span>Duplikat</span>
                                </button>
                            </div>

                            <button
                                @click="removeColumn(activeColIndex)"
                                type="button"
                                class="flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-bold text-rose-700 transition-colors hover:bg-rose-100"
                            >
                                <NotionIcon name="trash" size="12" />
                                <span>Hapus Kolom</span>
                            </button>
                        </div>
                    </div>

                    <!-- Footer Setting Kolom -->
                    <div
                        class="flex justify-end border-t border-slate-200 pt-3"
                    >
                        <button
                            @click="closeColSetting"
                            type="button"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-700"
                        >
                            Selesai (Terapkan)
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
