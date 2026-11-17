<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import NotionIcon from '@/Components/NotionIcon.vue';

const props = defineProps({
    entry: Object,
    validation: Object,
    dailyRecords: Array,
    historical: Array,
    indicatorValidationStatus: Object,
    permissions: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const bulanLabel = computed(() => bulanNames[props.entry.period?.bulan || 1] || '-');
const tahunLabel = computed(() => props.entry.period?.tahun || 2026);

// Cek apakah indikator ini merupakan Indikator Mutu Prioritas Unit (IMP-U)
const isPrioritasUnit = computed(() => {
    const kode = props.entry.indicator?.kode || '';
    const jenis = props.entry.indicator?.jenis || [];
    const jenisArr = Array.isArray(jenis) ? jenis : [jenis];
    return kode.toUpperCase().includes('IMP-U') || jenisArr.some(j => typeof j === 'string' && (j.toUpperCase().includes('IMP-U') || j.toLowerCase().includes('prioritas unit')));
});

// Operator & Target Logic
const isTargetMet = computed(() => {
    const cap = parseFloat(props.validation?.unit_capaian ?? props.entry.capaian ?? 0);
    const target = parseFloat(props.entry.indicator?.target_capaian || 0);
    const op = props.entry.indicator?.target_operator || '>=';
    if (op === '>=') return cap >= target;
    if (op === '<=') return cap <= target;
    return cap === target;
});

// State Mode Edit Administrator
const isEditing = ref(false);

// Modal states
const showRejectModal = ref(false);
const showRevisionModal = ref(false);
const showSampleTableModal = ref(false);
const showExportMenu = ref(false);

// Snapshot data pengumpul (Unit)
const unitNum = computed(() => parseFloat(props.validation?.unit_numerator ?? props.entry.numerator ?? 0));
const unitDen = computed(() => parseFloat(props.validation?.unit_denominator ?? props.entry.denominator ?? 0));
const unitCap = computed(() => parseFloat(props.validation?.unit_capaian ?? props.entry.capaian ?? 0));
const validationResultIsValid = computed(() => props.validation?.is_valid
    ?? ((props.validation?.tingkat_kesesuaian ?? 100) >= 90));

// Rekomendasi Ukuran Sampel Berdasarkan Tabel 3. Besar Sampel Validasi (Standar Kemenkes / SNARS)
const getSampleRecommendation = (populasi) => {
    const n = Math.max(0, parseInt(populasi) || 0);
    if (n <= 0) return { pengumpul: 0, validator: 0, valRule: 'Populasi 0' };

    // 1. Petugas Pengumpul Data
    let pengumpul = 0;
    if (n >= 640) pengumpul = 128;
    else if (n >= 320) pengumpul = Math.round(n * 0.20);
    else if (n >= 64) pengumpul = 64;
    else pengumpul = n;

    // 2. Petugas Validasi Data
    let validator = 0;
    let valRule = '';
    if (n >= 480) {
        validator = 48;
        valRule = 'Populasi ≥ 480 → 48 sampel';
    } else if (n >= 161) {
        validator = 48;
        valRule = 'Populasi 161–480 → 48 sampel';
    } else if (n >= 17) {
        validator = Math.max(16, Math.round(n * 0.10));
        valRule = 'Populasi 17–160 → Minimal 16 sampel atau 10% populasi';
    } else {
        validator = n;
        valRule = 'Populasi 1–16 → 100% populasi';
    }

    return { populasi: n, pengumpul, validator, valRule };
};

const sampleRec = computed(() => getSampleRecommendation(unitDen.value));

// Dropdown Options Sesuai Panduan Dokumen
const justifikasiOptions = [
    'Wajib validasi awal tahun (Imut Prioritas Unit)',
    'Imut baru',
    'Data akan dipublikasikan',
    'Perubahan indikator dari sebelumnya (profil, instrumen, petugas pengumpul data, dll)',
    'Data indikator berubah tanpa dapat dijelaskan',
    'Sumber data berubah',
    'Subjek pengumpulan data berubah',
    'Perubahan sumber data dari berkas rekam medis ke eMR',
    'Audit Penjaminan Mutu Berkala',
    'Lainnya (Tulis manual)',
];

const metodeOptions = [
    'Measurement Result Agreement / Kesepakatan pengukuran berdasarkan hasil',
    'Measurement Result Agreement',
    'Independent Re-abstraction (Abstraksi Ulang Independen)',
    'Audit Sampel Rekam Medis / EMR Unit',
    'Observasi Langsung Pelaksanaan Layanan',
];

// Form Uji Petik Validasi
const approveForm = useForm({
    catatan: props.validation?.catatan_validator || props.entry?.latest_validation?.catatan_validator || '',
    jumlah_sampel_validasi: props.validation?.jumlah_sampel_validasi || props.entry?.latest_validation?.jumlah_sampel_validasi || sampleRec.value.validator || unitDen.value || 35,
    justifikasi_perlu_validasi: props.validation?.justifikasi_perlu_validasi || props.entry?.latest_validation?.justifikasi_perlu_validasi || (isPrioritasUnit.value ? 'Wajib validasi awal tahun (Imut Prioritas Unit)' : 'Imut baru'),
    metode_validasi: props.validation?.metode_validasi || props.entry?.latest_validation?.metode_validasi || 'Measurement Result Agreement / Kesepakatan pengukuran berdasarkan hasil',
    validator_num: props.validation?.validator_numerator !== null && props.validation?.validator_numerator !== undefined 
        ? props.validation.validator_numerator 
        : (props.entry?.latest_validation?.validator_numerator ?? unitNum.value),
    validator_den: props.validation?.validator_denominator !== null && props.validation?.validator_denominator !== undefined 
        ? props.validation.validator_denominator 
        : (props.entry?.latest_validation?.validator_denominator ?? (sampleRec.value.validator || unitDen.value)),
    hasil_validasi: '',
    hasil_analisa: '',
    kesimpulan: '',
    rencana_tindak_lanjut: props.validation?.rencana_tindak_lanjut || props.entry?.latest_validation?.rencana_tindak_lanjut || 'Lanjutkan pengukuran indicator mutu sesuai profil indikator',
    lampiran_file: null,
});

// Apakah Form Audit Sedang Terbuka & Aktif Diinput/Diedit
const isAuditFormActive = computed(() => {
    return (props.permissions?.canValidate && props.entry.status === 'submitted') || (props.permissions?.canEditValidation && isEditing.value);
});

// Validator user info & role helper
const validatorUser = computed(() => props.entry.latest_validation?.validator);
const isValidatorKomite = computed(() => validatorUser.value?.role === 'komite_mutu');
const isValidatorAdmin = computed(() => validatorUser.value?.role === 'superadmin');

// Sinkronisasi nilai form saat mulai atau batalkan edit
const initFormValues = () => {
    const v = props.validation || props.entry?.latest_validation || {};
    approveForm.catatan = v.catatan_validator || '';
    approveForm.jumlah_sampel_validasi = v.jumlah_sampel_validasi || sampleRec.value.validator || unitDen.value || 35;
    approveForm.justifikasi_perlu_validasi = v.justifikasi_perlu_validasi || (isPrioritasUnit.value ? 'Wajib validasi awal tahun (Imut Prioritas Unit)' : 'Imut baru');
    approveForm.metode_validasi = v.metode_validasi || 'Measurement Result Agreement / Kesepakatan pengukuran berdasarkan hasil';
    approveForm.validator_num = v.validator_numerator !== null && v.validator_numerator !== undefined ? v.validator_numerator : unitNum.value;
    approveForm.validator_den = v.validator_denominator !== null && v.validator_denominator !== undefined ? v.validator_denominator : (sampleRec.value.validator || unitDen.value);
    approveForm.rencana_tindak_lanjut = v.rencana_tindak_lanjut || 'Lanjutkan pengukuran indicator mutu sesuai profil indikator';
    updateAutomatedTexts();
};

const startEdit = () => {
    initFormValues();
    isEditing.value = true;
};

const cancelEdit = () => {
    initFormValues();
    isEditing.value = false;
};

// Terapkan Rekomendasi Tabel 3
const applySampleRecommendation = () => {
    const rec = sampleRec.value;
    if (rec.validator > 0) {
        approveForm.jumlah_sampel_validasi = rec.validator;
        approveForm.validator_den = rec.validator;
        if (approveForm.validator_num > rec.validator) {
            approveForm.validator_num = Math.round((unitNum.value / (unitDen.value || 1)) * rec.validator);
        }
        updateAutomatedTexts();
        window.notify?.toast?.info('Rekomendasi Diterapkan', `Sampel validator disesuaikan dengan Tabel 3: ${rec.validator} sampel (${rec.valRule}).`);
    }
};

// Salin data pengumpul sebagai baseline uji petik validator (100% Sensus)
const copyUnitBaseline = () => {
    approveForm.validator_num = unitNum.value;
    approveForm.validator_den = unitDen.value;
    approveForm.jumlah_sampel_validasi = unitDen.value;
    updateAutomatedTexts();
    window.notify?.toast?.info('Data Disalin', 'Data Form Pengumpul disalin sebagai baseline uji petik.');
};

// Perhitungan Otomatis Uji Petik
const validatorCapaian = computed(() => {
    const d = parseFloat(approveForm.validator_den);
    const n = parseFloat(approveForm.validator_num);
    if (!d || d <= 0) return 0;
    return Math.round((n / d) * 10000) / 100;
});

const deltaNumerator = computed(() => {
    return (parseFloat(approveForm.validator_num) || 0) - unitNum.value;
});

const deltaDenominator = computed(() => {
    return (parseFloat(approveForm.validator_den) || 0) - unitDen.value;
});

const deltaCapaian = computed(() => {
    return Math.round((validatorCapaian.value - unitCap.value) * 100) / 100;
});

// Formula Kesesuaian: (Capaian yang kecil / yang besar) x 100%
const tingkatKesesuaian = computed(() => {
    const uCap = unitCap.value;
    const valCap = validatorCapaian.value;
    if (uCap === valCap) return 100;
    const maxVal = Math.max(uCap, valCap);
    const minVal = Math.min(uCap, valCap);
    if (maxVal <= 0) return 100;
    const agreement = (minVal / maxVal) * 100;
    return Math.round(agreement * 100) / 100;
});

const isValid = computed(() => {
    return tingkatKesesuaian.value >= 90;
});

// Sinkronkan text otomatis saat angka uji petik berubah (Persis standar dokumen panduan)
const updateAutomatedTexts = () => {
    const nVal = Number(approveForm.validator_num).toLocaleString('id-ID');
    const dVal = Number(approveForm.validator_den).toLocaleString('id-ID');
    const capVal = validatorCapaian.value;
    const uCap = unitCap.value;
    const agreeVal = tingkatKesesuaian.value;

    const minCap = Math.min(capVal, uCap);
    const maxCap = Math.max(capVal, uCap);

    // Format dokumen: 89% (31/35 x 100%)
    approveForm.hasil_validasi = `${capVal}% (${nVal}/${dVal} x 100%)`;

    // Format dokumen: 89 / 89 x 100% = 100% ----> 90% (standar) atau (60/75) x 100% = 80%
    approveForm.hasil_analisa = `${minCap} / ${maxCap} x 100% = ${agreeVal}% ----> 90% (standar ${agreeVal >= 90 ? 'terpenuhi' : 'tidak terpenuhi'})`;
    
    // Format dokumen: Data bulan [Bulan] [Tahun] valid / tidak valid
    if (isValid.value) {
        approveForm.kesimpulan = `Data bulan ${bulanLabel.value} ${tahunLabel.value} valid`;
        approveForm.rencana_tindak_lanjut = 'Lanjutkan pengukuran indicator mutu sesuai profil indikator';
    } else {
        approveForm.kesimpulan = `Data bulan ${bulanLabel.value} ${tahunLabel.value} tidak valid`;
        approveForm.rencana_tindak_lanjut = 'Samakan persepsi dan pilih bulan yang datanya siap untuk validasi ulang pada tahun yang sama';
    }
};

watch([() => approveForm.validator_num, () => approveForm.validator_den], () => {
    updateAutomatedTexts();
}, { immediate: true });

// Actions
const submitApprove = async () => {
    updateAutomatedTexts();

    let warningGateHtml = '';
    if (!isValid.value) {
        warningGateHtml = `
            <div class="mt-3 p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-900 text-xs text-left">
                <div class="font-bold text-rose-700 mb-1">
                    PERINGATAN AKREDITASI (Kesesuaian &lt; 90%)
                </div>
                Standar validasi rumah sakit mensyaratkan tingkat kesesuaian minimal <b>90%</b> agar data dapat dinyatakan valid. Deviasi data saat ini melebihi ambang batas toleransi. Jika ditetapkan tidak valid, unit <b>${props.entry.unit?.nama}</b> dapat memilih bulan mana pun yang datanya siap untuk mengajukan ulang pada tahun yang sama.
            </div>
        `;
    }

    const isEditMode = isEditing.value;
    const isInternalUnit = props.permissions?.isStaffOfThisUnit && !props.permissions?.isQualityCommittee && !props.permissions?.isAdmin;

    const dialogTitle = isEditMode 
        ? 'Konfirmasi Perubahan Validasi' 
        : (isInternalUnit ? 'Konfirmasi Pengesahan Internal Unit' : 'Konfirmasi Pengesahan Validasi');

    const confirmed = await window.notify.confirm({
        title: dialogTitle,
        html: `
            <div class="space-y-2 text-xs text-slate-600 text-left">
                <p>${isEditMode ? 'Menyimpan pembaruan data validasi oleh Administrator' : (isInternalUnit ? 'Persetujuan validasi data internal unit oleh PIC / Kepala Unit' : 'Pengesahan data indikator mutu')} untuk <b>[${props.entry.indicator?.kode}] ${props.entry.indicator?.nama || props.entry.indicator?.judul}</b> unit <b>${props.entry.unit?.nama}</b> periode <b>${bulanLabel.value} ${tahunLabel.value}</b>.</p>
                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 grid grid-cols-3 gap-2 text-center my-2">
                    <div><span class="text-slate-400 block text-[10px]">Capaian Unit</span><span class="font-bold text-slate-800">${unitCap.value}%</span></div>
                    <div><span class="text-slate-400 block text-[10px]">Data Validasi</span><span class="font-bold text-slate-800">${validatorCapaian.value}%</span></div>
                    <div><span class="text-slate-400 block text-[10px]">Kesesuaian</span><span class="font-bold ${isValid.value ? 'text-emerald-600' : 'text-rose-600'}">${tingkatKesesuaian.value}%</span></div>
                </div>
                ${warningGateHtml}
            </div>
        `,
        icon: isValid.value ? 'question' : 'warning',
        confirmButtonText: isEditMode ? 'Ya, Simpan Perubahan' : (isInternalUnit ? 'Ya, Sahkan Validasi Internal' : 'Ya, Sahkan & Setujui'),
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669',
    });

    if (confirmed) {
        approveForm.post(route('validation.approve', props.entry.id), {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
                window.notify?.toast?.success(isEditMode ? 'Data Validasi Diperbarui' : 'Validasi Disahkan', 'Data mutu berhasil disimpan.');
            }
        });
    }
};

const rejectForm = useForm({
    catatan: '',
    validator_num: 0,
    validator_den: 0,
});
const submitReject = () => {
    if (!rejectForm.catatan.trim()) {
        window.notify?.alert?.warning('Alasan Diperlukan', 'Harap isi alasan penolakan data mutu secara lengkap.');
        return;
    }
    rejectForm.validator_num = approveForm.validator_num;
    rejectForm.validator_den = approveForm.validator_den;
    rejectForm.post(route('validation.reject', props.entry.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            window.notify?.toast?.info('Pengajuan Ditolak', 'Unit dapat mengajukan ulang dengan memilih bulan yang datanya siap pada tahun yang sama.');
        },
    });
};

