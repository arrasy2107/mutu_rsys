<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({ 
    indicators: Array, 
    units: Array,
    prefill: Object,
    jenisTindakLanjut: String,
});

const form = useForm({
    jenis_tindak_lanjut: props.jenisTindakLanjut || 'pdsa',
    indicator_id: props.prefill?.indicator_id || '',
    unit_id: props.prefill?.unit_id || '',
    period_id: props.prefill?.period_id || '',
    capaian_aktual: props.prefill?.capaian_aktual || '',
    target_capaian: props.prefill?.target_capaian || '',
    masalah: props.prefill?.masalah || '',
    analisa_penyebab_manusia: '',
    analisa_penyebab_metode: '',
    analisa_penyebab_alat: '',
    analisa_penyebab_material: '',
    analisa_penyebab_lingkungan: '',
    akar_masalah: '',
    rekomendasi_tindak_lanjut: '',
    pj_rtl: '',
    bukti_rtl: '',
    plan: '',
    do_action: '',
    study: '',
    act: '',
    rencana_selesai: '',
});

// Auto-fill target and unit when indicator selected
const onIndicatorChange = () => {
    const ind = props.indicators.find(i => i.id == form.indicator_id);
    if (ind) {
        if (!form.target_capaian) form.target_capaian = ind.target_capaian;
        if (!form.unit_id && ind.unit_id) form.unit_id = ind.unit_id;
    }
};

onMounted(() => {
    if (form.indicator_id && !form.target_capaian) {
        onIndicatorChange();
    }
});

