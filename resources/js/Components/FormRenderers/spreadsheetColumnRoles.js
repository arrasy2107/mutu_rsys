const supportedRoles = new Set([
    'auxiliary',
    'denominator',
    'label',
    'numerator',
]);

const maximumSpreadsheetColumns = 16384;
const maximumSpreadsheetRows = 1048576;
export const minimumSpreadsheetBuilderColumns = 500;
export const minimumSpreadsheetBuilderRows = 500;
export const minimumSpreadsheetEntryColumns = 100;
export const minimumSpreadsheetEntryRows = 510;
export const maximumSpreadsheetEntryColumns = 500;

const normalizeSpreadsheetMetricColumnType = (column) => {
    if (!['number', 'decimal', 'checkbox'].includes(column.type)) {
        column.type = 'number';
    }
};

export const setSpreadsheetColumnRole = (column, role) => {
    if (!supportedRoles.has(role)) {
        throw new RangeError(`Unsupported spreadsheet column role: ${role}`);
    }

    column.role = role;
};

export const setSpreadsheetMetricRoleFromColumn = (
    columns,
    role,
    columnKey,
) => {
    if (!['numerator', 'denominator'].includes(role)) {
        throw new RangeError(`Unsupported spreadsheet metric role: ${role}`);
    }

    const selectedColumn = columns.find((column) => column.key === columnKey);

    if (!selectedColumn) {
        return false;
    }

    const otherRole = role === 'numerator' ? 'denominator' : 'numerator';

    if (selectedColumn.role === otherRole) {
        return false;
    }

    if (selectedColumn.role === role) {
        normalizeSpreadsheetMetricColumnType(selectedColumn);

        return true;
    }

    columns.forEach((column) => {
        if (column.role === role) {
            column.role = 'auxiliary';
        }
    });
    selectedColumn.role = role;
    normalizeSpreadsheetMetricColumnType(selectedColumn);

    return true;
};

export const getSpreadsheetColumnLetters = (zeroBasedColumnIndex) => {
    if (
        !Number.isInteger(zeroBasedColumnIndex) ||
        zeroBasedColumnIndex < 0 ||
        zeroBasedColumnIndex >= maximumSpreadsheetColumns
    ) {
        return null;
    }

    let columnNumber = zeroBasedColumnIndex + 1;
    let letters = '';

    while (columnNumber > 0) {
        const remainder = (columnNumber - 1) % 26;
        letters = String.fromCharCode(65 + remainder) + letters;
        columnNumber = Math.floor((columnNumber - 1) / 26);
    }

    return letters;
};

export const getSpreadsheetCellAddress = (
    zeroBasedColumnIndex,
    oneBasedRowNumber,
) => {
    const columnLetters = getSpreadsheetColumnLetters(zeroBasedColumnIndex);

    if (
        !columnLetters ||
        !Number.isInteger(oneBasedRowNumber) ||
        oneBasedRowNumber < 1 ||
        oneBasedRowNumber > maximumSpreadsheetRows
    ) {
        return '';
    }

    return `${columnLetters}${oneBasedRowNumber}`;
};

export const getMeaningfulSpreadsheetHeaderIndexes = (headerCells) => {
    if (!headerCells || typeof headerCells !== 'object') {
        return [];
    }

    return Object.entries(headerCells)
        .filter(([, cell]) => {
            if (!cell || typeof cell !== 'object') {
                return false;
            }

            const value = cell.v;

            return typeof value === 'string' && value.trim() !== '';
        })
        .map(([index]) => Number(index))
        .filter((index) => Number.isInteger(index) && index >= 0);
};

