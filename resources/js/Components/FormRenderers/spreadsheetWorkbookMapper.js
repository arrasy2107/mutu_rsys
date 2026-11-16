export const maximumSpreadsheetEntryRows = 500;

export const getSpreadsheetEntryRowCount = ({
    activeRowCount = 0,
    fixedRowCount = 0,
    maximumRowCount,
    rowType,
}) => {
    if (!Number.isInteger(maximumRowCount) || maximumRowCount < 0) {
        return 0;
    }

    const requestedRowCount =
        rowType === 'fixed_rows' ? fixedRowCount : Math.max(1, activeRowCount);

    if (!Number.isInteger(requestedRowCount) || requestedRowCount < 0) {
        return 0;
    }

    return Math.min(requestedRowCount, maximumRowCount);
};

export const getSpreadsheetLogicalEntryRowCount = ({
    activeRowCount = 0,
    baselineRowCount = 0,
    currentWorksheetRowCount = 0,
    initialWorksheetRowCount = 0,
    rowType,
    savedRowCount,
}) => {
    const savedCount =
        savedRowCount === null || savedRowCount === undefined
            ? Number.NaN
            : Number(savedRowCount);
    const baselineCount =
        Number.isInteger(savedCount) && savedCount >= 0
            ? savedCount
            : baselineRowCount;
    const structuralCount = Math.max(
        0,
        baselineCount + currentWorksheetRowCount - initialWorksheetRowCount,
    );

    return rowType === 'fixed_rows'
        ? structuralCount
        : Math.max(1, activeRowCount, structuralCount);
};

export const getSpreadsheetFixedRows = ({ columns, fixedRows, values }) => {
    const dayColumnIndex = columns.findIndex((column) => column.key === 'day');
    const labelColumnIndex = columns.findIndex(
        (column) => column.role === 'label',
    );
    const configuredRowsByLabel = new Map(
        fixedRows
            .filter(
                (row) =>
                    row.label !== null &&
                    row.label !== undefined &&
                    String(row.label).trim() !== '',
            )
            .map((row) => [String(row.label).trim(), row]),
    );
    const candidateKeys = values.map((rowValues) => {
        const dayValue =
            dayColumnIndex >= 0 ? rowValues?.[dayColumnIndex] : null;
        if (Number.isInteger(Number(dayValue)) && Number(dayValue) > 0) {
            return String(Number(dayValue));
        }

        const labelValue =
            labelColumnIndex >= 0 ? rowValues?.[labelColumnIndex] : null;

        return (
            configuredRowsByLabel.get(String(labelValue ?? '').trim())?.key ||
            null
        );
    });
    const candidateCounts = candidateKeys.reduce((counts, key) => {
        if (key) {
            counts.set(key, (counts.get(key) || 0) + 1);
        }

        return counts;
    }, new Map());
    const reservedKeys = new Set(
        candidateKeys.filter((key) => candidateCounts.get(key) === 1),
    );
    const usedKeys = new Set();

    return values.map((_, index) => {
        const configuredRow = fixedRows[index];
        let key = candidateKeys[index];

        if (!key || candidateCounts.get(key) !== 1 || usedKeys.has(key)) {
            const configuredKey = String(configuredRow?.key || '');
            key =
                configuredKey &&
                !reservedKeys.has(configuredKey) &&
                !usedKeys.has(configuredKey)
                    ? configuredKey
                    : `row_${index + 1}`;
            while (reservedKeys.has(key) || usedKeys.has(key)) {
                key = `row_${Number(key.replace(/^row_/, '')) + 1}`;
            }
        }

        usedKeys.add(key);

        return {
            ...(configuredRow || {}),
            key,
        };
    });
};

