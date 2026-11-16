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

// Entitas dokter dari skema
const entities = computed(() => {
    return props.schema?.entities || [];
});

const columns = computed(() => {
    return props.schema?.columns || [
        { key: 'sample', label: 'Jml Sampel', type: 'number', is_denominator: true },
        { key: 'pasien', label: 'Nama Pasien', type: 'number', auxiliary: true },
        { key: 'tgl_lahir', label: 'Tgl Lahir', type: 'number', auxiliary: true },
        { key: 'no_rm', label: 'No. RM', type: 'number', auxiliary: true },
        { key: 'nama_obat', label: 'Nama Obat', type: 'number', auxiliary: true },
        { key: 'dosis', label: 'Dosis Obat', type: 'number', auxiliary: true },
        { key: 'aturan_pakai', label: 'Aturan Pakai', type: 'number', is_numerator: true },
    ];
});

// State Data
const entityRecords = ref({});
const searchQuery = ref('');

// Helper aman agar template tidak pernah crash jika entitas belum siap
const getEnt = (entId) => {
    if (!entityRecords.value[entId]) {
        entityRecords.value[entId] = {
            sample: 0,
            pasien: 0,
            tgl_lahir: 0,
            no_rm: 0,
            nama_obat: 0,
            dosis: 0,
            aturan_pakai: 0,
        };
    }
    return entityRecords.value[entId];
};

// Inisialisasi dari modelValue
const initData = () => {
    const existing = props.modelValue?.entities || props.modelValue || {};
    const map = {};

    entities.value.forEach(ent => {
        const entData = existing[ent.id] || existing[ent.name] || {};
        map[ent.id] = {
            sample: Number(entData.sample ?? 0),
            pasien: Number(entData.pasien ?? (entData.sample ?? 0)),
            tgl_lahir: Number(entData.tgl_lahir ?? (entData.sample ?? 0)),
            no_rm: Number(entData.no_rm ?? (entData.sample ?? 0)),
            nama_obat: Number(entData.nama_obat ?? (entData.sample ?? 0)),
            dosis: Number(entData.dosis ?? (entData.sample ?? 0)),
            aturan_pakai: Number(entData.aturan_pakai ?? 0),
        };
    });

    entityRecords.value = map;
    emitSummary();
};

// Filter entitas berdasarkan pencarian
const filteredEntities = computed(() => {
    if (!searchQuery.value) return entities.value;
    const q = searchQuery.value.toLowerCase();
    return entities.value.filter(e => e.name.toLowerCase().includes(q));
});

// Perhitungan Rollup
const totals = computed(() => {
    let sumSample = 0;
    let sumAturanPakai = 0;

    entities.value.forEach(ent => {
        const row = entityRecords.value[ent.id] || {};
        sumSample += Number(row.sample || 0);
        sumAturanPakai += Number(row.aturan_pakai || 0);
    });

    const capaian = sumSample > 0 ? Number(((sumAturanPakai / sumSample) * 100).toFixed(2)) : 0;

    return {
        sample: sumSample,
        aturan_pakai: sumAturanPakai,
        capaian: capaian,
    };
});

const emitSummary = () => {
    emit('update:summary', {
        numerator: totals.value.aturan_pakai,
        denominator: totals.value.sample,
        capaian: totals.value.capaian,
    });
};

const calculateRollup = () => {
    emit('update:modelValue', {
        entities: entityRecords.value,
        totals: totals.value,
    });
    emitSummary();
    emit('change');
};

// Panggil secara sinkron di setup
initData();

watch(() => [props.indicator?.id, props.bulan, props.tahun], () => {
    initData();
});

const onInput = () => {
    calculateRollup();
};

// Quick action: set aturan_pakai = sample untuk satu dokter
const setCompliantRow = (entId) => {
    if (entityRecords.value[entId]) {
        const smp = entityRecords.value[entId].sample;
        entityRecords.value[entId].aturan_pakai = smp;
        entityRecords.value[entId].pasien = smp;
        entityRecords.value[entId].tgl_lahir = smp;
        entityRecords.value[entId].no_rm = smp;
        entityRecords.value[entId].nama_obat = smp;
        entityRecords.value[entId].dosis = smp;
        calculateRollup();
    }
};