export const getSpreadsheetHeaderColumnTitles = (
    workbook,
    headerRowCount,
    minimumColumnCount = 0,
    maximumColumnCount = maximumSpreadsheetColumns,
) => {
    if (
        !workbook ||
        typeof workbook !== 'object' ||
        !Number.isInteger(headerRowCount) ||
        headerRowCount < 1 ||
        !Number.isInteger(minimumColumnCount) ||
        minimumColumnCount < 0 ||
        !Number.isInteger(maximumColumnCount) ||
        maximumColumnCount < 1
    ) {
        return [];
    }

    const sheetId = workbook.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;

    if (!sheet) {
        return [];
    }

    const headerRows = Array.from({ length: headerRowCount }, (_, index) =>
        Object.entries(sheet.cellData?.[String(index)] || {}).reduce(
            (cells, [columnIndex, cell]) => {
                if (
                    cell &&
                    typeof cell.v === 'string' &&
                    cell.v.trim() !== ''
                ) {
                    cells.set(Number(columnIndex), cell.v.trim());
                }

                return cells;
            },
            new Map(),
        ),
    );
    const mergeData = Array.isArray(sheet.mergeData) ? sheet.mergeData : [];
    let lastColumnIndex = Math.min(minimumColumnCount, maximumColumnCount) - 1;

    headerRows.forEach((cells) => {
        cells.forEach((_, columnIndex) => {
            if (
                Number.isInteger(columnIndex) &&
                columnIndex >= 0 &&
                columnIndex < maximumColumnCount
            ) {
                lastColumnIndex = Math.max(lastColumnIndex, columnIndex);
            }
        });
    });

    mergeData.forEach((merge) => {
        if (
            !merge ||
            Number(merge.startRow) >= headerRowCount ||
            Number(merge.endRow) < 0
        ) {
            return;
        }

        const startColumn = Number(merge.startColumn);
        const endColumn = Number(merge.endColumn ?? startColumn);

        if (
            Number.isInteger(startColumn) &&
            Number.isInteger(endColumn) &&
            startColumn >= 0 &&
            startColumn < maximumColumnCount &&
            endColumn >= startColumn
        ) {
            lastColumnIndex = Math.max(
                lastColumnIndex,
                Math.min(endColumn, maximumColumnCount - 1),
            );
        }
    });

    if (lastColumnIndex < 0) {
        return [];
    }

    return Array.from({ length: lastColumnIndex + 1 }, (_, columnIndex) => {
        for (let rowIndex = headerRows.length - 1; rowIndex >= 0; rowIndex--) {
            const title = headerRows[rowIndex].get(columnIndex);

            if (title) {
                const containingMerge = mergeData.find(
                    (merge) =>
                        Number(merge.startRow) <= rowIndex &&
                        Number(merge.endRow) >= rowIndex &&
                        Number(merge.startColumn) <= columnIndex &&
                        Number(merge.endColumn ?? merge.startColumn) >=
                            columnIndex,
                );
                const mergeWidth =
                    containingMerge &&
                    Number(containingMerge.endColumn ?? columnIndex) -
                        Number(containingMerge.startColumn) +
                        1;

                if (mergeWidth > 1) {
                    continue;
                }

                return title;
            }
        }

        return null;
    });
};

export const getSpreadsheetHeaderRowCount = (
    workbook,
    configuredHeaderRowCount,
    columns = [],
) => {
    if (
        !Number.isInteger(configuredHeaderRowCount) ||
        configuredHeaderRowCount < 1
    ) {
        return 1;
    }

    const sheetId = workbook?.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;
    if (!sheet) {
        return configuredHeaderRowCount;
    }

    const metricColumns = (Array.isArray(columns) ? columns : [])
        .map((column, columnIndex) => ({ column, columnIndex }))
        .filter(({ column }) =>
            ['numerator', 'denominator'].includes(column?.role),
        );

    for (let rowIndex = 1; rowIndex < configuredHeaderRowCount; rowIndex++) {
        const cells = sheet.cellData?.[rowIndex] || {};
        const hasFormula = Object.values(cells).some(
            (cell) => typeof cell?.f === 'string' && cell.f.trim() !== '',
        );
        const hasMetricInput = metricColumns.some(({ column, columnIndex }) => {
            const value = cells[columnIndex]?.v;

            if (value === null || value === undefined || value === '') {
                return false;
            }

            if (typeof value === 'number' || typeof value === 'boolean') {
                return true;
            }

            if (typeof value !== 'string') {
                return false;
            }

            const normalizedValue = value.trim().toLocaleLowerCase();

            return (
                !Number.isNaN(Number(normalizedValue)) ||
                (column.type === 'checkbox' &&
                    [
                        'false',
                        'no',
                        'sesuai',
                        'tidak',
                        'tidak sesuai',
                        'true',
                        'v',
                        'x',
                        'ya',
                        'yes',
                        '√',
                    ].includes(normalizedValue))
            );
        });

        if (hasFormula || hasMetricInput) {
            return rowIndex;
        }
    }

    return configuredHeaderRowCount;
};

