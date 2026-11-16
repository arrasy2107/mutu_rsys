<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    sub: String,
    rentang: String,
    tahun: Number,
    bulan: Number,
    supervisiDataImut: Array,
    trenKepatuhanInput: Array,
    supervisiRtl: Array,
    kpiData: Object,
    filters: Object,
});

const currentSub = ref(props.sub || 'data_imut');
const currentBulan = ref(props.bulan || 9);
const currentRentang = ref(props.rentang || 'bulanan');

const switchSub = (s) => {
    currentSub.value = s;
    router.get(route('supervisi.index'), {
        sub: s,
        tahun: props.tahun,
        bulan: currentBulan.value,
        rentang: currentRentang.value,
    }, { preserveState: true });
};

const changeMonth = () => {
    router.get(route('supervisi.index'), {
        sub: currentSub.value,
        tahun: props.tahun,
        bulan: currentBulan.value,
        rentang: currentRentang.value,
    }, { preserveState: true });
};

const months = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

// SVG points for compliance trend
const chartTrendPoints = computed(() => {
    if (!props.trenKepatuhanInput) return '';
    const data = props.trenKepatuhanInput.filter(d => d.rate !== null);
    if (!data.length) return '';
    const stepX = 680 / (data.length > 1 ? data.length - 1 : 1);

    return data
        .map((d, idx) => {
            const x = 50 + idx * stepX;
            const y = 160 - ((d.rate - 80) / 20) * 120; // Scale 80% - 100%
            return `${x},${y}`;
        })
        .join(' ');
});
</script>

