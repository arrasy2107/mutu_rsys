<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ units: Array, prefill: Object });

const form = useForm({
    kode: '',
    judul: props.prefill?.judul || '',
    jenis: props.prefill?.jenis ? [props.prefill.jenis] : (props.prefill?.judul ? ['IMP-U'] : []),
    dasar_pemikiran: props.prefill?.dasar_pemikiran || '',
    dimensi_mutu: [],
    tujuan: '',
    definisi_operasional: '',
    satuan_pengukuran: 'Persentase',
    kriteria_inklusi: '',
    kriteria_eksklusi: '',
    formula: '',
    cara_pengumpulan: '',
    jenis_indikator: ['Proses'],
    target_capaian: 85,
    target_operator: '>=',
    frekuensi: 'Bulanan',
    sumber_data: '',
    penanggung_jawab: props.prefill?.penanggung_jawab || '',
    validator: '',
    pengumpul_data: '',
    unit_id: props.prefill?.unit_id ? (Number(props.prefill.unit_id) || props.prefill.unit_id) : '',
    is_active: true,
    numerator_keterangan: '',
    denominator_keterangan: '',
    metode_pengumpulan: '',
    instrumen_pengambilan: '',
    besar_sampel: '',
    cara_pengambilan_sampel: '',
    periode_analisis: '',
    publikasi_data: '',
    form_layout_type: 'dynamic_rows',
});

const submit = () => form.post(route('indicators.store'));

