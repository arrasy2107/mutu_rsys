<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UniverSpreadsheetRenderer from '@/Components/FormRenderers/UniverSpreadsheetRenderer.vue';
import {
    createSpreadsheetDraftKey,
    deleteSpreadsheetDraft,
    getWorkbookSnapshotSize,
    isValidSpreadsheetDraft,
    loadSpreadsheetDraft,
    saveSpreadsheetDraft,
} from '@/Components/FormRenderers/spreadsheetDraftStorage.js';
import {
    createLegacySpreadsheetConfig,
    legacyRecordsToSpreadsheetRows,
    spreadsheetRowsToLegacyRecords,
} from '@/Components/FormRenderers/legacySpreadsheetAdapter.js';
import {
    isCompleteSpreadsheetMetricMapping,
    supportsSpreadsheetMetricMapping,
} from '@/Components/FormRenderers/spreadsheetMetricMapping.js';

const props = defineProps({
    units: Array,
    selectedUnit: Object,
    unitIndicators: Array,
    hospitalIndicators: Array,
    selectedIndicator: Object,
    entrySchema: Object,
    formFields: Array,
    dailyRecords: [Object, Array],
    daysInMonth: Number,
    currentPeriodEntry: Object,
    annualSummary: Object,
    validationStatus: Object,
    roleScope: String,
    permissions: Object,
    filters: Object,
});

const page = usePage();
const currentUser = page.props.auth?.user;

const access = computed(
    () =>
        props.permissions || {
            canEditDaily:
                currentUser?.role !== 'direksi' &&
                (currentUser?.role === 'superadmin' ||
                    currentUser?.role === 'komite_mutu' ||
                    currentUser?.unit_id === props.selectedUnit?.id),
            canTemporarilyCorrectPeriod: false,
            canSaveMetricMappingDefault:
                currentUser?.role !== 'direksi' &&
                (currentUser?.role === 'superadmin' ||
                    currentUser?.role === 'komite_mutu' ||
                    (['kepala_unit', 'pic_unit'].includes(currentUser?.role) &&
                        currentUser?.unit_id === props.selectedUnit?.id &&
                        props.selectedIndicator?.unit_id !== null &&
                        props.selectedIndicator?.unit_id !== undefined)),
            canSubmitValidation:
                currentUser?.role !== 'direksi' &&
                currentUser?.role !== 'komite_mutu' &&
                (currentUser?.role === 'superadmin' ||
                    currentUser?.unit_id === props.selectedUnit?.id),
            isExecutive: currentUser?.role === 'direksi',
            isQualityCommittee: currentUser?.role === 'komite_mutu',
            isAdmin: currentUser?.role === 'superadmin',
        },
);

const monthNames = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];
const unitId = ref(props.filters?.unit_id || props.selectedUnit?.id);
const indicatorId = ref(props.selectedIndicator?.id || '');
const year = ref(Number(props.filters?.tahun) || 2026);
const month = ref(Number(props.filters?.bulan) || 1);
const isEditing = ref(false);
const isSaving = ref(false);
const isLoadingDraft = ref(false);
const rendererRef = ref(null);
const entryRows = ref({});
const entryWorkbook = ref(null);
const entryMetricMapping = ref({
    version: 1,
    numerator: { range: '', aggregation: 'sum' },
    denominator: { range: '', aggregation: 'sum' },
});
const selectedMetricRange = ref('');
const saveMetricMappingAsDefault = ref(false);
const hasUnsavedChanges = ref(false);
const draftStatus = ref('idle');
const entrySummary = ref({
    numerator: 0,
    denominator: 0,
    capaian: 0,
});
let draftTimer = null;
let draftStorageWarningShown = false;
let saveFailureShown = false;

