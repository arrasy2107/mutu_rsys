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

const columnName = (index) => {
    let value = index + 1;
    let name = '';

    while (value > 0) {
        const remainder = (value - 1) % 26;
        name = String.fromCharCode(65 + remainder) + name;
        value = Math.floor((value - 1) / 26);
    }

    return name;
};

const makeColumn = (key, title, type = 'number', role = 'auxiliary') => ({
    key,
    title,
    type,
    role,
});

const makeRows = (items) =>
    items.map((item, index) => ({
        id: `legacy-row-${index + 1}`,
        key: String(item.key),
        label: item.label,
        values: item.values || {},
    }));

const recordsByPath = (records, path) => {
    const data = records?.[path] ?? records;

    return data && typeof data === 'object' && !Array.isArray(data) ? data : {};
};

const mapLegacyColumns = (schemaColumns = []) =>
    schemaColumns.map((column, index) => {
        const sourceType = column.type || 'text';

        return {
            key: column.key || `column_${index + 1}`,
            title:
                column.label ||
                column.title ||
                column.key ||
                `Kolom ${index + 1}`,
            type: [
                'checkbox',
                'date',
                'decimal',
                'number',
                'select',
                'text',
                'time',
            ].includes(sourceType)
                ? sourceType
                : 'text',
            role: column.is_numerator
                ? 'numerator'
                : column.is_denominator
                  ? 'denominator'
                  : column.role || 'auxiliary',
        };
    });

const addFormula = (row, key, formula) => ({
    ...row,
    __formulas: {
        ...row.__formulas,
        [key]: formula,
    },
});