export const reconcileSpreadsheetColumns = (
    existingColumns,
    headerTitles,
    { preserveMetricRolesForPositionallyMatchedColumns = false } = {},
) => {
    const currentColumns = Array.isArray(existingColumns)
        ? existingColumns
        : [];
    const titles = Array.isArray(headerTitles) ? headerTitles : [];
    const normalizedTitle = (title) =>
        typeof title === 'string'
            ? title.trim().replace(/\s+/g, ' ').toLocaleLowerCase()
            : '';
    const matchingColumns = new Map();

    currentColumns.forEach((column) => {
        const title = normalizedTitle(column.title);
        if (!title) {
            return;
        }

        const matches = matchingColumns.get(title) || [];
        matches.push(column);
        matchingColumns.set(title, matches);
    });

    const exactColumns = titles.map((headerTitle) => {
        const title = normalizedTitle(headerTitle);
        const matches = matchingColumns.get(title) || [];
        return title && matches.length === 1 ? matches[0] : null;
    });
    const reservedExactColumns = new Set(exactColumns.filter(Boolean));
    const usedColumns = new Set();
    const assignedKeys = new Set(
        currentColumns
            .map((column) => column.key)
            .filter((key) => typeof key === 'string' && key !== ''),
    );
    const nextColumns = titles.map((headerTitle, index) => {
        const title = normalizedTitle(headerTitle);
        let column = exactColumns[index];
        let isPositionallyMatched = false;

        if (
            (!column || usedColumns.has(column)) &&
            currentColumns[index] &&
            !usedColumns.has(currentColumns[index]) &&
            (!reservedExactColumns.has(currentColumns[index]) ||
                exactColumns[index] === currentColumns[index])
        ) {
            column = currentColumns[index];
            isPositionallyMatched = true;
        }

        if (column && usedColumns.has(column)) {
            column = null;
        }

        if (!column) {
            let keyNumber = index + 1;
            while (assignedKeys.has(`col_${keyNumber}`)) {
                keyNumber++;
            }
            const key = `col_${keyNumber}`;
            assignedKeys.add(key);

            column = {
                id: `c_${key}`,
                key,
                type: 'text',
                role: 'auxiliary',
            };
        } else {
            usedColumns.add(column);
            column = { ...column };
        }

        if (headerTitle) {
            if (
                normalizedTitle(column.title) !== title &&
                ['numerator', 'denominator'].includes(column.role) &&
                !(
                    preserveMetricRolesForPositionallyMatchedColumns &&
                    isPositionallyMatched
                )
            ) {
                column.role = 'auxiliary';
            }
            column.title = headerTitle;
        } else {
            if (['numerator', 'denominator'].includes(column.role)) {
                column.role = 'auxiliary';
            }
            column.title = `Kolom ${getSpreadsheetColumnLetters(index)}`;
        }

        return column;
    });

    return nextColumns;
};