const indicatorSchema = computed(
    () => props.entrySchema || props.selectedIndicator?.form_schema || {},
);
const layoutType = computed(
    () =>
        indicatorSchema.value.row_type ||
        props.selectedIndicator?.form_layout_type ||
        'daily_grid',
);
const defaultMetricMapping = computed(
    () =>
        props.selectedIndicator?.form_schema?.metric_mapping_default ||
        indicatorSchema.value.metric_mapping_default ||
        null,
);
const requiresMetricMapping = computed(() =>
    supportsSpreadsheetMetricMapping(gridSchema.value, layoutType.value),
);
const hasCompleteMetricMapping = computed(() =>
    isCompleteSpreadsheetMetricMapping(entryMetricMapping.value),
);
const metricDefinitions = [
    { key: 'numerator', label: 'Numerator (N)' },
    { key: 'denominator', label: 'Denominator (D)' },
];
const legacyLayouts = [
    'daily_grid',
    'entity_checklist',
    'entity_rows',
    'grouped_category',
    'inspection_points',
    'location_matrix',
    'monthly_summary',
    'patient_register',
];
const usesLegacyAdapter = computed(() =>
    legacyLayouts.includes(layoutType.value),
);
const legacyConfig = computed(() =>
    createLegacySpreadsheetConfig({
        bulan: month.value,
        daysInMonth: props.daysInMonth,
        formFields: props.formFields,
        indicator: props.selectedIndicator,
        layoutType: layoutType.value,
        schema: indicatorSchema.value,
        tahun: year.value,
    }),
);
const gridSchema = computed(() =>
    usesLegacyAdapter.value ? legacyConfig.value.schema : indicatorSchema.value,
);
const gridModelValue = computed(() =>
    usesLegacyAdapter.value
        ? legacyRecordsToSpreadsheetRows({
              bulan: month.value,
              config: legacyConfig.value,
              layoutType: layoutType.value,
              records: entryRows.value,
          })
        : entryRows.value,
);
const currentEntry = computed(() => props.currentPeriodEntry || null);
const spreadsheetDraftKey = computed(() => {
    if (
        !currentUser?.id ||
        !props.selectedUnit?.id ||
        !props.selectedIndicator?.id
    ) {
        return null;
    }

    return createSpreadsheetDraftKey({
        userId: currentUser.id,
        unitId: props.selectedUnit.id,
        indicatorId: props.selectedIndicator.id,
        year: year.value,
        month: month.value,
        schemaVersion: indicatorSchema.value.schema_version || 0,
        entryId: currentEntry.value?.id ?? 'new',
        entryUpdatedAt: currentEntry.value?.updated_at ?? 'new',
    });
});
const rendererWorkbookSnapshot = computed(() =>
    isEditing.value
        ? entryWorkbook.value
        : currentEntry.value?.workbook_snapshot,
);
const isSubmitted = computed(() => currentEntry.value?.status === 'submitted');
const isApproved = computed(() => currentEntry.value?.status === 'approved');
const isHistoricalPeriod = computed(() => {
    const now = new Date();
    return (
        year.value * 12 + month.value <
        now.getFullYear() * 12 + now.getMonth() + 1
    );
});
const isApprovedPeriodLocked = computed(
    () =>
        isApproved.value &&
        !(
            (access.value.isAdmin && isHistoricalPeriod.value) ||
            access.value.canTemporarilyCorrectPeriod
        ),
);
const canEditPeriod = computed(
    () =>
        access.value.canEditDaily &&
        (!isSubmitted.value || access.value.canTemporarilyCorrectPeriod) &&
        !isApprovedPeriodLocked.value,
);
const savedSummary = computed(() => ({
    numerator: Number(currentEntry.value?.numerator || 0),
    denominator: Number(currentEntry.value?.denominator || 0),
    capaian: Number(currentEntry.value?.capaian || 0),
}));
const visibleSummary = computed(() =>
    isEditing.value ? entrySummary.value : savedSummary.value,
);
const target = computed(
    () => Number(props.selectedIndicator?.target_capaian) || 0,
);
const measurementUnit = computed(() => {
    const unit = String(
        props.selectedIndicator?.satuan_pengukuran || '',
    ).trim();

    return !unit || unit.toLowerCase() === 'persentase' ? '%' : unit;
});
const targetOperator = computed(
    () => props.selectedIndicator?.target_operator || '>=',
);
const targetMet = computed(() => {
    if (visibleSummary.value.denominator <= 0) {
        return null;
    }

    if (targetOperator.value === '<=') {
        return visibleSummary.value.capaian <= target.value;
    }
    if (targetOperator.value === '=') {
        return visibleSummary.value.capaian === target.value;
    }
    return visibleSummary.value.capaian >= target.value;
});
const statusInfo = computed(() => {
    const status = currentEntry.value?.status;
    if (status === 'approved') {
        return {
            label: 'Disetujui',
            classes: 'bg-emerald-50 text-emerald-800',
        };
    }
    if (status === 'exempt' || props.validationStatus?.is_exempt) {
        return { label: 'Bebas validasi', classes: 'bg-teal-50 text-teal-800' };
    }
    if (status === 'submitted') {
        return {
            label: 'Menunggu validasi',
            classes: 'bg-amber-50 text-amber-800',
        };
    }
    if (status === 'revision') {
        return {
            label: 'Perlu revisi',
            classes: 'bg-orange-50 text-orange-800',
        };
    }
    if (status === 'rejected') {
        return { label: 'Ditolak', classes: 'bg-rose-50 text-rose-800' };
    }
    return {
        label: currentEntry.value ? 'Draft' : 'Belum ada data',
        classes: 'bg-slate-100 text-slate-700',
    };
});
const formInstructions = computed(
    () =>
        props.selectedIndicator?.definisi_operasional ||
        'Isi kolom sesuai definisi operasional indikator. Sistem menghitung Numerator, Denominator, dan capaian dari nilai yang disimpan.',
);

