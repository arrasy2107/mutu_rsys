<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    incident: Object,
    units: Array,
    defaultUnitId: [Number, String],
    autoNomor: String,
    isEdit: Boolean,
});

const form = useForm({
    nomor_laporan: props.incident?.nomor_laporan || props.autoNomor,
    tanggal_laporan: props.incident?.tanggal_laporan || new Date().toISOString().split('T')[0],
    tanggal_kejadian: props.incident?.tanggal_kejadian 
        ? props.incident.tanggal_kejadian.replace(' ', 'T').substring(0, 16)
        : new Date().toISOString().substring(0, 16),
    tipe_insiden: props.incident?.tipe_insiden || 'Prosedur Medis / Tindakan Klinis',
    insiden: props.incident?.insiden || '',
    kronologi: props.incident?.kronologi || '',
    jenis_insiden: props.incident?.jenis_insiden || 'KNC',
    grading: props.incident?.grading || 'biru',
    unit_pelapor_id: props.incident?.unit_pelapor_id || props.defaultUnitId || '',
    unit_terkait_id: props.incident?.unit_terkait_id || '',
    tipe_penyelesaian: props.incident?.tipe_penyelesaian || '',
    tanggal_selesai_penyelesaian: props.incident?.tanggal_selesai_penyelesaian || '',
    kriteria_waktu_penyelesaian: props.incident?.kriteria_waktu_penyelesaian || 'dalam_proses',
    rencana_tindak_lanjut: props.incident?.rencana_tindak_lanjut || '',
    penanggung_jawab_rtl: props.incident?.penanggung_jawab_rtl || '',
    status: props.incident?.status || 'dilaporkan',
    bukti_rtl: null,
});

const filePreviewName = ref(props.incident?.bukti_rtl_path ? 'Berkas bukti saat ini tersimpan' : '');

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.bukti_rtl = file;
        filePreviewName.value = file.name;
    }
};

const tipeOptions = [
    'Prosedur Medis / Tindakan Klinis',
    'Medikasi / Obat / Farmasi',
    'Dokumentasi & Rekam Medis',
    'Infeksi Terkait Layanan (HAIs)',
    'Pasien Jatuh / Keselamatan Fisik',
    'Sarana / Prasarana / Alat Medis',
    'Laboratorium & Diagnostik',
    'Gizi / Nutrisi Pasien',
    'Komunikasi & Edukasi',
    'Lain-lain',
];

const jenisDescriptions = {
    Sentinel: 'Kematian tidak terduga atau kehilangan fungsi fisik/psikologis permanen yang tidak terkait riwayat penyakit.',
    KTD: 'Insiden yang mengakibatkan cedera atau luka pada pasien (Kejadian Tidak Diharapkan).',
    KTC: 'Insiden yang terpapar ke pasien tetapi tidak menimbulkan cedera (Kejadian Tidak Cedera).',
    KNC: 'Terjadinya insiden yang belum terpapar ke pasien karena dicegah terlebih dahulu (Kejadian Nyaris Cedera).',
    KPC: 'Kondisi yang sangat berpotensi menimbulkan cedera tetapi belum terjadi insiden (Kondisi Potensial Cedera).',
};

const gradingGuidance = {
    biru: 'Tingkat risiko Rendah (Low) - Investigasi sederhana maksimal 1 minggu oleh atasan langsung/unit.',
    hijau: 'Tingkat risiko Sedang (Moderate) - Investigasi sederhana maksimal 2 minggu oleh unit kerja & komite mutu.',
    kuning: 'Tingkat risiko Tinggi (High) - Wajib Investigasi Komprehensif / RCA (Root Cause Analysis) maks 45 hari.',
    merah: 'Tingkat risiko Ekstrem (Extreme) - Wajib Investigasi Komprehensif / RCA maks 45 hari dan dilaporkan ke Direksi.',
};