export const reconcileSpreadsheetColumnsWithWorkbook = (
    existingColumns,
    workbook,
    headerRowCount,
    maximumColumnCount = maximumSpreadsheetColumns,
    minimumColumnCount = Array.isArray(existingColumns)
        ? existingColumns.length
        : 0,
    options = {},
) => {
    const columns = Array.isArray(existingColumns) ? existingColumns : [];
    const headerTitles = getSpreadsheetHeaderColumnTitles(
        workbook,
        headerRowCount,
        minimumColumnCount,
        maximumColumnCount,
    );

    return headerTitles.length
        ? reconcileSpreadsheetColumns(columns, headerTitles, options)
        : columns;
};

const getColumnIndexFromDefinedNameReference = (reference) => {
    if (typeof reference !== 'string') {
        return null;
    }

    const ref = reference.trim().replace(/^=/, '');
    const rangeReference = ref.includes('!')
        ? ref.slice(ref.lastIndexOf('!') + 1)
        : ref;
    const cellRange = rangeReference.match(
        /^\$?([A-Z]{1,3})\$?[1-9]\d*(?::\$?([A-Z]{1,3})\$?[1-9]\d*)?$/i,
    );
    const wholeColumnRange = rangeReference.match(
        /^\$?([A-Z]{1,3}):\$?([A-Z]{1,3})$/i,
    );
    const firstColumn = cellRange?.[1] || wholeColumnRange?.[1];
    const lastColumn =
        cellRange?.[2] || wholeColumnRange?.[2] || firstColumn;

    if (!firstColumn || firstColumn.toUpperCase() !== lastColumn.toUpperCase()) {
        return null;
    }

    let columnNumber = 0;
    for (const letter of firstColumn.toUpperCase()) {
        columnNumber =
            columnNumber * 26 + letter.charCodeAt(0) - 'A'.charCodeAt(0) + 1;
    }

    return columnNumber - 1;
};

export const getDefinedNameMetricColumns = (definedNames, columns) => {
    const columnList = Array.isArray(columns) ? columns : [];
    const names = Array.isArray(definedNames) ? definedNames : [];
    const metricColumns = {
        numeratorColumnKey: '',
        denominatorColumnKey: '',
    };

    names.forEach((definedName) => {
        const name = (
            typeof definedName?.getName === 'function'
                ? definedName.getName()
                : definedName?.name
        )
            ?.trim()
            .toLocaleLowerCase();
        const reference =
            typeof definedName?.getFormulaOrRefString === 'function'
                ? definedName.getFormulaOrRefString()
                : (definedName?.formulaOrRefString ??
                  definedName?.formula_or_ref_string);
        const columnIndex = getColumnIndexFromDefinedNameReference(reference);
        const columnKey = columnList[columnIndex]?.key;

        if (!columnKey) {
            return;
        }

        if (name === 'numerator') {
            metricColumns.numeratorColumnKey = columnKey;
        } else if (name === 'denumerator') {
            metricColumns.denominatorColumnKey = columnKey;
        }
    });

    if (
        metricColumns.numeratorColumnKey &&
        metricColumns.numeratorColumnKey === metricColumns.denominatorColumnKey
    ) {
        return {
            numeratorColumnKey: '',
            denominatorColumnKey: '',
        };
    }

    return metricColumns;
};

export const getSpreadsheetWorkbookColumnCount = (
    workbook,
    minimumColumnCount = 0,
    maximumColumnCount = maximumSpreadsheetColumns,
) => {
    if (
        !workbook ||
        typeof workbook !== 'object' ||
        !Number.isInteger(minimumColumnCount) ||
        minimumColumnCount < 0 ||
        !Number.isInteger(maximumColumnCount) ||
        maximumColumnCount < 1
    ) {
        return 0;
    }

    const sheetId = workbook.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;
    if (!sheet) {
        return minimumColumnCount;
    }

    let columnCount = Math.min(minimumColumnCount, maximumColumnCount);
    const existingColumnCount = Number(sheet.columnCount);
    if (Number.isInteger(existingColumnCount) && existingColumnCount > 0) {
        columnCount = Math.max(
            columnCount,
            Math.min(existingColumnCount, maximumColumnCount),
        );
    }

    Object.values(sheet.cellData || {}).forEach((cells) => {
        Object.keys(cells || {}).forEach((columnIndex) => {
            const index = Number(columnIndex);
            if (
                Number.isInteger(index) &&
                index >= 0 &&
                index < maximumColumnCount
            ) {
                columnCount = Math.max(columnCount, index + 1);
            }
        });
    });

    (sheet.mergeData || []).forEach((merge) => {
        const endColumn = Number(merge?.endColumn);
        if (
            Number.isInteger(endColumn) &&
            endColumn >= 0 &&
            endColumn < maximumColumnCount
        ) {
            columnCount = Math.max(columnCount, endColumn + 1);
        }
    });

    return columnCount;
};

