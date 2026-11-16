<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ pdsa: Object, indicators: Array, units: Array });

const form = useForm({
    masalah:         props.pdsa.masalah        || '',
    analisa_penyebab_manusia: props.pdsa.analisa_penyebab_manusia || '',
    analisa_penyebab_metode: props.pdsa.analisa_penyebab_metode || '',
    analisa_penyebab_alat: props.pdsa.analisa_penyebab_alat || '',
    analisa_penyebab_material: props.pdsa.analisa_penyebab_material || '',
    analisa_penyebab_lingkungan: props.pdsa.analisa_penyebab_lingkungan || '',
    akar_masalah:    props.pdsa.akar_masalah   || '',
    rekomendasi_tindak_lanjut: props.pdsa.rekomendasi_tindak_lanjut || '',
    pj_rtl:          props.pdsa.pj_rtl         || '',
    bukti_rtl:       props.pdsa.bukti_rtl      || '',
    plan:            props.pdsa.plan           || '',
    do_action:       props.pdsa.do_action      || '',
    study:           props.pdsa.study          || '',
    act:             props.pdsa.act            || '',
    rencana_selesai: props.pdsa.rencana_selesai ? props.pdsa.rencana_selesai.substring(0, 10) : '',
});

const submit = () => form.put(route('pdsa.update', props.pdsa.id));
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('pdsa.index')" class="hover:text-indigo-600">PDSA</Link>
                <span>/</span>
                <Link :href="route('pdsa.show', pdsa.id)" class="hover:text-indigo-600">Detail</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Edit</span>
            </nav>
        </template>

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">
                        Edit {{ pdsa.jenis_tindak_lanjut === 'rtl' ? 'Rencana Tindak Lanjut (RTL)' : 'Analisis PDSA' }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ pdsa.indicator?.kode }} — {{ pdsa.indicator?.judul }}
                    </p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Capaian info readonly -->
                    <div class="p-4 bg-rose-50 rounded-lg border border-rose-100 flex gap-6">
                        <div class="text-center">
                            <p class="text-xs text-rose-600 font-medium">Capaian Aktual</p>
                            <p class="text-2xl font-bold text-rose-700">{{ pdsa.capaian_aktual }}%</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-600 font-medium">Target</p>
                            <p class="text-2xl font-bold text-gray-700">{{ pdsa.target_capaian }}%</p>
                        </div>
                        <div class="ml-auto">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Target Selesai</label>
                            <input v-model="form.rencana_selesai" type="date" class="text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                    </div>

                    <!-- Identifikasi Masalah -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Identifikasi Masalah *</label>
                        <textarea v-model="form.masalah" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        <p v-if="form.errors.masalah" class="text-xs text-rose-500 mt-1">{{ form.errors.masalah }}</p>
                    </div>

                    <!-- Analisa 5M -->
                    <div class="bg-gray-50/50 rounded-xl border border-gray-200 p-5 space-y-4">
                        <h3 class="text-sm font-semibold text-indigo-900 border-b border-gray-200 pb-2">Analisa Penyebab Masalah (Fishbone / 5M)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Manusia (Man)</label>
                                <textarea v-model="form.analisa_penyebab_manusia" rows="2" class="w-full text-sm border-gray-200 rounded-lg" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Metode (Method)</label>
                                <textarea v-model="form.analisa_penyebab_metode" rows="2" class="w-full text-sm border-gray-200 rounded-lg" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Alat/Mesin (Machine)</label>
                                <textarea v-model="form.analisa_penyebab_alat" rows="2" class="w-full text-sm border-gray-200 rounded-lg" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Material (Material)</label>
                                <textarea v-model="form.analisa_penyebab_material" rows="2" class="w-full text-sm border-gray-200 rounded-lg" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Faktor Lingkungan (Milieu)</label>
                                <textarea v-model="form.analisa_penyebab_lingkungan" rows="2" class="w-full text-sm border-gray-200 rounded-lg" />
                            </div>
                        </div>
                    </div>

                    <!-- Akar Masalah & RTL -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Akar Masalah Utama</label>
                            <textarea v-model="form.akar_masalah" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div v-if="pdsa.jenis_tindak_lanjut === 'rtl'">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Rekomendasi Tindak Lanjut (RTL)</label>
                            <textarea v-model="form.rekomendasi_tindak_lanjut" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                    </div>

                    <!-- PJ RTL & Bukti RTL -->
                    <div v-if="pdsa.jenis_tindak_lanjut === 'rtl'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Penanggung Jawab RTL (PJ RTL)</label>
                            <input v-model="form.pj_rtl" type="text" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Ka. Instalasi Farmasi, PJ Rawat Jalan" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Bukti Dukung RTL</label>
                            <input v-model="form.bukti_rtl" type="text" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Notulen rapat, foto trolley akrilik, kontrol sheet" />
                        </div>
                    </div>

                    <!-- 4 PDSA Phases -->
                    <div v-if="pdsa.jenis_tindak_lanjut !== 'rtl'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
                            <label class="block text-xs font-bold text-blue-700 mb-1.5">📋 PLAN</label>
                            <textarea v-model="form.plan" rows="3" class="w-full text-sm border-blue-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white" />
                        </div>
                        <div class="bg-indigo-50 rounded-lg border border-indigo-200 p-4">
                            <label class="block text-xs font-bold text-indigo-700 mb-1.5">⚙️ DO</label>
                            <textarea v-model="form.do_action" rows="3" class="w-full text-sm border-indigo-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white" />
                        </div>
                        <div class="bg-purple-50 rounded-lg border border-purple-200 p-4">
                            <label class="block text-xs font-bold text-purple-700 mb-1.5">🔍 STUDY</label>
                            <textarea v-model="form.study" rows="3" class="w-full text-sm border-purple-200 rounded-lg focus:ring-purple-500 focus:border-purple-500 bg-white" />
                        </div>
                        <div class="bg-emerald-50 rounded-lg border border-emerald-200 p-4">
                            <label class="block text-xs font-bold text-emerald-700 mb-1.5">✅ ACT</label>
                            <textarea v-model="form.act" rows="3" class="w-full text-sm border-emerald-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 bg-white" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('pdsa.show', pdsa.id)" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Batal</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : `Perbarui ${pdsa.jenis_tindak_lanjut === 'rtl' ? 'RTL' : 'PDSA'}` }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
