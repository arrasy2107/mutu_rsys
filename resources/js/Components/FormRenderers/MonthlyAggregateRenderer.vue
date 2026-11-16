<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: [Object, Array],
    indicator: Object,
    schema: Object,
    formFields: Array,
    tahun: Number,
    bulan: Number,
    permissions: Object,
    isPeriodApproved: Boolean,
    annualSummary: {
        type: [Object, Array],
        default: () => ({})
    },
    currentPeriodEntry: Object,
});

const emit = defineEmits(['update:modelValue', 'update:summary', 'change', 'switch-month']);

const bulanList = [
    { v: 1, l: 'Januari' }, { v: 2, l: 'Februari' }, { v: 3, l: 'Maret' },
    { v: 4, l: 'April' }, { v: 5, l: 'Mei' }, { v: 6, l: 'Juni' },
    { v: 7, l: 'Juli' }, { v: 8, l: 'Agustus' }, { v: 9, l: 'September' },
    { v: 10, l: 'Oktober' }, { v: 11, l: 'November' }, { v: 12, l: 'Desember' },
];

const currentSchema = computed(() => {
    return props.schema || props.indicator?.form_schema || {};
});

const tableTitle = computed(() => {
    return currentSchema.value?.table_title || `Rekapitulasi Bulanan: ${props.indicator?.judul || 'Indikator Mutu'}`;
});

const petunjuk = computed(() => {
    return currentSchema.value?.petunjuk_pengisian || props.indicator?.definisi_operasional || 'Masukkan realisasi data Numerator dan Denominator untuk bulan aktif. Indikator ini dihitung langsung secara periodik bulanan.';
});

const isEditable = computed(() => {
    return props.permissions?.canEditDaily && !props.isPeriodApproved;
});

// Current active month fields
const activeMonthNum = ref(0);
const activeMonthDen = ref(0);
const activeMonthNotes = ref('');

// Sync from props
const initActiveMonthData = () => {
    const val = props.modelValue;
    if (val && typeof val === 'object' && !Array.isArray(val)) {
        if (val[props.bulan]) {
            activeMonthNum.value = parseFloat(val[props.bulan].num) || 0;
            activeMonthDen.value = parseFloat(val[props.bulan].den) || 0;
            activeMonthNotes.value = val[props.bulan].notes || '';
        } else if (val.num !== undefined || val.numerator !== undefined) {
            activeMonthNum.value = parseFloat(val.num ?? val.numerator) || 0;
            activeMonthDen.value = parseFloat(val.den ?? val.denominator) || 0;
            activeMonthNotes.value = val.notes || '';
        }
    } else if (props.currentPeriodEntry) {
        activeMonthNum.value = parseFloat(props.currentPeriodEntry.numerator) || 0;
        activeMonthDen.value = parseFloat(props.currentPeriodEntry.denominator) || 0;
    }
    calculateRollup();
};

const multiplier = computed(() => {
    if (props.indicator?.multiplier) {
        return parseFloat(props.indicator.multiplier);
    }
    const f = props.indicator?.formula || '';
    if (f.includes('25')) return 25;
    if (f.includes('1000')) return 1000;
    return 100;
});

const activeMonthCapaian = computed(() => {
    const d = parseFloat(activeMonthDen.value) || 0;
    const n = parseFloat(activeMonthNum.value) || 0;
    if (d <= 0) return 0;
    return parseFloat(((n / d) * multiplier.value).toFixed(2));
});

const targetCapaian = computed(() => parseFloat(props.indicator?.target_capaian || 100));
const targetOperator = computed(() => props.indicator?.target_operator || '>=');

const isTargetMet = computed(() => {
    if (activeMonthDen.value <= 0) return null;
    const cap = activeMonthCapaian.value;
    const tgt = targetCapaian.value;
    if (targetOperator.value === '<=') return cap <= tgt;
    return cap >= tgt;
});

const calculateRollup = () => {
    emit('update:summary', {
        numerator: parseFloat(activeMonthNum.value) || 0,
        denominator: parseFloat(activeMonthDen.value) || 0,
        capaian: activeMonthCapaian.value,
    });
};