const submit = () => form.post(route('pdsa.store'));
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('pdsa.index')" class="hover:text-indigo-600">RTL & PDSA</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">{{ form.jenis_tindak_lanjut.toUpperCase() }}</span>
            </nav>
        </template>

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">
                        {{ form.jenis_tindak_lanjut === 'rtl' ? 'Buat Rencana Tindak Lanjut (RTL)' : 'Buat Analisis PDSA' }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ form.jenis_tindak_lanjut === 'rtl' ? 'Rencana perbaikan untuk capaian yang belum memenuhi target' : 'Plan-Do-Study-Act setelah capaian meningkat dan memenuhi target' }}
                    </p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Indikator & Unit -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Indikator *</label>
                            <select v-model="form.indicator_id" @change="onIndicatorChange" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Pilih Indikator --</option>
                                <option v-for="ind in indicators" :key="ind.id" :value="ind.id">{{ ind.kode }} — {{ ind.judul }}</option>
                            </select>
                            <p v-if="form.errors.indicator_id" class="text-xs text-rose-500 mt-1">{{ form.errors.indicator_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Unit *</label>
                            <select v-model="form.unit_id" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Pilih Unit --</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                            </select>
                            <p v-if="form.errors.unit_id" class="text-xs text-rose-500 mt-1">{{ form.errors.unit_id }}</p>
                        </div>
                    </div>

                    <!-- Capaian vs Target -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 p-4 bg-rose-50 rounded-lg border border-rose-100">
                        <div>
                            <label class="block text-xs font-semibold text-rose-700 mb-1.5">Capaian Aktual (%)</label>
                            <input v-model="form.capaian_aktual" type="number" step="0.01" min="0" max="100" class="w-full text-sm border-rose-200 rounded-lg focus:ring-rose-500 focus:border-rose-500 bg-white" placeholder="0.00" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Target Capaian (%)</label>
                            <input v-model="form.target_capaian" type="number" step="0.01" min="0" max="100" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white" placeholder="Auto" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Target Selesai</label>
                            <input v-model="form.rencana_selesai" type="date" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white" />
                        </div>
                    </div>

                    <!-- Masalah & Akar -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Identifikasi Masalah *</label>
                            <textarea v-model="form.masalah" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Uraikan masalah atau ketidaktercapaian target mutu..." />
                            <p v-if="form.errors.masalah" class="text-xs text-rose-500 mt-1">{{ form.errors.masalah }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Analisis Akar Masalah</label>
                            <textarea v-model="form.akar_masalah" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Akar masalah utama (5 Why / Fishbone summary)..." />
                        </div>
                    </div>

                    <!-- Analisa 5M (Fishbone Diagram) -->
                    <div class="bg-gray-50/50 rounded-xl border border-gray-200 p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-200 pb-2">
                            <h3 class="text-sm font-semibold text-indigo-900">Analisa Penyebab Masalah (Fishbone / 5M)</h3>
                            <span class="text-[11px] text-gray-500">Struktur & Proses Masalah</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Manusia (Man / SDM)</label>
                                <textarea v-model="form.analisa_penyebab_manusia" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Kurang kepatuhan, kelelahan, kurang pelatihan..." />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Metode (Method / SPO)</label>
                                <textarea v-model="form.analisa_penyebab_metode" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Alur SPO belum jelas, belum tersosialisasi..." />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Alat/Mesin (Machine / IT)</label>
                                <textarea v-model="form.analisa_penyebab_alat" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="SIMRS lambat, alat ukur belum terkalibrasi..." />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Material (Material / Obat / Formulir)</label>
                                <textarea v-model="form.analisa_penyebab_material" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Stok kosong, format lembar habis..." />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Lingkungan (Milieu / Sarana)</label>
                                <textarea v-model="form.analisa_penyebab_lingkungan" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Kondisi ruang tunggu, antrean padat, pencahayaan..." />
                            </div>
                        </div>
                    </div>

                    <!-- Rekomendasi Tindak Lanjut (RTL) -->
                    <div v-if="form.jenis_tindak_lanjut === 'rtl'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Rekomendasi Tindak Lanjut (RTL) *</label>
                            <textarea v-model="form.rekomendasi_tindak_lanjut" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Rencana aksi rekomendasi bagi kepala unit dan pimpinan..." />
                            <p v-if="form.errors.rekomendasi_tindak_lanjut" class="text-xs text-rose-500 mt-1">{{ form.errors.rekomendasi_tindak_lanjut }}</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Penanggung Jawab RTL (PJ RTL)</label>
                                <input v-model="form.pj_rtl" type="text" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Ka. Instalasi Farmasi, PJ Rawat Jalan" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Bukti Dukung RTL</label>
                                <input v-model="form.bukti_rtl" type="text" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Notulen rapat, foto trolley akrilik, kontrol sheet" />
                            </div>
                        </div>
                    </div>

                    <!-- 4 PDSA Phases -->
                    <div v-if="form.jenis_tindak_lanjut === 'pdsa'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
                            <label class="block text-xs font-bold text-blue-700 mb-1.5">📋 PLAN — Rencana Tindakan</label>
                            <textarea v-model="form.plan" rows="3" class="w-full text-sm border-blue-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Apa rencana perbaikan yang akan dilakukan?" />
                        </div>
                        <div class="bg-indigo-50 rounded-lg border border-indigo-200 p-4">
                            <label class="block text-xs font-bold text-indigo-700 mb-1.5">⚙️ DO — Pelaksanaan</label>
                            <textarea v-model="form.do_action" rows="3" class="w-full text-sm border-indigo-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white" placeholder="Langkah nyata apa yang diterapkan?" />
                        </div>
                        <div class="bg-purple-50 rounded-lg border border-purple-200 p-4">
                            <label class="block text-xs font-bold text-purple-700 mb-1.5">🔍 STUDY — Evaluasi Hasil</label>
                            <textarea v-model="form.study" rows="3" class="w-full text-sm border-purple-200 rounded-lg focus:ring-purple-500 focus:border-purple-500 bg-white" placeholder="Bagaimana hasil observasi setelah tindakan?" />
                        </div>
                        <div class="bg-emerald-50 rounded-lg border border-emerald-200 p-4">
                            <label class="block text-xs font-bold text-emerald-700 mb-1.5">✅ ACT — Tindak Lanjut & Standarisasi</label>
                            <textarea v-model="form.act" rows="3" class="w-full text-sm border-emerald-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 bg-white" placeholder="Apakah diadopsi sebagai SPO standar baru atau siklus ulang?" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('pdsa.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Batal</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Menyimpan...' : `Simpan ${form.jenis_tindak_lanjut.toUpperCase()}` }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
