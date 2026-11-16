const aggregations = new Set(['sum', 'count', 'counta']);
const a1RangePattern = /^\$?[A-Z]{1,3}\$?\d+(?::\$?[A-Z]{1,3}\$?\d+)?$/i;

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

const isNumeric = (value) =>
    typeof value === 'number' ||
    (typeof value === 'string' &&
        value.trim() !== '' &&
        !Number.isNaN(Number(value)));

export const supportsSpreadsheetMetricMapping = (schema, layoutType) => {
    if (schema?.renderer) {
        return schema.renderer === 'spreadsheet';
    }

    return ['dynamic_rows', 'fixed_rows'].includes(
        schema?.row_type || layoutType,
    );
};

export const isCompleteSpreadsheetMetricMapping = (mapping) =>
    ['numerator', 'denominator'].every((metric) => {
        const source = mapping?.[metric];

        return (
            typeof source?.range === 'string' &&
            a1RangePattern.test(source.range.trim()) &&
            aggregations.has(source.aggregation)
        );
    });

export const parseSpreadsheetMetricRange = (range) => {
    if (typeof range !== 'string') {
        return null;
    }

    const match = range
        .trim()
        .match(/^\$?([A-Z]{1,3})\$?(\d+)(?::\$?([A-Z]{1,3})\$?(\d+))?$/i);
    if (!match) {
        return null;
    }

    const columnIndex = (letters) =>
        [...letters.toUpperCase()].reduce(
            (index, letter) => index * 26 + letter.charCodeAt(0) - 64,
            0,
        );
    const startColumn = columnIndex(match[1]);
    const startRow = Number(match[2]);
    const endColumn = match[3] ? columnIndex(match[3]) : startColumn;
    const endRow = match[4] ? Number(match[4]) : startRow;

    if (startRow < 1 || endRow < startRow || endColumn < startColumn) {
        return null;
    }

    return { startColumn, startRow, endColumn, endRow };
};

export const resolveSpreadsheetMetricSelection = ({
    selectedRange,
    workbookSnapshot,
    headerRowCount,
    summaryRowIndexes = [],
}) => {
    const bounds = parseSpreadsheetMetricRange(selectedRange);
    const sheetId = workbookSnapshot?.sheetOrder?.[0];
    const sheet = sheetId ? workbookSnapshot?.sheets?.[sheetId] : null;

    if (
        !bounds ||
        !sheet ||
        bounds.startRow <= headerRowCount ||
        bounds.endRow > sheet.rowCount ||
        bounds.endColumn > sheet.columnCount ||
        (bounds.endRow - bounds.startRow + 1) *
            (bounds.endColumn - bounds.startColumn + 1) >
            10000
    ) {
        return null;
    }

    const includesSummaryRow = summaryRowIndexes.some(
        (rowIndex) =>
            rowIndex >= bounds.startRow - 1 && rowIndex <= bounds.endRow - 1,
    );

    if (!includesSummaryRow) {
        return { range: selectedRange, aggregation: null };
    }

    if (
        bounds.startRow !== bounds.endRow ||
        bounds.startColumn !== bounds.endColumn
    ) {
        return null;
    }

    const formula =
        sheet.cellData?.[String(bounds.startRow - 1)]?.[
            String(bounds.startColumn - 1)
        ]?.f;
    const formulaMatch =
        typeof formula === 'string'
            ? formula
                  .trim()
                  .match(
                      /^=?\s*(COUNTA|COUNT|SUM)\s*\(\s*(\$?[A-Z]{1,3}\$?\d+(?::\$?[A-Z]{1,3}\$?\d+)?)\s*\)$/i,
                  )
            : null;

    if (!formulaMatch) {
        return null;
    }

    const sourceRange = formulaMatch[2];
    const sourceBounds = parseSpreadsheetMetricRange(sourceRange);
    if (
        !sourceBounds ||
        sourceBounds.startRow <= headerRowCount ||
        sourceBounds.endRow > sheet.rowCount ||
        sourceBounds.endColumn > sheet.columnCount ||
        (sourceBounds.endRow - sourceBounds.startRow + 1) *
            (sourceBounds.endColumn - sourceBounds.startColumn + 1) >
            10000 ||
        summaryRowIndexes.some(
            (rowIndex) =>
                rowIndex >= sourceBounds.startRow - 1 &&
                rowIndex <= sourceBounds.endRow - 1,
        )
    ) {
        return null;
    }

    return {
        range: sourceRange,
        aggregation: formulaMatch[1].toLowerCase(),
    };
};

export const calculateSpreadsheetMetricSource = (values, aggregation) => {
    if (!aggregations.has(aggregation)) {
        throw new RangeError(
            `Unsupported spreadsheet aggregation: ${aggregation}`,
        );
    }

    const cells = (Array.isArray(values) ? values : []).flat(Infinity);

    if (aggregation === 'counta') {
        return cells.filter(
            (value) => value !== null && value !== undefined && value !== '',
        ).length;
    }

    if (aggregation === 'count') {
        return cells.filter(isNumeric).length;
    }

    return cells.reduce((sum, value) => {
        if (isNumeric(value)) {
            return sum + Number(value);
        }

        return sum + (isChecked(value) ? 1 : 0);
    }, 0);
};
