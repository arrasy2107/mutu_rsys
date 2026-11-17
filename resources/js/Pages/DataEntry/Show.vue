<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import NotionIcon from '@/Components/NotionIcon.vue';

const props = defineProps({ entry: Object });

const submitForm = useForm({});
const doSubmit = () => submitForm.post(route('data-entry.submit', props.entry.id));

const statusColors = {
    draft:     'bg-gray-100 text-gray-600',
    submitted: 'bg-blue-100 text-blue-700',
    approved:  'bg-emerald-100 text-emerald-700',
    exempt:    'bg-teal-100 text-teal-700',
    rejected:  'bg-rose-100 text-rose-700',
    revision:  'bg-amber-100 text-amber-700',
};
const statusLabels = {
    draft: 'Draft', submitted: 'Menunggu Validasi', approved: 'Disetujui', exempt: 'Bebas Validasi', rejected: 'Ditolak', revision: 'Perlu Revisi',
};

const isTargetMet = (cap, target, op) => op === '>=' ? parseFloat(cap) >= parseFloat(target) : parseFloat(cap) <= parseFloat(target);
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('data-entry.index')" class="hover:text-indigo-600">Entri Data</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Detail</span>
            </nav>
        </template>

        <div class="max-w-3xl mx-auto space-y-5">
            <!-- Indikator Summary -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs font-mono font-bold text-indigo-700">{{ entry.indicator?.kode }}</span>
                        <h1 class="text-lg font-bold text-gray-900 mt-1">{{ entry.indicator?.judul }}</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Unit: <strong>{{ entry.unit?.nama }}</strong> ·
                            Periode: <strong>Bln {{ entry.period?.bulan }}/{{ entry.period?.tahun }}</strong>
                        </p>
                    </div>
                    <span :class="['px-3 py-1.5 rounded-full text-sm font-semibold', statusColors[entry.status]]">
                        {{ statusLabels[entry.status] }}
                    </span>
                </div>
            </div>

            <!-- Data Numerator/Denominator/Capaian -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Data Pengukuran</h2>
                <div class="grid grid-cols-3 gap-6 text-center">
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Numerator</p>
                        <p class="text-4xl font-bold text-gray-800 mt-2">{{ entry.numerator }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Denominator</p>
                        <p class="text-4xl font-bold text-gray-800 mt-2">{{ entry.denominator }}</p>
                    </div>
                    <div :class="['rounded-xl p-3 border-2', isTargetMet(entry.capaian, entry.indicator?.target_capaian, entry.indicator?.target_operator) ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200']">
                        <p class="text-xs font-medium" :class="isTargetMet(entry.capaian, entry.indicator?.target_capaian, entry.indicator?.target_operator) ? 'text-emerald-600' : 'text-rose-600'">Capaian</p>
                        <p class="text-4xl font-bold mt-2" :class="isTargetMet(entry.capaian, entry.indicator?.target_capaian, entry.indicator?.target_operator) ? 'text-emerald-700' : 'text-rose-700'">
                            {{ entry.capaian }}
                            <span class="text-xl font-medium">{{ entry.indicator?.satuan_pengukuran === 'Persentase' ? '%' : (entry.indicator?.satuan_pengukuran === 'Permil' ? '‰' : entry.indicator?.satuan_pengukuran) }}</span>
                        </p>
                        <p class="text-xs mt-1" :class="isTargetMet(entry.capaian, entry.indicator?.target_capaian, entry.indicator?.target_operator) ? 'text-emerald-500' : 'text-rose-500'">
                            Target: {{ entry.indicator?.target_operator === '>=' ? '≥' : '≤' }} {{ entry.indicator?.target_capaian }}
                            <span class="text-[10px]">{{ entry.indicator?.satuan_pengukuran === 'Persentase' ? '%' : (entry.indicator?.satuan_pengukuran === 'Permil' ? '‰' : entry.indicator?.satuan_pengukuran) }}</span>
                        </p>
                    </div>
                </div>

                <div v-if="entry.tanggal" class="mt-4 text-sm text-gray-500">
                    Tanggal: <strong>{{ new Date(entry.tanggal).toLocaleDateString('id-ID', { dateStyle: 'long' }) }}</strong>
                </div>

                <div v-if="entry.indicator?.frekuensi === 'bulanan' && entry.log_harian && Object.keys(entry.log_harian).length > 0" class="mt-6 border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-xs font-semibold text-gray-700">Tally Sheet / Log Harian</h3>
                    </div>
                    <div class="max-h-60 overflow-y-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-white sticky top-0 border-b border-gray-200 z-10 text-xs text-gray-500">
                                <tr>
                                    <th class="px-4 py-2 text-center w-20">Tgl</th>
                                    <th class="px-4 py-2">Numerator (Pembilang)</th>
                                    <th class="px-4 py-2">Denominator (Penyebut)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="(data, day) in entry.log_harian" :key="day" class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-center font-mono text-gray-500">{{ day }}</td>
                                    <td class="px-4 py-2 font-mono text-gray-700">{{ data.n || '-' }}</td>
                                    <td class="px-4 py-2 font-mono text-gray-700">{{ data.d || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="entry.catatan" class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-xs font-medium text-gray-600 mb-1">Catatan:</p>
                    <p class="text-sm text-gray-700">{{ entry.catatan }}</p>
                </div>

                <div v-if="entry.file_bukti" class="mt-4">
                    <a
                        :href="route('evidence.show', { type: 'data-entry', record: entry.id })"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-2 text-sm text-indigo-700 hover:bg-indigo-100"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        Lihat File Bukti
                    </a>
                </div>
            </div>

            <!-- Validasi History -->
            <div v-if="entry.validations?.length" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Riwayat Validasi</h2>
                <div class="space-y-3">
                    <div v-for="v in entry.validations" :key="v.id" class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 border border-gray-100">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0', v.status === 'approved' ? 'bg-emerald-500' : v.status === 'rejected' ? 'bg-rose-500' : 'bg-amber-500']">
                            {{ (v.actor?.name || v.validator?.name || '-').charAt(0) }}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">
                                {{ v.actor?.name ? `Diubah oleh ${v.actor.name}` : v.validator?.name }}
                            </p>
                            <p class="text-xs text-gray-400">{{ new Date(v.tanggal_validasi).toLocaleDateString('id-ID') }}</p>
                            <p v-if="v.catatan_validator" class="text-sm text-gray-600 mt-1">{{ v.catatan_validator }}</p>
                        </div>
                        <span :class="['text-xs px-2 py-1 rounded-full font-medium', statusColors[v.status]]">
                            {{ statusLabels[v.status] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-2xs p-4 flex gap-2">
                <Link v-if="['draft', 'revision'].includes(entry.status)" :href="route('data-entry.edit', entry.id)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-700 border border-slate-200 text-xs font-medium rounded-lg hover:bg-slate-50 shadow-2xs">
                    <NotionIcon name="edit" size="13" class="text-slate-500" />
                    <span>Edit Data</span>
                </Link>
                <button v-if="['draft', 'revision'].includes(entry.status)" @click="doSubmit" :disabled="submitForm.processing"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 text-white text-xs font-medium rounded-lg hover:bg-slate-900 disabled:opacity-50 shadow-2xs">
                    <NotionIcon name="send" size="13" />
                    <span>Ajukan Validasi</span>
                </button>
            </div>
        </div>
    </AppLayout>
</template>
