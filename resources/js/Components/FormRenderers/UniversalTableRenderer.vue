<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: [Object, Array],
    indicator: Object,
    schema: Object,
    formFields: Array,
    daysInMonth: Number,
    tahun: Number,
    bulan: Number,
    permissions: Object,
    readOnly: {
        type: Boolean,
        default: false,
    },
    isPeriodApproved: Boolean,
});

const emit = defineEmits(['update:modelValue', 'update:summary', 'change']);

// Normalized schema
const currentSchema = computed(() => {
    return props.schema || props.indicator?.form_schema || {};
});

const rowType = computed(() => {
    return currentSchema.value?.row_type || props.indicator?.form_layout_type || 'fixed_rows';
});

const tableTitle = computed(() => {
    return currentSchema.value?.table_title || `Tabel Pencatatan ${props.indicator?.judul || 'Indikator'}`;
});

const columns = computed(() => {
    if (currentSchema.value?.columns?.length > 0) {
        return currentSchema.value.columns;
    }
    if (rowType.value === 'dynamic_rows') {
        return [
            { id: 'c1', key: 'tanggal', title: 'Tanggal', type: 'text', role: 'label' },
            { id: 'c2', key: 'norm', title: 'No. Rekam Medis (RM)', type: 'text', role: 'label' },
            { id: 'c3', key: 'nama', title: 'Nama Pasien', type: 'text', role: 'label' },
            { id: 'c4', key: 'num', title: 'Kepatuhan Standar Mutu', type: 'checkbox', role: 'numerator' },
            { id: 'c5', key: 'keterangan', title: 'Keterangan / Observer', type: 'text', role: 'label' },
        ];
    }
    return [
        { id: 'c1', key: 'col_label', title: 'Uraian', type: 'label', role: 'label' },
        { id: 'c2', key: 'num', title: 'Numerator (N)', type: 'number', role: 'numerator' },
        { id: 'c3', key: 'den', title: 'Denominator (D)', type: 'number', role: 'denominator' },
    ];
});

const rows = computed(() => {
    return currentSchema.value?.rows || [];
});

const petunjuk = computed(() => {
    return currentSchema.value?.petunjuk_pengisian || props.indicator?.definisi_operasional || '';
});

// Header grouping for 2-tier merged headers
const hasGroups = computed(() => {
    return columns.value.some(c => c.group && String(c.group).trim() !== '');
});