const clone = (value) => JSON.parse(JSON.stringify(value ?? {}));
const initializeRows = () => {
    entryRows.value = clone(props.dailyRecords);
    entryWorkbook.value =
        props.currentPeriodEntry?.workbook_snapshot ||
        indicatorSchema.value.workbook_template ||
        null;
    const savedMetricMapping =
        entryWorkbook.value?.entry_metadata?.metric_mapping ||
        defaultMetricMapping.value;
    entryMetricMapping.value = {
        version: 1,
        numerator: {
            range: savedMetricMapping?.numerator?.range || '',
            aggregation: savedMetricMapping?.numerator?.aggregation || 'sum',
        },
        denominator: {
            range: savedMetricMapping?.denominator?.range || '',
            aggregation: savedMetricMapping?.denominator?.aggregation || 'sum',
        },
    };
    selectedMetricRange.value = '';
    saveMetricMappingAsDefault.value = false;
    entrySummary.value = savedSummary.value;
};
initializeRows();

const reportDraftStorageFailure = (error, operation = 'disimpan') => {
    console.error(`Draft spreadsheet lokal gagal ${operation}.`, error);
    draftStatus.value = 'error';

    if (draftStorageWarningShown) {
        return;
    }

    draftStorageWarningShown = true;
    const detail =
        error instanceof Error && error.message ? ` ${error.message}` : '';
    window.notify?.toast?.warning(
        'Pemulihan draft lokal terganggu',
        `Draft lokal gagal ${operation}. Perubahan di editor tetap dapat dikirim dengan tombol Simpan data.${detail}`,
    );
};

const persistSpreadsheetDraft = async (
    key = spreadsheetDraftKey.value,
    snapshot = entryWorkbook.value,
    records = entryRows.value,
) => {
    if (!key || !snapshot) {
        throw new Error('Snapshot spreadsheet belum tersedia untuk draft.');
    }

    const workbookSnapshot = clone(snapshot);
    getWorkbookSnapshotSize(workbookSnapshot);
    await saveSpreadsheetDraft(key, {
        version: 1,
        key,
        records: clone(records),
        workbookSnapshot,
        savedAt: new Date().toISOString(),
    });
    draftStorageWarningShown = false;
    draftStatus.value = 'saved';
};

const scheduleSpreadsheetDraftSave = () => {
    if (!isEditing.value || !hasUnsavedChanges.value) {
        return;
    }

    window.clearTimeout(draftTimer);
    draftStatus.value = 'saving';
    const key = spreadsheetDraftKey.value;
    draftTimer = window.setTimeout(() => {
        if (!isEditing.value || key !== spreadsheetDraftKey.value) {
            return;
        }

        persistSpreadsheetDraft(key).catch(reportDraftStorageFailure);
    }, 500);
};

const flushDraftBeforePageHide = () => {
    if (!isEditing.value || !hasUnsavedChanges.value) {
        return;
    }

    window.clearTimeout(draftTimer);
    try {
        rendererRef.value?.flushChanges?.();
        persistSpreadsheetDraft().catch(reportDraftStorageFailure);
    } catch (error) {
        reportDraftStorageFailure(error);
    }
};

onMounted(() => {
    window.addEventListener('pagehide', flushDraftBeforePageHide);
});

onBeforeUnmount(() => {
    flushDraftBeforePageHide();
    window.clearTimeout(draftTimer);
    window.removeEventListener('pagehide', flushDraftBeforePageHide);
    removeInvalidResponseListener();
    removeRequestExceptionListener();
});

watch(
    () => [
        props.filters?.unit_id,
        props.filters?.indicator_id,
        props.filters?.tahun,
        props.filters?.bulan,
    ],
    () => {
        unitId.value = props.filters?.unit_id || props.selectedUnit?.id;
        indicatorId.value = props.selectedIndicator?.id || '';
        year.value = Number(props.filters?.tahun) || 2026;
        month.value = Number(props.filters?.bulan) || 1;
        isEditing.value = false;
        initializeRows();
    },
);
watch(
    () => [
        props.dailyRecords,
        props.currentPeriodEntry?.id,
        props.currentPeriodEntry?.updated_at,
    ],
    () => {
        if (!isEditing.value) {
            initializeRows();
        }
    },
    { deep: true },
);

const navigate = (overrides = {}) => {
    if (isEditing.value) {
        window.notify?.toast?.warning(
            'Selesaikan input terlebih dahulu',
            'Simpan atau batalkan perubahan sebelum berpindah unit, indikator, atau periode.',
        );
        return;
    }

    const query = {
        unit_id: unitId.value,
        indicator_id: indicatorId.value,
        tahun: year.value,
        bulan: month.value,
        ...overrides,
    };
    router.get(route('data-entry.index'), query, {
        preserveScroll: true,
        preserveState: false,
    });
};

