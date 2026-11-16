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

const locations = computed(() => {
    return props.schema?.locations || [
        { key: 'depo_igd', label: 'Depo Gawat Darurat (IGD)' },
        { key: 'depo_ranap', label: 'Depo Rawat Inap' },
        { key: 'depo_rajal', label: 'Depo Rawat Jalan' },
    ];
});

const locationRecords = ref({});

// Helper aman agar template tidak pernah crash jika depo belum siap
const getLoc = (key) => {
    if (!locationRecords.value[key]) {
        locationRecords.value[key] = {
            fornas: 0,
            generik: 0,
            total_resep: 0,
        };
    }
    return locationRecords.value[key];
};

const initData = () => {
    const existing = props.modelValue?.locations || props.modelValue || {};
    const map = {};

    locations.value.forEach(loc => {
        const locData = existing[loc.key] || {};
        map[loc.key] = {
            fornas: Number(locData.fornas ?? 0),
            generik: Number(locData.generik ?? 0),
            total_resep: Number(locData.total_resep ?? 0),
        };
    });

    locationRecords.value = map;
    emitSummary();
};

const totals = computed(() => {
    let sumFornas = 0;
    let sumGenerik = 0;
    let sumTotalResep = 0;

    locations.value.forEach(loc => {
        const row = locationRecords.value[loc.key] || {};
        sumFornas += Number(row.fornas || 0);
        sumGenerik += Number(row.generik || 0);
        sumTotalResep += Number(row.total_resep || 0);
    });

    const capaian = sumTotalResep > 0 ? Number(((sumFornas / sumTotalResep) * 100).toFixed(2)) : 0;

    return {
        fornas: sumFornas,
        generik: sumGenerik,
        total_resep: sumTotalResep,
        capaian: capaian,
    };
});

const emitSummary = () => {
    emit('update:summary', {
        numerator: totals.value.fornas,
        denominator: totals.value.total_resep,
        capaian: totals.value.capaian,
    });
};

