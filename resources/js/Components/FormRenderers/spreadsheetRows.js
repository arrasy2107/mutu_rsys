import { toRaw } from 'vue';
import {
    getSpreadsheetSummaryRowIndexes,
    isSpreadsheetAggregateFormula,
} from './spreadsheetWorkbookMapper.js';

export const cloneSpreadsheetRows = (rows) =>
    rows.map((row) => structuredClone(toRaw(row)));

export const getSpreadsheetFormulaPatterns = (
    workbook,
    headerRows,
    columns,
) => {
    const sheetId = workbook?.sheetOrder?.[0];
    const sheet = sheetId ? workbook.sheets?.[sheetId] : null;
    const patterns = {};
    const summaryRows = new Set(
        getSpreadsheetSummaryRowIndexes(workbook, headerRows),
    );

    if (
        !sheet ||
        !Number.isInteger(headerRows) ||
        headerRows < 1 ||
        !Array.isArray(columns)
    ) {
        return patterns;
    }

    Object.entries(sheet.cellData || {})
        .sort(([firstRow], [secondRow]) => Number(firstRow) - Number(secondRow))
        .forEach(([rowIndex, cells]) => {
            const sourceRow = Number(rowIndex);
            if (
                !Number.isInteger(sourceRow) ||
                sourceRow < headerRows ||
                summaryRows.has(sourceRow)
            ) {
                return;
            }

            Object.entries(cells || {}).forEach(([columnIndex, cell]) => {
                const column = columns[Number(columnIndex)];
                if (
                    !column?.key ||
                    !cell?.f ||
                    isSpreadsheetAggregateFormula(cell.f) ||
                    patterns[column.key]
                ) {
                    return;
                }

                const formula = String(cell.f);
                patterns[column.key] = {
                    formula: formula.startsWith('=') ? formula : `=${formula}`,
                    sourceRow: sourceRow + 1,
                };
            });
        });

    return patterns;
};

export const translateSpreadsheetFormula = (formula, rowOffset) => {
    if (typeof formula !== 'string' || !Number.isInteger(rowOffset)) {
        return formula;
    }

    return formula.replace(/("(?:[^"]|"")*")|([^"]+)/g, (segment, quoted) => {
        if (quoted) {
            return segment;
        }

        return segment.replace(
            /(^|[^A-Za-z0-9_.])(\$?)([A-Z]{1,3})(\$?)(\d+)(?![A-Za-z0-9_])(?!\s*\()/gi,
            (reference, prefix, absoluteColumn, column, absoluteRow, row) => {
                if (absoluteRow) {
                    return reference;
                }

                const translatedRow = Number(row) + rowOffset;
                return translatedRow > 0
                    ? `${prefix}${absoluteColumn}${column}${absoluteRow}${translatedRow}`
                    : reference;
            },
        );
    });
};