const jenisOptions = ['IMN', 'IMP-RS', 'IMP-U', 'IMU', 'SPM'];
const jenisIndikatorOptions = ['Input', 'Proses', 'Output', 'Outcome'];
const dimensiOptions = ['Efektif', 'Keselamatan', 'Berorientasi pada pasien/ pelayanan', 'Tepat Waktu', 'Efisien', 'Adil', 'Terintegrasi'];
const satuanOptions = [
    { value: 'Persentase', label: 'Persentase (%)' },
    { value: 'Permil', label: 'Permil (‰)' },
    { value: 'Indeks', label: 'Indeks' },
];
const frekuensiOptions = ['Bulanan', 'Semester', 'Tahunan'];
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('indicators.index')" class="hover:text-indigo-600">Profil Indikator Mutu</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Tambah Baru</span>
            </nav>
        </template>

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Profil Indikator Baru</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Isi formulir kamus indikator mutu sesuai standar KARS</p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Identitas -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kode Indikator <span class="text-rose-500">*</span></label>
                            <input v-model="form.kode" type="text" placeholder="cth: INM-01" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            <p v-if="form.errors.kode" class="text-xs text-rose-500 mt-1">{{ form.errors.kode }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Judul Indikator <span class="text-rose-500">*</span></label>
                            <input v-model="form.judul" type="text" placeholder="Nama lengkap indikator" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            <p v-if="form.errors.judul" class="text-xs text-rose-500 mt-1">{{ form.errors.judul }}</p>
                        </div>
                    </div>

                    <!-- Jenis, Tipe Indikator & Dimensi Mutu -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4 bg-gray-50/70 rounded-xl border border-gray-100">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Jenis Indikator Mutu <span class="text-rose-500">*</span></label>
                            <div class="flex flex-wrap gap-2.5">
                                <label v-for="j in jenisOptions" :key="j" class="inline-flex items-center gap-1.5 text-xs text-gray-700 bg-white px-2.5 py-1.5 rounded-lg border border-gray-200 cursor-pointer hover:border-indigo-300">
                                    <input type="checkbox" v-model="form.jenis" :value="j" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                                    {{ j }}
                                </label>
                            </div>
                            <p v-if="form.errors.jenis" class="text-xs text-rose-500 mt-1">{{ form.errors.jenis }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Jenis / Tipe Indikator <span class="text-rose-500">*</span></label>
                            <div class="flex flex-wrap gap-2.5">
                                <label v-for="ji in jenisIndikatorOptions" :key="ji" class="inline-flex items-center gap-1.5 text-xs text-gray-700 bg-white px-2.5 py-1.5 rounded-lg border border-gray-200 cursor-pointer hover:border-indigo-300">
                                    <input type="checkbox" v-model="form.jenis_indikator" :value="ji" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                                    {{ ji }}
                                </label>
                            </div>
                            <p v-if="form.errors.jenis_indikator" class="text-xs text-rose-500 mt-1">{{ form.errors.jenis_indikator }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Dimensi Mutu <span class="text-rose-500">*</span></label>
                            <div class="flex flex-wrap gap-2">
                                <label v-for="d in dimensiOptions" :key="d" class="inline-flex items-center gap-1.5 text-xs text-gray-700 bg-white px-2 py-1.5 rounded-lg border border-gray-200 cursor-pointer hover:border-indigo-300">
                                    <input type="checkbox" v-model="form.dimensi_mutu" :value="d" class="w-3.5 h-3.5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                                    {{ d }}
                                </label>
                            </div>
                            <p v-if="form.errors.dimensi_mutu" class="text-xs text-rose-500 mt-1">{{ form.errors.dimensi_mutu }}</p>
                        </div>
                    </div>

                    <!-- Dasar Pemikiran & Tujuan -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Dasar Pemikiran</label>
                            <textarea v-model="form.dasar_pemikiran" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Latar belakang mengapa indikator ini diperlukan..." />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tujuan</label>
                            <textarea v-model="form.tujuan" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Tujuan pengukuran indikator ini..." />
                        </div>
                    </div>

                    <!-- Definisi Operasional -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Definisi Operasional</label>
                        <textarea v-model="form.definisi_operasional" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Batasan pengertian yang dijadikan pedoman..." />
                    </div>

                    <!-- Kriteria -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kriteria Inklusi</label>
                            <textarea v-model="form.kriteria_inklusi" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kriteria Eksklusi</label>
                            <textarea v-model="form.kriteria_eksklusi" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                    </div>

                    <!-- Numerator & Denominator -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Numerator (Pembilang)</label>
                            <textarea v-model="form.numerator_keterangan" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Keterangan jumlah subjek yang ingin diukur..." />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Denominator (Penyebut)</label>
                            <textarea v-model="form.denominator_keterangan" rows="2" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Semua peluang yang ingin diukur..." />
                        </div>
                    </div>

                    <!-- Formula -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Formula Perhitungan</label>
                        <input v-model="form.formula" type="text" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="cth: (Numerator / Denominator) × 100%" />
                    </div>

                    <!-- Metodologi -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Metode Pengumpulan Data</label>
                            <input v-model="form.metode_pengumpulan" type="text" placeholder="cth: Retrospektif" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Instrumen Pengambilan Data</label>
                            <input v-model="form.instrumen_pengambilan" type="text" placeholder="cth: Formulir kepatuhan..." class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Besar Sampel</label>
                            <input v-model="form.besar_sampel" type="text" placeholder="cth: Total populasi" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Cara Pengambilan Sampel</label>
                            <input v-model="form.cara_pengambilan_sampel" type="text" placeholder="cth: Probability Sampling" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Periode Analisis dan Pelaporan</label>
                            <input v-model="form.periode_analisis" type="text" placeholder="cth: Bulanan, Tahunan" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                    </div>

                    <!-- Target & Parameter Pengukuran -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                        <div>
                            <label class="block text-xs font-semibold text-indigo-800 mb-1.5">Target Capaian <span class="text-rose-500">*</span></label>
                            <input v-model.number="form.target_capaian" type="number" min="0" step="0.01" class="w-full text-sm border-indigo-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white" />
                            <p v-if="form.errors.target_capaian" class="text-xs text-rose-500 mt-1">{{ form.errors.target_capaian }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-indigo-800 mb-1.5">Operator <span class="text-rose-500">*</span></label>
                            <select v-model="form.target_operator" class="w-full text-sm border-indigo-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option value=">=">≥ (Minimal / Lebih dari atau sama dengan)</option>
                                <option value="<=">≤ (Maksimal / Kurang dari atau sama dengan)</option>
                                <option value="=">= (Sama Dengan)</option>
                                <option value=">">> (Lebih Dari)</option>
                                <option value="<">< (Kurang Dari)</option>
                            </select>
                            <p v-if="form.errors.target_operator" class="text-xs text-rose-500 mt-1">{{ form.errors.target_operator }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-indigo-800 mb-1.5">Satuan <span class="text-rose-500">*</span></label>
                            <select v-model="form.satuan_pengukuran" class="w-full text-sm border-indigo-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option v-for="opt in satuanOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <p v-if="form.errors.satuan_pengukuran" class="text-xs text-rose-500 mt-1">{{ form.errors.satuan_pengukuran }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-indigo-800 mb-1.5">Periode Pengumpulan Data <span class="text-rose-500">*</span></label>
                            <select v-model="form.frekuensi" class="w-full text-sm border-indigo-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option v-for="freq in frekuensiOptions" :key="freq" :value="freq">{{ freq }}</option>
                            </select>
                            <p v-if="form.errors.frekuensi" class="text-xs text-rose-500 mt-1">{{ form.errors.frekuensi }}</p>
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-xs font-semibold text-indigo-800 mb-1.5">Sumber Data</label>
                            <input v-model="form.sumber_data" type="text" placeholder="cth: Rekam Medis, SIMRS" class="w-full text-sm border-indigo-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white" />
                            <p v-if="form.errors.sumber_data" class="text-xs text-rose-500 mt-1">{{ form.errors.sumber_data }}</p>
                        </div>
                    </div>

                    <!-- Unit & PJ -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Unit Spesifik (kosongkan = semua)</label>
                            <select v-model="form.unit_id" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Semua Unit</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.kode }} – {{ u.nama }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Publikasi Data</label>
                            <input v-model="form.publikasi_data" type="text" placeholder="cth: Internal RS, mutufasyankes" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                    </div>

                    <!-- Petugas & Validator -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Penanggung Jawab Data</label>
                            <input v-model="form.penanggung_jawab" type="text" placeholder="Nama / Jabatan" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Pengumpul Data</label>
                            <input v-model="form.pengumpul_data" type="text" placeholder="Nama / Jabatan" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Validator</label>
                            <input v-model="form.validator" type="text" placeholder="Nama / Jabatan" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                    </div>

                    <!-- Aktif -->
                    <div class="flex items-center gap-3">
                        <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                        <label for="is_active" class="text-sm text-gray-700">Indikator aktif (termasuk dalam pengumpulan data)</label>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <a :href="route('indicators.index')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-200 rounded-lg hover:bg-gray-50">
                            Batal
                        </a>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Indikator' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