const calculateRollup = () => {
    emit('update:modelValue', {
        locations: locationRecords.value,
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

const onInput = () => {
    calculateRollup();
};
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Header Mode Format -->
        <div class="px-5 py-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    <NotionIcon name="hospital" size="18" class="text-white" />
                </span>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 leading-snug">
                        {{ schema?.title || 'Matriks Kepatuhan Formularium Nasional per Depo Pelayanan Farmasi' }}
                    </h3>
                    <p class="text-xs text-gray-500">
                        Pencatatan volume resep FORNAS dan Generik per depo layanan instalasi farmasi.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-5 space-y-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                    <thead class="bg-slate-100 text-gray-700 text-xs font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Depo Pelayanan Farmasi</th>
                            <th class="px-4 py-3 text-right w-44 bg-blue-50/60 text-blue-900">Resep FORNAS (N)</th>
                            <th class="px-4 py-3 text-right w-44 text-gray-600 font-medium">Resep Generik</th>
                            <th class="px-4 py-3 text-right w-48 bg-slate-50 text-gray-900">Total Resep (D)</th>
                            <th class="px-4 py-3 text-center w-36">Kepatuhan (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="loc in locations" :key="loc.key" class="hover:bg-slate-50/80 transition-colors">
                            <!-- Nama Depo -->
                            <td class="px-4 py-3 text-xs font-semibold text-gray-900">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                    <span>{{ loc.label }}</span>
                                </div>
                            </td>

                            <!-- Resep FORNAS (N) -->
                            <td class="px-4 py-2.5 text-right bg-blue-50/20">
                                <div v-if="permissions.isExecutive" class="text-xs font-mono font-bold py-1.5 px-3 text-blue-800 bg-blue-50/50 rounded-lg">
                                    {{ (getLoc(loc.key).fornas || 0).toLocaleString('id-ID') }}
                                </div>
                                <input
                                    v-else
                                    type="number"
                                    min="0"
                                    v-model.number="getLoc(loc.key).fornas"
                                    @input="onInput"
                                    :disabled="!permissions.canEditDaily || isPeriodApproved"
                                    class="w-full text-xs font-mono py-1.5 px-2.5 text-right border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 font-bold text-blue-800"
                                    placeholder="0"
                                />
                            </td>

                            <!-- Resep Generik -->
                            <td class="px-4 py-2.5 text-right">
                                <div v-if="permissions.isExecutive" class="text-xs font-mono py-1.5 px-3 text-gray-700 bg-slate-50 rounded-lg">
                                    {{ (getLoc(loc.key).generik || 0).toLocaleString('id-ID') }}
                                </div>
                                <input
                                    v-else
                                    type="number"
                                    min="0"
                                    v-model.number="getLoc(loc.key).generik"
                                    @input="onInput"
                                    :disabled="!permissions.canEditDaily || isPeriodApproved"
                                    class="w-full text-xs font-mono py-1.5 px-2.5 text-right border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 text-gray-600"
                                    placeholder="0"
                                />
                            </td>

                            <!-- Total Resep (D) -->
                            <td class="px-4 py-2.5 text-right bg-slate-50/30">
                                <div v-if="permissions.isExecutive" class="text-xs font-mono font-bold py-1.5 px-3 text-gray-900 bg-slate-50 rounded-lg">
                                    {{ (getLoc(loc.key).total_resep || 0).toLocaleString('id-ID') }}
                                </div>
                                <input
                                    v-else
                                    type="number"
                                    min="0"
                                    v-model.number="getLoc(loc.key).total_resep"
                                    @input="onInput"
                                    :disabled="!permissions.canEditDaily || isPeriodApproved"
                                    class="w-full text-xs font-mono py-1.5 px-2.5 text-right border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 font-bold text-gray-900"
                                    placeholder="0"
                                />
                            </td>

                            <!-- Capaian % per Depo -->
                            <td class="px-4 py-3 text-center text-xs font-mono font-bold">
                                <span
                                    v-if="getLoc(loc.key).total_resep > 0"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-xs font-bold inline-block',
                                        (getLoc(loc.key).fornas / getLoc(loc.key).total_resep) >= 0.8
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                            : 'bg-rose-50 text-rose-700 border border-rose-200'
                                    ]"
                                >
                                    {{ ((getLoc(loc.key).fornas / getLoc(loc.key).total_resep) * 100).toFixed(1) }}%
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                        </tr>
                    </tbody>

                    <!-- Total Baris Agregat -->
                    <tfoot class="bg-amber-50/70 border-t-2 border-amber-200 font-bold text-xs text-gray-900">
                        <tr>
                            <td class="px-4 py-3 uppercase tracking-wider text-amber-900">
                                TOTAL KEPATUHAN RUMAH SAKIT
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-blue-900">
                                {{ totals.fornas.toLocaleString('id-ID') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-gray-600">
                                {{ totals.generik.toLocaleString('id-ID') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-gray-900">
                                {{ totals.total_resep.toLocaleString('id-ID') }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-sm">
                                <span class="px-3 py-1 rounded-lg bg-blue-700 text-white shadow-2xs font-bold">
                                    {{ totals.capaian }}%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Petunjuk Indikator -->
            <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-100 flex items-start gap-3 text-xs text-blue-900">
                <NotionIcon name="table" size="18" class="text-blue-600 shrink-0" />
                <div class="space-y-1">
                    <p class="font-bold">Ketentuan Standar Indikator Nasional Mutu (INM) Kepatuhan FORNAS:</p>
                    <p>
                        Numerator adalah jumlah lembar resep yang menggunakan obat dalam Formularium Nasional (FORNAS). Denominator adalah seluruh jumlah lembar resep yang dilayani di seluruh depo farmasi rumah sakit. Target standar nasional adalah ≥ 80%.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