export const ensureSpreadsheetWorkbookColumnCount = (
    workbook,
    minimumColumnCount,
    maximumColumnCount = maximumSpreadsheetColumns,
) => {
    if (
        !workbook ||
        typeof workbook !== 'object' ||
        !Number.isInteger(minimumColumnCount) ||
        minimumColumnCount < 0 ||
        !Number.isInteger(maximumColumnCount) ||
        maximumColumnCount < 1 ||
        minimumColumnCount > maximumColumnCount
    ) {
        return workbook;
    }

    const sheetId = workbook.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;

    if (sheet) {
        sheet.columnCount = getSpreadsheetWorkbookColumnCount(
            workbook,
            minimumColumnCount,
            maximumColumnCount,
        );
    }

    return workbook;
};

export const ensureSpreadsheetWorkbookRowCount = (
    workbook,
    minimumRowCount,
) => {
    if (
        !workbook ||
        typeof workbook !== 'object' ||
        !Number.isInteger(minimumRowCount) ||
        minimumRowCount < 1 ||
        minimumRowCount > maximumSpreadsheetRows
    ) {
        return workbook;
    }

    const sheetId = workbook.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;

    if (sheet) {
        const lastCellRow = Object.keys(sheet.cellData || {}).reduce(
            (lastRow, rowIndex) => {
                const index = Number(rowIndex);

                return Number.isInteger(index) &&
                    index >= 0 &&
                    index < maximumSpreadsheetRows
                    ? Math.max(lastRow, index)
                    : lastRow;
            },
            -1,
        );
        const lastMergedRow = (sheet.mergeData || []).reduce(
            (lastRow, merge) => {
                const index = Number(merge?.endRow);

                return Number.isInteger(index) &&
                    index >= 0 &&
                    index < maximumSpreadsheetRows
                    ? Math.max(lastRow, index)
                    : lastRow;
            },
            -1,
        );

        sheet.rowCount = Math.min(
            maximumSpreadsheetRows,
            Math.max(
                Number(sheet.rowCount) || 0,
                minimumRowCount,
                lastCellRow + 1,
                lastMergedRow + 1,
            ),
        );
    }

    return workbook;
};

export const getContiguousSpreadsheetHeaderIndexes = (
    headerCells,
    startIndex,
    maximumColumnCount,
) => {
    if (
        !Number.isInteger(startIndex) ||
        startIndex < 0 ||
        !Number.isInteger(maximumColumnCount) ||
        maximumColumnCount < 1
    ) {
        return [];
    }

    const meaningfulIndexes = new Set(
        getMeaningfulSpreadsheetHeaderIndexes(headerCells),
    );
    const indexes = [];

    for (
        let index = startIndex;
        index < maximumColumnCount && meaningfulIndexes.has(index);
        index++
    ) {
        indexes.push(index);
    }

    return indexes;
};