const onDataChanged = () => {
    calculateRollup();

    // Prepare full monthly map
    const existingMap = (props.modelValue && typeof props.modelValue === 'object') ? { ...props.modelValue } : {};
    existingMap[props.bulan] = {
        num: parseFloat(activeMonthNum.value) || 0,
        den: parseFloat(activeMonthDen.value) || 0,
        capaian: activeMonthCapaian.value,
        notes: activeMonthNotes.value,
    };

    emit('update:modelValue', existingMap);
    emit('change');
};

// 12-Month Annual View Computation
const monthlyRows = computed(() => {
    return bulanList.map(b => {
        const isCurrent = b.v === props.bulan;
        let num = 0;
        let den = 0;
        let cap = 0;
        let hasEntry = false;

        // Check if there's annualSummary from backend (Array or Object key map)
        let summaryEntry = null;
        if (Array.isArray(props.annualSummary)) {
            summaryEntry = props.annualSummary.find(s => s && (s.bulan === b.v || s.bulan === String(b.v)));
        } else if (props.annualSummary && typeof props.annualSummary === 'object') {
            summaryEntry = props.annualSummary[b.v] ?? props.annualSummary[String(b.v)] ?? null;
        }

        if (summaryEntry) {
            num = parseFloat(summaryEntry.numerator) || 0;
            den = parseFloat(summaryEntry.denominator) || 0;
            cap = summaryEntry.capaian !== null && summaryEntry.capaian !== undefined 
                ? parseFloat(summaryEntry.capaian) 
                : (den > 0 ? parseFloat(((num / den) * multiplier.value).toFixed(2)) : 0);
            hasEntry = den > 0 || num > 0;
        }

        // If it's the currently active month, reflect the local reactive state
        if (isCurrent) {
            num = parseFloat(activeMonthNum.value) || 0;
            den = parseFloat(activeMonthDen.value) || 0;
            cap = activeMonthCapaian.value;
            hasEntry = den > 0 || num > 0;
        }

        let met = null;
        if (den > 0) {
            met = targetOperator.value === '<=' ? cap <= targetCapaian.value : cap >= targetCapaian.value;
        }

        return {
            bulanNum: b.v,
            bulanName: b.l,
            isCurrent,
            num,
            den,
            cap,
            hasEntry,
            isMet: met,
        };
    });
});

const annualTotals = computed(() => {
    let totN = 0;
    let totD = 0;
    let monthsWithData = 0;

    monthlyRows.value.forEach(r => {
        if (r.den > 0 || r.num > 0) {
            totN += r.num;
            totD += r.den;
            monthsWithData++;
        }
    });

    const avgCap = totD > 0 ? parseFloat(((totN / totD) * multiplier.value).toFixed(2)) : 0;
    return {
        totalN: totN,
        totalD: totD,
        avgCap,
        monthsWithData,
    };
});

watch(() => [props.bulan, props.tahun, props.indicator?.id], () => {
    initActiveMonthData();
});

watch(() => props.modelValue, () => {
    initActiveMonthData();
}, { deep: true });

onMounted(() => {
    initActiveMonthData();
});
</script>