const headerStructure = computed(() => {
    if (!hasGroups.value) {
        return {
            tier1: columns.value.map(c => ({
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
            currentGroupCols.forEach(col => tier2.push(col));
            currentGroup = null;
            currentGroupCols = [];
        }
    };

    columns.value.forEach(col => {
        const grp = col.group && String(col.group).trim() !== '' ? String(col.group).trim() : null;
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

// Formula configuration
const formulaConfig = computed(() => {
    return currentSchema.value?.formula_config || {};
});

// Composite criteria keys (e.g. ['s', 'b', 'a', 'r'] for SBAR or ['double_1', 'double_2'])
const compositeKeys = computed(() => {
    return formulaConfig.value?.composite_criteria || [];
});

// Multiplier calculation (e.g., 25 for index scale 1-4, or 100 for standard percentage)
const multiplier = computed(() => {
    if (props.indicator?.multiplier) {
        return parseFloat(props.indicator.multiplier);
    }
    const f = props.indicator?.formula || '';
    if (f.includes('25')) return 25;
    if (f.includes('1000')) return 1000;
    return 100;
});

// Target comparison
const targetCapaian = computed(() => parseFloat(props.indicator?.target_capaian || 100));
const targetOperator = computed(() => props.indicator?.target_operator || '>=');

// Can edit state
const isEditable = computed(() => {
    return (
        !props.readOnly &&
        props.permissions?.canEditDaily &&
        !props.isPeriodApproved
    );
});

// Local reactive records store
const localRecords = ref({});

// Initialize records
const initLocalRecords = () => {
    const val = props.modelValue;

    if (rowType.value === 'dynamic_rows') {
        let rawList = [];
        if (Array.isArray(val) && val.length > 0) {
            rawList = JSON.parse(JSON.stringify(val));
        } else if (val && typeof val === 'object' && Array.isArray(val.items)) {
            rawList = JSON.parse(JSON.stringify(val.items));
        } else if (val && typeof val === 'object' && Object.keys(val).length > 0 && !val.num) {
            const arr = Object.values(val);
            if (arr.length > 0 && typeof arr[0] === 'object') {
                rawList = arr;
            }
        }

        if (rawList.length > 0) {
            localRecords.value = rawList.map(item => {
                const row = { _id: Date.now() + Math.random().toString(36).substr(2, 5), ...item };
                columns.value.forEach(col => {
                    if (col.type === 'checkbox') {
                        const raw = row[col.key];
                        row[col.key] = (raw === true || raw === 1 || raw === '1' || raw === 'v' || raw === 'V' || raw === 'ya' || raw === 'Ya' || raw === 'Sesuai' || raw === '√');
                    }
                });
                return row;
            });
        } else {
            localRecords.value = [createNewDynamicRow()];
        }
    } else if (rowType.value === 'daily_grid') {
        const map = {};
        const days = props.daysInMonth || 31;
        for (let d = 1; d <= days; d++) {
            const existing = val?.[d] || {};
            map[d] = {};
            columns.value.forEach(col => {
                map[d][col.key] = existing[col.key] !== undefined ? existing[col.key] : (col.type === 'number' ? 0 : (col.type === 'checkbox' ? false : ''));
            });
        }
        localRecords.value = map;
    } else {
        // fixed_rows
        const map = {};
        const rowList = rows.value.length > 0 ? rows.value : [{ key: 'item_1', label: 'Item 1' }];
        rowList.forEach(r => {
            const existing = val?.[r.key] || {};
            map[r.key] = {};
            columns.value.forEach(col => {
                map[r.key][col.key] = existing[col.key] !== undefined ? existing[col.key] : (col.type === 'number' ? 0 : (col.type === 'checkbox' ? false : ''));
            });
        });
        localRecords.value = map;
    }

    calculateRollup();
};

const createNewDynamicRow = () => {
    const rowObj = { _id: Date.now() + Math.random().toString(36).substr(2, 5) };
    const defaultDate = `${props.tahun || 2026}-${String(props.bulan || 1).padStart(2, '0')}-01`;
    columns.value.forEach(col => {
        if (col.type === 'date') {
            rowObj[col.key] = defaultDate;
        } else if (col.type === 'number' || col.type === 'decimal') {
            rowObj[col.key] = col.role === 'denominator' ? 1 : 0;
        } else if (col.type === 'checkbox') {
            rowObj[col.key] = false;
        } else {
            rowObj[col.key] = '';
        }
    });
    return rowObj;
};

const addDynamicRow = () => {
    if (!Array.isArray(localRecords.value)) {
        localRecords.value = [];
    }
    localRecords.value.push(createNewDynamicRow());
    onCellChange();
};

const removeDynamicRow = (index) => {
    if (localRecords.value.length <= 1) {
        localRecords.value = [createNewDynamicRow()];
    } else {
        localRecords.value.splice(index, 1);
    }
    onCellChange();
};

// Calculations & Rollup
const numCols = computed(() => columns.value.filter(c => c.role === 'numerator').map(c => c.key));
const denCols = computed(() => columns.value.filter(c => c.role === 'denominator').map(c => c.key));

// Check if a dynamic row is compliant under composite criteria
const isRowCompliant = (row) => {
    if (compositeKeys.value.length === 0) return null;
    return compositeKeys.value.every(k => row[k] === true || row[k] === 1 || row[k] === '1' || row[k] === '√' || row[k] === 'V' || row[k] === 'v' || row[k] === 'ya' || row[k] === 'Ya' || row[k] === 'Sesuai');
};

// Column totals
const columnTotals = computed(() => {
    const totals = {};
    columns.value.forEach(col => {
        totals[col.key] = 0;
    });

    if (rowType.value === 'dynamic_rows' && Array.isArray(localRecords.value)) {
        localRecords.value.forEach(row => {
            columns.value.forEach(col => {
                if (col.type === 'number' || col.type === 'decimal') {
                    totals[col.key] += parseFloat(row[col.key]) || 0;
                } else if (col.type === 'checkbox') {
                    if (row[col.key] === true || row[col.key] === 1 || row[col.key] === '1' || row[col.key] === '√' || row[col.key] === 'v' || row[col.key] === 'V' || row[col.key] === 'ya' || row[col.key] === 'Ya' || row[col.key] === 'Sesuai') {
                        totals[col.key]++;
                    }
                }
            });
        });
    } else if (typeof localRecords.value === 'object') {
        Object.values(localRecords.value).forEach(row => {
            columns.value.forEach(col => {
                if (col.type === 'number' || col.type === 'decimal') {
                    totals[col.key] += parseFloat(row?.[col.key]) || 0;
                } else if (col.type === 'checkbox') {
                    if (row?.[col.key] === true || row?.[col.key] === 1 || row?.[col.key] === '1' || row?.[col.key] === '√' || row?.[col.key] === 'v' || row?.[col.key] === 'V' || row?.[col.key] === 'ya' || row?.[col.key] === 'Ya' || row?.[col.key] === 'Sesuai') {
                        totals[col.key]++;
                    }
                }
            });
        });
    }

    return totals;
});

// Summary Rollup Stats
const rollupStats = computed(() => {
    let totalN = 0;
    let totalD = 0;
    let validRowCount = 0;

    if (rowType.value === 'dynamic_rows' && Array.isArray(localRecords.value)) {
        localRecords.value.forEach(row => {
            // Check if row has data (not empty)
            const hasData = Object.entries(row).some(([k, v]) => k !== '_id' && v !== '' && v !== null && v !== false && v !== 0);
            if (hasData) {
                validRowCount++;

                // If composite criteria exists (e.g. SBAR)
                if (compositeKeys.value.length > 0) {
                    if (isRowCompliant(row)) {
                        totalN++;
                    }
                }
            }
        });

        // Denominator in dynamic rows:
        // If explicit denominator columns exist, sum them. Otherwise, Denominator is the number of valid patient rows!
        if (denCols.value.length > 0) {
            denCols.value.forEach(key => {
                totalD += columnTotals.value[key] || 0;
            });
        } else {
            totalD = validRowCount;
        }

        // Numerator in dynamic rows:
        if (compositeKeys.value.length === 0) {
            numCols.value.forEach(key => {
                totalN += columnTotals.value[key] || 0;
            });
        }
    } else {
        // Daily grid or fixed rows
        numCols.value.forEach(key => {
            totalN += columnTotals.value[key] || 0;
        });

        denCols.value.forEach(key => {
            totalD += columnTotals.value[key] || 0;
        });
    }

    const capaian = totalD > 0 ? parseFloat(((totalN / totalD) * multiplier.value).toFixed(2)) : 0;

    let isMet = null;
    if (totalD > 0) {
        isMet = targetOperator.value === '<=' ? capaian <= targetCapaian.value : capaian >= targetCapaian.value;
    }

    return {
        numerator: totalN,
        denominator: totalD,
        capaian,
        validRowCount,
        isMet,
    };
});

const calculateRollup = () => {
    emit('update:summary', {
        numerator: rollupStats.value.numerator,
        denominator: rollupStats.value.denominator,
        capaian: rollupStats.value.capaian,
    });
};

const onCellChange = () => {
    calculateRollup();
    emit('update:modelValue', localRecords.value);
    emit('change');
};

watch(() => props.modelValue, () => {
    initLocalRecords();
}, { deep: true });

watch(() => [props.schema, props.indicator?.id], () => {
    initLocalRecords();
});

onMounted(() => {
    initLocalRecords();
});
</script>

<template>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-4 sm:p-5 space-y-4">

        <!-- Table Header & Petunjuk -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <h3 class="text-base font-bold text-slate-800 tracking-tight">{{ tableTitle }}</h3>
                    <span v-if="rowType === 'dynamic_rows'" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Log Register Pasien / Transaksi
                    </span>
                    <span v-else-if="rowType === 'fixed_rows'" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        Matriks Kategori / Entitas
                    </span>
                </div>
                <p v-if="petunjuk" class="text-xs text-slate-500 mt-1 max-w-3xl leading-relaxed flex items-center gap-1.5">
                    <NotionIcon name="lightbulb" size="14" class="text-amber-500 shrink-0" />
                    <span><strong class="font-semibold text-slate-700">Petunjuk:</strong> {{ petunjuk }}</span>
                </p>
            </div>

            <!-- Dynamic Row Action -->
            <div v-if="rowType === 'dynamic_rows' && isEditable" class="shrink-0 flex items-center gap-2">
                <button
                    @click="addDynamicRow"
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs hover:shadow-md transition-all flex items-center gap-1.5 active:scale-95"
                >
                    <NotionIcon name="plus" size="13" />
                    <span>Tambah Pasien / Transaksi</span>
                </button>
            </div>
        </div>

        <!-- REALTIME ROLLUP SUMMARY STRIP (FOR DYNAMIC ROWS) -->
        <div v-if="rowType === 'dynamic_rows'" class="bg-slate-50 rounded-xl p-3 border border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-500">Total Pasien Terdata:</span>
                    <span class="font-bold text-slate-800 font-mono">{{ rollupStats.validRowCount }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-emerald-700 font-semibold">Numerator [N]:</span>
                    <span class="font-bold text-emerald-800 font-mono bg-emerald-100/70 px-2 py-0.5 rounded-md">
                        {{ rollupStats.numerator }}
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-sky-700 font-semibold">Denominator [D]:</span>
                    <span class="font-bold text-sky-800 font-mono bg-sky-100/70 px-2 py-0.5 rounded-md">
                        {{ rollupStats.denominator }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs font-mono">
                <span class="text-slate-500 font-sans">Capaian:</span>
                <span class="text-base font-black" :class="rollupStats.isMet === true ? 'text-emerald-700' : (rollupStats.isMet === false ? 'text-rose-600' : 'text-slate-700')">
                    {{ rollupStats.capaian }}%
                </span>
                <span
                    v-if="rollupStats.isMet !== null"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="rollupStats.isMet ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                >
                    {{ rollupStats.isMet ? 'Memenuhi Target' : 'Belum Capai Target' }}
                </span>
            </div>
        </div>

        <!-- TABLE CONTENT -->
        <div class="overflow-x-auto rounded-xl border border-slate-200/90 shadow-2xs">
            <table class="w-full text-left text-xs">
                <!-- Table Headers -->
                <thead class="bg-slate-50 text-slate-700">
                    <!-- TIER 1 -->
                    <tr class="border-b border-slate-200">
                        <th
                            v-if="rowType === 'dynamic_rows'"
                            :rowspan="hasGroups ? 2 : 1"
                            class="py-3 px-3 font-bold text-center w-12 border-r border-slate-200 bg-slate-100/70"
                        >
                            No
                        </th>

                        <template v-for="(item, idx) in headerStructure.tier1" :key="'t1-' + idx">
                            <!-- GROUP PARENT HEADER (MERGED HEADER) -->
                            <th
                                v-if="item.isGroup"
                                :colspan="item.colspan"
                                :rowspan="item.rowspan"
                                class="py-2 px-3 font-bold text-center bg-indigo-50/90 text-indigo-950 border-r border-b border-indigo-200 tracking-wide uppercase text-[11px]"
                            >
                                <div class="flex items-center justify-center gap-1.5">
                                    <NotionIcon name="layers" size="13" class="text-indigo-600" />
                                    <span>{{ item.title }}</span>
                                </div>
                            </th>

                            <!-- STANDALONE COLUMN (ROWSPAN = 2 if hasGroups) -->
                            <th
                                v-else
                                :rowspan="item.rowspan"
                                :colspan="item.colspan"
                                class="py-3 px-3 font-bold whitespace-nowrap border-r border-slate-200"
                                :class="{
                                    'text-center': item.col.type === 'number' || item.col.type === 'decimal' || item.col.type === 'time' || item.col.type === 'checkbox' || item.col.type === 'date',
                                    'text-emerald-800 bg-emerald-50/40': item.col.role === 'numerator',
                                    'text-sky-800 bg-sky-50/40': item.col.role === 'denominator',
                                }"
                            >
                                <div class="flex items-center gap-1.5" :class="item.col.type === 'number' || item.col.type === 'checkbox' || item.col.type === 'date' ? 'justify-center' : ''">
                                    <span>{{ item.col.title }}</span>
                                    <span v-if="item.col.role === 'numerator'" class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-emerald-200 text-emerald-800">
                                        [N]
                                    </span>
                                    <span v-else-if="item.col.role === 'denominator'" class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-sky-200 text-sky-800">
                                        [D]
                                    </span>
                                </div>
                            </th>
                        </template>

                        <th
                            v-if="compositeKeys.length > 0 && rowType === 'dynamic_rows'"
                            :rowspan="hasGroups ? 2 : 1"
                            class="py-3 px-3 text-center w-24 font-bold text-slate-700 bg-slate-100/60 border-r border-slate-200"
                        >
                            Status Kepatuhan
                        </th>
                        <th
                            v-if="rowType === 'dynamic_rows' && isEditable"
                            :rowspan="hasGroups ? 2 : 1"
                            class="py-3 px-2 text-center w-12 font-bold"
                        >
                            Aksi
                        </th>
                    </tr>

                    <!-- TIER 2 (SUB-COLUMNS UNDER GROUPS) -->
                    <tr v-if="hasGroups" class="border-b border-slate-200 bg-slate-50/90 text-slate-600">
                        <th
                            v-for="(subCol, sIdx) in headerStructure.tier2"
                            :key="'t2-' + sIdx"
                            class="py-2 px-3 font-semibold whitespace-nowrap text-[11px] border-r border-slate-200"
                            :class="{
                                'text-center': subCol.type === 'number' || subCol.type === 'decimal' || subCol.type === 'time' || subCol.type === 'checkbox' || subCol.type === 'date',
                                'text-emerald-800 bg-emerald-50/50 font-bold': subCol.role === 'numerator',
                                'text-sky-800 bg-sky-50/50 font-bold': subCol.role === 'denominator',
                            }"
                        >
                            <div class="flex items-center gap-1" :class="subCol.type === 'number' || subCol.type === 'checkbox' || subCol.type === 'date' ? 'justify-center' : ''">
                                <span>{{ subCol.title }}</span>
                                <span v-if="subCol.role === 'numerator'" class="px-1 py-0.2 rounded text-[9px] font-mono font-bold bg-emerald-200 text-emerald-800">[N]</span>
                                <span v-else-if="subCol.role === 'denominator'" class="px-1 py-0.2 rounded text-[9px] font-mono font-bold bg-sky-200 text-sky-800">[D]</span>
                            </div>
                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="divide-y divide-slate-100">

                    <!-- CASE A: FIXED ROWS (Kategori Layanan / Matriks Dokter) -->
                    <template v-if="rowType === 'fixed_rows'">
                        <tr
                            v-for="(row, rIdx) in (rows.length > 0 ? rows : [{ key: 'item_1', label: 'Baris Pengamatan' }])"
                            :key="row.id || row.key || rIdx"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td
                                v-for="(col, cIdx) in columns"
                                :key="col.id || col.key"
                                class="py-2.5 px-3"
                                :class="{
                                    'bg-emerald-50/20': col.role === 'numerator',
                                    'bg-sky-50/20': col.role === 'denominator',
                                }"
                            >
                                <!-- Col 0 / Label Role -->
                                <div v-if="col.role === 'label' || cIdx === 0" class="font-bold text-slate-800 min-w-[180px]">
                                    {{ row.label }}
                                </div>

                                <!-- Number Input -->
                                <div v-else-if="col.type === 'number' || col.type === 'decimal'" class="text-center">
                                    <input
                                        v-if="localRecords[row.key]"
                                        v-model.number="localRecords[row.key][col.key]"
                                        @input="onCellChange"
                                        :disabled="!isEditable"
                                        type="number"
                                        min="0"
                                        :step="col.type === 'decimal' ? '0.1' : '1'"
                                        class="w-24 text-xs font-mono font-bold text-center rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 py-1 disabled:bg-slate-50 disabled:text-slate-500"
                                    />
                                </div>

                                <!-- Time Input -->
                                <div v-else-if="col.type === 'time'" class="text-center">
                                    <input
                                        v-if="localRecords[row.key]"
                                        v-model="localRecords[row.key][col.key]"
                                        @input="onCellChange"
                                        :disabled="!isEditable"
                                        type="time"
                                        class="w-28 text-xs font-mono text-center rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 py-1 disabled:bg-slate-50"
                                    />
                                </div>

                                <!-- Checkbox Input -->
                                <div v-else-if="col.type === 'checkbox'" class="text-center">
                                    <input
                                        v-if="localRecords[row.key]"
                                        v-model="localRecords[row.key][col.key]"
                                        @change="onCellChange"
                                        :disabled="!isEditable"
                                        type="checkbox"
                                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 disabled:opacity-60 cursor-pointer"
                                    />
                                </div>

                                <!-- Text Input -->
                                <div v-else>
                                    <input
                                        v-if="localRecords[row.key]"
                                        v-model="localRecords[row.key][col.key]"
                                        @input="onCellChange"
                                        :disabled="!isEditable"
                                        type="text"
                                        placeholder="Keterangan..."
                                        class="w-full min-w-[140px] text-xs rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 py-1 disabled:bg-slate-50"
                                    />
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- CASE B: DYNAMIC ROWS (Log Pasien / Transaksi) -->
                    <template v-else-if="rowType === 'dynamic_rows'">
                        <tr
                            v-for="(row, rIdx) in localRecords"
                            :key="row._id || rIdx"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="py-2.5 px-3 text-center font-bold text-slate-500 font-mono">
                                {{ rIdx + 1 }}
                            </td>

                            <td
                                v-for="col in columns"
                                :key="col.id || col.key"
                                class="py-2.5 px-3"
                                :class="{
                                    'bg-emerald-50/20': col.role === 'numerator',
                                    'bg-sky-50/20': col.role === 'denominator',
                                }"
                            >
                                <!-- Number Input -->
                                <div v-if="col.type === 'number' || col.type === 'decimal'" class="text-center">
                                    <input
                                        v-model.number="row[col.key]"
                                        @input="onCellChange"
                                        :disabled="!isEditable"
                                        type="number"
                                        min="0"
                                        :step="col.type === 'decimal' ? '0.1' : '1'"
                                        class="w-24 text-xs font-mono font-bold text-center rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 py-1 disabled:bg-slate-50 disabled:text-slate-500"
                                    />
                                </div>

                                <!-- Date Input -->
                                <div v-else-if="col.type === 'date'" class="text-center">
                                    <input
                                        v-model="row[col.key]"
                                        @input="onCellChange"
                                        :disabled="!isEditable"
                                        type="date"
                                        class="w-32 text-xs font-mono text-center rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 py-1 disabled:bg-slate-50"
                                    />
                                </div>

                                <!-- Time Input -->
                                <div v-else-if="col.type === 'time'" class="text-center">
                                    <input
                                        v-model="row[col.key]"
                                        @input="onCellChange"
                                        :disabled="!isEditable"
                                        type="time"
                                        class="w-28 text-xs font-mono text-center rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 py-1 disabled:bg-slate-50"
                                    />
                                </div>

                                <!-- Checkbox Input -->
                                <div v-else-if="col.type === 'checkbox'" class="flex justify-center items-center">
                                    <input
                                        v-model="row[col.key]"
                                        @change="onCellChange"
                                        :disabled="!isEditable"
                                        type="checkbox"
                                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 disabled:opacity-60 cursor-pointer"
                                    />
                                </div>

                                <!-- Text Input -->
                                <div v-else>
                                    <input
                                        v-model="row[col.key]"
                                        @input="onCellChange"
                                        :disabled="!isEditable"
                                        type="text"
                                        :placeholder="col.key === 'norm' ? 'No. RM...' : (col.key === 'nama' ? 'Nama Pasien...' : '...')"
                                        class="w-full min-w-[120px] text-xs rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 py-1 disabled:bg-slate-50"
                                    />
                                </div>
                            </td>

                            <!-- Composite Status Badge Column -->
                            <td v-if="compositeKeys.length > 0" class="py-2.5 px-3 text-center bg-slate-50/50">
                                <span
                                    v-if="isRowCompliant(row)"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800"
                                >
                                    <NotionIcon name="check" size="12" />
                                    <span>Lengkap</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800"
                                >
                                    Belum
                                </span>
                            </td>

                            <!-- Delete Row Action -->
                            <td v-if="isEditable" class="py-2.5 px-2 text-center">
                                <button
                                    @click="removeDynamicRow(rIdx)"
                                    type="button"
                                    class="p-1 text-slate-400 hover:text-rose-600 rounded transition-colors"
                                    title="Hapus baris ini"
                                >
                                    <NotionIcon name="trash" size="14" />
                                </button>
                            </td>
                        </tr>
                    </template>

                    <!-- CASE C: DAILY GRID (Kalender Harian 1-31) -->
                    <template v-else>
                        <tr
                            v-for="day in (daysInMonth || 31)"
                            :key="day"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td
                                v-for="(col, cIdx) in columns"
                                :key="col.id || col.key"
                                class="py-2 px-3"
                                :class="{
                                    'bg-emerald-50/20': col.role === 'numerator',
                                    'bg-sky-50/20': col.role === 'denominator',
                                }"
                            >
                                <div v-if="cIdx === 0 || col.role === 'label'" class="font-bold text-slate-800 font-mono">
                                    Hari ke-{{ day }}
                                </div>

                                <div v-else-if="col.type === 'number' || col.type === 'decimal'" class="text-center">
                                    <input
                                        v-if="localRecords[day]"
                                        v-model.number="localRecords[day][col.key]"
                                        @input="onCellChange"
                                        :disabled="!isEditable"
                                        type="number"
                                        min="0"
                                        class="w-20 text-xs font-mono font-bold text-center rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 py-1 disabled:bg-slate-50 disabled:text-slate-500"
                                    />
                                </div>

                                <div v-else-if="col.type === 'checkbox'" class="flex justify-center items-center">
                                    <input
                                        v-if="localRecords[day]"
                                        v-model="localRecords[day][col.key]"
                                        @change="onCellChange"
                                        :disabled="!isEditable"
                                        type="checkbox"
                                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 disabled:opacity-60 cursor-pointer"
                                    />
                                </div>

                                <div v-else>
                                    <input
                                        v-if="localRecords[day]"
                                        v-model="localRecords[day][col.key]"
                                        @input="onCellChange"
                                        :disabled="!isEditable"
                                        type="text"
                                        class="w-full min-w-[100px] text-xs rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 py-1 disabled:bg-slate-50"
                                    />
                                </div>
                            </td>
                        </tr>
                    </template>

                </tbody>

                <!-- Table Summary Footer -->
                <tfoot>
                    <tr class="bg-slate-100/90 font-bold text-slate-800 border-t-2 border-slate-300">
                        <td v-if="rowType === 'dynamic_rows'" class="py-3 px-3 text-center font-bold">∑</td>
                        <td
                            v-for="(col, cIdx) in columns"
                            :key="col.id || col.key"
                            class="py-3 px-3"
                            :class="{
                                'text-center': col.type === 'number' || col.type === 'decimal' || col.type === 'checkbox',
                                'text-emerald-800 bg-emerald-100/60': col.role === 'numerator',
                                'text-sky-800 bg-sky-100/60': col.role === 'denominator',
                            }"
                        >
                            <span v-if="cIdx === 0 && col.role === 'label'">TOTAL AKUMULASI</span>
                            <span v-else-if="col.type === 'number' || col.type === 'decimal'" class="font-mono text-sm">
                                {{ (columnTotals[col.key] || 0).toLocaleString('id-ID') }}
                            </span>
                            <span v-else-if="col.type === 'checkbox'" class="font-mono text-xs">
                                {{ columnTotals[col.key] || 0 }} Ya
                            </span>
                            <span v-else class="text-slate-400 font-normal">-</span>
                        </td>
                        <td v-if="compositeKeys.length > 0 && rowType === 'dynamic_rows'" class="py-3 px-3 text-center font-mono font-bold text-emerald-800 bg-emerald-100/40">
                            {{ rollupStats.numerator }} Sesuai
                        </td>
                        <td v-if="rowType === 'dynamic_rows' && isEditable"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</template>
