<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, router } from '@inertiajs/vue3';

const props = defineProps({ pdsa: Object });

const submitForm = useForm({ catatan_review: '' });

const review = async () => {
    const confirmed = await window.notify.confirm({
        title: 'Sahkan Telaah PDSA/RTL?',
        text: 'Apakah Komite Mutu yakin ingin mengesahkan dan menyelesaikan review siklus peningkatan mutu ini?',
        icon: 'question',
        confirmButtonText: 'Ya, Sahkan Telaah',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669',
    });
    if (confirmed) {
        submitForm.post(route('pdsa.review', props.pdsa.id), {
            onSuccess: () => {
                window.notify?.toast?.success('Review Disahkan', 'Siklus PDSA telah disahkan oleh Komite Mutu.');
            }
        });
    }
};

const submit = async () => {
    const jenisTindakLanjut = props.pdsa.jenis_tindak_lanjut === 'rtl' ? 'RTL' : 'PDSA';
    const confirmed = await window.notify.confirm({
        title: `Ajukan Dokumen ${jenisTindakLanjut}?`,
        text: `Dokumen ${jenisTindakLanjut} ini akan dikirimkan kepada Komite Mutu untuk ditelaah.`,
        icon: 'question',
        confirmButtonText: 'Ya, Ajukan Review',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2563eb',
    });
    if (confirmed) {
        router.post(route('pdsa.submit', props.pdsa.id), {
            onSuccess: () => {
                window.notify?.toast?.success('Pengajuan Berhasil', `Dokumen ${jenisTindakLanjut} berhasil diajukan untuk review.`);
            }
        });
    }
};

const deletePdsa = async () => {
    const confirmed = await window.notify.dangerConfirm({
        title: 'Hapus Dokumen PDSA?',
        text: 'Apakah Anda yakin ingin menghapus dokumen PDSA ini? Tindakan ini tidak dapat dibatalkan.',
        confirmButtonText: 'Ya, Hapus Dokumen',
    });
    if (confirmed) {
        router.delete(route('pdsa.destroy', props.pdsa.id), {
            onSuccess: () => {
                window.notify?.toast?.success('Dokumen Dihapus', 'Dokumen PDSA telah berhasil dihapus.');
            }
        });
    }
};

