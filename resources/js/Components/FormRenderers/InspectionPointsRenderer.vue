<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Object, Array],
        default: () => ({}),
    },
    indicator: {
        type: Object,
        required: true,
    },
    schema: {
        type: Object,
        default: () => ({}),
    },
    bulan: {
        type: Number,
        default: 1,
    },
    tahun: {
        type: Number,
        default: 2026,
    },
    permissions: {
        type: Object,
        default: () => ({}),
    },
    isPeriodApproved: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'update:summary', 'change']);

const points = computed(() => {
    return props.schema?.points || [
        { key: 'lemari_ha_ibs', label: 'Lemari Khusus High Alert Farmasi IBS' },
        { key: 'troli_ok_1', label: 'Troli Emergensi Kamar Bedah 1' },
        { key: 'troli_ok_2', label: 'Troli Emergensi Kamar Bedah 2' },
        { key: 'troli_ok_3', label: 'Troli Emergensi Kamar Bedah 3 / Minor' },
        { key: 'lemari_narkotika', label: 'Lemari Narkotika & Psikotropika IBS (Double Lock)' },
        { key: 'kotak_lasa', label: 'Penyimpanan Obat LASA / NORUM IBS' },
        { key: 'kulkas_farmasi', label: 'Kulkas Obat IBS (Suhu 2°C - 8°C)' },
    ];
});

const criteria = computed(() => {
    return props.schema?.criteria || [
        { key: 'label_ha', label: 'Stiker High Alert Merah' },
        { key: 'pemisahan_lasa', label: 'Pemisahan Khusus LASA' },
        { key: 'double_check', label: 'Kartu Stok & Double Check' },
        { key: 'suhu_terjaga', label: 'Suhu Sesuai & Dicatat' },
    ];
});

const pointRecords = ref({});

// Helper aman agar template tidak pernah crash jika titik belum siap
const getPoint = (pKey) => {
    if (!pointRecords.value[pKey]) {
        const crit = {};
        criteria.value.forEach(c => {
            crit[c.key] = true;
        });
        pointRecords.value[pKey] = {
            checked: true,
            criteria: crit,
            catatan: '',
        };
    }
    return pointRecords.value[pKey];
};

const initData = () => {
    const existing = props.modelValue?.points || props.modelValue || {};
    const map = {};

    points.value.forEach(p => {
        const pData = existing[p.key] || {};
        map[p.key] = {
            checked: Boolean(pData.checked ?? true), // apakah titik ini diperiksa
            criteria: {},
            catatan: pData.catatan || '',
        };
        criteria.value.forEach(c => {
            map[p.key].criteria[c.key] = Boolean(pData.criteria?.[c.key] ?? true);
        });
    });

    pointRecords.value = map;
    emitSummary();
};

// Cek apakah satu titik memenuhi semua kriteria
const isPointCompliant = (pKey) => {
    const p = pointRecords.value[pKey];
    if (!p || !p.checked) return false;
    return criteria.value.every(c => p.criteria?.[c.key] === true);
};

const totals = computed(() => {
    let inspectedCount = 0;
    let compliantCount = 0;

    points.value.forEach(p => {
        const pData = pointRecords.value[p.key];
        if (pData && pData.checked) {
            inspectedCount++;
            if (isPointCompliant(p.key)) {
                compliantCount++;
            }
        }
    });

    const capaian = inspectedCount > 0 ? Number(((compliantCount / inspectedCount) * 100).toFixed(2)) : 0;

    return {
        inspected: inspectedCount,
        compliant: compliantCount,
        capaian: capaian,
    };
});

const emitSummary = () => {
    emit('update:summary', {
        numerator: totals.value.compliant,
        denominator: totals.value.inspected,
        capaian: totals.value.capaian,
    });
};

const calculateRollup = () => {
    emit('update:modelValue', {
        points: pointRecords.value,
        totals: totals.value,
    });
    emitSummary();
    emit('change');
};

// Panggil secara sinkron di setup
initData();

watch(() => props.indicator?.id, () => {
    initData();
});

const toggleCriterion = (pKey, cKey) => {
    if (!props.permissions.canEditDaily || props.isPeriodApproved) return;
    if (pointRecords.value[pKey]?.criteria) {
        pointRecords.value[pKey].criteria[cKey] = !pointRecords.value[pKey].criteria[cKey];
        calculateRollup();
    }
};