const submit = () => {
    if (props.isEdit) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('ikp.update', props.incident.id));
    } else {
        form.post(route('ikp.store'));
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-gradient-to-b from-rose-500 to-red-600 rounded-full inline-block"></span>
                        {{ isEdit ? 'Perbarui Laporan Insiden IKP' : 'Buat Laporan Baru Insiden Keselamatan Pasien' }}
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Format standar pelaporan dan investigasi keselamatan pasien rumah sakit (PMK No. 11/2017)
                    </p>
                </div>
                <Link
                    :href="route('ikp.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition"
                >
                    &larr; Kembali ke Daftar
                </Link>
            </div>
        </template>

        <div class="max-w-5xl mx-auto p-4 sm:p-6 space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- ─── CARD 1: INFORMASI IDENTITAS & WAKTU LAPORAN ──────────────── -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-black">1</span>
                        Identitas & Waktu Kejadian
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Nomor Laporan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.nomor_laporan"
                                class="w-full text-xs font-mono font-bold bg-gray-50 rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                required
                            />
                            <p class="text-[10px] text-gray-400 mt-0.5">Auto-generated format nomor resmi</p>
                            <span v-if="form.errors.nomor_laporan" class="text-xs text-rose-500">{{ form.errors.nomor_laporan }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Tanggal & Waktu Kejadian <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="datetime-local"
                                v-model="form.tanggal_kejadian"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                required
                            />
                            <span v-if="form.errors.tanggal_kejadian" class="text-xs text-rose-500">{{ form.errors.tanggal_kejadian }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Tanggal Lapor Dibuat <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="date"
                                v-model="form.tanggal_laporan"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                required
                            />
                            <span v-if="form.errors.tanggal_laporan" class="text-xs text-rose-500">{{ form.errors.tanggal_laporan }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Unit Pelapor (Tempat Menemukan/Asal) <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.unit_pelapor_id"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                required
                            >
                                <option value="" disabled>-- Pilih Unit Pelapor --</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                            </select>
                            <span v-if="form.errors.unit_pelapor_id" class="text-xs text-rose-500">{{ form.errors.unit_pelapor_id }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Unit Terkait / Lokasi Kejadian (Opsional)
                            </label>
                            <select
                                v-model="form.unit_terkait_id"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            >
                                <option value="">-- Sama dengan Unit Pelapor / Tidak Ada --</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                            </select>
                            <span v-if="form.errors.unit_terkait_id" class="text-xs text-rose-500">{{ form.errors.unit_terkait_id }}</span>
                        </div>
                    </div>
                </div>

                <!-- ─── CARD 2: KLASIFIKASI INSIDEN & GRADING RISIKO ────────────── -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-black">2</span>
                        Klasifikasi & Grading Matriks Risiko
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Tipe Insiden <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.tipe_insiden"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                required
                            >
                                <option v-for="t in tipeOptions" :key="t" :value="t">{{ t }}</option>
                            </select>
                            <span v-if="form.errors.tipe_insiden" class="text-xs text-rose-500">{{ form.errors.tipe_insiden }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Jenis Insiden Keselamatan Pasien <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.jenis_insiden"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                required
                            >
                                <option value="Sentinel">Sentinel (Kematian/Cedera Permanen)</option>
                                <option value="KTD">KTD (Kejadian Tidak Diharapkan)</option>
                                <option value="KTC">KTC (Kejadian Tidak Cedera)</option>
                                <option value="KNC">KNC (Kejadian Nyaris Cedera)</option>
                                <option value="KPC">KPC (Kondisi Potensial Cedera)</option>
                            </select>
                            <p class="text-[11px] text-gray-500 mt-1 italic bg-gray-50 p-2 rounded-lg border border-gray-100">
                                {{ jenisDescriptions[form.jenis_insiden] }}
                            </p>
                            <span v-if="form.errors.jenis_insiden" class="text-xs text-rose-500">{{ form.errors.jenis_insiden }}</span>
                        </div>
                    </div>

                    <!-- Grading Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Grading Risiko Pasien (Matrix Grading) <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <label
                                :class="[
                                    'p-3 rounded-xl border-2 cursor-pointer transition flex flex-col justify-between',
                                    form.grading === 'biru' ? 'border-blue-600 bg-blue-50/70 shadow-xs' : 'border-gray-200 hover:bg-gray-50'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-blue-700">Biru (Low)</span>
                                    <input type="radio" v-model="form.grading" value="biru" class="text-blue-600 focus:ring-blue-500" />
                                </div>
                                <span class="text-[10px] text-gray-500">Dampak minor, probabilitas jarang.</span>
                            </label>

                            <label
                                :class="[
                                    'p-3 rounded-xl border-2 cursor-pointer transition flex flex-col justify-between',
                                    form.grading === 'hijau' ? 'border-emerald-600 bg-emerald-50/70 shadow-xs' : 'border-gray-200 hover:bg-gray-50'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-emerald-700">Hijau (Moderate)</span>
                                    <input type="radio" v-model="form.grading" value="hijau" class="text-emerald-600 focus:ring-emerald-500" />
                                </div>
                                <span class="text-[10px] text-gray-500">Dampak sedang, probabilitas sedang.</span>
                            </label>

                            <label
                                :class="[
                                    'p-3 rounded-xl border-2 cursor-pointer transition flex flex-col justify-between',
                                    form.grading === 'kuning' ? 'border-amber-500 bg-amber-50/70 shadow-xs' : 'border-gray-200 hover:bg-gray-50'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-amber-800">Kuning (High)</span>
                                    <input type="radio" v-model="form.grading" value="kuning" class="text-amber-500 focus:ring-amber-400" />
                                </div>
                                <span class="text-[10px] text-gray-500">Dampak berat, wajib investigasi RCA.</span>
                            </label>

                            <label
                                :class="[
                                    'p-3 rounded-xl border-2 cursor-pointer transition flex flex-col justify-between',
                                    form.grading === 'merah' ? 'border-red-600 bg-red-50/70 shadow-xs' : 'border-gray-200 hover:bg-gray-50'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-red-700">Merah (Extreme)</span>
                                    <input type="radio" v-model="form.grading" value="merah" class="text-red-600 focus:ring-red-500" />
                                </div>
                                <span class="text-[10px] text-gray-500">Dampak katastropik, lapor Direksi & RCA.</span>
                            </label>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-2 bg-amber-50 p-2.5 rounded-xl border border-amber-200">
                            <strong>Panduan Tindak Lanjut:</strong> {{ gradingGuidance[form.grading] }}
                        </p>
                    </div>
                </div>

                <!-- ─── CARD 3: DESKRIPSI & KRONOLOGI INSIDEN ───────────────────── -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-black">3</span>
                        Deskripsi & Kronologi Peristiwa
                    </h2>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Judul / Ringkasan Insiden <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.insiden"
                            placeholder="Contoh: Kesalahan pemberian etiket dosis obat pada pasien rawat inap"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            required
                        />
                        <span v-if="form.errors.insiden" class="text-xs text-rose-500">{{ form.errors.insiden }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Kronologi Lengkap Peristiwa Kejadian <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="form.kronologi"
                            rows="4"
                            placeholder="Jelaskan secara kronologis bagaimana insiden terjadi, faktor penyebab langsung, tindakan penanganan darurat yang langsung dilakukan saat kejadian..."
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            required
                        ></textarea>
                        <span v-if="form.errors.kronologi" class="text-xs text-rose-500">{{ form.errors.kronologi }}</span>
                    </div>
                </div>

                <!-- ─── CARD 4: PENYELESAIAN & RENCANA TINDAK LANJUT (RTL) ──────── -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-black">4</span>
                        Penyelesaian, Investigasi, dan Bukti RTL
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Tipe Penyelesaian
                            </label>
                            <select
                                v-model="form.tipe_penyelesaian"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            >
                                <option value="">-- Belum Ditentukan --</option>
                                <option value="investigasi_sederhana">Investigasi Sederhana</option>
                                <option value="rca">RCA / AAM (Analisis Akar Masalah)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Tanggal Selesai Penyelesaian
                            </label>
                            <input
                                type="date"
                                v-model="form.tanggal_selesai_penyelesaian"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Status Penanganan Laporan
                            </label>
                            <select
                                v-model="form.status"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            >
                                <option value="dilaporkan">Dilaporkan (Menunggu Review)</option>
                                <option value="investigasi">Dalam Proses Investigasi</option>
                                <option value="selesai">Selesai / Closed</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Rencana Tindak Lanjut (RTL) / Rekomendasi
                            </label>
                            <textarea
                                v-model="form.rencana_tindak_lanjut"
                                rows="3"
                                placeholder="Tindakan korektif dan preventif untuk mencegah kejadian serupa terulang..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            ></textarea>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Penanggung Jawab (PJ) RTL
                                </label>
                                <input
                                    type="text"
                                    v-model="form.penanggung_jawab_rtl"
                                    placeholder="Nama Kepala Ruang / PIC Unit"
                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Upload Berkas Bukti RTL (PDF / JPG / PNG, Max 5MB)
                                </label>
                                <input
                                    type="file"
                                    @change="handleFileChange"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100"
                                />
                                <p v-if="filePreviewName" class="text-[11px] text-emerald-600 mt-1 font-medium">
                                    &bull; {{ filePreviewName }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── SUBMIT & ACTION BAR ────────────────────────────────────── -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('ikp.index')"
                        class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-xs font-semibold hover:bg-gray-50 transition"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white text-xs font-bold shadow-md shadow-rose-500/25 transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan Laporan' : 'Kirim Laporan IKP') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