const startEditing = async () => {
    if (!canEditPeriod.value) {
        window.notify?.toast?.warning(
            'Periode tidak dapat diedit',
            isSubmitted.value
                ? 'Data sedang menunggu validasi. Minta Komite Mutu membuka revisi.'
                : isApprovedPeriodLocked.value
                  ? 'Periode yang telah disetujui hanya dapat dibuka kembali Administrator sesuai aturan.'
                  : 'Akun ini tidak memiliki akses untuk mengubah data periode ini.',
        );
        return;
    }

    if (isLoadingDraft.value) {
        return;
    }

    isLoadingDraft.value = true;
    initializeRows();
    const key = spreadsheetDraftKey.value;

    try {
        const draft = key ? await loadSpreadsheetDraft(key) : null;
        if (draft && isValidSpreadsheetDraft(draft, key)) {
            const shouldRestore = await window.notify.confirm({
                title: 'Draft spreadsheet ditemukan',
                text: `Draft lokal tersimpan ${new Date(draft.savedAt).toLocaleString('id-ID')}. Pulihkan perubahan tersebut?`,
                confirmButtonText: 'Pulihkan draft',
                cancelButtonText: 'Mulai dari data tersimpan',
            });

            if (shouldRestore) {
                entryRows.value = clone(draft.records);
                entryWorkbook.value = clone(draft.workbookSnapshot);
                const draftMetricMapping =
                    draft.workbookSnapshot.entry_metadata?.metric_mapping ||
                    defaultMetricMapping.value;
                entryMetricMapping.value = {
                    version: 1,
                    numerator: {
                        range: draftMetricMapping?.numerator?.range || '',
                        aggregation:
                            draftMetricMapping?.numerator?.aggregation || 'sum',
                    },
                    denominator: {
                        range: draftMetricMapping?.denominator?.range || '',
                        aggregation:
                            draftMetricMapping?.denominator?.aggregation ||
                            'sum',
                    },
                };
                hasUnsavedChanges.value = true;
                draftStatus.value = 'saved';
            } else {
                await deleteSpreadsheetDraft(key);
                hasUnsavedChanges.value = false;
                draftStatus.value = 'idle';
            }
        } else if (draft && key) {
            await deleteSpreadsheetDraft(key);
            window.notify?.toast?.warning(
                'Draft lokal diabaikan',
                'Draft tidak cocok dengan periode atau struktur spreadsheet saat ini.',
            );
        }
    } catch (error) {
        reportDraftStorageFailure(error, 'dibuka');
        initializeRows();
    } finally {
        isLoadingDraft.value = false;
    }

    isEditing.value = true;
};

const cancelEditing = async () => {
    const key = spreadsheetDraftKey.value;
    window.clearTimeout(draftTimer);
    initializeRows();
    isEditing.value = false;
    hasUnsavedChanges.value = false;
    draftStatus.value = 'idle';

    if (key) {
        try {
            await deleteSpreadsheetDraft(key);
        } catch (error) {
            reportDraftStorageFailure(error, 'dihapus');
        }
    }
};

const handleGridUpdate = (rows) => {
    if (!isEditing.value) {
        return;
    }

    if (!usesLegacyAdapter.value) {
        entryRows.value = rows;
        return;
    }

    const legacyRows = spreadsheetRowsToLegacyRecords({
        bulan: month.value,
        config: legacyConfig.value,
        layoutType: layoutType.value,
        previousRecords: entryRows.value,
        spreadsheetRows: rows,
    });
    entryRows.value = {
        ...legacyRows,
        __spreadsheet: {
            layout_type: layoutType.value,
            row_type: legacyConfig.value.schema.row_type,
            columns: legacyConfig.value.schema.columns,
            rows: legacyConfig.value.schema.rows,
            header_rows: legacyConfig.value.schema.header_rows,
            records: rows,
        },
    };
};

const handleSummaryUpdate = (summary) => {
    entrySummary.value = summary;
};

const handleWorkbookUpdate = (workbook) => {
    if (!isEditing.value) {
        return;
    }

    entryWorkbook.value = workbook;
};

const handleSpreadsheetChange = () => {
    if (!isEditing.value) {
        return;
    }

    hasUnsavedChanges.value = true;
    scheduleSpreadsheetDraftSave();
};

const useSelectedRange = (metric) => {
    const selection = rendererRef.value?.getSelectedRange?.();
    if (!selection) {
        window.notify?.toast?.warning(
            'Rentang belum dipilih',
            'Pilih rentang data, atau satu sel total berformula SUM/COUNT/COUNTA. Jika memilih total, sistem akan memakai rentang detail pada formulanya.',
        );
        return;
    }

    selectedMetricRange.value = selection.range;
    entryMetricMapping.value = {
        ...entryMetricMapping.value,
        [metric]: {
            ...entryMetricMapping.value[metric],
            range: selection.range,
            ...(selection.aggregation
                ? { aggregation: selection.aggregation }
                : {}),
        },
    };
    if (entryWorkbook.value) {
        entryWorkbook.value.entry_metadata = {
            ...entryWorkbook.value.entry_metadata,
            metric_mapping: clone(entryMetricMapping.value),
        };
    }
    handleSpreadsheetChange();
};