export const createLegacySpreadsheetConfig = ({
    bulan,
    daysInMonth,
    formFields = [],
    indicator,
    layoutType,
    schema = {},
    tahun,
}) => {
    const columns = [];
    let rows = [];
    let persistedKeys = [];
    let spreadsheetRowType = 'fixed_rows';

    if (layoutType === 'daily_grid') {
        columns.push(
            makeColumn('day', 'Tanggal', 'text', 'label'),
            makeColumn('weekday', 'Hari', 'text', 'label'),
            ...formFields.map((field) =>
                makeColumn(
                    field.key,
                    field.name,
                    'number',
                    field.type === 'numerator' || field.type === 'denominator'
                        ? field.type
                        : 'auxiliary',
                ),
            ),
            makeColumn('capaian', 'Capaian (%)'),
        );
        rows = makeRows(
            Array.from({ length: daysInMonth }, (_, index) => {
                const day = index + 1;
                const weekday = new Date(
                    tahun,
                    bulan - 1,
                    day,
                ).toLocaleDateString('id-ID', { weekday: 'short' });

                return {
                    key: day,
                    label: String(day),
                    values: { day, weekday },
                };
            }),
        );
        persistedKeys = formFields.map((field) => field.key);
    } else if (layoutType === 'grouped_category') {
        const categories = schema.categories || [
            {
                key: 'obat_jadi',
                label: 'Obat Jadi',
                default_sample: 778,
                default_compliant: 670,
            },
            {
                key: 'obat_umum',
                label: 'Obat Umum',
                default_sample: 219,
                default_compliant: 198,
            },
            {
                key: 'obat_racikan',
                label: 'Obat Racikan',
                default_sample: 134,
                default_compliant: 130,
            },
        ];
        columns.push(
            makeColumn('label', 'Kategori', 'text', 'label'),
            makeColumn('sample', 'Jumlah Sampel', 'number', 'denominator'),
            makeColumn('compliant', 'Sesuai Standar', 'number', 'numerator'),
            makeColumn('total_minutes', 'Total Waktu (Menit)'),
            makeColumn('capaian', 'Capaian (%)'),
        );
        rows = makeRows(
            categories.map((category) => ({
                key: category.key,
                label: category.label,
                values: {
                    sample: category.default_sample ?? 0,
                    compliant: category.default_compliant ?? 0,
                    total_minutes: 0,
                },
            })),
        );
        persistedKeys = ['sample', 'compliant', 'total_minutes'];
    } else if (layoutType === 'entity_checklist') {
        const sourceColumns = schema.columns?.length
            ? mapLegacyColumns(schema.columns)
            : [
                  makeColumn(
                      'sample',
                      'Jumlah Sampel',
                      'number',
                      'denominator',
                  ),
                  makeColumn('pasien', 'Pasien'),
                  makeColumn('tgl_lahir', 'Tanggal Lahir'),
                  makeColumn('no_rm', 'No. RM'),
                  makeColumn('nama_obat', 'Nama Obat'),
                  makeColumn('dosis', 'Dosis'),
                  makeColumn(
                      'aturan_pakai',
                      'Aturan Pakai',
                      'number',
                      'numerator',
                  ),
              ];
        const entities = schema.entities || [];
        columns.push(
            makeColumn('label', 'Dokter / Entitas', 'text', 'label'),
            ...sourceColumns,
            makeColumn('capaian', 'Capaian (%)'),
        );
        rows = makeRows(
            entities.map((entity) => ({
                key: entity.id ?? entity.key ?? entity.name,
                label: entity.name ?? entity.label ?? String(entity.key),
            })),
        );
        persistedKeys = sourceColumns.map((column) => column.key);
    } else if (layoutType === 'location_matrix') {
        const locations = schema.locations || [
            { key: 'depo_igd', label: 'Depo Gawat Darurat (IGD)' },
            { key: 'depo_ranap', label: 'Depo Rawat Inap' },
            { key: 'depo_rajal', label: 'Depo Rawat Jalan' },
        ];
        columns.push(
            makeColumn('label', 'Depo Pelayanan', 'text', 'label'),
            makeColumn('fornas', 'Resep FORNAS', 'number', 'numerator'),
            makeColumn('generik', 'Resep Generik'),
            makeColumn('total_resep', 'Total Resep', 'number', 'denominator'),
            makeColumn('capaian', 'Capaian (%)'),
        );
        rows = makeRows(locations);
        persistedKeys = ['fornas', 'generik', 'total_resep'];
    } else if (layoutType === 'inspection_points') {
        const points = schema.points || [
            {
                key: 'lemari_ha_ibs',
                label: 'Lemari Khusus High Alert Farmasi IBS',
            },
            { key: 'troli_ok_1', label: 'Troli Emergensi Kamar Bedah 1' },
            { key: 'troli_ok_2', label: 'Troli Emergensi Kamar Bedah 2' },
            {
                key: 'troli_ok_3',
                label: 'Troli Emergensi Kamar Bedah 3 / Minor',
            },
            {
                key: 'lemari_narkotika',
                label: 'Lemari Narkotika & Psikotropika IBS (Double Lock)',
            },
            { key: 'kotak_lasa', label: 'Penyimpanan Obat LASA / NORUM IBS' },
            {
                key: 'kulkas_farmasi',
                label: 'Kulkas Obat IBS (Suhu 2°C - 8°C)',
            },
        ];
        const criteria = schema.criteria || [
            { key: 'label_ha', label: 'Stiker High Alert Merah' },
            { key: 'pemisahan_lasa', label: 'Pemisahan Khusus LASA' },
            { key: 'double_check', label: 'Kartu Stok & Double Check' },
            { key: 'suhu_terjaga', label: 'Suhu Sesuai & Dicatat' },
        ];
        columns.push(
            makeColumn('label', 'Tempat / Titik', 'text', 'label'),
            makeColumn('checked', 'Diperiksa', 'checkbox', 'denominator'),
            ...criteria.map((criterion) =>
                makeColumn(criterion.key, criterion.label, 'checkbox'),
            ),
            makeColumn('compliant', 'Sesuai', 'number', 'numerator'),
            makeColumn('catatan', 'Catatan', 'text'),
        );
        rows = makeRows(points);
        persistedKeys = [
            'checked',
            ...criteria.map((criterion) => criterion.key),
            'compliant',
            'catatan',
        ];
    } else if (layoutType === 'monthly_summary') {
        columns.push(
            makeColumn('label', 'Bulan', 'text', 'label'),
            makeColumn('num', 'Numerator', 'number', 'numerator'),
            makeColumn('den', 'Denominator', 'number', 'denominator'),
            makeColumn('capaian', 'Capaian (%)'),
            makeColumn('notes', 'Catatan', 'text'),
        );
        rows = makeRows([{ key: bulan, label: monthNames[bulan - 1] }]);
        persistedKeys = ['num', 'den', 'notes'];
    } else if (['entity_rows', 'patient_register'].includes(layoutType)) {
        columns.push(
            ...mapLegacyColumns(
                schema.columns?.length
                    ? schema.columns
                    : [
                          { key: 'tanggal', title: 'Tanggal', type: 'date' },
                          { key: 'norm', title: 'No. Rekam Medis' },
                          { key: 'nama', title: 'Nama Pasien' },
                          {
                              key: 'num',
                              title: 'Kepatuhan Standar Mutu',
                              type: 'checkbox',
                              role: 'numerator',
                          },
                          { key: 'keterangan', title: 'Keterangan / Observer' },
                      ],
            ),
        );
        rows = makeRows(schema.rows || []);
        spreadsheetRowType =
            layoutType === 'entity_rows' && rows.length > 0
                ? 'fixed_rows'
                : 'dynamic_rows';
        persistedKeys = columns
            .filter((column) => column.role !== 'label')
            .map((column) => column.key);
    }

    const headerRows = Math.max(2, Number(schema.header_rows) || 2);
    const labelIndex = columns.findIndex((column) => column.role === 'label');
    const formulasByRow = {};

    rows.forEach((row, rowIndex) => {
        const sheetRow = headerRows + rowIndex + 1;
        const cell = (key) =>
            `${columnName(columns.findIndex((column) => column.key === key))}${sheetRow}`;
        let formula = null;
        let formulaKey = 'capaian';

        if (layoutType === 'daily_grid') {
            const numerator = formFields.find(
                (field) => field.type === 'numerator',
            );
            const denominator = formFields.find(
                (field) => field.type === 'denominator',
            );
            if (numerator && denominator) {
                formula = `=IF(${cell(denominator.key)}=0,0,${cell(numerator.key)}/${cell(denominator.key)}*${Number(indicator?.multiplier) || 100})`;
            }
        } else if (layoutType === 'grouped_category') {
            formula = `=IF(${cell('sample')}=0,0,${cell('compliant')}/${cell('sample')}*100)`;
        } else if (layoutType === 'entity_checklist') {
            const numerator = columns.find(
                (column) => column.role === 'numerator',
            );
            const denominator = columns.find(
                (column) => column.role === 'denominator',
            );
            if (numerator && denominator) {
                formula = `=IF(${cell(denominator.key)}=0,0,${cell(numerator.key)}/${cell(denominator.key)}*100)`;
            }
        } else if (layoutType === 'location_matrix') {
            formula = `=IF(${cell('total_resep')}=0,0,${cell('fornas')}/${cell('total_resep')}*100)`;
        } else if (layoutType === 'inspection_points') {
            formulaKey = 'compliant';
            const checkKeys = [
                'checked',
                ...columns
                    .filter(
                        (column) =>
                            column.type === 'checkbox' &&
                            column.key !== 'checked',
                    )
                    .map((column) => column.key),
            ];
            formula = `=IF(AND(${checkKeys.map(cell).join(',')}),1,0)`;
        } else if (layoutType === 'monthly_summary') {
            formula = `=IF(${cell('den')}=0,0,${cell('num')}/${cell('den')}*${Number(indicator?.multiplier) || 100})`;
        }

        if (formula && labelIndex >= 0) {
            formulasByRow[row.key] = { [formulaKey]: formula };
        }
    });

    return {
        schema: {
            ...schema,
            renderer: 'spreadsheet',
            engine: 'univer',
            row_type: spreadsheetRowType,
            header_rows: headerRows,
            columns,
            rows,
            legacy_layout_type: layoutType,
            legacy_persisted_keys: persistedKeys,
            workbook_template: schema.workbook_template || null,
        },
        formulasByRow,
    };
};

