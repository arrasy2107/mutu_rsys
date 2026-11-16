export const getFormatForIndicator = (indicator, format) => {
    if (!indicator?.id) {
        return null;
    }

    if (format && Number(format.indicator_id) === Number(indicator.id)) {
        return format;
    }

    const schema = indicator.form_schema;
    if (
        !schema?.format_id ||
        !Array.isArray(schema.columns) ||
        schema.columns.length === 0
    ) {
        return null;
    }

    return {
        id: null,
        indicator_id: indicator.id,
        nama_format:
            schema.nama_format ||
            `Format: ${indicator.kode || indicator.judul}`,
        table_title: schema.table_title || '',
        row_type: schema.row_type || indicator.form_layout_type,
        petunjuk_pengisian: schema.petunjuk_pengisian || '',
        schema,
    };
};

export const getIndicatorById = (indicatorId, indicators, fallback = null) => {
    if (
        indicatorId === null ||
        indicatorId === undefined ||
        indicatorId === '' ||
        !Number.isFinite(Number(indicatorId))
    ) {
        return null;
    }

    const matchingIndicator = (
        Array.isArray(indicators) ? indicators : []
    ).find((indicator) => Number(indicator?.id) === Number(indicatorId));

    if (matchingIndicator) {
        return matchingIndicator;
    }

    return Number(fallback?.id) === Number(indicatorId) ? fallback : null;
};