const handleMetricAggregationChange = () => {
    if (entryWorkbook.value) {
        entryWorkbook.value.entry_metadata = {
            ...entryWorkbook.value.entry_metadata,
            metric_mapping: clone(entryMetricMapping.value),
        };
    }
    handleSpreadsheetChange();
};

const handleSpreadsheetSyncError = (message) => {
    window.notify?.toast?.error(
        'Perubahan spreadsheet gagal disiapkan',
        message,
    );
};

const handlePermissionError = (message) => {
    window.notify?.toast?.error('Spreadsheet tidak siap', message);
};

const reportSaveFailure = (message) => {
    if (saveFailureShown) {
        return;
    }

    saveFailureShown = true;
    window.notify?.toast?.error('Data belum tersimpan', message);
};

const saveEntry = async () => {
    if (!isEditing.value || !canEditPeriod.value || isSaving.value) {
        return;
    }

    if (requiresMetricMapping.value && !hasCompleteMetricMapping.value) {
        window.notify?.toast?.warning(
            'Pemetaan N/D belum lengkap',
            'Pilih rentang sumber untuk Numerator dan Denominator sebelum menyimpan data.',
        );
        return;
    }

    isSaving.value = true;
    saveFailureShown = false;
    const draftKey = spreadsheetDraftKey.value;

    try {
        rendererRef.value?.flushChanges?.();
        const workbookSnapshot =
            rendererRef.value?.getWorkbookSnapshot?.() || entryWorkbook.value;
        const records = clone(entryRows.value);

        if (workbookSnapshot) {
            if (requiresMetricMapping.value && hasCompleteMetricMapping.value) {
                workbookSnapshot.entry_metadata = {
                    ...workbookSnapshot.entry_metadata,
                    metric_mapping: clone(entryMetricMapping.value),
                };
            }
            getWorkbookSnapshotSize(workbookSnapshot);
            window.clearTimeout(draftTimer);
            try {
                await persistSpreadsheetDraft(
                    draftKey,
                    workbookSnapshot,
                    records,
                );
            } catch (error) {
                reportDraftStorageFailure(error, 'dihapus');
            }
        }

        router.post(
            route('data-entry.save-daily-records'),
            {
                indicator_id: props.selectedIndicator.id,
                unit_id: props.selectedUnit.id,
                tahun: year.value,
                bulan: month.value,
                schema_version: indicatorSchema.value.schema_version || null,
                records,
                save_metric_mapping_as_default:
                    requiresMetricMapping.value &&
                    saveMetricMappingAsDefault.value,
                ...(workbookSnapshot
                    ? { workbook_snapshot: workbookSnapshot }
                    : {}),
            },
            {
                preserveScroll: true,
                onSuccess: async (response) => {
                    const serverError = response.props.flash?.error;
                    if (typeof serverError === 'string' && serverError.trim()) {
                        reportSaveFailure(serverError);
                        return;
                    }

                    window.clearTimeout(draftTimer);
                    if (draftKey) {
                        try {
                            await deleteSpreadsheetDraft(draftKey);
                        } catch (error) {
                            reportDraftStorageFailure(error);
                        }
                    }

                    hasUnsavedChanges.value = false;
                    draftStatus.value = 'idle';
                    isEditing.value = false;
                    window.notify?.toast?.success(
                        'Data tersimpan',
                        'Data dan perhitungan periode telah disimpan. Buka kembali periode untuk memastikan perubahan.',
                    );
                },
                onError: (errors) => {
                    const message = Object.values(errors)
                        .flatMap((error) =>
                            Array.isArray(error) ? error : [error],
                        )
                        .filter(
                            (error) =>
                                typeof error === 'string' &&
                                error.trim() !== '',
                        )
                        .join(' ');
                    reportSaveFailure(
                        message ||
                            'Server menolak data. Periksa kembali isian form.',
                    );
                },
                onFinish: () => {
                    isSaving.value = false;
                    saveFailureShown = false;
                },
            },
        );
    } catch (error) {
        console.error('Gagal menyiapkan penyimpanan data spreadsheet.', error);
        isSaving.value = false;
        reportSaveFailure(
            error instanceof Error && error.message
                ? error.message
                : 'Spreadsheet gagal disiapkan untuk disimpan. Perubahan masih tersedia di editor.',
        );
    }
};

const removeInvalidResponseListener = router.on('invalid', (event) => {
    if (!isSaving.value) {
        return;
    }

    const response = event.detail.response;
    const responseMessage =
        response.data &&
        typeof response.data === 'object' &&
        typeof response.data.message === 'string'
            ? response.data.message
            : `Server mengembalikan kesalahan HTTP ${response.status}. Periksa ukuran dan format data yang dikirim.`;
    reportSaveFailure(responseMessage);
});