export const legacyRecordsToSpreadsheetRows = ({
    bulan,
    config,
    layoutType,
    records,
}) => {
    const schema = config.schema;
    const rows = {};

    if (layoutType === 'daily_grid') {
        schema.rows.forEach((row) => {
            rows[row.key] = { ...row.values, ...(records?.[row.key] || {}) };
        });
    } else if (layoutType === 'grouped_category') {
        const categories = recordsByPath(records, 'categories');
        schema.rows.forEach((row) => {
            rows[row.key] = { ...row.values, ...(categories[row.key] || {}) };
        });
    } else if (layoutType === 'entity_checklist') {
        const entities = recordsByPath(records, 'entities');
        const defaultValues = Object.fromEntries(
            schema.legacy_persisted_keys.map((key) => [key, 0]),
        );
        schema.rows.forEach((row) => {
            rows[row.key] = { ...defaultValues, ...(entities[row.key] || {}) };
        });
    } else if (layoutType === 'location_matrix') {
        const locations = recordsByPath(records, 'locations');
        schema.rows.forEach((row) => {
            rows[row.key] = {
                fornas: 0,
                generik: 0,
                total_resep: 0,
                ...(locations[row.key] || {}),
            };
        });
    } else if (layoutType === 'inspection_points') {
        const points = recordsByPath(records, 'points');
        const criteriaKeys = schema.columns
            .filter(
                (column) =>
                    column.type === 'checkbox' && column.key !== 'checked',
            )
            .map((column) => column.key);
        schema.rows.forEach((row) => {
            const point = points[row.key] || {};
            rows[row.key] = {
                checked: point.checked ?? true,
                ...Object.fromEntries(
                    criteriaKeys.map((key) => [
                        key,
                        point.criteria?.[key] ?? true,
                    ]),
                ),
                compliant: point.compliant ?? 0,
                catatan: point.catatan || '',
                ...(point.__formulas ? { __formulas: point.__formulas } : {}),
            };
        });
    } else if (layoutType === 'monthly_summary') {
        const activeMonth = records?.[bulan] || records || {};
        const monthData =
            activeMonth.num !== undefined || activeMonth.numerator !== undefined
                ? activeMonth
                : {};
        const row = {
            num: monthData.num ?? monthData.numerator ?? 0,
            den: monthData.den ?? monthData.denominator ?? 0,
            notes: monthData.notes || '',
        };
        rows[bulan] = row;
    } else if (['entity_rows', 'patient_register'].includes(layoutType)) {
        if (schema.row_type === 'fixed_rows') {
            schema.rows.forEach((row) => {
                rows[row.key] = { ...(records?.[row.key] || {}) };
            });
        } else if (Array.isArray(records)) {
            return records;
        } else if (records && typeof records === 'object') {
            return Object.entries(records)
                .filter(
                    ([key, record]) =>
                        !key.startsWith('__') &&
                        record &&
                        typeof record === 'object',
                )
                .map(([, record]) => record);
        }
    }

    schema.rows.forEach((row) => {
        rows[row.key] ||= {};
        const formulas = config.formulasByRow[row.key];
        if (formulas && !Object.keys(rows[row.key].__formulas || {}).length) {
            rows[row.key] = addFormula(
                rows[row.key],
                ...Object.entries(formulas)[0],
            );
        }
    });

    return rows;
};