<template>
    <AppLayout>
        <Head title="Supervisi Mutu Tingkat RS" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-xs font-semibold uppercase tracking-wider backdrop-blur-sm mb-2">
                            <span>🔍 Butir B.7 Roadmap Mutu</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Tingkat Rumah Sakit</span>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">Supervisi Mutu Rumah Sakit</h1>
                        <p class="text-blue-100 text-sm mt-1 max-w-2xl">
                            Monitoring kepatuhan jadwal pengisian data mutu harian oleh unit kerja dan pengawasan realisasi rencana tindak lanjut (RTL) secara berkala.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sub-Menu Switcher (Butir B.7.a & B.7.b Roadmap) -->
            <div class="bg-white rounded-xl p-2 border border-gray-200 shadow-sm flex gap-2">
                <button
                    @click="switchSub('data_imut')"
                    :class="[
                        'flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        currentSub === 'data_imut' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>📊 a. Supervisi Data IMUT (Kepatuhan Input)</span>
                </button>
                <button
                    @click="switchSub('rtl')"
                    :class="[
                        'flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2',
                        currentSub === 'rtl' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    <span>📋 b. Supervisi Rencana Tindak Lanjut (RTL)</span>
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Kepatuhan Input Global</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ kpiData.kepatuhan_global }}%</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                        📈
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Lengkap Input</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1">{{ kpiData.unit_lengkap }} / {{ kpiData.total_unit }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                        🏥
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Cut-Off Pengisian</p>
                        <p class="text-lg font-bold text-gray-900 mt-1">Tgl 5 Tiap Bulan</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg">
                        ⏰
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Item RTL Terfasilitasi</p>
                        <p class="text-2xl font-bold text-indigo-600 mt-1">{{ kpiData.total_rtl_aktif }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg">
                        🎯
                    </div>
                </div>
            </div>

            <!-- SUB-MENU A: SUPERVISI DATA IMUT -->
            <div v-if="currentSub === 'data_imut'" class="space-y-6">
                <!-- Trend Graphic -->
                <div class="bg-white rounded-xl p-6 border border-gray-200/80 shadow-sm space-y-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Grafik Kepatuhan Input Data IMUT Seluruh Unit (Tahun {{ tahun }})</h3>
                            <p class="text-xs text-gray-500">Persentase pengisian tally sheet sebelum tanggal cut-off bulanan</p>
                        </div>
                    </div>

                    <!-- SVG Trend Line Chart -->
                    <div class="w-full overflow-x-auto">
                        <svg viewBox="0 0 760 190" class="w-full h-44 select-none">
                            <line x1="50" y1="20" x2="730" y2="20" stroke="#f1f5f9" stroke-width="1" />
                            <line x1="50" y1="50" x2="730" y2="50" stroke="#f1f5f9" stroke-width="1" />
                            <line x1="50" y1="80" x2="730" y2="80" stroke="#f1f5f9" stroke-width="1" />
                            <line x1="50" y1="110" x2="730" y2="110" stroke="#f1f5f9" stroke-width="1" />
                            <line x1="50" y1="140" x2="730" y2="140" stroke="#cbd5e1" stroke-width="1" />

                            <text x="35" y="24" class="text-[9px] fill-gray-400 font-mono" text-anchor="end">100%</text>
                            <text x="35" y="84" class="text-[9px] fill-gray-400 font-mono" text-anchor="end">90%</text>
                            <text x="35" y="144" class="text-[9px] fill-gray-400 font-mono" text-anchor="end">80%</text>

                            <!-- Line -->
                            <polyline
                                v-if="chartTrendPoints"
                                fill="none"
                                stroke="#10b981"
                                stroke-width="2.5"
                                :points="chartTrendPoints"
                            />

                            <!-- Points -->
                            <g v-for="(p, idx) in trenKepatuhanInput" :key="idx">
                                <g v-if="p.rate !== null">
                                    <circle
                                        :cx="50 + idx * (680 / (trenKepatuhanInput.filter(d => d.rate !== null).length > 1 ? trenKepatuhanInput.filter(d => d.rate !== null).length - 1 : 1))"
                                        :cy="160 - ((p.rate - 80) / 20) * 120"
                                        r="4"
                                        fill="#10b981"
                                        stroke="#ffffff"
                                        stroke-width="2"
                                    />
                                    <text
                                        :x="50 + idx * (680 / (trenKepatuhanInput.filter(d => d.rate !== null).length > 1 ? trenKepatuhanInput.filter(d => d.rate !== null).length - 1 : 1))"
                                        :y="160 - ((p.rate - 80) / 20) * 120 - 7"
                                        text-anchor="middle"
                                        class="text-[9px] font-bold fill-gray-800"
                                    >
                                        {{ p.rate }}%
                                    </text>
                                </g>
                                <text
                                    :x="50 + idx * (680 / 11)"
                                    y="160"
                                    text-anchor="middle"
                                    class="text-[9px] fill-gray-500 font-medium"
                                >
                                    {{ p.bulan }}
                                </text>
                            </g>
                        </svg>
                    </div>
                </div>

                <!-- Unit Compliance Table -->
                <div class="bg-white rounded-xl border border-gray-200/80 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50/70">
                        <div>
                            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
                                Kepatuhan Pengisian Data Mutu per Unit Kerja
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Evaluasi kelengkapan pengisian form pengumpul data bulanan</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-xs font-medium text-gray-600">Pilih Bulan:</label>
                            <select v-model="currentBulan" @change="changeMonth" class="text-xs font-semibold rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }} {{ tahun }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 text-gray-600 font-semibold uppercase tracking-wider border-b">
                                <tr>
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4 min-w-[220px]">Unit / Instalasi Kerja</th>
                                    <th class="py-3 px-4 text-center">Wajib Input</th>
                                    <th class="py-3 px-4 text-center">Sudah Diisi</th>
                                    <th class="py-3 px-4 text-center">Persentase</th>
                                    <th class="py-3 px-4 text-center">Status Kelengkapan</th>
                                    <th class="py-3 px-4 min-w-[180px]">Ketepatan Waktu Input</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(u, idx) in supervisiDataImut" :key="u.unit_id" class="hover:bg-blue-50/40">
                                    <td class="py-3 px-4 text-center text-gray-400 font-mono">{{ idx + 1 }}</td>
                                    <td class="py-3 px-4">
                                        <p class="font-bold text-gray-900">{{ u.unit_nama }}</p>
                                        <span class="text-[10px] text-gray-400 font-mono">{{ u.unit_kode }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-semibold">{{ u.target_ind }} Indikator</td>
                                    <td class="py-3 px-4 text-center font-mono font-bold text-blue-700">{{ u.terisi_ind }} Indikator</td>
                                    <td class="py-3 px-4 text-center font-extrabold font-mono">
                                        <span :class="u.persen >= 100 ? 'text-emerald-600' : (u.persen >= 50 ? 'text-amber-600' : 'text-red-600')">
                                            {{ u.persen }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span v-if="u.status_input === 'lengkap'" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            Lengkap (100%)
                                        </span>
                                        <span v-else-if="u.status_input === 'sebagian'" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Belum Lengkap
                                        </span>
                                        <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                            Belum Mengisi
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 text-[11px]">
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-medium">
                                            ✓ {{ u.tgl_terakhir }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SUB-MENU B: SUPERVISI RTL -->
            <div v-if="currentSub === 'rtl'" class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/70">
                        <div>
                            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
                                Monitoring Realisasi RTL & Ketersediaan Berkas Bukti Dukung
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Pemantauan penyelesaian tindak lanjut perbaikan mutu unit kerja</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 text-gray-600 font-semibold uppercase tracking-wider border-b">
                                <tr>
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4 min-w-[220px]">Unit / Instalasi Kerja</th>
                                    <th class="py-3 px-4 text-center">Total Item RTL</th>
                                    <th class="py-3 px-4 text-center">Selesai & Terverifikasi</th>
                                    <th class="py-3 px-4 text-center">Sedang Berjalan</th>
                                    <th class="py-3 px-4 text-center">Bukti Terlampir</th>
                                    <th class="py-3 px-4 text-center">Persentase Selesai</th>
                                    <th class="py-3 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(r, idx) in supervisiRtl" :key="r.unit_id" class="hover:bg-blue-50/40">
                                    <td class="py-3 px-4 text-center text-gray-400 font-mono">{{ idx + 1 }}</td>
                                    <td class="py-3 px-4 font-bold text-gray-900">{{ r.unit_nama }}</td>
                                    <td class="py-3 px-4 text-center font-mono font-semibold">{{ r.total_rtl }}</td>
                                    <td class="py-3 px-4 text-center font-mono font-bold text-emerald-600">{{ r.selesai }}</td>
                                    <td class="py-3 px-4 text-center font-mono text-amber-600">{{ r.proses }}</td>
                                    <td class="py-3 px-4 text-center font-mono text-indigo-600">{{ r.ada_bukti }}</td>
                                    <td class="py-3 px-4 text-center font-extrabold font-mono text-blue-700">
                                        {{ r.persen_selesai }}%
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <Link :href="route('hospital.rtl.index', { unit_id: r.unit_id })" class="text-blue-600 hover:text-blue-800 font-semibold text-xs">
                                            Periksa RTL Unit →
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