// Quick action: set semua aturan_pakai = sample (100% kepatuhan)
const setAllCompliant = () => {
    entities.value.forEach(ent => {
        setCompliantRow(ent.id);
    });
};
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Header Mode Format -->
        <div class="px-5 py-4 bg-gradient-to-r from-purple-50/80 to-indigo-50/50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    <NotionIcon name="stethoscope" size="18" class="text-white" />
                </span>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 leading-snug">
                        {{ schema?.title || 'Audit Ketepatan Pengisian Resep EMR oleh Dokter DPJP' }}
                    </h3>
                    <p class="text-xs text-gray-500">
                        Checklist kepatuhan pengisian aturan pakai obat di Electronic Medical Record (EMR) per DPJP ({{ entities.length }} Dokter).
                    </p>
                </div>
            </div>

            <!-- Toolbar Action & Search -->
            <div class="flex items-center gap-2.5">
                <!-- Search Box -->
                <div class="relative w-48 sm:w-60">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama dokter DPJP..."
                        class="w-full text-xs border-gray-200 rounded-lg pl-7 pr-2.5 py-1.5 focus:ring-purple-500 focus:border-purple-500 bg-white"
                    />
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Tombol Set All 100% (Entri Cepat) -->
                <button
                    v-if="permissions.canEditDaily && !isPeriodApproved"
                    @click="setAllCompliant"
                    class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-semibold rounded-lg transition-colors shadow-2xs whitespace-nowrap"
                    title="Setel semua dokter patuh 100% sesuai jumlah sampel"
                >
                    ⚡ Set Semua 100%
                </button>
            </div>
        </div>

        <!-- Tabel Checklist 34 DPJP -->
        <div class="overflow-x-auto max-h-[620px] overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs border-separate border-spacing-0">
                <thead class="bg-slate-100 sticky top-0 z-10 text-gray-700 uppercase font-bold tracking-wider shadow-xs">
                    <tr>
                        <th class="px-3 py-3 text-center w-12 border-b border-r border-gray-200">No</th>
                        <th class="px-4 py-3 text-left min-w-[200px] border-b border-r border-gray-200">Nama Dokter DPJP</th>
                        <th class="px-3 py-3 text-right w-28 border-b border-r border-gray-200 bg-blue-50/50 text-blue-900">
                            Jml Sampel (D)
                        </th>
                        <th class="px-2.5 py-3 text-right w-24 border-b border-r border-gray-200 text-gray-500 font-medium">Nama Pasien</th>
                        <th class="px-2.5 py-3 text-right w-24 border-b border-r border-gray-200 text-gray-500 font-medium">Tgl Lahir</th>
                        <th class="px-2.5 py-3 text-right w-24 border-b border-r border-gray-200 text-gray-500 font-medium">No. RM</th>
                        <th class="px-2.5 py-3 text-right w-24 border-b border-r border-gray-200 text-gray-500 font-medium">Nama Obat</th>
                        <th class="px-2.5 py-3 text-right w-24 border-b border-r border-gray-200 text-gray-500 font-medium">Dosis Obat</th>
                        <th class="px-3 py-3 text-right w-32 border-b border-r border-gray-200 bg-purple-50/50 text-purple-900 font-bold">
                            Aturan Pakai (N)
                        </th>
                        <th class="px-3 py-3 text-center w-28 border-b border-r border-gray-200">Kepatuhan</th>
                        <th v-if="permissions.canEditDaily && !isPeriodApproved" class="px-2.5 py-3 text-center w-20 border-b border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr
                        v-for="(ent, idx) in filteredEntities"
                        :key="ent.id"
                        class="hover:bg-purple-50/20 transition-colors"
                    >
                        <!-- No Urut -->
                        <td class="px-3 py-2 text-center font-mono text-gray-500 border-r border-gray-100">
                            {{ ent.id || idx + 1 }}
                        </td>

                        <!-- Nama Dokter -->
                        <td class="px-4 py-2 font-semibold text-gray-900 border-r border-gray-100">
                            <div class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                                <span>{{ ent.name }}</span>
                            </div>
                        </td>

                        <!-- Jumlah Sampel (D) -->
                        <td class="px-2.5 py-1.5 border-r border-gray-100 bg-blue-50/20">
                            <div v-if="permissions.isExecutive" class="text-right font-mono font-bold text-gray-900 py-1">
                                {{ (getEnt(ent.id).sample || 0).toLocaleString('id-ID') }}
                            </div>
                            <input
                                v-else
                                type="number"
                                min="0"
                                v-model.number="getEnt(ent.id).sample"
                                @input="onInput"
                                :disabled="!permissions.canEditDaily || isPeriodApproved"
                                class="w-full text-xs font-mono py-1 px-2 text-right border-gray-200 rounded focus:ring-purple-500 focus:border-purple-500 disabled:bg-gray-50 font-semibold"
                                placeholder="0"
                            />
                        </td>

                        <!-- Kolom Auxiliary (Nama Pasien, Tgl Lahir, No. RM, Nama Obat, Dosis) -->
                        <td v-for="colKey in ['pasien', 'tgl_lahir', 'no_rm', 'nama_obat', 'dosis']" :key="colKey" class="px-2 py-1.5 border-r border-gray-100 text-gray-400">
                            <div v-if="permissions.isExecutive" class="text-right font-mono py-1">
                                {{ (getEnt(ent.id)[colKey] || 0) }}
                            </div>
                            <input
                                v-else
                                type="number"
                                min="0"
                                v-model.number="getEnt(ent.id)[colKey]"
                                @input="onInput"
                                :disabled="!permissions.canEditDaily || isPeriodApproved"
                                class="w-full text-[11px] font-mono py-0.5 px-1.5 text-right border-gray-200 rounded focus:ring-purple-500 focus:border-purple-500 disabled:bg-gray-50 text-gray-600"
                                placeholder="0"
                            />
                        </td>

                        <!-- Aturan Pakai (Numerator) -->
                        <td class="px-2.5 py-1.5 border-r border-gray-100 bg-purple-50/20">
                            <div v-if="permissions.isExecutive" class="text-right font-mono font-bold text-purple-700 py-1">
                                {{ (getEnt(ent.id).aturan_pakai || 0).toLocaleString('id-ID') }}
                            </div>
                            <input
                                v-else
                                type="number"
                                min="0"
                                v-model.number="getEnt(ent.id).aturan_pakai"
                                @input="onInput"
                                :disabled="!permissions.canEditDaily || isPeriodApproved"
                                class="w-full text-xs font-mono py-1 px-2 text-right border-gray-200 rounded focus:ring-purple-500 focus:border-purple-500 disabled:bg-gray-50 font-bold text-purple-700"
                                placeholder="0"
                            />
                        </td>

                        <!-- Capaian % Per Dokter -->
                        <td class="px-3 py-2 text-center font-mono font-bold border-r border-gray-100">
                            <span
                                v-if="getEnt(ent.id).sample > 0"
                                :class="[
                                    'px-2 py-0.5 rounded text-[11px] font-bold inline-block',
                                    getEnt(ent.id).aturan_pakai >= getEnt(ent.id).sample
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-rose-100 text-rose-800'
                                ]"
                            >
                                {{ ((getEnt(ent.id).aturan_pakai / getEnt(ent.id).sample) * 100).toFixed(0) }}%
                            </span>
                            <span v-else class="text-gray-300">-</span>
                        </td>

                        <!-- Tombol Quick 100% Row -->
                        <td v-if="permissions.canEditDaily && !isPeriodApproved" class="px-2 py-1.5 text-center">
                            <button
                                @click="setCompliantRow(ent.id)"
                                class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 hover:bg-purple-100 text-gray-700 hover:text-purple-700 transition-colors"
                                title="Samakan Aturan Pakai dengan Sampel"
                            >
                                100%
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- Total Agregat Baris Bawah -->
                <tfoot class="bg-amber-50/80 border-t-2 border-amber-200 font-bold text-xs sticky bottom-0 shadow-lg">
                    <tr>
                        <td colspan="2" class="px-4 py-3 uppercase tracking-wider text-amber-900 border-r border-amber-200">
                            TOTAL REKAPITULASI ({{ entities.length }} DOKTER DPJP)
                        </td>
                        <td class="px-3 py-3 text-right font-mono text-sm text-blue-900 border-r border-amber-200">
                            {{ totals.sample.toLocaleString('id-ID') }}
                        </td>
                        <td colspan="5" class="px-3 py-3 text-center text-gray-400 font-normal italic border-r border-amber-200">
                            Variabel Verifikasi Telaah Resep
                        </td>
                        <td class="px-3 py-3 text-right font-mono text-sm text-purple-900 border-r border-amber-200">
                            {{ totals.aturan_pakai.toLocaleString('id-ID') }}
                        </td>
                        <td class="px-3 py-3 text-center font-mono text-sm border-r border-amber-200">
                            <span class="px-2.5 py-1 rounded bg-purple-700 text-white font-bold">
                                {{ totals.capaian }}%
                            </span>
                        </td>
                        <td v-if="permissions.canEditDaily && !isPeriodApproved" class="px-2 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