export const spreadsheetRowsToLegacyRecords = ({
    bulan,
    config,
    layoutType,
    previousRecords,
    spreadsheetRows,
}) => {
    const schema = config.schema;
    const getPersistedRow = (rowKey) => {
        const source = spreadsheetRows?.[rowKey] || {};
        const row = {};

        schema.legacy_persisted_keys.forEach((key) => {
            if (source[key] !== undefined) {
                row[key] = source[key];
            }
        });
        Object.entries(source)
            .filter(
                ([key]) =>
                    /^col_\d+$/.test(key) &&
                    !schema.legacy_persisted_keys.includes(key),
            )
            .forEach(([key, value]) => {
                row[key] = value;
            });

        const formulas = Object.fromEntries(
            Object.entries(source.__formulas || {}).filter(([key]) =>
                schema.legacy_persisted_keys.includes(key) ||
                /^col_\d+$/.test(key),
            ),
        );
        if (Object.keys(formulas).length) {
            row.__formulas = formulas;
        }

        return row;
    };

    if (layoutType === 'daily_grid') {
        return Object.fromEntries(
            Object.keys(spreadsheetRows || {})
                .filter((rowKey) =>
                    schema.rows.some(
                        (row) => String(row.key) === String(rowKey),
                    ),
                )
                .map((rowKey) => [rowKey, getPersistedRow(rowKey)]),
        );
    }

    if (layoutType === 'grouped_category') {
        const existing = recordsByPath(previousRecords, 'categories');
        const categories = Object.fromEntries(
            schema.rows.map((row) => [
                row.key,
                { ...(existing[row.key] || {}), ...getPersistedRow(row.key) },
            ]),
        );

        return {
            categories,
            sampling_logs: previousRecords?.sampling_logs || [],
        };
    }

    if (layoutType === 'entity_checklist') {
        const existing = recordsByPath(previousRecords, 'entities');

        return {
            entities: Object.fromEntries(
                schema.rows.map((row) => [
                    row.key,
                    {
                        ...(existing[row.key] || {}),
                        ...getPersistedRow(row.key),
                    },
                ]),
            ),
        };
    }

    if (layoutType === 'location_matrix') {
        const existing = recordsByPath(previousRecords, 'locations');

        return {
            locations: Object.fromEntries(
                schema.rows.map((row) => [
                    row.key,
                    {
                        ...(existing[row.key] || {}),
                        ...getPersistedRow(row.key),
                    },
                ]),
            ),
        };
    }

    if (layoutType === 'inspection_points') {
        const existing = recordsByPath(previousRecords, 'points');
        const criteriaKeys = schema.columns
            .filter(
                (column) =>
                    column.type === 'checkbox' &&
                    !['checked'].includes(column.key),
            )
            .map((column) => column.key);

        return {
            points: Object.fromEntries(
                schema.rows.map((row) => {
                    const values = getPersistedRow(row.key);
                    const criteria = Object.fromEntries(
                        criteriaKeys.map((key) => [key, values[key] ?? false]),
                    );
                    const point = {
                        ...(existing[row.key] || {}),
                        checked: values.checked ?? false,
                        compliant: values.compliant ?? 0,
                        criteria,
                        catatan: values.catatan || '',
                    };
                    const formulas = values.__formulas || {};
                    if (Object.keys(formulas).length) {
                        point.__formulas = formulas;
                    }

                    return [row.key, point];
                }),
            ),
        };
    }

    if (layoutType === 'monthly_summary') {
        const existing =
            previousRecords && typeof previousRecords === 'object'
                ? { ...previousRecords }
                : {};
        const current = getPersistedRow(bulan);
        existing[bulan] = {
            num: current.num ?? 0,
            den: current.den ?? 0,
            notes: current.notes || '',
            ...(current.__formulas ? { __formulas: current.__formulas } : {}),
        };

        return existing;
    }

    if (['entity_rows', 'patient_register'].includes(layoutType)) {
        return config.schema.row_type === 'fixed_rows'
            ? Object.fromEntries(
                  schema.rows.map((row) => [row.key, getPersistedRow(row.key)]),
              )
            : spreadsheetRows;
    }

    return spreadsheetRows;
};