export const isSpreadsheetAggregateFormula = (formula) => {
    if (
        typeof formula !== 'string' ||
        !/\b(?:SUM|SUBTOTAL|COUNT|COUNTA|AVERAGE|MIN|MAX)\s*\(/i.test(formula)
    ) {
        return false;
    }

    return [
        ...formula.matchAll(
            /\$?[A-Z]{1,3}\$?(\d+)\s*:\s*\$?[A-Z]{1,3}\$?(\d+)/gi,
        ),
    ].some(([, firstRow, lastRow]) => firstRow !== lastRow);
};

export const getSpreadsheetSummaryRowIndexes = (workbook, headerRowCount) => {
    const sheetId = workbook?.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;

    if (!sheet || !Number.isInteger(headerRowCount) || headerRowCount < 1) {
        return [];
    }

    return Object.entries(sheet.cellData || {})
        .filter(([rowIndex, cells]) => {
            if (Number(rowIndex) < headerRowCount) {
                return false;
            }

            const hasAggregateFormula = Object.values(cells || {}).some(
                (cell) => isSpreadsheetAggregateFormula(cell?.f),
            );
            const hasSummaryLabel = Object.values(cells || {}).some(
                (cell) =>
                    typeof cell?.v === 'string' &&
                    /^(?:grand\s+)?(?:sub)?total$|^(?:jumlah|subtotal)(?:\s+keseluruhan)?$/i.test(
                        cell.v.trim(),
                    ),
            );

            return hasAggregateFormula || hasSummaryLabel;
        })
        .map(([rowIndex]) => Number(rowIndex))
        .filter(Number.isInteger)
        .sort((first, second) => first - second);
};

export const getSpreadsheetSummaryMetricRowRange = (
    workbook,
    headerRowCount,
    columnIndex,
) => {
    const sheetId = workbook?.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;

    if (
        !sheet ||
        !Number.isInteger(headerRowCount) ||
        headerRowCount < 1 ||
        !Number.isInteger(columnIndex) ||
        columnIndex < 0
    ) {
        return null;
    }

    let spreadsheetColumnNumber = columnIndex + 1;
    let columnName = '';
    while (spreadsheetColumnNumber > 0) {
        const remainder = (spreadsheetColumnNumber - 1) % 26;
        columnName = String.fromCharCode(65 + remainder) + columnName;
        spreadsheetColumnNumber = Math.floor(
            (spreadsheetColumnNumber - 1) / 26,
        );
    }

    const summaryRows = getSpreadsheetSummaryRowIndexes(
        workbook,
        headerRowCount,
    );

    for (const summaryRowIndex of [...summaryRows].reverse()) {
        const formula = sheet.cellData?.[summaryRowIndex]?.[columnIndex]?.f;
        const match =
            typeof formula === 'string'
                ? formula.match(
                      /^\s*=?\s*SUM\(\s*\$?([A-Z]+)\$?(\d+)\s*:\s*\$?([A-Z]+)\$?(\d+)\s*\)\s*$/i,
                  )
                : null;

        if (
            !match ||
            match[1].toUpperCase() !== columnName ||
            match[3].toUpperCase() !== columnName
        ) {
            continue;
        }

        const startRow = Number(match[2]) - headerRowCount - 1;
        const endRow = Number(match[4]) - headerRowCount - 1;

        if (
            Number.isInteger(startRow) &&
            Number.isInteger(endRow) &&
            endRow >= 0 &&
            endRow >= startRow
        ) {
            return {
                startRow: Math.max(0, startRow),
                endRow,
            };
        }
    }

    return null;
};

export const clearSpreadsheetBodyValues = (
    workbook,
    headerRowCount,
    summaryRowIndexes = [],
) => {
    const sheetId = workbook?.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;

    if (!sheet?.cellData || !Number.isInteger(headerRowCount)) {
        return workbook;
    }

    const summaryRows = new Set(summaryRowIndexes);

    Object.entries(sheet.cellData).forEach(([rowIndex, cells]) => {
        const numericRowIndex = Number(rowIndex);

        if (
            numericRowIndex < headerRowCount ||
            summaryRows.has(numericRowIndex)
        ) {
            return;
        }

        Object.entries(cells || {}).forEach(([columnIndex, cell]) => {
            if (cell?.f) {
                return;
            }

            if (cell?.s !== null && cell?.s !== undefined) {
                sheet.cellData[rowIndex][columnIndex] = { s: cell.s };
            } else {
                delete sheet.cellData[rowIndex][columnIndex];
            }
        });

        if (!Object.keys(sheet.cellData[rowIndex]).length) {
            delete sheet.cellData[rowIndex];
        }
    });

    return workbook;
};

export const getSpreadsheetPopulatedRowCount = (values, formulas) =>
    values.reduce((lastPopulatedRow, rowValues, rowIndex) => {
        const hasData = (rowValues || []).some(
            (value, columnIndex) =>
                !formulas[rowIndex]?.[columnIndex] &&
                value !== null &&
                value !== undefined &&
                value !== '',
        );

        return hasData ? rowIndex + 1 : lastPopulatedRow;
    }, 0);

export const mapSpreadsheetRows = ({
    columns,
    fixedRows = [],
    formulas,
    rowType,
    values,
}) => {
    const hasLabelColumn = columns.some((column) => column.role === 'label');
    const mappedRows = values.map((rowValues, rowIndex) => {
        const row = {};
        const rowFormulas = {};

        columns.forEach((column, columnIndex) => {
            if (
                rowType === 'fixed_rows' &&
                (column.role === 'label' ||
                    (!hasLabelColumn && columnIndex === 0))
            ) {
                return;
            }

            const formula = formulas[rowIndex]?.[columnIndex] || '';
            row[column.key] = rowValues[columnIndex] ?? '';

            if (formula) {
                rowFormulas[column.key] = formula.startsWith('=')
                    ? formula
                    : `=${formula}`;
            }
        });

        if (Object.keys(rowFormulas).length) {
            row.__formulas = rowFormulas;
        }

        return row;
    });

    if (rowType !== 'fixed_rows') {
        return mappedRows;
    }

    return Object.fromEntries(
        fixedRows.map((row, index) => [row.key, mappedRows[index] || {}]),
    );
};