<template>
    <div class="space-y-5">
        <!-- HEADER & PETUNJUK -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        <h3 class="text-base font-bold text-slate-800 tracking-tight">{{ tableTitle }}</h3>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            Arketipe: Rekap Bulanan Langsung
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-3xl flex items-center gap-1.5">
                        <NotionIcon name="lightbulb" size="14" class="text-amber-500 shrink-0" />
                        <span><strong class="font-semibold text-slate-700">Petunjuk Pengisian:</strong> {{ petunjuk }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                    <span class="text-xs text-slate-500 font-medium">Bulan Aktif:</span>
                    <span class="text-xs font-bold text-blue-700 font-mono">
                        {{ bulanList[bulan - 1]?.l }} {{ tahun }}
                    </span>
                </div>
            </div>

            <!-- ACTIVE MONTH INPUT PANEL -->
            <div class="mt-4 pt-1">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    
                    <!-- Numerator Input Card -->
                    <div class="md:col-span-4 bg-emerald-50/50 rounded-xl p-3.5 border border-emerald-100/90 relative group hover:border-emerald-300 transition-all">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-emerald-200 text-emerald-800">[N]</span>
                                <span>Numerator</span>
                            </label>
                            <span class="text-[10px] text-emerald-700 font-medium truncate max-w-[140px]" :title="indicator?.numerator_keterangan || 'Capaian'">
                                {{ indicator?.numerator_keterangan || 'Data Terpenuhi' }}
                            </span>
                        </div>
                        <div class="relative">
                            <input
                                v-model.number="activeMonthNum"
                                @input="onDataChanged"
                                :disabled="!isEditable"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="0"
                                class="w-full text-base font-mono font-bold text-slate-800 bg-white rounded-lg border-emerald-200 focus:ring-emerald-500 focus:border-emerald-500 py-2 px-3 shadow-2xs disabled:bg-slate-100 disabled:text-slate-500"
                            />
                        </div>
                        <p class="text-[10px] text-emerald-700/80 mt-1.5 truncate">
                            Contoh: Jumlah kegiatan/PKS/klaim selesai tepat waktu.
                        </p>
                    </div>

                    <!-- Denominator Input Card -->
                    <div class="md:col-span-4 bg-sky-50/50 rounded-xl p-3.5 border border-sky-100/90 relative group hover:border-sky-300 transition-all">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-sky-900 flex items-center gap-1.5">
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-sky-200 text-sky-800">[D]</span>
                                <span>Denominator</span>
                            </label>
                            <span class="text-[10px] text-sky-700 font-medium truncate max-w-[140px]" :title="indicator?.denominator_keterangan || 'Target / Total'">
                                {{ indicator?.denominator_keterangan || 'Total Rencana / Sampel' }}
                            </span>
                        </div>
                        <div class="relative">
                            <input
                                v-model.number="activeMonthDen"
                                @input="onDataChanged"
                                :disabled="!isEditable"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="0"
                                class="w-full text-base font-mono font-bold text-slate-800 bg-white rounded-lg border-sky-200 focus:ring-sky-500 focus:border-sky-500 py-2 px-3 shadow-2xs disabled:bg-slate-100 disabled:text-slate-500"
                            />
                        </div>
                        <p class="text-[10px] text-sky-700/80 mt-1.5 truncate">
                            Contoh: Total seluruh agenda yang direncanakan / berkas.
                        </p>
                    </div>

                    <!-- Result Capaian Badge Card -->
                    <div class="md:col-span-4 bg-slate-50 rounded-xl p-3.5 border border-slate-200/90 flex flex-col justify-between h-full">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700">Capaian Bulan Ini</span>
                            <span class="text-[11px] text-slate-500 font-mono">
                                Target: {{ targetOperator === '>=' ? '≥' : (targetOperator === '<=' ? '≤' : '=') }} {{ targetCapaian }}%
                            </span>
                        </div>

                        <div class="my-1.5 flex items-baseline gap-2">
                            <span class="text-2xl font-black font-mono tracking-tight" :class="isTargetMet === true ? 'text-emerald-700' : (isTargetMet === false ? 'text-rose-600' : 'text-slate-700')">
                                {{ activeMonthCapaian }}%
                            </span>
                            <span
                                v-if="isTargetMet !== null"
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="isTargetMet ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            >
                                {{ isTargetMet ? '✓ Memenuhi Target' : '⚠ Belum Tercapai' }}
                            </span>
                            <span v-else class="text-xs text-slate-400">
                                (Masukkan N & D)
                            </span>
                        </div>

                        <p class="text-[10px] text-slate-500">
                            Rumus: (N / D) × {{ multiplier }}
                        </p>
                    </div>

                </div>

                <!-- Notes / Keterangan Bulan Berjalan -->
                <div class="mt-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Catatan / Uraian Pelaksanaan Bulan {{ bulanList[bulan - 1]?.l }} (Opsional):
                    </label>
                    <input
                        v-model="activeMonthNotes"
                        @input="onDataChanged"
                        :disabled="!isEditable"
                        type="text"
                        placeholder="Catatan kendala pihak ke-3, rincian kegiatan, atau tindak lanjut..."
                        class="w-full text-xs rounded-xl border-slate-200 focus:ring-blue-500 focus:border-blue-500 py-1.5 px-3 disabled:bg-slate-50"
                    />
                </div>
            </div>
        </div>

        <!-- TABEL REKAPITULASI TAHUNAN 12 BULAN (ANNUAL MATRIX VIEW) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="p-4 bg-slate-50/70 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h4 class="text-xs font-bold text-slate-800 tracking-wide uppercase">
                        Tabel Rekapitulasi Triwulan & Tahunan (Januari - Desember {{ tahun }})
                    </h4>
                    <p class="text-[11px] text-slate-500">
                        Klik nama bulan untuk berpindah periode pengumpulan data secara cepat.
                    </p>
                </div>
                <div class="flex items-center gap-3 text-xs font-mono">
                    <span class="text-slate-600 font-semibold">Tercatat: {{ annualTotals.monthsWithData }}/12 Bulan</span>
                    <span class="text-slate-300">|</span>
                    <span class="text-blue-700 font-bold">Rata-rata: {{ annualTotals.avgCap }}%</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-100/80 text-slate-600 border-b border-slate-200 font-bold">
                            <th class="py-2.5 px-3 text-center w-12">No</th>
                            <th class="py-2.5 px-4 min-w-[140px]">Bulan</th>
                            <th class="py-2.5 px-4 text-center text-emerald-800 bg-emerald-50/40 min-w-[130px]">
                                Numerator [N]
                            </th>
                            <th class="py-2.5 px-4 text-center text-sky-800 bg-sky-50/40 min-w-[130px]">
                                Denominator [D]
                            </th>
                            <th class="py-2.5 px-4 text-center min-w-[110px]">Capaian (%)</th>
                            <th class="py-2.5 px-4 text-center min-w-[130px]">Status Target</th>
                            <th class="py-2.5 px-3 text-center w-20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="row in monthlyRows"
                            :key="row.bulanNum"
                            class="transition-colors"
                            :class="row.isCurrent ? 'bg-blue-50/60 font-semibold ring-1 ring-blue-200/50' : 'hover:bg-slate-50/80'"
                        >
                            <td class="py-2.5 px-3 text-center font-mono text-slate-400">
                                {{ row.bulanNum }}
                            </td>
                            <td class="py-2.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-800 font-medium">{{ row.bulanName }}</span>
                                    <span v-if="row.isCurrent" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-600 text-white font-mono">
                                        AKTIF
                                    </span>
                                </div>
                            </td>
                            <td class="py-2.5 px-4 text-center font-mono text-emerald-800 bg-emerald-50/20">
                                {{ row.num.toLocaleString('id-ID') }}
                            </td>
                            <td class="py-2.5 px-4 text-center font-mono text-sky-800 bg-sky-50/20">
                                {{ row.den.toLocaleString('id-ID') }}
                            </td>
                            <td class="py-2.5 px-4 text-center font-mono font-bold" :class="row.isMet === true ? 'text-emerald-700' : (row.isMet === false ? 'text-rose-600' : 'text-slate-500')">
                                {{ row.den > 0 ? row.cap + '%' : '-' }}
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <span
                                    v-if="row.den > 0 && row.isMet !== null"
                                    class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                    :class="row.isMet ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                >
                                    {{ row.isMet ? 'Tercapai' : 'Tidak Tercapai' }}
                                </span>
                                <span v-else class="text-slate-400 text-[11px]">-</span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <button
                                    v-if="!row.isCurrent"
                                    @click="emit('switch-month', row.bulanNum)"
                                    type="button"
                                    class="px-2 py-1 text-[11px] font-semibold text-blue-600 hover:text-blue-800 hover:bg-blue-100/60 rounded-lg transition-all"
                                >
                                    Pilih
                                </button>
                                <span v-else class="text-blue-600 text-xs font-bold font-mono">✓</span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100/90 font-bold text-slate-800 border-t-2 border-slate-300">
                            <td class="py-3 px-3 text-center">∑</td>
                            <td class="py-3 px-4">TOTAL TAHUNAN {{ tahun }}</td>
                            <td class="py-3 px-4 text-center font-mono text-emerald-900 bg-emerald-100/60 text-sm">
                                {{ annualTotals.totalN.toLocaleString('id-ID') }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-sky-900 bg-sky-100/60 text-sm">
                                {{ annualTotals.totalD.toLocaleString('id-ID') }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-slate-900 text-sm">
                                {{ annualTotals.totalD > 0 ? annualTotals.avgCap + '%' : '-' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span v-if="annualTotals.totalD > 0" class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="annualTotals.avgCap >= targetCapaian ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                                    {{ annualTotals.avgCap >= targetCapaian ? 'Tahunan Memenuhi' : 'Tahunan Belum' }}
                                </span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>