export const normalizeSpreadsheetWorkbookColumns = (workbook, columnCount) => {
    if (
        !workbook ||
        typeof workbook !== 'object' ||
        !Number.isInteger(columnCount) ||
        columnCount < 0
    ) {
        return workbook;
    }

    const sheetId = workbook.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;
    if (!sheet) {
        return workbook;
    }

    if (sheet.cellData && typeof sheet.cellData === 'object') {
        Object.entries(sheet.cellData).forEach(([rowIndex, cells]) => {
            if (!cells || typeof cells !== 'object') {
                delete sheet.cellData[rowIndex];
                return;
            }

            Object.entries(cells).forEach(([columnIndex]) => {
                if (Number(columnIndex) >= columnCount) {
                    delete cells[columnIndex];
                }
            });

            if (!Object.keys(cells).length) {
                delete sheet.cellData[rowIndex];
            }
        });
    }

    sheet.columnCount = columnCount;
    if (Array.isArray(sheet.mergeData)) {
        sheet.mergeData = sheet.mergeData.flatMap((merge) => {
            if (!merge || typeof merge !== 'object') {
                return [];
            }

            const startColumn = Number(merge.startColumn) || 0;
            const endColumn = Number(merge.endColumn ?? startColumn);

            if (startColumn >= columnCount) {
                return [];
            }

            return [
                {
                    ...merge,
                    endColumn: Math.min(endColumn, columnCount - 1),
                },
            ];
        });
    }

    return workbook;
};

export const resolveSpreadsheetCellReference = (
    cellAddress,
    columns,
    headerRows = 2,
) => {
    const match =
        typeof cellAddress === 'string'
            ? cellAddress.trim().match(/^\$?([A-Za-z]{1,3})\$?([1-9]\d*)$/)
            : null;

    if (!match || !Array.isArray(columns)) {
        return null;
    }

    const columnLetters = match[1].toUpperCase();
    const rowNumber = Number(match[2]);
    let columnNumber = 0;

    for (const letter of columnLetters) {
        columnNumber =
            columnNumber * 26 + letter.charCodeAt(0) - 'A'.charCodeAt(0) + 1;
    }

    if (
        columnNumber > maximumSpreadsheetColumns ||
        columnNumber > columns.length ||
        rowNumber <= headerRows ||
        rowNumber > maximumSpreadsheetRows
    ) {
        return null;
    }

    const columnIndex = columnNumber - 1;
    const column = columns[columnIndex];

    if (!column?.key) {
        return null;
    }

    return {
        address: `${columnLetters}${rowNumber}`,
        columnIndex,
        columnKey: column.key,
        rowNumber,
    };
};

export const setSpreadsheetMetricRoleFromCell = (
    columns,
    role,
    cellAddress,
    headerRows = 2,
) => {
    if (!['numerator', 'denominator'].includes(role)) {
        throw new RangeError(`Unsupported spreadsheet metric role: ${role}`);
    }

    const resolvedCell = resolveSpreadsheetCellReference(
        cellAddress,
        columns,
        headerRows,
    );

    if (!resolvedCell) {
        return null;
    }

    const otherRole = role === 'numerator' ? 'denominator' : 'numerator';
    const selectedColumn = columns[resolvedCell.columnIndex];

    if (selectedColumn.role === otherRole) {
        return null;
    }

    if (selectedColumn.role === role) {
        normalizeSpreadsheetMetricColumnType(selectedColumn);

        return resolvedCell;
    }

    columns.forEach((column) => {
        if (column.role === role) {
            column.role = 'auxiliary';
        }
    });
    selectedColumn.role = role;
    normalizeSpreadsheetMetricColumnType(selectedColumn);

    return resolvedCell;
};

export const getSpreadsheetFormulaConfig = (columns) => {
    const numeratorColumns = columns
        .filter((column) => column.role === 'numerator')
        .map((column) => column.key);
    const denominatorColumns = columns
        .filter((column) => column.role === 'denominator')
        .map((column) => column.key);

    return {
        numerator_cols: numeratorColumns,
        denominator_cols: denominatorColumns,
        numerator_col: numeratorColumns[0] || 'num',
        denominator_col: denominatorColumns[0] || 'den',
    };
};