const removeRequestExceptionListener = router.on('exception', (event) => {
    if (!isSaving.value) {
        return;
    }

    reportSaveFailure(
        event.detail.exception.message ||
            'Koneksi ke server gagal. Draft lokal tetap tersedia untuk dipulihkan.',
    );
});

const submitPeriod = async () => {
    if (isEditing.value) {
        window.notify?.toast?.warning(
            'Simpan perubahan terlebih dahulu',
            'Data yang sedang diedit harus disimpan atau dibatalkan sebelum diajukan.',
        );
        return;
    }
    if (savedSummary.value.denominator <= 0) {
        window.notify?.alert?.warning(
            'Data belum lengkap',
            'Denominator harus lebih besar dari 0 sebelum data dapat diajukan.',
        );
        return;
    }

    const confirmed = await window.notify.confirm({
        title: 'Ajukan data untuk validasi?',
        text: `${monthNames[month.value - 1]} ${year.value} — ${props.selectedIndicator?.kode}. Capaian ${savedSummary.value.capaian}% dengan N ${savedSummary.value.numerator} dan D ${savedSummary.value.denominator}.`,
        confirmButtonText: 'Ajukan',
        cancelButtonText: 'Periksa kembali',
    });
    if (!confirmed) {
        return;
    }

    router.post(
        route('data-entry.submit-period'),
        {
            indicator_id: props.selectedIndicator.id,
            unit_id: props.selectedUnit.id,
            tahun: year.value,
            bulan: month.value,
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                window.notify?.toast?.success(
                    'Pengajuan terkirim',
                    'Data periode diajukan untuk proses validasi.',
                ),
        },
    );
};

const exportExcel = () => {
    window.location.href = route('data-entry.export-excel', {
        indicator_id: props.selectedIndicator.id,
        unit_id: props.selectedUnit.id,
        tahun: year.value,
        bulan: month.value,
    });
};