const pdsaPhases = [
    { key: 'plan', label: 'Plan', icon: '📋', desc: 'Rencana Tindakan', color: 'bg-blue-50 border-blue-200' },
    { key: 'do_action', label: 'Do', icon: '⚙️', desc: 'Pelaksanaan', color: 'bg-indigo-50 border-indigo-200' },
    { key: 'study', label: 'Study', icon: '🔍', desc: 'Evaluasi Hasil', color: 'bg-purple-50 border-purple-200' },
    { key: 'act', label: 'Act', icon: '✅', desc: 'Tindak Lanjut', color: 'bg-emerald-50 border-emerald-200' },
];
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('pdsa.index')" class="hover:text-indigo-600">PDSA</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Detail</span>
            </nav>
        </template>

        <div class="max-w-4xl mx-auto space-y-5">
            <!-- Header Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-bold text-indigo-700">{{ pdsa.indicator?.kode }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ pdsa.jenis_tindak_lanjut === 'rtl' ? 'RTL' : 'PDSA' }}
                            </span>
                        </div>
                        <h1 class="text-lg font-bold text-gray-900 mt-1">{{ pdsa.indicator?.judul }}</h1>
                        <p class="text-sm text-gray-500 mt-1">Unit: <strong>{{ pdsa.unit?.nama }}</strong></p>
                    </div>
                    <div class="text-right">
                        <span :class="['px-3 py-1.5 rounded-full text-sm font-semibold', {
                            'bg-gray-100 text-gray-600': pdsa.status === 'draft',
                            'bg-blue-100 text-blue-700': pdsa.status === 'submitted',
                            'bg-emerald-100 text-emerald-700': pdsa.status === 'reviewed',
                        }]">
                            {{ { draft: 'Draft', submitted: 'Menunggu Review', reviewed: 'Selesai' }[pdsa.status] }}
                        </span>
                    </div>
                </div>

                <!-- Capaian Gap -->
                <div class="mt-4 flex items-center gap-6 p-4 bg-rose-50 rounded-lg border border-rose-100">
                    <div class="text-center">
                        <p class="text-xs text-rose-600 font-medium">Capaian Aktual</p>
                        <p class="text-2xl font-bold text-rose-700">{{ pdsa.capaian_aktual }}%</p>
                    </div>
                    <div class="text-2xl text-gray-300">→</div>
                    <div class="text-center">
                        <p class="text-xs text-gray-600 font-medium">Target</p>
                        <p class="text-2xl font-bold text-gray-700">{{ pdsa.target_capaian }}%</p>
                    </div>
                    <div class="text-2xl text-gray-300">|</div>
                    <div class="text-center">
                        <p class="text-xs text-gray-600 font-medium">Gap</p>
                        <p class="text-2xl font-bold text-rose-600">
                            {{ Math.abs(parseFloat(pdsa.target_capaian) - parseFloat(pdsa.capaian_aktual)).toFixed(1) }}%
                        </p>
                    </div>
                    <div v-if="pdsa.rencana_selesai" class="text-center ml-auto">
                        <p class="text-xs text-gray-600 font-medium">Target Selesai</p>
                        <p class="text-sm font-bold text-gray-700">{{ new Date(pdsa.rencana_selesai).toLocaleDateString('id-ID', { dateStyle: 'medium' }) }}</p>
                    </div>
                </div>

                <!-- Masalah & Akar -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">🔴 Masalah</p>
                        <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 border border-gray-100">{{ pdsa.masalah }}</p>
                    </div>
                    <div v-if="pdsa.akar_masalah">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">🔍 Akar Masalah Utama</p>
                        <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 border border-gray-100">{{ pdsa.akar_masalah }}</p>
                    </div>
                </div>

                <!-- 5M Analisa -->
                <div class="mt-4 bg-gray-50/50 rounded-xl border border-gray-200 p-5" v-if="pdsa.analisa_penyebab_manusia || pdsa.analisa_penyebab_metode || pdsa.analisa_penyebab_alat || pdsa.analisa_penyebab_material || pdsa.analisa_penyebab_lingkungan || pdsa.rekomendasi_tindak_lanjut">
                    <h3 class="text-sm font-semibold text-indigo-900 border-b border-gray-200 pb-2 mb-4">Analisa Penyebab Masalah (Fishbone / 5M)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="pdsa.analisa_penyebab_manusia">
                            <p class="text-xs font-semibold text-gray-500 mb-1">Faktor Manusia (Man)</p>
                            <p class="text-sm text-gray-700">{{ pdsa.analisa_penyebab_manusia }}</p>
                        </div>
                        <div v-if="pdsa.analisa_penyebab_metode">
                            <p class="text-xs font-semibold text-gray-500 mb-1">Faktor Metode (Method)</p>
                            <p class="text-sm text-gray-700">{{ pdsa.analisa_penyebab_metode }}</p>
                        </div>
                        <div v-if="pdsa.analisa_penyebab_alat">
                            <p class="text-xs font-semibold text-gray-500 mb-1">Faktor Alat/Mesin (Machine)</p>
                            <p class="text-sm text-gray-700">{{ pdsa.analisa_penyebab_alat }}</p>
                        </div>
                        <div v-if="pdsa.analisa_penyebab_material">
                            <p class="text-xs font-semibold text-gray-500 mb-1">Faktor Material (Material)</p>
                            <p class="text-sm text-gray-700">{{ pdsa.analisa_penyebab_material }}</p>
                        </div>
                        <div v-if="pdsa.analisa_penyebab_lingkungan" class="md:col-span-2">
                            <p class="text-xs font-semibold text-gray-500 mb-1">Faktor Lingkungan (Milieu)</p>
                            <p class="text-sm text-gray-700">{{ pdsa.analisa_penyebab_lingkungan }}</p>
                        </div>
                    </div>
                    <div v-if="pdsa.rekomendasi_tindak_lanjut" class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Rekomendasi Tindak Lanjut (RTL)</p>
                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg border border-gray-100 font-medium">{{ pdsa.rekomendasi_tindak_lanjut }}</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 pt-3 border-t border-gray-100">
                            <div>
                                <span class="text-xs font-semibold text-slate-500 block mb-0.5">Penanggung Jawab RTL:</span>
                                <p class="text-sm font-bold text-slate-800">{{ pdsa.pj_rtl || 'Ka. Unit / PJ Mutu' }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-500 block mb-0.5">Bukti Dukung RTL:</span>
                                <p class="text-sm text-slate-700 italic">{{ pdsa.bukti_rtl || 'Dokumen / Notulen / Foto' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PDSA 4 Phases -->
            <div v-if="pdsa.jenis_tindak_lanjut !== 'rtl'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="phase in pdsaPhases" :key="phase.key" :class="['bg-white rounded-xl border shadow-sm p-5', phase.color]">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xl">{{ phase.icon }}</span>
                        <div>
                            <p class="text-sm font-bold text-gray-800">{{ phase.label }}</p>
                            <p class="text-xs text-gray-500">{{ phase.desc }}</p>
                        </div>
                    </div>
                    <p v-if="pdsa[phase.key]" class="text-sm text-gray-700 leading-relaxed">{{ pdsa[phase.key] }}</p>
                    <p v-else class="text-sm text-gray-400 italic">Belum diisi</p>
                </div>
            </div>

            <!-- Catatan Review -->
            <div v-if="pdsa.catatan_review || pdsa.reviewed_at" class="bg-emerald-50 rounded-xl border border-emerald-200 p-5">
                <div class="flex items-center gap-2 mb-2">
                    <p class="text-sm font-semibold text-emerald-700">✅ Catatan Review</p>
                    <p class="text-xs text-emerald-600">oleh {{ pdsa.reviewed_by?.name }} · {{ new Date(pdsa.reviewed_at).toLocaleDateString('id-ID') }}</p>
                </div>
                <p class="text-sm text-emerald-800">{{ pdsa.catatan_review || '(tidak ada catatan)' }}</p>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-wrap gap-3">
                <Link :href="route('pdsa.edit', pdsa.id)" v-if="pdsa.status === 'draft'" class="px-4 py-2 bg-amber-50 text-amber-700 border border-amber-200 text-sm font-medium rounded-lg hover:bg-amber-100 flex items-center gap-1.5">
                    <span>✏️</span> Edit {{ pdsa.jenis_tindak_lanjut === 'rtl' ? 'RTL' : 'PDSA' }}
                </Link>
                <button v-if="pdsa.status === 'draft'" @click="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 flex items-center gap-1.5">
                    <span>📤</span> Ajukan untuk Review
                </button>
                <button v-if="pdsa.status === 'draft'" @click="deletePdsa" class="px-4 py-2 bg-rose-50 text-rose-700 border border-rose-200 text-sm font-medium rounded-lg hover:bg-rose-100 ml-auto flex items-center gap-1.5">
                    <span>🗑️</span> Hapus {{ pdsa.jenis_tindak_lanjut === 'rtl' ? 'RTL' : 'PDSA' }}
                </button>

                <!-- Review form (untuk Komite Mutu) -->
                <div v-if="pdsa.status === 'submitted'" class="w-full space-y-3">
                    <p class="text-sm font-semibold text-gray-700">Review PDSA:</p>
                    <textarea v-model="submitForm.catatan_review" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Catatan review (opsional)..." />
                    <button @click="review" :disabled="submitForm.processing" class="px-6 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                        ✅ Selesaikan Review
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
