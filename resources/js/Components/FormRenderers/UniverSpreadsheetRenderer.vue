<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { UniverSheetsCorePreset } from '@univerjs/preset-sheets-core';
import UniverPresetSheetsCoreEnUS from '@univerjs/preset-sheets-core/locales/en-US';
import { createUniver, LocaleType, mergeLocales } from '@univerjs/presets';
import {
    clearSpreadsheetBodyValues,
    getSpreadsheetEntryRowCount,
    getSpreadsheetFixedRows,
    getSpreadsheetLogicalEntryRowCount,
    getSpreadsheetPopulatedRowCount,
    getSpreadsheetSummaryMetricRowRange,
    getSpreadsheetSummaryRowIndexes,
    maximumSpreadsheetEntryRows,
    mapSpreadsheetRows,
} from './spreadsheetWorkbookMapper';
import {
    ensureSpreadsheetWorkbookColumnCount,
    ensureSpreadsheetWorkbookRowCount,
    getSpreadsheetWorkbookColumnCount,
    getSpreadsheetHeaderRowCount,
    maximumSpreadsheetEntryColumns,
    minimumSpreadsheetBuilderColumns,
    minimumSpreadsheetBuilderRows,
    minimumSpreadsheetEntryColumns,
    minimumSpreadsheetEntryRows,
    normalizeSpreadsheetWorkbookColumns,
    reconcileSpreadsheetColumnsWithWorkbook,
} from './spreadsheetColumnRoles';
import {
    calculateSpreadsheetMetricSource,
    isCompleteSpreadsheetMetricMapping,
    resolveSpreadsheetMetricSelection,
} from './spreadsheetMetricMapping';
import '@univerjs/preset-sheets-core/lib/index.css';