const monthStatusClass = (monthNumber) => {
    const summary = props.annualSummary?.[monthNumber];
    if (!summary || summary.capaian === null) {
        return 'border-slate-200 bg-white text-slate-500';
    }
    if (summary.status === 'approved') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    }
    return summary.capaian >= target.value
        ? 'border-teal-200 bg-teal-50 text-teal-800'
        : 'border-rose-200 bg-rose-50 text-rose-800';
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-indigo-600"
                    >
                        Pengumpulan data mutu
                    </p>
                    <h1 class="text-lg font-bold text-slate-900">
                        Form Pengumpul Data
                    </h1>
                </div>
                <span
                    v-if="access.isExecutive"
                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"
                >
                    Monitoring · baca saja
                </span>
            </div>
        </template>

        <main class="space-y-5">
            <section
                class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-4"
                aria-label="Pilih unit dan periode data"
            >
                <label
                    v-if="
                        access.isAdmin ||
                        access.isQualityCommittee ||
                        access.isExecutive
                    "
                    class="space-y-1.5"
                >
                    <span class="text-xs font-bold uppercase text-slate-500"
                        >Unit kerja</span
                    >
                    <select
                        v-model="unitId"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="navigate({ unit_id: unitId })"
                    >
                        <option
                            v-for="unit in units"
                            :key="unit.id"
                            :value="unit.id"
                        >
                            {{ unit.kode }} — {{ unit.nama }}
                        </option>
                    </select>
                </label>
                <div v-else class="space-y-1.5 rounded-lg bg-slate-50 p-3">
                    <span class="text-xs font-bold uppercase text-slate-500"
                        >Unit kerja</span
                    >
                    <p class="text-sm font-semibold text-slate-900">
                        {{ selectedUnit?.kode }} — {{ selectedUnit?.nama }}
                    </p>
                </div>

                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase text-slate-500"
                        >Indikator mutu</span
                    >
                    <select
                        v-model="indicatorId"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="navigate({ indicator_id: indicatorId })"
                    >
                        <option
                            v-for="indicator in unitIndicators"
                            :key="indicator.id"
                            :value="indicator.id"
                        >
                            {{ indicator.kode }} — {{ indicator.judul }}
                        </option>
                    </select>
                </label>

                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase text-slate-500"
                        >Tahun</span
                    >
                    <input
                        v-model.number="year"
                        type="number"
                        min="2020"
                        max="2100"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="navigate({ tahun: year })"
                    />
                </label>

                <div class="flex items-end">
                    <button
                        type="button"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        @click="exportExcel"
                        :disabled="!selectedIndicator || !selectedUnit"
                    >
                        Unduh Excel
                    </button>
                </div>
            </section>

            <section
                v-if="selectedIndicator"
                class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                aria-label="Entri data periode"
            >
                <div
                    class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white"
                            >2</span
                        >
                        <div>
                            <h2 class="font-bold text-slate-900">
                                Data periode
                            </h2>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ selectedIndicator.kode }} ·
                                {{ monthNames[month - 1] }} {{ year }}
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 sm:flex sm:flex-wrap">
                        <button
                            v-for="monthNumber in 12"
                            :key="monthNumber"
                            type="button"
                            :disabled="isEditing"
                            :aria-pressed="month === monthNumber"
                            :class="[
                                'min-w-16 rounded-lg border px-3 py-2 text-xs font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                                month === monthNumber
                                    ? 'border-indigo-600 bg-indigo-600 text-white'
                                    : monthStatusClass(monthNumber),
                            ]"
                            @click="navigate({ bulan: monthNumber })"
                        >
                            {{ monthNames[monthNumber - 1].slice(0, 3) }}
                            <span
                                v-if="
                                    props.annualSummary?.[monthNumber]
                                        ?.capaian !== null &&
                                    props.annualSummary?.[monthNumber]
                                        ?.capaian !== undefined
                                "
                                class="mt-0.5 block font-mono text-[10px]"
                            >
                                {{ props.annualSummary[monthNumber].capaian }}%
                            </span>
                        </button>
                    </div>
                </div>

                <div
                    v-if="isEditing"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"
                    role="status"
                >
                    Mode edit aktif. Ubah sel, rumus, merge, serta baris atau
                    kolom sesuai kebutuhan. Spreadsheet dan data periode
                    disimpan bersama.
                    <span
                        v-if="draftStatus === 'saving'"
                        class="ml-1 font-semibold"
                    >
                        Menyimpan draft lokal…
                    </span>
                    <span
                        v-else-if="draftStatus === 'saved'"
                        class="ml-1 font-semibold"
                    >
                        Draft lokal tersimpan di browser; klik Simpan data untuk
                        mengirimnya ke server.
                    </span>
                    <span
                        v-else-if="draftStatus === 'error'"
                        class="ml-1 font-semibold"
                    >
                        Draft lokal gagal tersimpan; segera klik Simpan data
                        agar perubahan terkirim ke server.
                    </span>
                </div>

                <div
                    v-if="isApprovedPeriodLocked"
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900"
                >
                    Data ini sudah disetujui dan terkunci. Untuk koreksi,
                    hubungi Administrator.
                </div>
                <div
                    v-else-if="
                        isSubmitted && !access.canTemporarilyCorrectPeriod
                    "
                    class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"
                >
                    Data sedang menunggu validasi, sehingga belum dapat diedit.
                </div>

                <div
                    class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2 xl:grid-cols-5"
                >
                    <div>
                        <p class="text-xs text-slate-500">Status data</p>
                        <span
                            class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="statusInfo.classes"
                        >
                            {{ statusInfo.label }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Numerator (N)</p>
                        <p
                            class="mt-1 font-mono text-lg font-bold text-emerald-700"
                        >
                            {{
                                visibleSummary.numerator.toLocaleString('id-ID')
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Denominator (D)</p>
                        <p
                            class="mt-1 font-mono text-lg font-bold text-sky-700"
                        >
                            {{
                                visibleSummary.denominator.toLocaleString(
                                    'id-ID',
                                )
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Capaian</p>
                        <p
                            class="mt-1 font-mono text-lg font-bold"
                            :class="
                                targetMet === null
                                    ? 'text-slate-700'
                                    : targetMet
                                      ? 'text-emerald-700'
                                      : 'text-rose-700'
                            "
                        >
                            {{ visibleSummary.capaian }}{{ measurementUnit }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Target</p>
                        <p class="mt-1 font-semibold text-slate-800">
                            {{ targetOperator }} {{ target
                            }}{{ measurementUnit }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-col gap-3 border-b border-slate-100 pb-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3 class="font-semibold text-slate-900">
                            {{ selectedIndicator.judul }}
                        </h3>
                        <p class="mt-1 max-w-3xl text-xs text-slate-600">
                            {{ formInstructions }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="!isEditing && canEditPeriod"
                            type="button"
                            class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                            :disabled="isLoadingDraft"
                            @click="startEditing"
                        >
                            {{
                                isLoadingDraft
                                    ? 'Memeriksa draft…'
                                    : currentEntry
                                      ? 'Edit data'
                                      : 'Input data'
                            }}
                        </button>
                        <button
                            v-if="isEditing"
                            type="button"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            :disabled="isSaving"
                            @click="cancelEditing"
                        >
                            Batalkan
                        </button>
                        <button
                            v-if="isEditing"
                            type="button"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60"
                            :disabled="
                                isSaving ||
                                (requiresMetricMapping &&
                                    !hasCompleteMetricMapping)
                            "
                            @click="saveEntry"
                        >
                            {{ isSaving ? 'Menyimpan...' : 'Simpan data' }}
                        </button>
                        <button
                            v-if="
                                access.canSubmitValidation &&
                                currentEntry?.status !== 'submitted' &&
                                currentEntry?.status !== 'approved' &&
                                !props.validationStatus?.is_exempt &&
                                !isEditing
                            "
                            type="button"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                            :disabled="visibleSummary.denominator <= 0"
                            @click="submitPeriod"
                        >
                            Ajukan validasi
                        </button>
                        <Link
                            v-if="
                                currentEntry?.status === 'submitted' &&
                                (access.isQualityCommittee || access.isAdmin)
                            "
                            :href="
                                route('validation.index', {
                                    unit_id: selectedUnit?.id,
                                })
                            "
                            class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700"
                        >
                            Buka validasi
                        </Link>
                        <Link
                            v-if="
                                currentEntry?.status === 'submitted' &&
                                access.canSubmitValidation
                            "
                            :href="route('validation.show', currentEntry.id)"
                            class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                        >
                            Lihat pengajuan
                        </Link>
                    </div>
                </div>

                <section
                    v-if="isEditing && requiresMetricMapping"
                    class="grid gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 p-3"
                >
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">
                            Tentukan sumber Numerator dan Denominator
                        </h4>
                        <p class="mt-1 text-xs text-slate-600">
                            Pilih sel atau rentang data di spreadsheet, lalu
                            tetapkan sebagai sumber N atau D. Sel berisi data
                            tetap dapat dipilih. Untuk memakai baris total,
                            pilih satu sel total berformula SUM, COUNT, atau
                            COUNTA; sistem akan menggunakan rentang detailnya.
                        </p>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div
                            v-for="metric in metricDefinitions"
                            :key="metric.key"
                            class="grid gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-[1fr_auto]"
                        >
                            <label
                                class="grid gap-1 text-xs font-medium text-slate-700"
                            >
                                {{ metric.label }}
                                <input
                                    :value="
                                        entryMetricMapping[metric.key].range ||
                                        'Belum dipilih'
                                    "
                                    readonly
                                    class="rounded-md border-slate-300 bg-slate-50 text-sm"
                                />
                            </label>
                            <div class="flex items-end gap-2">
                                <label
                                    class="grid gap-1 text-xs text-slate-600"
                                >
                                    Hitung
                                    <select
                                        v-model="
                                            entryMetricMapping[metric.key]
                                                .aggregation
                                        "
                                        class="rounded-md border-slate-300 text-sm"
                                        @change="handleMetricAggregationChange"
                                    >
                                        <option value="sum">
                                            Jumlah (SUM)
                                        </option>
                                        <option value="count">
                                            Angka (COUNT)
                                        </option>
                                        <option value="counta">
                                            Sel terisi (COUNTA)
                                        </option>
                                    </select>
                                </label>
                                <button
                                    type="button"
                                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                    @click="useSelectedRange(metric.key)"
                                >
                                    Gunakan seleksi
                                </button>
                            </div>
                        </div>
                    </div>
                    <label
                        v-if="access.canSaveMetricMappingDefault"
                        class="flex items-start gap-2 text-xs text-slate-700"
                    >
                        <input
                            v-model="saveMetricMappingAsDefault"
                            type="checkbox"
                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span>
                            Simpan pemetaan ini sebagai default indikator untuk
                            periode berikutnya
                        </span>
                    </label>
                    <p
                        v-if="!hasCompleteMetricMapping"
                        class="text-xs font-medium text-amber-700"
                    >
                        Pemetaan N dan D wajib dipilih sebelum data dapat
                        disimpan.
                    </p>
                    <p
                        v-else-if="selectedMetricRange"
                        class="text-xs text-slate-500"
                    >
                        Seleksi terakhir: {{ selectedMetricRange }}
                    </p>
                </section>

                <UniverSpreadsheetRenderer
                    ref="rendererRef"
                    :key="`${selectedIndicator.id}-${indicatorSchema.schema_version || 0}-${year}-${month}-${isEditing}`"
                    :model-value="gridModelValue"
                    :indicator="selectedIndicator"
                    :schema="gridSchema"
                    :workbook-snapshot="rendererWorkbookSnapshot"
                    :metric-mapping="entryMetricMapping"
                    :form-fields="formFields"
                    :tahun="year"
                    :bulan="month"
                    :permissions="access"
                    :is-period-approved="isApprovedPeriodLocked"
                    :read-only="!isEditing"
                    @update:model-value="handleGridUpdate"
                    @update:summary="handleSummaryUpdate"
                    @update:workbook="handleWorkbookUpdate"
                    @change="handleSpreadsheetChange"
                    @sync-error="handleSpreadsheetSyncError"
                    @permission-error="handlePermissionError"
                />
            </section>

            <section
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"
            >
                <h2 class="text-lg font-bold text-slate-900">
                    Belum ada indikator aktif
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Pilih unit yang memiliki indikator mutu aktif untuk mulai
                    mengumpulkan data.
                </p>
            </section>
        </main>
    </AppLayout>
</template>