const revisionForm = useForm({ catatan: '' });
const submitRevision = () => {
    if (!revisionForm.catatan.trim()) {
        window.notify?.alert?.warning('Catatan Diperlukan', 'Harap isi rincian catatan perbaikan yang wajib direvisi oleh unit kerja.');
        return;
    }
    revisionForm.post(route('validation.revision', props.entry.id), {
        onSuccess: () => {
            showRevisionModal.value = false;
            window.notify?.toast?.warning('Revisi Diminta', 'Formulir telah dikembalikan ke unit kerja untuk diperbaiki.');
        },
    });
};

const showDailyRecords = ref(false);

const printValidationSheet = () => {
    window.print();
};
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500">
                <Link :href="route('validation.index')" class="hover:text-slate-900 transition-colors flex items-center gap-1.5">
                    <NotionIcon name="shield" size="13" class="text-slate-500" />
                    <span>Validasi Data</span>
                </Link>
                <span>/</span>
                <span class="text-slate-400">{{ entry.unit?.nama }}</span>
                <span>/</span>
                <span class="text-slate-700 font-semibold">[{{ entry.indicator?.kode }}] Lembar Validasi</span>
            </nav>
        </template>

        <div class="max-w-5xl mx-auto space-y-3.5">
            <!-- ─── Action Bar / Compact Header ─── -->
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <Link
                        :href="route('validation.index')"
                        class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 transition-colors shrink-0 mt-0.5"
                        title="Kembali ke Daftar Validasi"
                    >
                        <NotionIcon name="chevron-left" size="15" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded font-mono font-bold text-xs bg-slate-100 text-slate-800 border border-slate-200">
                                {{ entry.indicator?.kode }}
                            </span>
                            <span :class="['px-2 py-0.5 rounded-md text-[11px] font-semibold border', {
                                'bg-amber-50 text-amber-800 border-amber-200': entry.status === 'submitted',
                                'bg-emerald-50 text-emerald-800 border-emerald-200': entry.status === 'approved' && validationResultIsValid,
                                'bg-rose-50 text-rose-800 border-rose-200': entry.status === 'approved' && !validationResultIsValid,
                                'bg-slate-100 text-slate-700 border-slate-200': entry.status === 'revision',
                                'bg-rose-50 text-rose-800 border-rose-200': entry.status === 'rejected',
                            }]">
                                {{ entry.status === 'approved' ? (validationResultIsValid ? 'VALID' : 'TIDAK VALID') : { submitted: 'Menunggu Pengesahan', rejected: 'Ditolak Administrator', revision: 'Perlu Revisi', draft: 'Draft' }[entry.status] }}
                            </span>
                            <span
                                v-if="isPrioritasUnit"
                                class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200"
                            >
                                IMP-U • Wajib Awal Tahun
                            </span>
                            <span
                                v-else
                                class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200"
                            >
                                Indikator Reguler
                            </span>
                        </div>
                        <h1 class="text-sm sm:text-base font-bold text-slate-900 mt-1 leading-snug">
                            {{ entry.indicator?.nama || entry.indicator?.judul }}
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Unit: <strong class="text-slate-700">{{ entry.unit?.nama }}</strong> • Periode: <strong class="text-slate-700">{{ bulanLabel }} {{ tahunLabel }}</strong> • Target: <span class="font-mono font-medium text-slate-700">{{ entry.indicator?.target_operator === '>=' ? '≥' : (entry.indicator?.target_operator === '<=' ? '≤' : entry.indicator?.target_operator) }} {{ entry.indicator?.target_capaian }}%</span>
                        </p>
                    </div>
                </div>

                <!-- Action Toolbar Buttons -->
                <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                    <!-- Tombol edit administrator untuk hasil yang sudah ditetapkan -->
                    <template v-if="entry.status === 'approved' && permissions?.canEditValidation">
                        <button
                            v-if="!isEditing"
                            @click="startEdit"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold rounded-lg border border-slate-300 transition-colors shadow-2xs"
                            title="Edit hasil validasi ini (Wewenang Administrator)"
                        >
                            <NotionIcon name="edit" size="13" class="text-slate-600" />
                            <span>Edit Validasi</span>
                        </button>
                        <button
                            v-else
                            @click="cancelEdit"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg border border-slate-200 transition-colors shadow-2xs"
                        >
                            <span>Batal Edit</span>
                        </button>
                    </template>

                    <button
                        @click="showSampleTableModal = true"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-200 transition-colors shadow-2xs"
                        title="Buka Tabel 3. Besar Sampel Validasi"
                    >
                        <NotionIcon name="table" size="13" class="text-slate-500" />
                        <span>Tabel 3 Sampel</span>
                    </button>

                    <Link
                        :href="route('data-entry.index', { unit_id: entry.unit_id, tahun: entry.period?.tahun, bulan: entry.period?.bulan, indicator_id: entry.indicator_id })"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-200 transition-colors shadow-2xs"
                        title="Buka Form Pengumpul Data Tally Sheet"
                    >
                        <NotionIcon name="file-text" size="13" class="text-slate-500" />
                        <span>Form Pengumpul</span>
                    </Link>

                    <!-- Tombol Ekspor Tunggal (Pilihan PDF & Excel) -->
                    <div class="relative inline-block text-left">
                        <button
                            @click="showExportMenu = !showExportMenu"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-2xs hover:border-slate-300 transition-colors cursor-pointer"
                            title="Pilih format untuk mengekspor lembar validasi (PDF atau Excel)"
                        >
                            <NotionIcon name="download" size="13" class="text-slate-500" />
                            <span>Ekspor Laporan</span>
                            <NotionIcon name="chevron-down" size="11" class="text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': showExportMenu }" />
                        </button>

                        <!-- Dropdown Pilihan Format -->
                        <div
                            v-if="showExportMenu"
                            class="origin-top-right absolute right-0 mt-1.5 w-56 rounded-xl shadow-lg bg-white ring-1 ring-slate-200 divide-y divide-slate-100 z-50 p-1.5"
                            @mouseleave="showExportMenu = false"
                        >
                            <div class="p-1">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-2 py-1">Pilih Format Unduhan</p>
                                
                                <!-- Opsi Unduh PDF -->
                                <a
                                    :href="route('validation.export-single-pdf', entry.id)"
                                    @click="showExportMenu = false"
                                    class="flex items-center gap-2.5 px-2 py-2 text-xs font-medium text-slate-700 hover:bg-rose-50 hover:text-rose-900 rounded-lg transition-colors group cursor-pointer"
                                >
                                    <div class="w-6 h-6 rounded bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                        <NotionIcon name="file-text" size="13" />
                                    </div>
                                    <div class="flex-1 text-left">
                                        <span class="font-bold block text-xs">Dokumen PDF (.pdf)</span>
                                        <span class="text-[10px] text-slate-400 group-hover:text-rose-600 block">Format Resmi Siap Cetak KARS/SNARS</span>
                                    </div>
                                </a>

                                <!-- Opsi Pratinjau PDF -->
                                <a
                                    :href="`${route('validation.export-single-pdf', entry.id)}?preview=1`"
                                    target="_blank"
                                    @click="showExportMenu = false"
                                    class="flex items-center gap-2 px-2 py-1.5 text-xs text-slate-600 hover:bg-slate-100 hover:text-slate-900 rounded-lg transition-colors ml-8"
                                >
                                    <NotionIcon name="eye" size="12" class="text-slate-400" />
                                    <span class="text-[11px]">Pratinjau PDF di Tab Baru</span>
                                </a>

                                <!-- Opsi Unduh Excel -->
                                <a
                                    :href="route('validation.export-excel', entry.id)"
                                    @click="showExportMenu = false"
                                    class="flex items-center gap-2.5 px-2 py-2 text-xs font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 rounded-lg transition-colors group cursor-pointer mt-1"
                                >
                                    <div class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <NotionIcon name="table" size="13" />
                                    </div>
                                    <div class="flex-1 text-left">
                                        <span class="font-bold block text-xs">Tabel Excel (.xlsx)</span>
                                        <span class="text-[10px] text-slate-400 group-hover:text-emerald-600 block">Lembar Rekapitulasi Spreadsheet</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <button
                        @click="printValidationSheet"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg shadow-2xs transition-colors"
                        title="Cetak lembar validasi"
                    >
                        <NotionIcon name="eye" size="13" />
                        <span>Cetak</span>
                    </button>
                </div>
            </div>

            <!-- ─── Banner Notifikasi Mode Edit Komite Mutu ─── -->
            <div v-if="isEditing" class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <NotionIcon name="edit" size="14" class="text-amber-700 shrink-0" />
                    <span><strong>Mode Edit Administrator:</strong> Anda sedang menyesuaikan hasil validasi, kesimpulan, atau rencana tindak lanjut untuk unit ini.</span>
                </div>
                <button @click="cancelEdit" class="text-xs text-amber-800 underline hover:text-amber-950 font-medium">Batal</button>
            </div>

            <!-- ─── Compact Semantic Status Alerts ─── -->
            <div v-if="entry.status === 'approved' && (validation?.is_valid || entry.latest_validation?.is_valid)" class="bg-emerald-50 border border-emerald-200 rounded-lg p-2.5 sm:px-3 text-xs text-emerald-900 flex items-center gap-2.5">
                <NotionIcon name="check" size="15" class="text-emerald-700 shrink-0" />
                <p class="leading-relaxed">
                    <strong>Indikator Lolos Validasi Mutu (Kesesuaian {{ validation?.tingkat_kesesuaian ?? entry.latest_validation?.tingkat_kesesuaian }}% ≥ 90%).</strong> 
                    Data dinyatakan Valid untuk periode {{ bulanLabel }} {{ tahunLabel }}. Unit {{ entry.unit?.nama }} dibebaskan dari kewajiban validasi profil ini selama tahun {{ tahunLabel }}.
                </p>
            </div>

            <div v-else-if="entry.status === 'rejected'" class="bg-rose-50 border border-rose-200 rounded-lg p-2.5 sm:px-3 text-xs text-rose-900 flex items-center gap-2.5">
                <NotionIcon name="alert-triangle" size="15" class="text-rose-700 shrink-0" />
                <p class="leading-relaxed">
                    <strong>Data Pengajuan Tidak Lolos Validasi (&lt; 90%).</strong> 
                    Tingkat kesesuaian belum memenuhi kriteria baku mutu. Unit {{ entry.unit?.nama }} dapat memilih bulan mana pun yang datanya siap untuk mengajukan validasi ulang pada tahun {{ tahunLabel }}.
                </p>
            </div>

            <div v-else-if="indicatorValidationStatus?.is_mandatory_retry" class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 sm:px-3 text-xs text-amber-900 flex items-center gap-2.5">
                <NotionIcon name="clock" size="15" class="text-amber-700 shrink-0" />
                <p class="leading-relaxed">
                    <strong>Pengajuan Validasi Ulang Wajib.</strong> 
                    Data validasi indikator ini pada bulan sebelumnya ({{ indicatorValidationStatus.previous_failed_month_name }}) belum lolos validasi. Komite Mutu mengaudit data bulan ini untuk verifikasi kepatuhan metodologi.
                </p>
            </div>

            <!-- ─── PERBANDINGAN BERDAMPINGAN (SIDE-BY-SIDE) ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                        Perbandingan: Data Form Pengumpul (Unit) vs Data Validasi ({{ isValidatorKomite ? 'Komite Mutu' : (isValidatorAdmin ? 'Administrator' : 'Internal Unit') }})
                    </span>
                    <span class="text-[11px] text-slate-500 font-mono">
                        Target: {{ entry.indicator?.target_operator === '>=' ? '≥' : (entry.indicator?.target_operator === '<=' ? '≤' : entry.indicator?.target_operator) }} {{ entry.indicator?.target_capaian }}%
                    </span>
                </div>

                <div class="p-4 space-y-4">
                    <!-- Grid Dua Kolom Side-by-Side -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- KARTU KIRI: DATA PENGUMPUL DATA (UNIT) -->
                        <div class="bg-slate-50/70 rounded-xl p-3.5 border border-slate-200 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                                            1. Data Pengumpul (Unit Kerja)
                                        </h3>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200 text-slate-700">
                                        Snapshot Unit
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1.5">
                                    Unit: <strong class="text-slate-700">{{ entry.unit?.nama }}</strong> • PIC: <strong class="text-slate-700">{{ entry.user?.profile?.nama_lengkap || entry.user?.name }}</strong>
                                </p>
                            </div>

                            <!-- Angka Unit N & D -->
                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200 text-center shadow-2xs">
                                    <span class="text-[10px] font-semibold text-slate-500 uppercase block">Numerator Unit [N]</span>
                                    <p class="text-xl font-bold font-mono text-slate-900 mt-0.5">{{ unitNum.toLocaleString('id-ID') }}</p>
                                    <span class="text-[10px] text-slate-400 truncate block mt-0.5" :title="entry.indicator?.numerator_keterangan">
                                        {{ entry.indicator?.numerator_keterangan || 'Capaian' }}
                                    </span>
                                </div>
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200 text-center shadow-2xs">
                                    <span class="text-[10px] font-semibold text-slate-500 uppercase block">Denominator Unit [D]</span>
                                    <p class="text-xl font-bold font-mono text-slate-900 mt-0.5">{{ unitDen.toLocaleString('id-ID') }}</p>
                                    <span class="text-[10px] text-slate-400 truncate block mt-0.5" :title="entry.indicator?.denominator_keterangan">
                                        {{ entry.indicator?.denominator_keterangan || 'Populasi / Sasaran' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Capaian Unit Box -->
                            <div class="bg-white p-2.5 rounded-lg border border-slate-200 flex items-center justify-between shadow-2xs">
                                <div>
                                    <span class="text-[10px] font-semibold text-slate-500 uppercase block">Capaian Mutu Unit</span>
                                    <span class="text-[11px] text-slate-400">(N / D) × {{ entry.indicator?.multiplier || 100 }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xl font-bold font-mono" :class="isTargetMet ? 'text-emerald-700' : 'text-rose-600'">
                                        {{ unitCap }}%
                                    </span>
                                    <span class="block text-[10px] font-medium" :class="isTargetMet ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ isTargetMet ? 'Memenuhi Target' : 'Belum Tercapai' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Tombol Toggle Tabel Snapshot Harian -->
                            <div class="pt-1.5 border-t border-slate-200">
                                <button
                                    @click="showDailyRecords = !showDailyRecords"
                                    type="button"
                                    class="text-xs text-slate-700 hover:text-slate-900 font-medium inline-flex items-center gap-1 transition-colors"
                                >
                                    <span>{{ showDailyRecords ? '▲ Sembunyikan' : '▼ Tampilkan' }} Rincian Baris/Harian Pengumpul</span>
                                </button>
                            </div>
                        </div>

                        <!-- KARTU KANAN: DATA VALIDASI (INTERNAL UNIT / KOMITE MUTU) -->
                        <div class="bg-slate-50/70 rounded-xl p-3.5 border border-slate-200 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-slate-700"></span>
                                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                                            2. Data Validasi {{ isEditing ? '(Koreksi Komite)' : (isValidatorKomite ? '(Komite Mutu)' : '(Internal Unit)') }}
                                        </h3>
                                    </div>
                                    
                                    <!-- Aksi Cepat Sampel Validator (Aktif saat form terbuka) -->
                                    <div v-if="isAuditFormActive" class="flex items-center gap-1">
                                        <button
                                            @click="applySampleRecommendation"
                                            type="button"
                                            class="px-2 py-0.5 text-[11px] font-medium bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-md shadow-2xs transition-colors"
                                            title="Terapkan ukuran sampel uji petik sesuai Tabel 3"
                                        >
                                            <span>Rekomendasi ({{ sampleRec.validator }})</span>
                                        </button>
                                        <button
                                            @click="copyUnitBaseline"
                                            type="button"
                                            class="px-2 py-0.5 text-[11px] font-medium bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-md shadow-2xs transition-colors"
                                            title="Salin nilai Numerator & Denominator unit (100% Sensus)"
                                        >
                                            <span>Sensus (100%)</span>
                                        </button>
                                    </div>
                                    <span v-else class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200 text-slate-700">
                                        {{ isValidatorKomite ? 'Audit Komite Mutu' : 'Pengesahan Unit' }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1.5">
                                    {{ entry.latest_validation?.actor ? 'Diperbarui oleh' : 'Pengesah' }}:
                                    <strong class="text-slate-700">{{ isEditing ? (currentUser?.name + ' (Komite Mutu)') : (entry.latest_validation?.actor?.name || entry.latest_validation?.validator?.profile?.nama_lengkap || entry.latest_validation?.validator?.name || currentUser?.name) }}</strong>
                                    • Telaah Validasi
                                </p>
                            </div>

                            <!-- Input N & D Validator (Jika Form Aktif / Mode Edit) -->
                            <div v-if="isAuditFormActive" class="grid grid-cols-2 gap-2.5">
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200 shadow-2xs">
                                    <label class="text-[10px] font-semibold text-slate-600 uppercase block mb-1">Numerator Uji Petik (N_val)</label>
                                    <input
                                        v-model.number="approveForm.validator_num"
                                        type="number"
                                        min="0"
                                        step="any"
                                        class="w-full text-lg font-bold font-mono text-slate-900 border-slate-200 rounded-lg focus:ring-slate-400 py-1 px-2"
                                    />
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Hasil hitung sampel validator</span>
                                </div>
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200 shadow-2xs">
                                    <label class="text-[10px] font-semibold text-slate-600 uppercase block mb-1">Denominator Uji Petik (D_val)</label>
                                    <input
                                        v-model.number="approveForm.validator_den"
                                        type="number"
                                        min="1"
                                        step="any"
                                        class="w-full text-lg font-bold font-mono text-slate-900 border-slate-200 rounded-lg focus:ring-slate-400 py-1 px-2"
                                    />
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Jumlah sampel yang divalidasi</span>
                                </div>
                            </div>

                            <!-- Display Mode N & D (Jika Sudah Disahkan & Sedang Tidak Diedit) -->
                            <div v-else class="grid grid-cols-2 gap-2.5">
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200 text-center shadow-2xs">
                                    <span class="text-[10px] font-semibold text-slate-500 uppercase block">Numerator Validator [N]</span>
                                    <p class="text-xl font-bold font-mono text-slate-900 mt-0.5">
                                        {{ (validation?.validator_numerator ?? entry.latest_validation?.validator_numerator ?? unitNum).toLocaleString('id-ID') }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Sampel terverifikasi</span>
                                </div>
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200 text-center shadow-2xs">
                                    <span class="text-[10px] font-semibold text-slate-500 uppercase block">Denominator Validator [D]</span>
                                    <p class="text-xl font-bold font-mono text-slate-900 mt-0.5">
                                        {{ (validation?.validator_denominator ?? entry.latest_validation?.validator_denominator ?? unitDen).toLocaleString('id-ID') }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Total sampel validasi</span>
                                </div>
                            </div>

                            <!-- Capaian Validator Box -->
                            <div class="bg-white p-2.5 rounded-lg border border-slate-200 flex items-center justify-between shadow-2xs">
                                <div>
                                    <span class="text-[10px] font-semibold text-slate-500 uppercase block">Capaian Mutu Validator</span>
                                    <span class="text-[11px] text-slate-400">Hasil Uji Petik Validasi</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xl font-bold font-mono text-slate-900">
                                        {{ isAuditFormActive ? validatorCapaian : (validation?.validator_capaian ?? entry.latest_validation?.validator_capaian ?? unitCap) }}%
                                    </span>
                                    <span class="block text-[10px] font-medium text-slate-600">
                                        {{ isValidatorKomite ? 'Terverifikasi Komite' : 'Tervalidasi Unit' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Label Pengesahan -->
                            <div class="pt-1.5 border-t border-slate-200 text-[11px] text-slate-600">
                                Metode: <strong>Kesepakatan pengukuran berdasarkan hasil (Measurement Result Agreement)</strong>
                            </div>
                        </div>

                    </div>

                    <!-- ─── BAROMETER KESESUAIAN DATA (MINIMALIS) ─── -->
                    <div class="bg-white rounded-xl border border-slate-200 p-3.5 space-y-3 shadow-2xs">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                                <NotionIcon name="check" size="13" class="text-slate-600" />
                                <span>Evaluasi Kesesuaian Data (Measurement Result Agreement)</span>
                            </span>
                            <span class="text-xs text-slate-500 font-medium">
                                Ambang Batas Kelulusan: <strong class="text-slate-800 font-mono">≥ 90.0%</strong>
                            </span>
                        </div>

                        <!-- Grid 4 Metrik Deviasi -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-center">
                            <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase block">Selisih Numerator (ΔN)</span>
                                <p class="text-lg font-bold font-mono mt-0.5" :class="deltaNumerator === 0 ? 'text-slate-700' : 'text-amber-700'">
                                    {{ deltaNumerator > 0 ? '+' : '' }}{{ deltaNumerator.toLocaleString('id-ID') }}
                                </p>
                            </div>
                            <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase block">Selisih Denominator (ΔD)</span>
                                <p class="text-lg font-bold font-mono mt-0.5" :class="deltaDenominator === 0 ? 'text-slate-700' : 'text-amber-700'">
                                    {{ deltaDenominator > 0 ? '+' : '' }}{{ deltaDenominator.toLocaleString('id-ID') }}
                                </p>
                            </div>
                            <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase block">Selisih Capaian (Δ%)</span>
                                <p class="text-lg font-bold font-mono mt-0.5" :class="deltaCapaian === 0 ? 'text-slate-700' : 'text-amber-700'">
                                    {{ deltaCapaian > 0 ? '+' : '' }}{{ deltaCapaian }}%
                                </p>
                            </div>
                            <div :class="['p-2.5 rounded-lg border text-center', isValid ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900']">
                                <span class="text-[10px] font-bold uppercase block" :class="isValid ? 'text-emerald-700' : 'text-rose-700'">Tingkat Kesesuaian</span>
                                <p class="text-xl font-bold font-mono mt-0.5" :class="isValid ? 'text-emerald-800' : 'text-rose-800'">
                                    {{ isAuditFormActive ? tingkatKesesuaian : (validation?.tingkat_kesesuaian ?? entry.latest_validation?.tingkat_kesesuaian ?? 100) }}%
                                </p>
                            </div>
                        </div>

                        <!-- Status Banner Kelulusan Validasi Minimalis -->
                        <div
                            :class="[
                                'p-2.5 rounded-lg border text-xs leading-relaxed flex items-start gap-2',
                                isValid ? 'bg-emerald-50 text-emerald-900 border-emerald-200' : 'bg-rose-50 text-rose-900 border-rose-200'
                            ]"
                        >
                            <NotionIcon :name="isValid ? 'check' : 'alert-triangle'" size="15" :class="isValid ? 'text-emerald-700' : 'text-rose-700'" class="shrink-0 mt-0.5" />
                            <div class="space-y-0.5">
                                <p class="font-bold">
                                    {{ isValid ? 'Data Memenuhi Syarat (VALID / Lolos Validasi)' : 'Tingkat Kesesuaian di Bawah Standar (< 90% TIDAK VALID)' }}
                                </p>
                                <p v-if="isValid" class="text-emerald-800 text-[11px]">
                                    Formula: <code>(Capaian kecil / Capaian besar) × 100% = {{ tingkatKesesuaian }}% ≥ 90%</code>. 
                                    Unit {{ entry.unit?.nama }} tidak perlu mengajukan validasi lagi untuk profil ini sepanjang tahun {{ tahunLabel }}.
                                </p>
                                <p v-else class="text-rose-800 text-[11px]">
                                    Formula: <code>(Capaian kecil / Capaian besar) × 100% = {{ tingkatKesesuaian }}% &lt; 90%</code>. 
                                    Unit {{ entry.unit?.nama }} dapat memilih bulan mana pun yang datanya siap untuk mengajukan validasi ulang pada tahun {{ tahunLabel }}.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ─── TABEL RINCIAN SNAPSHOT DATA PENGUMPUL (EXPANDABLE) ─── -->
                    <div v-if="showDailyRecords" class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                                Snapshot Rincian Data Harian Pengumpul Unit ({{ entry.unit?.nama }})
                            </span>
                            <span class="text-[11px] text-slate-500 font-mono">
                                {{ (validation?.unit_data_snapshot?.daily_records || dailyRecords)?.length || 0 }} Baris Tercatat
                            </span>
                        </div>

                        <div class="overflow-x-auto max-h-72 overflow-y-auto border border-slate-200 rounded-lg bg-white">
                            <table class="min-w-full text-[11px] divide-y divide-slate-200">
                                <thead class="bg-slate-50 sticky top-0 font-semibold text-slate-700">
                                    <tr>
                                        <th class="px-3 py-1.5 text-center w-12">Hari</th>
                                        <th class="px-3 py-1.5 text-center">Tanggal</th>
                                        <th class="px-3 py-1.5 text-right">Numerator (N)</th>
                                        <th class="px-3 py-1.5 text-right">Denominator (D)</th>
                                        <th class="px-3 py-1.5 text-center">Capaian</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-mono">
                                    <tr v-for="(rec, index) in (validation?.unit_data_snapshot?.daily_records || dailyRecords)" :key="rec.hari ?? rec.id ?? index" class="hover:bg-slate-50">
                                        <td class="px-3 py-1 text-center font-bold text-slate-700">{{ rec.hari }}</td>
                                        <td class="px-3 py-1 text-center text-slate-500">{{ rec.tanggal }}</td>
                                        <template v-if="typeof rec.data === 'object' && rec.data !== null && ('num' in rec.data || 'den' in rec.data)">
                                            <td class="px-3 py-1 text-right font-medium text-slate-800">{{ Number(rec.data?.num || 0).toLocaleString('id-ID') }}</td>
                                            <td class="px-3 py-1 text-right font-medium text-slate-800">{{ Number(rec.data?.den || 0).toLocaleString('id-ID') }}</td>
                                            <td class="px-3 py-1 text-center font-medium">
                                                {{ rec.data?.den > 0 ? ((rec.data.num / rec.data.den) * 100).toFixed(1) + '%' : '-' }}
                                            </td>
                                        </template>
                                        <td v-else colspan="3" class="px-3 py-1 text-left text-slate-700 whitespace-pre-wrap">
                                            {{ JSON.stringify(rec.data, null, 2) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!(validation?.unit_data_snapshot?.daily_records || dailyRecords)?.length">
                                        <td colspan="5" class="py-3 text-center text-slate-400">Tidak ada rincian data baris harian.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── Bagian B: Rekam Hasil Validasi Resmi (12 Baris Format Baku RS) ─── -->
            <!-- Tampil jika sudah divalidasi dan TIDAK sedang dalam mode edit -->
            <div v-if="entry.latest_validation && entry.status !== 'submitted' && !isEditing" class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 flex-wrap gap-2">
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                        <NotionIcon name="shield" size="14" class="text-emerald-600" />
                        <span>Laporan Validasi Data: {{ entry.indicator?.judul }} — Periode {{ bulanLabel }} {{ tahunLabel }}</span>
                    </span>
                    <div class="flex items-center gap-2.5">
                        <span class="text-xs text-slate-500 font-medium">
                            {{ entry.latest_validation.actor ? 'Diperbarui oleh' : 'Disahkan oleh' }}: <strong class="text-slate-800">{{ entry.latest_validation.actor?.name || entry.latest_validation.validator?.profile?.nama_lengkap || entry.latest_validation.validator?.name }}</strong> • 
                            Tgl: <strong class="text-slate-800">{{ new Date(entry.latest_validation.tanggal_validasi).toLocaleDateString('id-ID', { dateStyle: 'long' }) }}</strong>
                        </span>
                        <!-- Tombol edit cepat administrator -->
                        <button
                            v-if="permissions?.canEditValidation"
                            @click="startEdit"
                            type="button"
                            class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-md border border-slate-300 flex items-center gap-1 transition-colors"
                            title="Edit data validasi ini (Wewenang Administrator)"
                        >
                            <NotionIcon name="edit" size="11" class="text-slate-600" />
                            <span>Edit</span>
                        </button>
                    </div>
                </div>

                <!-- Tabel 12 Baris Persis Format Dokumen Panduan -->
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="min-w-full text-xs divide-y divide-slate-200">
                        <tbody class="divide-y divide-slate-200">
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 w-60 border-r border-slate-200">Judul Indikator</td>
                                <td class="px-3 py-2 font-bold text-slate-900">{{ entry.indicator?.judul }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Numerator</td>
                                <td class="px-3 py-2 text-slate-800">{{ entry.indicator?.numerator_keterangan || '-' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Denominator</td>
                                <td class="px-3 py-2 text-slate-800">{{ entry.indicator?.denominator_keterangan || '-' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Sumber Data</td>
                                <td class="px-3 py-2 text-slate-800">{{ entry.indicator?.sumber_data || 'Data sekunder (dari eMR)' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Capaian Indikator</td>
                                <td class="px-3 py-2 font-mono font-bold text-slate-900">
                                    {{ unitCap }}% ({{ Number(unitNum).toLocaleString('id-ID') }}/{{ Number(unitDen).toLocaleString('id-ID') }} x 100%)
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Jumlah Sampel Validasi</td>
                                <td class="px-3 py-2 font-mono font-bold text-slate-800">{{ entry.latest_validation.jumlah_sampel_validasi }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Justifikasi perlu validasi</td>
                                <td class="px-3 py-2 text-slate-800">{{ entry.latest_validation.justifikasi_perlu_validasi || '-' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Metode Validasi</td>
                                <td class="px-3 py-2 text-slate-800">{{ entry.latest_validation.metode_validasi || '-' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Hasil Validasi</td>
                                <td class="px-3 py-2 font-mono font-bold text-slate-900">{{ entry.latest_validation.hasil_validasi || '-' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Hasil Analisa</td>
                                <td class="px-3 py-2 font-mono font-bold text-slate-900">{{ entry.latest_validation.hasil_analisa || '-' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Kesimpulan</td>
                                <td class="px-3 py-2 font-bold text-slate-900">{{ entry.latest_validation.kesimpulan || '-' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Rencana Tindak Lanjut</td>
                                <td class="px-3 py-2 text-slate-800">{{ entry.latest_validation.rencana_tindak_lanjut || '-' }}</td>
                            </tr>
                            <tr v-if="entry.latest_validation.catatan_validator">
                                <td class="px-3 py-2 font-semibold text-slate-700 bg-slate-50 border-r border-slate-200">Catatan Tambahan Validator</td>
                                <td class="px-3 py-2 text-slate-700 italic">{{ entry.latest_validation.catatan_validator }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-[11px] text-slate-400 italic">
                        * Lampiran data imut yang divalidasi dan hasil validasinya.
                    </p>
                    <a
                        v-if="entry.latest_validation.lampiran_file"
                        :href="route('evidence.show', {
                            type: 'validation',
                            record: entry.latest_validation.id,
                        })"
                        target="_blank"
                        class="text-xs font-semibold text-indigo-600 hover:underline"
                    >
                        Buka lampiran validasi
                    </a>
                </div>

                <!-- Tanda Tangan Pengesahan (Resmi Standar RS) -->
                <div class="mt-4 pt-4 border-t border-slate-200 grid grid-cols-2 gap-6 text-center text-xs">
                    <div>
                        <p class="text-slate-500 mb-0.5">Kepala Unit / Pengumpul Data,</p>
                        <p class="font-bold text-slate-800">{{ entry.unit?.nama }}</p>
                        <div class="h-12 flex items-center justify-center">
                            <span class="text-[10px] text-slate-400 italic">[Tanda Tangan Pengumpul Data]</span>
                        </div>
                        <p class="font-bold text-slate-900 underline">{{ entry.user?.profile?.nama_lengkap || entry.user?.name || 'PIC Unit' }}</p>
                        <p class="text-[10px] text-slate-400">Pengumpul Data Unit</p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-0.5">Mengetahui &amp; Menyetujui,</p>
                        <p class="font-bold text-slate-800">{{ isValidatorKomite ? 'Validator Komite Mutu RS' : (isValidatorAdmin ? 'Administrator' : 'Pengesah Validasi Internal Unit') }}</p>
                        <div class="h-12 flex items-center justify-center">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded border" :class="isValidatorKomite ? 'text-emerald-700 bg-emerald-50 border-emerald-300' : 'text-blue-700 bg-blue-50 border-blue-300'">
                                {{ isValidatorKomite ? 'DIVERIFIKASI KOMITE MUTU' : (isValidatorAdmin ? 'DITETAPKAN ADMINISTRATOR' : 'DISAHKAN INTERNAL UNIT') }}
                            </span>
                        </div>
                        <p class="font-bold text-slate-900 underline">{{ validatorUser?.profile?.nama_lengkap || validatorUser?.name || (isValidatorKomite ? 'Tim Validator Komite Mutu' : 'PIC / Kepala Unit') }}</p>
                        <p class="text-[10px] text-slate-400">{{ isValidatorKomite ? 'Komite Mutu &amp; Keselamatan Pasien' : (isValidatorAdmin ? 'Administrator Sistem' : ('Unit ' + (entry.unit?.nama || ''))) }}</p>
                    </div>
                </div>
            </div>

            <!-- ─── FORMULIR TELAAH / PENGESAHAN / EDIT VALIDASI ─── -->
            <!-- Tampil saat: Status SUBMITTED (Unit / Komite) ATAU saat Mode EDIT KOMITE MUTU aktif -->
            <div v-else-if="isAuditFormActive" class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 flex-wrap gap-2">
                    <div>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                            <NotionIcon name="file-text" size="14" class="text-slate-600" />
                            <span>{{ isEditing ? 'Edit Data Validasi (Wewenang Administrator)' : (permissions?.isStaffOfThisUnit && !permissions?.isAdmin ? 'Pengesahan Validasi Internal Unit (PIC / Kepala Unit)' : 'Formulir Telaah & Pengesahan Validasi Mutu') }}</span>
                        </span>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            {{ isEditing ? 'Perubahan data akan memperbarui hasil validasi indikator ini.' : (permissions?.isStaffOfThisUnit && !permissions?.isAdmin ? 'Validasi data disahkan langsung oleh PIC / Kepala Unit.' : 'Bandingkan data validasi dengan snapshot Form Pengumpul Data unit.') }}
                        </p>
                    </div>
                    <button
                        @click="showSampleTableModal = true"
                        type="button"
                        class="text-xs text-slate-600 hover:text-slate-900 font-medium underline flex items-center gap-1"
                    >
                        <span>Pedoman Tabel 3 Sampel</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <!-- Justifikasi & Metode -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Justifikasi Perlu Validasi <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="approveForm.justifikasi_perlu_validasi"
                                class="w-full text-xs border-slate-200 rounded-lg bg-slate-50 focus:ring-slate-400 font-medium"
                            >
                                <option v-for="opt in justifikasiOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <input
                                v-if="approveForm.justifikasi_perlu_validasi === 'Lainnya (Tulis manual)'"
                                v-model="approveForm.catatan"
                                type="text"
                                placeholder="Sebutkan justifikasi spesifik..."
                                class="w-full text-xs border-slate-200 rounded-lg mt-1 focus:ring-slate-400"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Metode Validasi <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="approveForm.metode_validasi"
                                class="w-full text-xs border-slate-200 rounded-lg bg-slate-50 focus:ring-slate-400 font-medium"
                            >
                                <option v-for="opt in metodeOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Jumlah Sampel Validasi & Widget Tabel 3 -->
                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-0.5">
                                Jumlah Sampel Validasi (Denominator Audit Validator)
                            </label>
                            <p class="text-[11px] text-slate-500">
                                Berdasarkan populasi denominator unit (<strong>{{ unitDen }}</strong>), rekomendasi Tabel 3 adalah: <strong>{{ sampleRec.validator }} sampel</strong> (<em>{{ sampleRec.valRule }}</em>).
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="approveForm.jumlah_sampel_validasi"
                                type="number"
                                min="1"
                                class="w-24 text-xs font-mono font-bold border-slate-200 rounded-lg bg-white text-center py-1"
                            />
                            <button
                                @click="applySampleRecommendation"
                                type="button"
                                class="px-2.5 py-1 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg shadow-2xs transition-colors shrink-0"
                            >
                                Gunakan Rekomendasi
                            </button>
                        </div>
                    </div>

                    <!-- Hasil Analisa, Kesimpulan, RTL -->
                    <div class="space-y-2.5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Hasil Analisa (Formula: Capaian Kecil / Capaian Besar × 100%)
                            </label>
                            <input
                                v-model="approveForm.hasil_analisa"
                                type="text"
                                class="w-full text-xs font-mono font-semibold border-slate-200 rounded-lg bg-slate-50 focus:ring-slate-400 text-slate-900"
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kesimpulan Validasi</label>
                                <textarea
                                    v-model="approveForm.kesimpulan"
                                    rows="2"
                                    class="w-full text-xs border-slate-200 rounded-lg bg-slate-50 focus:ring-slate-400 font-medium"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Rencana Tindak Lanjut (RTL)</label>
                                <textarea
                                    v-model="approveForm.rencana_tindak_lanjut"
                                    rows="2"
                                    class="w-full text-xs border-slate-200 rounded-lg bg-slate-50 focus:ring-slate-400 font-medium"
                                ></textarea>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan Validator (Opsional)</label>
                            <textarea
                                v-model="approveForm.catatan"
                                rows="2"
                                placeholder="Tambahkan catatan khusus telaah mutu..."
                                class="w-full text-xs border-slate-200 rounded-lg bg-slate-50 focus:ring-slate-400"
                            ></textarea>
                        </div>
                    </div>

                    <!-- ─── Action Buttons ─── -->
                    <div class="flex flex-wrap items-center justify-between gap-2.5 pt-3 border-t border-slate-100">
                        <div class="flex items-center gap-2">
                            <!-- Jika sedang mode edit -->
                            <template v-if="isEditing">
                                <button
                                    @click="cancelEdit"
                                    type="button"
                                    class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-medium rounded-lg transition-colors shadow-2xs"
                                >
                                    Batal Edit
                                </button>
                            </template>

                            <!-- Hanya administrator yang dapat mengembalikan atau menolak pengajuan -->
                            <template v-else-if="permissions?.isAdmin">
                                <button
                                    @click="showRevisionModal = true"
                                    type="button"
                                    class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-medium rounded-lg transition-colors shadow-2xs flex items-center gap-1.5"
                                >
                                    <NotionIcon name="refresh-cw" size="13" class="text-slate-500" />
                                    <span>Minta Revisi Unit</span>
                                </button>

                                <button
                                    @click="showRejectModal = true"
                                    type="button"
                                    class="px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 text-xs font-medium rounded-lg transition-colors shadow-2xs flex items-center gap-1.5"
                                >
                                    <NotionIcon name="x" size="13" class="text-rose-600" />
                                    <span>Tolak (Wajib Uji Ulang)</span>
                                </button>
                            </template>
                            
                            <!-- Keterangan Internal Unit -->
                            <template v-else>
                                <span class="text-[11px] text-slate-500 italic">
                                    Persetujuan Internal Unit: Validasi disahkan langsung oleh PIC / Kepala Unit kerja.
                                </span>
                            </template>
                        </div>

                        <!-- Tombol Submit / Simpan -->
                        <button
                            @click="submitApprove"
                            :disabled="approveForm.processing"
                            type="button"
                            class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-medium rounded-lg transition-all shadow-2xs flex items-center gap-1.5 active:scale-95"
                        >
                            <NotionIcon name="check" size="13" />
                            <span>{{ approveForm.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan Validasi' : (permissions?.isStaffOfThisUnit && !permissions?.isAdmin ? 'Sahkan Validasi (Internal Unit)' : (isValid ? 'Sahkan & Loloskan Validasi (≥ 90%)' : 'Sahkan dengan Catatan (< 90%)'))) }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Jika status submitted dan pengguna tidak memiliki hak validasi (misal Eksekutif / Unit lain) -->
            <div v-else-if="entry.status === 'submitted'" class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center space-y-1.5">
                <NotionIcon name="clock" size="20" class="text-slate-500 mx-auto" />
                <h3 class="text-xs font-bold text-slate-800">Data Sedang Menunggu Pengesahan Validasi</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Data pengumpulan telah dibekukan. PIC / Kepala Unit terkait akan menetapkan hasil validasi ini.
                </p>
            </div>
        </div>

        <!-- ─── MODAL TABEL 3. BESAR SAMPEL VALIDASI (STANDAR RESMI) ─── -->
        <Teleport to="body">
            <div
                v-if="showSampleTableModal"
                class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
                @click.self="showSampleTableModal = false"
            >
                <div class="bg-white rounded-xl max-w-2xl w-full p-4 sm:p-5 shadow-xl border border-slate-200 space-y-3.5 animate-in fade-in zoom-in-95 duration-150">
                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-200">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
                                <NotionIcon name="table" size="16" />
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900">Tabel 3. Besar Sampel Validasi</h3>
                                <p class="text-[11px] text-slate-500">Standar Statistik Penentuan Ukuran Sampel Pengumpul &amp; Validator Mutu RS</p>
                            </div>
                        </div>
                        <button @click="showSampleTableModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <NotionIcon name="x" size="15" />
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="min-w-full text-xs divide-y divide-slate-200 text-center">
                            <thead class="bg-slate-50 font-semibold text-slate-700">
                                <tr>
                                    <th colspan="2" class="py-2 px-3 border-r border-slate-200 bg-slate-100 text-slate-800">
                                        Petugas Pengumpul Data (Unit)
                                    </th>
                                    <th colspan="2" class="py-2 px-3 bg-slate-100 text-slate-800">
                                        Petugas Validasi Data (PIC / Kepala Unit)
                                    </th>
                                </tr>
                                <tr class="text-[11px] text-slate-600 border-t border-slate-200">
                                    <th class="py-1 px-3 border-r border-slate-200">Populasi</th>
                                    <th class="py-1 px-3 border-r border-slate-200">Sampel</th>
                                    <th class="py-1 px-3 border-r border-slate-200">Populasi</th>
                                    <th class="py-1 px-3">Sampel</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                                <tr class="hover:bg-slate-50">
                                    <td class="py-1.5 px-3 border-r border-slate-100 font-semibold text-slate-700">≥ 640</td>
                                    <td class="py-1.5 px-3 border-r border-slate-200 font-semibold text-slate-800">128 sampel</td>
                                    <td class="py-1.5 px-3 border-r border-slate-100 font-semibold text-slate-700">≥ 480</td>
                                    <td class="py-1.5 px-3 font-semibold text-slate-800">48 sampel</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-1.5 px-3 border-r border-slate-100 font-semibold text-slate-700">320 – 639</td>
                                    <td class="py-1.5 px-3 border-r border-slate-200 font-semibold text-slate-800">20% dari total populasi</td>
                                    <td class="py-1.5 px-3 border-r border-slate-100 font-semibold text-slate-700">161 – 480</td>
                                    <td class="py-1.5 px-3 font-semibold text-slate-800">48 sampel</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-1.5 px-3 border-r border-slate-100 font-semibold text-slate-700">64 – 319</td>
                                    <td class="py-1.5 px-3 border-r border-slate-200 font-semibold text-slate-800">64 sampel</td>
                                    <td class="py-1.5 px-3 border-r border-slate-100 font-semibold text-slate-700">17 – 160</td>
                                    <td class="py-1.5 px-3 font-semibold text-slate-800">Minimal 16 atau 10% populasi</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-1.5 px-3 border-r border-slate-100 font-semibold text-slate-700">&lt; 64</td>
                                    <td class="py-1.5 px-3 border-r border-slate-200 font-semibold text-slate-800">100% populasi</td>
                                    <td class="py-1.5 px-3 border-r border-slate-100 font-semibold text-slate-700">1 – 16</td>
                                    <td class="py-1.5 px-3 font-semibold text-slate-800">100% populasi</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-xs text-slate-600 space-y-1">
                        <p class="font-semibold text-slate-800">Catatan Penerapan:</p>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-slate-600">
                            <li>Populasi diambil dari total Denominator pengumpulan data pada periode yang diaudit.</li>
                            <li>Bila unit telah menggunakan sensus 100%, validator dapat mengaudit sampel acak sesuai kolom kanan (Petugas Validasi Data).</li>
                        </ul>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button
                            @click="showSampleTableModal = false"
                            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg shadow-2xs"
                        >
                            Tutup Panduan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── Modal Minta Revisi ─── -->
        <Teleport to="body">
            <div v-if="showRevisionModal" class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-xl max-w-md w-full p-4 sm:p-5 shadow-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                            <NotionIcon name="refresh-cw" size="14" class="text-slate-600" />
                            <span>Minta Revisi ke Unit</span>
                        </h3>
                        <button @click="showRevisionModal = false" class="text-slate-400 hover:text-slate-600">
                            <NotionIcon name="x" size="14" />
                        </button>
                    </div>
                    <p class="text-xs text-slate-500">
                        Formulir Tally Sheet unit akan dibuka kembali sehingga PIC Unit dapat mengoreksi data sebelum diajukan ulang.
                    </p>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Revisi <span class="text-rose-500">*</span></label>
                        <textarea
                            v-model="revisionForm.catatan"
                            rows="4"
                            placeholder="Tuliskan secara jelas data atau tanggal yang perlu diverifikasi ulang oleh unit..."
                            class="w-full text-xs border-slate-200 rounded-lg focus:ring-slate-400"
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button
                            @click="showRevisionModal = false"
                            class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitRevision"
                            :disabled="revisionForm.processing"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-slate-800 hover:bg-slate-900 rounded-lg shadow-2xs"
                        >
                            {{ revisionForm.processing ? 'Mengirim...' : 'Kirim Catatan Revisi' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── Modal Tolak Data ─── -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-xl max-w-md w-full p-4 sm:p-5 shadow-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-rose-700 flex items-center gap-1.5">
                            <NotionIcon name="x" size="14" />
                            <span>Konfirmasi Penolakan Data</span>
                        </h3>
                        <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600">
                            <NotionIcon name="x" size="14" />
                        </button>
                    </div>
                    <p class="text-xs text-rose-800 bg-rose-50 p-2.5 rounded-lg border border-rose-200 leading-relaxed">
                        Penolakan administratif mengembalikan pengajuan ini. Unit <strong>{{ entry.unit?.nama }}</strong> dapat memilih bulan mana pun yang datanya siap untuk mengajukan validasi ulang pada tahun yang sama.
                    </p>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan <span class="text-rose-500">*</span></label>
                        <textarea
                            v-model="rejectForm.catatan"
                            rows="4"
                            placeholder="Tuliskan alasan penolakan data..."
                            class="w-full text-xs border-slate-200 rounded-lg focus:ring-rose-400"
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button
                            @click="showRejectModal = false"
                            class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitReject"
                            :disabled="rejectForm.processing"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-2xs"
                        >
                            {{ rejectForm.processing ? 'Memproses...' : 'Tolak Data' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style>
@media print {
    aside, header, nav, button, a[href*="data-entry"], a[href*="export-excel"] {
        display: none !important;
    }
    body, main {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .shadow-sm, .shadow-2xl, .shadow-xs, .shadow-2xs {
        box-shadow: none !important;
    }
    .border {
        border-color: #94a3b8 !important;
    }
}
</style>