const setAllCriteria = (val) => {
    if (!props.permissions.canEditDaily || props.isPeriodApproved) return;
    points.value.forEach(p => {
        if (pointRecords.value[p.key]?.criteria) {
            criteria.value.forEach(c => {
                pointRecords.value[p.key].criteria[c.key] = val;
            });
        }
    });
    calculateRollup();
};
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Header Mode Format -->
        <div class="px-5 py-4 bg-gradient-to-r from-rose-50/80 to-amber-50/50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    <NotionIcon name="alert" size="18" class="text-white" />
                </span>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 leading-snug">
                        {{ schema?.title || 'Checklist Titik Inspeksi Penyimpanan Obat High Alert' }}
                    </h3>
                    <p class="text-xs text-gray-500">
                        Pemeriksaan berkas kepatuhan penyimpanan obat risiko tinggi (High Alert) pada seluruh troli & depo kamar operasi.
                    </p>
                </div>
            </div>

            <!-- Quick Action Toggle All -->
            <div v-if="permissions.canEditDaily && !isPeriodApproved" class="flex items-center gap-2">
                <button
                    @click="setAllCriteria(true)"
                    class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-lg transition-colors shadow-2xs"
                >
                    ✅ Centang Semua Sesuai
                </button>
            </div>
        </div>

        <div class="p-5 space-y-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-xl overflow-hidden shadow-2xs text-xs">
                    <thead class="bg-slate-100 text-gray-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left min-w-[240px]">Tempat / Titik Penyimpanan di IBS</th>
                            <th v-for="c in criteria" :key="c.key" class="px-3 py-3 text-center">
                                {{ c.label }}
                            </th>
                            <th class="px-3 py-3 text-center w-36">Status Kepatuhan</th>
                            <th class="px-4 py-3 text-left w-56">Catatan / Temuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="p in points" :key="p.key" class="hover:bg-rose-50/20 transition-colors">
                            <!-- Nama Titik -->
                            <td class="px-4 py-3 font-semibold text-gray-900">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" :class="isPointCompliant(p.key) ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                    <span>{{ p.label }}</span>
                                </div>
                            </td>

                            <!-- Kriteria Checklist Toggles -->
                            <td v-for="c in criteria" :key="c.key" class="px-3 py-2.5 text-center">
                                <button
                                    type="button"
                                    @click="toggleCriterion(p.key, c.key)"
                                    :disabled="!permissions.canEditDaily || isPeriodApproved"
                                    :class="[
                                        'w-7 h-7 rounded-lg inline-flex items-center justify-center font-bold text-xs transition-all border',
                                        getPoint(p.key)?.criteria?.[c.key]
                                            ? 'bg-emerald-500 text-white border-emerald-600 shadow-2xs'
                                            : 'bg-gray-100 text-gray-400 border-gray-300 hover:bg-gray-200'
                                    ]"
                                    :title="getPoint(p.key)?.criteria?.[c.key] ? 'Terpenuhi' : 'Belum Terpenuhi'"
                                >
                                    {{ getPoint(p.key)?.criteria?.[c.key] ? '✓' : '✕' }}
                                </button>
                            </td>

                            <!-- Status Titik (Sesuai jika semua terpenuhi) -->
                            <td class="px-3 py-3 text-center font-bold">
                                <span
                                    v-if="isPointCompliant(p.key)"
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1.5"
                                >
                                    <NotionIcon name="check-circle" size="13" />
                                    <span>Sesuai Aturan (N=1)</span>
                                </span>
                                <span
                                    v-else
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center gap-1.5"
                                >
                                    <NotionIcon name="alert" size="13" />
                                    <span>Belum Sesuai (N=0)</span>
                                </span>
                            </td>

                            <!-- Catatan Temuan -->
                            <td class="px-4 py-2">
                                <div v-if="permissions.isExecutive" class="text-xs text-gray-600 italic">
                                    {{ getPoint(p.key)?.catatan || '-' }}
                                </div>
                                <input
                                    v-else
                                    v-model="getPoint(p.key).catatan"
                                    @input="calculateRollup"
                                    type="text"
                                    :disabled="!permissions.canEditDaily || isPeriodApproved"
                                    placeholder="Catatan hasil telusur..."
                                    class="w-full text-[11px] border-gray-200 rounded px-2 py-1 focus:ring-rose-500 focus:border-rose-500 disabled:bg-gray-50 text-gray-700"
                                />
                            </td>
                        </tr>
                    </tbody>

                    <!-- Total Rekapitulasi Titik -->
                    <tfoot class="bg-amber-50/80 border-t-2 border-amber-200 font-bold text-xs text-gray-900">
                        <tr>
                            <td class="px-4 py-3 uppercase tracking-wider text-amber-900">
                                TOTAL REKAPITULASI PEMERIKSAAN
                            </td>
                            <td :colspan="criteria.length" class="px-3 py-3 text-center text-gray-500 font-normal italic">
                                Titik Sesuai: {{ totals.compliant }} dari {{ totals.inspected }} Tempat Penyimpanan
                            </td>
                            <td class="px-3 py-3 text-center font-mono text-sm">
                                <span class="px-3 py-1 rounded-lg bg-rose-700 text-white shadow-2xs font-bold">
                                    {{ totals.capaian }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                Standar Akreditasi: 100%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Petunjuk Indikator -->
            <div class="p-4 rounded-xl bg-rose-50/50 border border-rose-100 flex items-start gap-3 text-xs text-rose-900">
                <span class="text-base shrink-0">⚠️</span>
                <div class="space-y-1">
                    <p class="font-bold">Ketentuan Standar Keselamatan Pasien (SKP) Penyimpanan Obat High Alert:</p>
                    <p>
                        Numerator adalah jumlah tempat penyimpanan obat high alert di Instalasi Bedah Sentral (IBS) yang memenuhi seluruh kriteria keselamatan (stiker penanda merah, pemisahan LASA/NORUM, kartu stok/double check, dan suhu penyimpanan sesuai). Denominator adalah total seluruh tempat penyimpanan obat high alert di IBS yang diperiksa.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