const props = defineProps({
    mode: {
        type: String,
        default: 'entry',
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
    modelValue: [Object, Array],
    indicator: Object,
    schema: Object,
    workbookSnapshot: Object,
    metricMapping: Object,
    formFields: Array,
    tahun: Number,
    bulan: Number,
    permissions: Object,
    isPeriodApproved: Boolean,
    designRowCount: {
        type: Number,
        default: 3,
    },
});

const emit = defineEmits([
    'update:modelValue',
    'update:summary',
    'update:workbook',
    'change',
    'sync-error',
    'permission-error',
]);

const editorElement = ref(null);
const rowCount = ref(0);
const activeEntryRowCount = ref(1);
const summary = ref({ numerator: 0, denominator: 0, capaian: 0 });
const isEditable = computed(
    () =>
        (props.mode === 'builder' && !props.readOnly) ||
        (props.mode === 'entry' &&
            !props.readOnly &&
            props.permissions?.canEditDaily &&
            !props.isPeriodApproved),
);
const getConfiguredColumns = () => {
    if (props.schema?.columns?.length) {
        return props.schema.columns;
    }

    return [
        {
            key: 'num',
            title: props.indicator?.numerator_keterangan || 'Numerator',
            type: 'number',
            role: 'numerator',
        },
        {
            key: 'den',
            title: props.indicator?.denominator_keterangan || 'Denominator',
            type: 'number',
            role: 'denominator',
        },
    ];
};
const headerRows = computed(() =>
    getSpreadsheetHeaderRowCount(
        props.mode === 'entry'
            ? props.workbookSnapshot || props.schema?.workbook_template
            : null,
        Math.max(1, Number(props.schema?.header_rows) || 2),
        getConfiguredColumns(),
    ),
);
const getColumnsForWorkbook = (workbookSnapshot) => {
    const configuredColumns = getConfiguredColumns();
    if (props.mode !== 'entry' || !workbookSnapshot) {
        return configuredColumns;
    }

    return reconcileSpreadsheetColumnsWithWorkbook(
        configuredColumns,
        workbookSnapshot,
        headerRows.value,
        maximumSpreadsheetEntryColumns,
        1,
        { preserveMetricRolesForPositionallyMatchedColumns: true },
    );
};
const columns = computed(() =>
    getColumnsForWorkbook(
        props.workbookSnapshot || props.schema?.workbook_template,
    ),
);
const rowType = computed(
    () =>
        props.schema?.row_type ||
        props.indicator?.form_layout_type ||
        'dynamic_rows',
);
const fixedRows = computed(() => props.schema?.rows || []);
const multiplier = computed(() => Number(props.indicator?.multiplier) || 100);
const summaryRowIndexes = ref(
    getSpreadsheetSummaryRowIndexes(
        props.workbookSnapshot || props.schema?.workbook_template,
        headerRows.value,
    ),
);
let univer = null;
let univerAPI = null;
let workbook = null;
let worksheet = null;
let commandSubscription = null;
let syncTimer = null;
let isDisposed = false;
let hasPendingUserMutation = false;
let renderedRowCount = 0;
let entryWorksheetRowCount = 0;
let entryLogicalRowCount = 0;

const cloneJson = (value) => JSON.parse(JSON.stringify(value));

const isMeaningful = (value) =>
    value !== null &&
    value !== undefined &&
    value !== '' &&
    value !== false &&
    value !== 0 &&
    value !== '0';

const isChecked = (value) =>
    value === true ||
    value === 1 ||
    value === '1' ||
    value === 'true' ||
    value === 'v' ||
    value === 'V' ||
    value === '√' ||
    value === 'ya' ||
    value === 'Ya' ||
    value === 'Sesuai';

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

const getRowsFromModel = () => {
    const summaryBodyIndexes = new Set(
        summaryRowIndexes.value.map((rowIndex) => rowIndex - headerRows.value),
    );
    const removeSummaryRows = (modelRows) =>
        modelRows.filter((_, rowIndex) => !summaryBodyIndexes.has(rowIndex));

    if (rowType.value === 'fixed_rows') {
        const modelRows = Object.values(props.modelValue || {}).filter(
            (row) => row && typeof row === 'object',
        );

        return modelRows.length
            ? modelRows
            : fixedRows.value.map((row) => props.modelValue?.[row.key] || {});
    }

    if (Array.isArray(props.modelValue)) {
        return removeSummaryRows(props.modelValue);
    }

    if (Array.isArray(props.modelValue?.items)) {
        return removeSummaryRows(props.modelValue.items);
    }

    if (props.modelValue && typeof props.modelValue === 'object') {
        return removeSummaryRows(
            Object.values(props.modelValue).filter(
                (row) => row && typeof row === 'object',
            ),
        );
    }

    return [];
};

const templateSnapshot = () => {
    const hasSavedWorkbookSnapshot =
        props.mode === 'entry' && props.workbookSnapshot;
    const sourceSnapshot = hasSavedWorkbookSnapshot
        ? props.workbookSnapshot
        : props.schema?.workbook_template;
    const snapshot = sourceSnapshot ? cloneJson(sourceSnapshot) : null;

    if (!snapshot) {
        return snapshot;
    }

    const sheetId = snapshot.sheetOrder?.[0];
    const sheet = sheetId ? snapshot.sheets?.[sheetId] : null;
    if (props.mode === 'entry') {
        const bodyRowCount =
            rowType.value === 'fixed_rows'
                ? fixedRows.value.length
                : Math.max(1, getRowsFromModel().length);
        ensureSpreadsheetWorkbookRowCount(
            snapshot,
            headerRows.value + bodyRowCount,
        );
    }

    if (props.mode === 'builder') {
        ensureSpreadsheetWorkbookRowCount(
            snapshot,
            minimumSpreadsheetBuilderRows,
        );
        ensureSpreadsheetWorkbookColumnCount(
            snapshot,
            Math.max(columns.value.length, minimumSpreadsheetBuilderColumns),
        );
        const workbookColumnCount = getSpreadsheetWorkbookColumnCount(
            snapshot,
            Math.max(columns.value.length, minimumSpreadsheetBuilderColumns),
        );

        return normalizeSpreadsheetWorkbookColumns(
            snapshot,
            workbookColumnCount,
        );
    }

    if (
        props.mode !== 'entry' ||
        hasSavedWorkbookSnapshot ||
        !sheet?.cellData
    ) {
        return snapshot;
    }

    return clearSpreadsheetBodyValues(
        snapshot,
        headerRows.value,
        summaryRowIndexes.value,
    );
};

const getWorkbookSnapshot = () => {
    if (!workbook) {
        return null;
    }

    const snapshot = workbook.save();
    const sheetId = snapshot.sheetOrder?.[0];
    const sheet = sheetId ? snapshot.sheets?.[sheetId] : null;
    if (!sheet) {
        return snapshot;
    }

    const usedRange = worksheet.getDataRange().getRange();
    const lastRow = Math.max(
        headerRows.value - 1,
        Number(usedRange.endRow) || 0,
    );
    sheet.rowCount = Math.max(
        Number(sheet.rowCount) || 0,
        lastRow + 1,
        props.mode === 'builder'
            ? minimumSpreadsheetBuilderRows
            : minimumSpreadsheetEntryRows,
    );
    sheet.columnCount = getSpreadsheetWorkbookColumnCount(
        snapshot,
        props.mode === 'builder'
            ? Math.max(columns.value.length, minimumSpreadsheetBuilderColumns)
            : Math.max(columns.value.length, minimumSpreadsheetEntryColumns),
        props.mode === 'builder' ? undefined : maximumSpreadsheetEntryColumns,
    );
    snapshot.defined_names = workbook.getDefinedNames().map((definedName) => ({
        name: definedName.getName(),
        formulaOrRefString: definedName.getFormulaOrRefString(),
    }));
    if (props.mode === 'entry') {
        const entryRows = getEntryRows(snapshot);
        snapshot.entry_metadata = {
            ...snapshot.entry_metadata,
            logical_row_count: Array.isArray(entryRows)
                ? entryRows.length
                : Object.keys(entryRows).length,
        };
        if (isCompleteSpreadsheetMetricMapping(props.metricMapping)) {
            snapshot.entry_metadata.metric_mapping = cloneJson(
                props.metricMapping,
            );
        }
    }

    return snapshot;
};

const getEntryRows = (workbookSnapshot = getWorkbookSnapshot()) => {
    const entryColumns = getColumnsForWorkbook(workbookSnapshot);
    summaryRowIndexes.value = getSpreadsheetSummaryRowIndexes(
        workbookSnapshot,
        headerRows.value,
    );
    const maximumBodyRows = Math.min(
        maximumSpreadsheetEntryRows,
        Math.max(0, worksheet.getMaxRows() - headerRows.value),
    );
    const fullBodyRange =
        maximumBodyRows > 0
            ? worksheet.getRange(
                  headerRows.value,
                  0,
                  maximumBodyRows,
                  entryColumns.length,
              )
            : null;
    const allValues = fullBodyRange?.getValues() || [];
    const allFormulas = fullBodyRange?.getFormulas() || [];
    const summaryBodyIndexes = new Set(
        summaryRowIndexes.value.map((rowIndex) => rowIndex - headerRows.value),
    );
    const inputValues = [];
    const inputFormulas = [];
    allValues.forEach((rowValues, rowIndex) => {
        if (!summaryBodyIndexes.has(rowIndex)) {
            inputValues.push(rowValues);
            inputFormulas.push(allFormulas[rowIndex] || []);
        }
    });
    const lastPopulatedRowCount = getSpreadsheetPopulatedRowCount(
        inputValues,
        inputFormulas,
    );
    const structuralRowCount = getSpreadsheetLogicalEntryRowCount({
        activeRowCount: activeEntryRowCount.value,
        baselineRowCount: entryLogicalRowCount,
        currentWorksheetRowCount: worksheet.getMaxRows(),
        initialWorksheetRowCount: entryWorksheetRowCount,
        rowType: rowType.value,
    });
    const bodyCount = getSpreadsheetEntryRowCount({
        activeRowCount: Math.max(
            activeEntryRowCount.value,
            structuralRowCount,
            lastPopulatedRowCount,
        ),
        fixedRowCount: Math.max(structuralRowCount, lastPopulatedRowCount),
        maximumRowCount: maximumBodyRows,
        rowType: rowType.value,
    });
    if (bodyCount === 0) {
        return rowType.value === 'fixed_rows' ? {} : [];
    }

    const normalizedValues = Array.from(
        { length: bodyCount },
        (_, rowIndex) => inputValues[rowIndex] || [],
    );
    const rowsForMapping =
        rowType.value === 'fixed_rows'
            ? getSpreadsheetFixedRows({
                  columns: entryColumns,
                  fixedRows: fixedRows.value,
                  values: normalizedValues,
              })
            : fixedRows.value;

    return mapSpreadsheetRows({
        columns: entryColumns,
        fixedRows: rowsForMapping,
        formulas: inputFormulas.slice(0, bodyCount),
        rowType: rowType.value,
        values: normalizedValues,
    });
};

const calculateSummary = (
    rows,
    entryColumns = columns.value,
    workbookSnapshot = getWorkbookSnapshot(),
) => {
    const metricMapping =
        props.metricMapping || workbookSnapshot?.entry_metadata?.metric_mapping;
    if (isCompleteSpreadsheetMetricMapping(metricMapping) && worksheet) {
        const numerator = calculateSpreadsheetMetricSource(
            worksheet.getRange(metricMapping.numerator.range).getValues(),
            metricMapping.numerator.aggregation,
        );
        const denominator = calculateSpreadsheetMetricSource(
            worksheet.getRange(metricMapping.denominator.range).getValues(),
            metricMapping.denominator.aggregation,
        );

        summary.value = {
            numerator,
            denominator,
            capaian:
                denominator > 0
                    ? Number(
                          (
                              (numerator / denominator) *
                              multiplier.value
                          ).toFixed(2),
                      )
                    : 0,
        };
        return;
    }

    const numeratorColumns = entryColumns.filter(
        (column) => column.role === 'numerator',
    );
    const denominatorColumns = entryColumns.filter(
        (column) => column.role === 'denominator',
    );
    const compositeCriteria =
        props.schema?.formula_config?.composite_criteria || [];
    const validRows = rows.filter((row) =>
        entryColumns.some((column) => isMeaningful(row[column.key])),
    );

    let numerator = 0;
    if (rowType.value === 'dynamic_rows' && compositeCriteria.length) {
        numerator = validRows.filter((row) =>
            compositeCriteria.every((key) => isChecked(row[key])),
        ).length;
    } else {
        numeratorColumns.forEach((column) => {
            const summaryRange = getSpreadsheetSummaryMetricRowRange(
                workbookSnapshot,
                headerRows.value,
                entryColumns.indexOf(column),
            );
            rows.forEach((row, rowIndex) => {
                if (
                    summaryRange &&
                    (rowIndex < summaryRange.startRow ||
                        rowIndex > summaryRange.endRow)
                ) {
                    return;
                }

                numerator += metricValue(row[column.key], column.type);
            });
        });
    }

    let denominator = 0;
    if (denominatorColumns.length) {
        denominatorColumns.forEach((column) => {
            const summaryRange = getSpreadsheetSummaryMetricRowRange(
                workbookSnapshot,
                headerRows.value,
                entryColumns.indexOf(column),
            );
            rows.forEach((row, rowIndex) => {
                if (
                    summaryRange &&
                    (rowIndex < summaryRange.startRow ||
                        rowIndex > summaryRange.endRow)
                ) {
                    return;
                }

                denominator += metricValue(row[column.key], column.type);
            });
        });
    } else if (rowType.value === 'dynamic_rows') {
        denominator = validRows.length;
    }

    summary.value = {
        numerator,
        denominator,
        capaian:
            denominator > 0
                ? Number(
                      ((numerator / denominator) * multiplier.value).toFixed(2),
                  )
                : 0,
    };
};

const emitEntryChanges = (
    workbookSnapshot = getWorkbookSnapshot(),
    hasUserMutation = false,
) => {
    const rows = getEntryRows(workbookSnapshot);

    emit('update:modelValue', rows);
    if (workbookSnapshot) {
        emit('update:workbook', workbookSnapshot);
    }

    const summaryRows = Array.isArray(rows) ? rows : Object.values(rows);
    calculateSummary(
        summaryRows,
        getColumnsForWorkbook(workbookSnapshot),
        workbookSnapshot,
    );
    rowCount.value = summaryRows.length;
    emit('update:summary', summary.value);
    if (hasUserMutation) {
        emit('change');
    }
};

const getSelectedMetricRange = () => {
    const selectedRange = worksheet
        ?.getSelection()
        ?.getActiveRange()
        ?.getA1Notation();
    const workbookSnapshot = getWorkbookSnapshot();

    return resolveSpreadsheetMetricSelection({
        selectedRange,
        workbookSnapshot,
        headerRowCount: headerRows.value,
        summaryRowIndexes: getSpreadsheetSummaryRowIndexes(
            workbookSnapshot,
            headerRows.value,
        ),
    });
};

watch(
    () => props.metricMapping,
    () => {
        if (props.mode === 'entry' && worksheet) {
            emitEntryChanges(getWorkbookSnapshot());
        }
    },
    { deep: true },
);

const syncChanges = () => {
    if (!workbook || isDisposed) {
        return;
    }

    try {
        const workbookSnapshot = getWorkbookSnapshot();
        if (props.mode === 'builder') {
            emit('update:workbook', workbookSnapshot);
            return;
        }

        const hasUserMutation = hasPendingUserMutation;
        hasPendingUserMutation = false;
        emitEntryChanges(workbookSnapshot, hasUserMutation);
    } catch (error) {
        hasPendingUserMutation = false;
        console.error('Gagal menyiapkan snapshot spreadsheet.', error);
        emit(
            'sync-error',
            'Perubahan spreadsheet tidak dapat disiapkan. Jangan tinggalkan halaman; coba simpan kembali setelah memeriksa perubahan terakhir.',
        );
    }
};

const scheduleSync = () => {
    window.clearTimeout(syncTimer);
    syncTimer = window.setTimeout(syncChanges, 40);
};

const flushChanges = () => {
    window.clearTimeout(syncTimer);
    syncChanges();
};

const configureWorkbookPermissions = async () => {
    if (!workbook || !worksheet) {
        return;
    }

    try {
        const workbookPermission = workbook.getWorkbookPermission();
        await workbookPermission.setMode(
            isEditable.value ? 'editor' : 'viewer',
        );

        if (!isEditable.value) {
            return;
        }

        const fullSheetRange = worksheet.getRange(
            0,
            0,
            worksheet.getMaxRows(),
            worksheet.getMaxColumns(),
        );
        if (!(await fullSheetRange.getRangePermission().unprotect())) {
            throw new Error('Proteksi range spreadsheet gagal dihapus.');
        }

        const worksheetPermission = worksheet.getWorksheetPermission();
        if (props.mode !== 'entry') {
            if (
                worksheetPermission.isProtected() &&
                !(await worksheetPermission.unprotect())
            ) {
                throw new Error(
                    'Proteksi worksheet spreadsheet gagal dihapus.',
                );
            }

            return;
        }

        if (
            worksheetPermission.isProtected() &&
            !(await worksheetPermission.unprotect())
        ) {
            throw new Error('Proteksi worksheet spreadsheet gagal dihapus.');
        }

        const workbookPermissionPoints = [
            'CreateSheet',
            'DeleteSheet',
            'RenameSheet',
            'MoveSheet',
            'HideSheet',
        ];
        await Promise.all(
            workbookPermissionPoints.map((point) =>
                workbookPermission.setPoint(
                    univerAPI.Enum.WorkbookPermissionPoint[point],
                    false,
                ),
            ),
        );

        await Promise.all(
            ['InsertRow', 'InsertColumn'].map((point) =>
                workbookPermission.setPoint(
                    univerAPI.Enum.WorkbookPermissionPoint[point],
                    true,
                ),
            ),
        );
    } catch (error) {
        console.error('Gagal mengatur izin spreadsheet.', error);
        emit(
            'permission-error',
            'Spreadsheet tidak dapat disiapkan untuk mode pengisian. Muat ulang halaman sebelum melanjutkan.',
        );
    }
};

const writeInitialRows = () => {
    if (props.mode !== 'entry' || props.workbookSnapshot) {
        return;
    }

    const rows = getRowsFromModel();
    if (rowType.value === 'fixed_rows') {
        fixedRows.value.forEach((fixedRow, rowIndex) => {
            const dataRowIndex = headerRows.value + rowIndex;
            const record = rows[rowIndex] || {};

            columns.value.forEach((column, columnIndex) => {
                if (column.role === 'label' || columnIndex === 0) {
                    if (column.role === 'label') {
                        worksheet
                            .getRange(dataRowIndex, columnIndex)
                            .setValue(
                                fixedRow.values?.[column.key] ??
                                    (columnIndex === 0
                                        ? fixedRow.label || ''
                                        : ''),
                            );
                    }
                    return;
                }

                const formula = record.__formulas?.[column.key];
                const value = formula ?? record[column.key];
                if (value !== undefined && value !== null && value !== '') {
                    worksheet
                        .getRange(dataRowIndex, columnIndex)
                        .setValue(value);
                }
            });
        });
        return;
    }

    rows.forEach((record, rowIndex) => {
        columns.value.forEach((column, columnIndex) => {
            const formula = record.__formulas?.[column.key];
            const value = formula ?? record[column.key];
            if (value !== undefined && value !== null && value !== '') {
                worksheet
                    .getRange(headerRows.value + rowIndex, columnIndex)
                    .setValue(value);
            }
        });
    });
};

const initializeWorkbook = () => {
    const snapshot = templateSnapshot();
    const bodyRowCount =
        rowType.value === 'fixed_rows'
            ? fixedRows.value.length
            : Math.max(
                  1,
                  props.mode === 'builder'
                      ? props.designRowCount
                      : getRowsFromModel().length,
              );
    const requiredRowCount =
        props.mode === 'builder'
            ? Math.max(
                  headerRows.value + bodyRowCount,
                  minimumSpreadsheetBuilderRows,
              )
            : headerRows.value + bodyRowCount;
    const snapshotColumnCount = snapshot
        ? getSpreadsheetWorkbookColumnCount(
              snapshot,
              props.mode === 'builder'
                  ? Math.max(
                        columns.value.length,
                        minimumSpreadsheetBuilderColumns,
                    )
                  : Math.max(
                        columns.value.length,
                        minimumSpreadsheetEntryColumns,
                    ),
              props.mode === 'builder'
                  ? undefined
                  : maximumSpreadsheetEntryColumns,
          )
        : props.mode === 'builder'
          ? minimumSpreadsheetBuilderColumns
          : Math.max(columns.value.length, minimumSpreadsheetEntryColumns);
    if (snapshot) {
        ensureSpreadsheetWorkbookRowCount(
            snapshot,
            props.mode === 'builder'
                ? requiredRowCount
                : Math.max(requiredRowCount, minimumSpreadsheetEntryRows),
        );
        if (props.mode === 'builder') {
            ensureSpreadsheetWorkbookColumnCount(snapshot, snapshotColumnCount);
        } else {
            ensureSpreadsheetWorkbookColumnCount(
                snapshot,
                snapshotColumnCount,
                maximumSpreadsheetEntryColumns,
            );
        }
    }

    const { univer: instance, univerAPI: api } = createUniver({
        locale: LocaleType.EN_US,
        locales: {
            [LocaleType.EN_US]: mergeLocales(UniverPresetSheetsCoreEnUS),
        },
        presets: [
            UniverSheetsCorePreset({
                container: editorElement.value,
            }),
        ],
    });

    univer = instance;
    univerAPI = api;
    workbook = univerAPI.createWorkbook(snapshot || {});
    worksheet = workbook.getActiveSheet();
    const snapshotSheetId = snapshot?.sheetOrder?.[0];
    const snapshotSheet = snapshotSheetId
        ? snapshot.sheets?.[snapshotSheetId]
        : null;
    const targetRowCount =
        props.mode === 'builder'
            ? Math.max(requiredRowCount, Number(snapshotSheet?.rowCount) || 0)
            : Math.max(
                  requiredRowCount,
                  minimumSpreadsheetEntryRows,
                  props.workbookSnapshot
                      ? Number(snapshotSheet?.rowCount) || 0
                      : 0,
              );
    if (
        worksheet.getMaxRows() < targetRowCount ||
        ((props.mode === 'builder' || props.mode === 'entry') &&
            worksheet.getMaxRows() !== targetRowCount)
    ) {
        worksheet.setRowCount(targetRowCount);
    }
    if (props.mode === 'builder' || props.mode === 'entry') {
        const targetColumnCount =
            props.mode === 'entry'
                ? getSpreadsheetWorkbookColumnCount(
                      snapshot || {},
                      snapshotColumnCount,
                      maximumSpreadsheetEntryColumns,
                  )
                : Math.max(
                      snapshotColumnCount,
                      Number(snapshotSheet?.columnCount) || 0,
                  );
        if (worksheet.getMaxColumns() !== targetColumnCount) {
            worksheet.setColumnCount(targetColumnCount);
        }
    }

    renderedRowCount =
        props.mode === 'builder'
            ? props.designRowCount
            : rowType.value === 'fixed_rows'
              ? fixedRows.value.length
              : getRowsFromModel().length;
    activeEntryRowCount.value = Math.max(1, getRowsFromModel().length);
    if (props.mode === 'entry') {
        entryWorksheetRowCount = worksheet.getMaxRows();
        entryLogicalRowCount = getSpreadsheetLogicalEntryRowCount({
            activeRowCount: getRowsFromModel().length,
            baselineRowCount:
                rowType.value === 'fixed_rows'
                    ? Math.max(
                          fixedRows.value.length,
                          getRowsFromModel().length,
                      )
                    : Math.max(1, getRowsFromModel().length),
            currentWorksheetRowCount: worksheet.getMaxRows(),
            initialWorksheetRowCount: worksheet.getMaxRows(),
            rowType: rowType.value,
            savedRowCount: snapshot?.entry_metadata?.logical_row_count,
        });
    }

    if (!snapshot && props.mode !== 'builder') {
        const title = props.indicator?.judul || 'Form Pengumpul Data';
        worksheet.getRange(0, 0).setValue(title);
        worksheet.getRange(0, 0, 1, columns.value.length).merge();
        worksheet
            .getRange(1, 0, 1, columns.value.length)
            .setValues([
                columns.value.map((column) => column.title || column.key),
            ]);
    }

    writeInitialRows();

    commandSubscription = univerAPI.addEvent(
        univerAPI.Event.CommandExecuted,
        (event) => {
            if (
                event.type === univerAPI.Enum.CommandType.COMMAND ||
                event.type === univerAPI.Enum.CommandType.MUTATION
            ) {
                hasPendingUserMutation = true;
                scheduleSync();
            }
        },
    );

    flushChanges();
    void configureWorkbookPermissions();
};

watch(
    () => columns.value.map((column) => [column.key, column.title]),
    (nextColumns, previousColumns) => {
        if (props.mode !== 'builder' || !worksheet || !previousColumns) {
            return;
        }

        // The workbook is the source of truth in builder mode. Inserting or
        // rewriting columns here would shift cells the user just entered.
        scheduleSync();
    },
    { deep: true },
);

watch(isEditable, () => {
    void configureWorkbookPermissions();
});

watch(
    () => [
        rowType.value,
        props.designRowCount,
        fixedRows.value.map((row) => [row.key, row.label]),
    ],
    () => {
        if (props.mode !== 'builder' || !worksheet) {
            return;
        }

        const nextCount =
            rowType.value === 'fixed_rows'
                ? fixedRows.value.length
                : props.designRowCount;

        if (nextCount > renderedRowCount) {
            worksheet.insertRowsAfter(
                headerRows.value + renderedRowCount - 1,
                nextCount - renderedRowCount,
            );
        } else if (nextCount < renderedRowCount) {
            worksheet.deleteRows(
                headerRows.value + nextCount,
                renderedRowCount - nextCount,
            );
        }

        renderedRowCount = nextCount;
        if (rowType.value === 'fixed_rows') {
            fixedRows.value.forEach((row, index) => {
                if (columns.value[0]?.role === 'label') {
                    worksheet
                        .getRange(headerRows.value + index, 0)
                        .setValue(row.label || '');
                }
            });
        }
        scheduleSync();
    },
    { deep: true },
);

watch(
    () => props.schema?.table_title,
    (title) => {
        if (props.mode === 'builder' && title && worksheet) {
            worksheet.getRange(0, 0).setValue(title);
        }
    },
);

onMounted(initializeWorkbook);

onBeforeUnmount(() => {
    isDisposed = true;
    window.clearTimeout(syncTimer);
    commandSubscription?.dispose();
    univer?.dispose();
    univer = null;
    univerAPI = null;
    workbook = null;
    worksheet = null;
});

defineExpose({
    flushChanges,
    getWorkbookSnapshot,
    getSelectedRange: getSelectedMetricRange,
});
</script>

<template>
    <section class="univer-spreadsheet space-y-2">
        <div
            v-if="mode === 'entry'"
            class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600"
        >
            <span
                >{{ rowCount }} baris · Copy-paste dan rumus spreadsheet
                tersedia.</span
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
            ref="editorElement"
            class="univer-spreadsheet-editor"
            :class="{
                'univer-spreadsheet-editor--builder': mode === 'builder',
            }"
        />
    </section>
</template>

<style>
.univer-spreadsheet-editor {
    height: min(60vh, 620px);
    min-height: 360px;
    overflow: hidden;
}

.univer-spreadsheet-editor--builder {
    height: min(68vh, 680px);
    min-height: 420px;
}
</style>
