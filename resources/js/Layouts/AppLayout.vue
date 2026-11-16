<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const sidebarOpen = ref(true);
const mobileOpen = ref(false);

// Check active status per modul
const isUnitActive = computed(() => {
    return (route().current('indicator-selections.*') && page.props.filters?.tipe !== 'rs') ||
        (route().current('indicators.*') && page.props.selectedUnitId !== 'rs' && page.props.filters?.jenis !== 'IMP-RS') ||
        route().current('data-entry.*') ||
        route().current('validation.*') ||
        route().current('data-analysis.*') ||
        route().current('unit.rtl.*') ||
        (route().current('pdsa.*') && !route().current('hospital.pdsa.*')) ||
        (route().current('reports.*') && page.props.filters?.scope !== 'rs');
});

const isRsActive = computed(() => {
    return (route().current('indicator-selections.*') && page.props.filters?.tipe === 'rs') ||
        (route().current('indicators.*') && (page.props.selectedUnitId === 'rs' || page.props.filters?.jenis === 'IMP-RS')) ||
        route().current('hospital.*') ||
        route().current('supervisi.*') ||
        (route().current('reports.*') && page.props.filters?.scope === 'rs');
});

const isImutActive = computed(() => isUnitActive.value || isRsActive.value);
const isIkpActive = computed(() => route().current('ikp.*'));
const isManriskActive = computed(() => route().current('manrisk.*'));
const isHvaActive = computed(() => route().current('hva.*'));

// Smart Context-Aware Dropdown States
const imutDropdownOpen = ref(isImutActive.value);
const unitDropdownOpen = ref(isUnitActive.value);
const rsDropdownOpen = ref(isRsActive.value);
const ikpDropdownOpen = ref(isIkpActive.value);
const manriskDropdownOpen = ref(isManriskActive.value);
const hvaDropdownOpen = ref(isHvaActive.value);

// Auto-sync accordion state based on active route
const syncActiveDropdown = () => {
    if (isImutActive.value) {
        imutDropdownOpen.value = true;
        unitDropdownOpen.value = isUnitActive.value;
        rsDropdownOpen.value = isRsActive.value;
        ikpDropdownOpen.value = false;
        manriskDropdownOpen.value = false;
        hvaDropdownOpen.value = false;
    } else if (isIkpActive.value) {
        ikpDropdownOpen.value = true;
        imutDropdownOpen.value = false;
        unitDropdownOpen.value = false;
        rsDropdownOpen.value = false;
        manriskDropdownOpen.value = false;
        hvaDropdownOpen.value = false;
    } else if (isManriskActive.value) {
        manriskDropdownOpen.value = true;
        imutDropdownOpen.value = false;
        unitDropdownOpen.value = false;
        rsDropdownOpen.value = false;
        ikpDropdownOpen.value = false;
        hvaDropdownOpen.value = false;
    } else if (isHvaActive.value) {
        hvaDropdownOpen.value = true;
        imutDropdownOpen.value = false;
        unitDropdownOpen.value = false;
        rsDropdownOpen.value = false;
        ikpDropdownOpen.value = false;
        manriskDropdownOpen.value = false;
    } else {
        // Dashboard or System settings: keep compact
        imutDropdownOpen.value = false;
        unitDropdownOpen.value = false;
        rsDropdownOpen.value = false;
        ikpDropdownOpen.value = false;
        manriskDropdownOpen.value = false;
        hvaDropdownOpen.value = false;
    }
};

const toggleImutDropdown = () => {
    if (!sidebarOpen.value) {
        sidebarOpen.value = true;
        imutDropdownOpen.value = true;
    } else {
        imutDropdownOpen.value = !imutDropdownOpen.value;
    }
    if (imutDropdownOpen.value) {
        ikpDropdownOpen.value = false;
        manriskDropdownOpen.value = false;
        hvaDropdownOpen.value = false;
        if (!unitDropdownOpen.value && !rsDropdownOpen.value) {
            unitDropdownOpen.value = true;
        }
    }
};

const toggleUnitDropdown = () => {
    unitDropdownOpen.value = !unitDropdownOpen.value;
    if (unitDropdownOpen.value) {
        rsDropdownOpen.value = false;
    }
};

const toggleRsDropdown = () => {
    rsDropdownOpen.value = !rsDropdownOpen.value;
    if (rsDropdownOpen.value) {
        unitDropdownOpen.value = false;
    }
};

const toggleIkpDropdown = () => {
    if (!sidebarOpen.value) {
        sidebarOpen.value = true;
        ikpDropdownOpen.value = true;
    } else {
        ikpDropdownOpen.value = !ikpDropdownOpen.value;
    }
    if (ikpDropdownOpen.value) {
        imutDropdownOpen.value = false;
        unitDropdownOpen.value = false;
        rsDropdownOpen.value = false;
        manriskDropdownOpen.value = false;
        hvaDropdownOpen.value = false;
    }
};

const toggleManriskDropdown = () => {
    if (!sidebarOpen.value) {
        sidebarOpen.value = true;
        manriskDropdownOpen.value = true;
    } else {
        manriskDropdownOpen.value = !manriskDropdownOpen.value;
    }
    if (manriskDropdownOpen.value) {
        imutDropdownOpen.value = false;
        unitDropdownOpen.value = false;
        rsDropdownOpen.value = false;
        ikpDropdownOpen.value = false;
        hvaDropdownOpen.value = false;
    }
};

const toggleHvaDropdown = () => {
    if (!sidebarOpen.value) {
        sidebarOpen.value = true;
        hvaDropdownOpen.value = true;
    } else {
        hvaDropdownOpen.value = !hvaDropdownOpen.value;
    }
    if (hvaDropdownOpen.value) {
        imutDropdownOpen.value = false;
        unitDropdownOpen.value = false;
        rsDropdownOpen.value = false;
        ikpDropdownOpen.value = false;
        manriskDropdownOpen.value = false;
    }
};

const ikpMenuItems = computed(() => [
    {
        letter: 'a',
        name: 'Data Laporan IKP',
        href: route('ikp.index'),
        active: route().current('ikp.index') || route().current('ikp.create') || route().current('ikp.show') || route().current('ikp.edit'),
    },
    {
        letter: 'b',
        name: 'Pemetaan & Grafik IKP',
        href: route('ikp.mapping'),
        active: route().current('ikp.mapping'),
    },
]);

const manriskMenuItems = computed(() => [
    {
        letter: 'a',
        name: 'Daftar Register Risiko',
        href: route('manrisk.index'),
        active: (route().current('manrisk.index') && (!page.props.filters?.scope || page.props.filters?.scope === 'all')) || route().current('manrisk.create') || route().current('manrisk.show') || route().current('manrisk.edit'),
    },
    {
        letter: 'b',
        name: 'Matriks & Profil 5x5',
        href: route('manrisk.matrix'),
        active: route().current('manrisk.matrix'),
    },
    {
        letter: 'c',
        name: 'Risiko Tingkat Unit',
        href: route('manrisk.index', { scope: 'unit' }),
        active: route().current('manrisk.index') && page.props.filters?.scope === 'unit',
    },
    {
        letter: 'd',
        name: 'Risiko Prioritas RS',
        href: route('manrisk.index', { scope: 'rs' }),
        active: route().current('manrisk.index') && page.props.filters?.scope === 'rs',
    },
    {
        letter: 'e',
        name: 'Risiko Fasilitas (MFK)',
        href: route('manrisk.index', { scope: 'mfk' }),
        active: route().current('manrisk.index') && page.props.filters?.scope === 'mfk',
    },
]);

const hvaMenuItems = computed(() => [
    {
        letter: 'a',
        name: 'Dashboard & Pengkajian HVA',
        href: route('hva.index'),
        active: (route().current('hva.index') && (!page.props.filters?.scope || page.props.filters?.scope === 'rs')) || (route().current('hva.category') && (!page.props.filters?.scope || page.props.filters?.scope === 'rs')),
    },
    {
        letter: 'b',
        name: 'HVA Tingkat Unit Kerja',
        href: route('hva.index', { scope: 'unit' }),
        active: (route().current('hva.index') && page.props.filters?.scope === 'unit') || (route().current('hva.category') && page.props.filters?.scope === 'unit'),
    },
    {
        letter: 'c',
        name: 'Summary & Grafik Analitik',
        href: route('hva.summary'),
        active: route().current('hva.summary'),
    },
    {
        letter: 'd',
        name: 'Rencana Tindak Lanjut (RTL)',
        href: route('hva.rtl'),
        active: route().current('hva.rtl'),
    },
]);

// Unit Submenu Items (a s.d h)
const unitMenuItems = computed(() => {
    const u = user.value;
    const items = [];

    if (['superadmin', 'komite_mutu', 'kepala_unit', 'pic_unit'].includes(u?.role)) {
        items.push({
            letter: 'a',
            name: 'Pemilihan Indikator Mutu',
            href: route('indicator-selections.index', { tipe: 'unit' }),
            active: route().current('indicator-selections.*') && page.props.filters?.tipe !== 'rs',
        });
    }

    items.push({
        letter: 'b',
        name: 'Profil Indikator Mutu',
        href: route('indicators.index', { scope: 'unit' }),
        active: route().current('indicators.*') && page.props.selectedUnitId !== 'rs' && page.props.filters?.jenis !== 'IMP-RS',
    });

    if (['superadmin', 'komite_mutu', 'kepala_unit', 'pic_unit', 'direksi'].includes(u?.role)) {
        items.push({
            letter: 'c',
            name: 'Form Pengumpul Data',
            href: route('data-entry.index'),
            active: route().current('data-entry.*'),
        });
        items.push({
            letter: 'd',
            name: 'Validasi Data',
            href: route('validation.index'),
            active: route().current('validation.*'),
        });
        items.push({
            letter: 'e',
            name: 'Data dan Analisa',
            href: route('data-analysis.index'),
            active: route().current('data-analysis.*'),
        });
    }

    if (['superadmin', 'komite_mutu', 'kepala_unit', 'pic_unit'].includes(u?.role)) {
        items.push({
            letter: 'f',
            name: 'Rencana Tindak Lanjut (RTL)',
            href: route('unit.rtl.index'),
            active: route().current('unit.rtl.*'),
        });
        items.push({
            letter: 'g',
            name: 'Siklus PDCA / PDSA',
            href: route('pdsa.index'),
            active: route().current('pdsa.*') && !route().current('hospital.pdsa.*'),
        });
    }

    if (['superadmin', 'komite_mutu', 'kepala_unit', 'direksi'].includes(u?.role)) {
        items.push({
            letter: 'h',
            name: 'Laporan Mutu Unit',
            href: route('reports.index'),
            active: route().current('reports.*') && page.props.filters?.scope !== 'rs',
        });
    }

    return items;
});

// Rumah Sakit Submenu Items (a s.d i)
const rsMenuItems = computed(() => {
    const u = user.value;
    const items = [];

    if (['superadmin', 'komite_mutu', 'direksi', 'kepala_unit'].includes(u?.role)) {
        items.push({
            letter: 'a',
            name: 'Pemilihan Indikator Mutu',
            href: route('indicator-selections.index', { tipe: 'rs' }),
            active: route().current('indicator-selections.*') && page.props.filters?.tipe === 'rs',
        });
    }

    items.push({
        letter: 'b',
        name: 'Profil Indikator Mutu',
        href: route('indicators.index', { unit_id: 'rs', jenis: 'IMP-RS' }),
        active: route().current('indicators.*') && (page.props.selectedUnitId === 'rs' || page.props.filters?.jenis === 'IMP-RS'),
    });

    if (['superadmin', 'komite_mutu', 'direksi', 'kepala_unit'].includes(u?.role)) {
        items.push({
            letter: 'c',
            name: 'Data Capaian RS',
            href: route('hospital.data-capaian.index'),
            active: route().current('hospital.data-capaian.*') || route().current('hospital.analysis.*'),
        });
        items.push({
            letter: 'd',
            name: 'Rencana Tindak Lanjut (RTL) RS',
            href: route('hospital.rtl.index'),
            active: route().current('hospital.rtl.*'),
        });
        items.push({
            letter: 'e',
            name: 'Siklus PDCA / PDSA RS',
            href: route('hospital.pdsa.index'),
            active: route().current('hospital.pdsa.*'),
        });
        items.push({
            letter: 'f',
            name: 'Agregasi Data Mutu',
            href: route('hospital.aggregation.index'),
            active: route().current('hospital.aggregation.*'),
        });
        items.push({
            letter: 'g',
            name: 'Benchmark Mutu',
            href: route('hospital.benchmark.index'),
            active: route().current('hospital.benchmark.*'),
        });
        items.push({
            letter: 'h',
            name: 'Supervisi Mutu RS',
            href: route('supervisi.index'),
            active: route().current('supervisi.*'),
        });
        items.push({
            letter: 'i',
            name: 'Laporan Eksekutif Mutu RS',
            href: route('reports.index', { scope: 'rs' }),
            active: route().current('reports.*') && page.props.filters?.scope === 'rs',
        });
    }

    return items;
});

// System Settings Menu Items
const systemLinks = computed(() => {
    const u = user.value;
    const links = [];

    if (['superadmin', 'komite_mutu'].includes(u?.role)) {
        links.push({ name: 'Periode Kerja Mutu', href: route('periods.index'), icon: 'periods', active: route().current('periods.*') });
    }
    if (u?.role === 'superadmin') {
        links.push({ name: 'Unit / Instalasi', href: route('units.index'), icon: 'units', active: route().current('units.*') });
        links.push({ name: 'Manajemen Pengguna', href: route('users.index'), icon: 'users', active: route().current('users.*') });
    }

    return links;
});

const roleColors = {
    superadmin:  'bg-blue-700',
    komite_mutu: 'bg-blue-500',
    kepala_unit: 'bg-sky-500',
    pic_unit:    'bg-cyan-500',
    direksi:     'bg-blue-800',
};
const roleColor = computed(() => roleColors[user.value?.role] || 'bg-blue-500');

// Handle Flash Messages with SweetAlert2 Toast
const handleFlashMessages = () => {
    const flash = page.props.flash;
    if (!flash) return;

    if (flash.success) {
        window.notify?.toast?.success('Sukses', flash.success);
    }
    if (flash.error) {
        window.notify?.toast?.error('Perhatian', flash.error);
    }
    if (flash.warning) {
        window.notify?.toast?.warning('Peringatan', flash.warning);
    }
    if (flash.info) {
        window.notify?.toast?.info('Informasi', flash.info);
    }
};

onMounted(() => {
    handleFlashMessages();
    syncActiveDropdown();
});

watch(() => page.props.flash, () => {
    handleFlashMessages();
}, { deep: true });

watch(() => page.url, () => {
    syncActiveDropdown();
});

const confirmLogout = async () => {
    const isConfirmed = await (window.notify?.confirm ? window.notify.confirm({
        title: 'Konfirmasi Keluar Sesi',
        text: 'Apakah Anda yakin ingin mengakhiri sesi kerja saat ini?',
        icon: 'question',
        confirmButtonText: 'Ya, Keluar Sesi',
        cancelButtonText: 'Tetap di Sini',
        confirmButtonColor: '#2563eb',
    }) : window.confirm('Apakah Anda yakin ingin keluar dari sistem?'));

    if (isConfirmed) {
        router.post(route('logout'));
    }
};
</script>

<template>
    <div class="flex h-screen bg-gray-50 font-sans overflow-hidden">

        <!-- Mobile Overlay -->
        <div v-if="mobileOpen" class="fixed inset-0 z-20 bg-black/40 lg:hidden" @click="mobileOpen = false" />

        <!-- ─── Sidebar ───────────────────────────────────────────────────── -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 flex flex-col transition-all duration-300 ease-in-out lg:relative lg:translate-x-0 relative overflow-hidden bg-gradient-to-b from-blue-950 via-blue-900 to-slate-900 border-r border-white/10 shadow-2xl',
                sidebarOpen ? 'w-72' : 'w-20',
                mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- Ambient Glow Backgrounds matching Login page -->
            <div class="absolute -top-24 -left-24 w-72 h-72 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/3 -right-24 w-60 h-60 bg-sky-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -left-20 w-60 h-60 bg-blue-600/15 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Logo RS & Brand Header -->
            <div class="relative z-10 flex items-center gap-3 px-4 py-4 border-b border-white/10 bg-white/5 backdrop-blur-md">
                <div class="flex-shrink-0 w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-950/50 overflow-hidden p-1 border border-white/20">
                    <img src="/images/logo_rsys.png" alt="Logo RS" class="w-full h-full object-contain" />
                </div>
                <div v-if="sidebarOpen" class="overflow-hidden">
                    <div class="flex items-center gap-1.5">
                        <p class="text-white font-extrabold text-sm tracking-wide">MUTU RSYS</p>
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.9)]"></span>
                    </div>
                    <p class="text-blue-300 text-[11px] font-medium leading-tight">Sistem Indikator Mutu RS</p>
                </div>
            </div>

            <!-- Navigation with Seamless Custom Scrollbar -->
            <nav class="relative z-10 flex-1 overflow-y-auto py-3 px-3 space-y-1.5 seamless-scrollbar">
                
                <!-- Dashboard Link -->
                <Link
                    :href="route('dashboard')"
                    @click="mobileOpen = false"
                    :class="[
                        'group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200',
                        route().current('dashboard') 
                            ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-md shadow-blue-500/30 font-bold border border-blue-400/30 transform translate-x-1' 
                            : 'text-blue-200/90 hover:bg-white/10 hover:text-white'
                    ]"
                    :title="!sidebarOpen ? 'Dashboard' : ''"
                >
                    <span :class="['flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-lg', route().current('dashboard') ? 'text-white' : 'text-sky-300']">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </span>
                    <span v-if="sidebarOpen" class="truncate">Dashboard Utama</span>
                </Link>

                <!-- ═════════════════════════════════════════════════════════════ -->
                <!-- I. INDIKATOR MUTU (MENU UTAMA DROPDOWN) -->
                <!-- ═════════════════════════════════════════════════════════════ -->
                <div class="space-y-1">
                    <!-- Dropdown Trigger Menu Utama: Indikator Mutu -->
                    <button
                        @click="toggleImutDropdown"
                        type="button"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200',
                            isImutActive
                                ? 'bg-sky-500/20 text-white border border-sky-400/40 shadow-sm backdrop-blur-sm'
                                : 'text-blue-200/90 hover:bg-white/[0.08] hover:text-white'
                        ]"
                        :title="!sidebarOpen ? 'Indikator Mutu' : ''"
                    >
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <span :class="['flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-lg', isImutActive ? 'text-sky-300' : 'text-sky-400/80']">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </span>
                            <span v-if="sidebarOpen" class="truncate tracking-wide text-left">
                                Indikator Mutu
                            </span>
                        </div>

                        <div v-if="sidebarOpen" class="flex items-center gap-1.5 flex-shrink-0">
                            <span v-if="isImutActive" class="w-1.5 h-1.5 rounded-full bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.9)] animate-pulse"></span>
                            <span :class="['px-1.5 py-0.5 rounded text-[9px] font-mono border', isImutActive ? 'bg-sky-400/20 text-sky-200 border-sky-400/30 font-bold' : 'bg-white/10 text-blue-200 border-white/15']">
                                {{ unitMenuItems.length + rsMenuItems.length }}
                            </span>
                            <svg
                                :class="['w-3.5 h-3.5 text-blue-200 transition-transform duration-200', imutDropdownOpen ? 'rotate-90 text-white' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>

                    <!-- Konten Dropdown Indikator Mutu: Tingkat Unit & Tingkat Rumah Sakit -->
                    <transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div
                            v-show="sidebarOpen && imutDropdownOpen"
                            class="pl-2 space-y-1.5 my-1 transition-all"
                        >
                            <!-- 1. TINGKAT UNIT DROPDOWN -->
                            <div class="space-y-1">
                                <!-- Trigger Button -->
                                <button
                                    @click="toggleUnitDropdown"
                                    type="button"
                                    :class="[
                                        'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200',
                                        isUnitActive
                                            ? 'bg-sky-500/15 text-white border border-sky-400/30 shadow-xs font-bold'
                                            : 'text-blue-200/90 hover:bg-white/[0.08] hover:text-white'
                                    ]"
                                >
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        <span class="flex-shrink-0 w-4.5 h-4.5 flex items-center justify-center text-sky-300">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4.5 h-4.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                        </span>
                                        <span class="truncate tracking-wide text-left">
                                            Tingkat Unit
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span :class="['px-1.5 py-0.5 rounded text-[9px] font-mono border', isUnitActive ? 'bg-sky-400/20 text-sky-200 border-sky-400/30 font-bold' : 'bg-white/10 text-blue-200 border-white/15']">
                                            {{ unitMenuItems.length }}
                                        </span>
                                        <svg
                                            :class="['w-3.5 h-3.5 text-blue-200 transition-transform duration-200', unitDropdownOpen ? 'rotate-90 text-white' : '']"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>

                                <!-- Sub-Menu Tingkat Unit (a s.d h) -->
                                <transition
                                    enter-active-class="transition-all duration-150 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-100 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1"
                                >
                                    <div
                                        v-show="unitDropdownOpen"
                                        class="pl-3.5 pr-1 space-y-1 border-l border-sky-400/25 ml-4 my-1 transition-all"
                                    >
                                        <Link
                                            v-for="sub in unitMenuItems"
                                            :key="sub.name"
                                            :href="sub.href"
                                            @click="mobileOpen = false"
                                            :class="[
                                                'group flex items-center justify-between px-3 py-1.5 rounded-lg text-[11px] font-medium transition-all duration-150',
                                                sub.active
                                                    ? 'bg-gradient-to-r from-blue-600 to-sky-600 text-white shadow-md shadow-blue-500/25 font-bold border border-sky-400/30'
                                                    : 'text-blue-200/90 hover:bg-white/10 hover:text-white'
                                            ]"
                                        >
                                            <span class="truncate leading-tight">{{ sub.name }}</span>
                                            <span v-if="sub.active" class="w-1.5 h-1.5 rounded-full bg-sky-200 flex-shrink-0 shadow-[0_0_6px_rgba(186,230,253,0.8)]"></span>
                                        </Link>
                                    </div>
                                </transition>
                            </div>

                            <!-- 2. TINGKAT RUMAH SAKIT DROPDOWN -->
                            <div class="space-y-1 pt-0.5">
                                <!-- Trigger Button -->
                                <button
                                    @click="toggleRsDropdown"
                                    type="button"
                                    :class="[
                                        'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200',
                                        isRsActive
                                            ? 'bg-blue-600/25 text-white border border-blue-400/35 shadow-xs font-bold'
                                            : 'text-blue-200/90 hover:bg-white/[0.08] hover:text-white'
                                    ]"
                                >
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        <span class="flex-shrink-0 w-4.5 h-4.5 flex items-center justify-center text-blue-300">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4.5 h-4.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </span>
                                        <span class="truncate tracking-wide text-left">
                                            Tingkat Rumah Sakit
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span :class="['px-1.5 py-0.5 rounded text-[9px] font-mono border', isRsActive ? 'bg-blue-500/20 text-blue-200 border-blue-400/30 font-bold' : 'bg-white/10 text-blue-200 border-white/15']">
                                            {{ rsMenuItems.length }}
                                        </span>
                                        <svg
                                            :class="['w-3.5 h-3.5 text-blue-200 transition-transform duration-200', rsDropdownOpen ? 'rotate-90 text-white' : '']"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>

                                <!-- Sub-Menu Tingkat Rumah Sakit (a s.d i) -->
                                <transition
                                    enter-active-class="transition-all duration-150 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-100 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1"
                                >
                                    <div
                                        v-show="rsDropdownOpen"
                                        class="pl-3.5 pr-1 space-y-1 border-l border-blue-400/25 ml-4 my-1 transition-all"
                                    >
                                        <Link
                                            v-for="sub in rsMenuItems"
                                            :key="sub.name"
                                            :href="sub.href"
                                            @click="mobileOpen = false"
                                            :class="[
                                                'group flex items-center justify-between px-3 py-1.5 rounded-lg text-[11px] font-medium transition-all duration-150',
                                                sub.active
                                                    ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-md shadow-blue-500/25 font-bold border border-blue-400/30'
                                                    : 'text-blue-200/90 hover:bg-white/10 hover:text-white'
                                            ]"
                                        >
                                            <span class="truncate leading-tight">{{ sub.name }}</span>
                                            <span v-if="sub.active" class="w-1.5 h-1.5 rounded-full bg-sky-300 flex-shrink-0 shadow-[0_0_6px_rgba(125,211,252,0.8)]"></span>
                                        </Link>
                                    </div>
                                </transition>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- ═════════════════════════════════════════════════════════════ -->
                <!-- II. INSIDEN KESELAMATAN PASIEN (IKP) -->
                <!-- ═════════════════════════════════════════════════════════════ -->
                <div class="space-y-1">
                    <!-- Dropdown Trigger Menu Utama: IKP -->
                    <button
                        @click="toggleIkpDropdown"
                        type="button"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200',
                            isIkpActive
                                ? 'bg-rose-500/20 text-white border border-rose-400/40 shadow-sm backdrop-blur-sm'
                                : 'text-blue-200/90 hover:bg-white/[0.08] hover:text-white'
                        ]"
                        :title="!sidebarOpen ? 'Insiden Keselamatan Pasien' : ''"
                    >
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <span :class="['flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-lg', isIkpActive ? 'text-rose-400' : 'text-rose-400/80']">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </span>
                            <span v-if="sidebarOpen" class="truncate tracking-wide text-left">
                                Pelaporan IKP
                            </span>
                        </div>

                        <div v-if="sidebarOpen" class="flex items-center gap-1.5 flex-shrink-0">
                            <span v-if="isIkpActive" class="w-1.5 h-1.5 rounded-full bg-rose-400 shadow-[0_0_8px_rgba(244,63,94,0.9)] animate-pulse"></span>
                            <span :class="['px-1.5 py-0.5 rounded text-[9px] font-mono border', isIkpActive ? 'bg-rose-400/20 text-rose-200 border-rose-400/30 font-bold' : 'bg-white/10 text-blue-200 border-white/15']">
                                {{ ikpMenuItems.length }}
                            </span>
                            <svg
                                :class="['w-3.5 h-3.5 text-blue-200 transition-transform duration-200', ikpDropdownOpen ? 'rotate-90 text-white' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>

                    <!-- Sub-menu IKP (a s.d b) -->
                    <transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div
                            v-show="sidebarOpen && ikpDropdownOpen"
                            class="pl-3.5 pr-1 space-y-1 border-l border-rose-400/25 ml-4 my-1 transition-all"
                        >
                            <Link
                                v-for="sub in ikpMenuItems"
                                :key="sub.name"
                                :href="sub.href"
                                @click="mobileOpen = false"
                                :class="[
                                    'group flex items-center justify-between px-3 py-1.5 rounded-lg text-[11px] font-medium transition-all duration-150',
                                    sub.active
                                        ? 'bg-gradient-to-r from-rose-600 to-red-600 text-white shadow-md shadow-rose-500/25 font-bold border border-rose-400/30'
                                        : 'text-blue-200/90 hover:bg-white/10 hover:text-white'
                                ]"
                            >
                                <span class="truncate leading-tight">{{ sub.name }}</span>
                                <span v-if="sub.active" class="w-1.5 h-1.5 rounded-full bg-rose-200 flex-shrink-0 shadow-[0_0_6px_rgba(254,205,211,0.8)]"></span>
                            </Link>
                        </div>
                    </transition>
                </div>

                <!-- ═════════════════════════════════════════════════════════════ -->
                <!-- III. MANAJEMEN RISIKO (MANRISK) -->
                <!-- ═════════════════════════════════════════════════════════════ -->
                <div class="space-y-1">
                    <!-- Dropdown Trigger Menu Utama: Manrisk -->
                    <button
                        @click="toggleManriskDropdown"
                        type="button"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200',
                            isManriskActive
                                ? 'bg-amber-500/20 text-white border border-amber-400/40 shadow-sm backdrop-blur-sm'
                                : 'text-blue-200/90 hover:bg-white/[0.08] hover:text-white'
                        ]"
                        :title="!sidebarOpen ? 'Manajemen Risiko' : ''"
                    >
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <span :class="['flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-lg', isManriskActive ? 'text-amber-400' : 'text-amber-400/80']">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </span>
                            <span v-if="sidebarOpen" class="truncate tracking-wide text-left">
                                Manajemen Risiko
                            </span>
                        </div>

                        <div v-if="sidebarOpen" class="flex items-center gap-1.5 flex-shrink-0">
                            <span v-if="isManriskActive" class="w-1.5 h-1.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.9)] animate-pulse"></span>
                            <span :class="['px-1.5 py-0.5 rounded text-[9px] font-mono border', isManriskActive ? 'bg-amber-400/20 text-amber-200 border-amber-400/30 font-bold' : 'bg-white/10 text-blue-200 border-white/15']">
                                {{ manriskMenuItems.length }}
                            </span>
                            <svg
                                :class="['w-3.5 h-3.5 text-blue-200 transition-transform duration-200', manriskDropdownOpen ? 'rotate-90 text-white' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>

                    <!-- Sub-menu Manrisk (a s.d e) -->
                    <transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div
                            v-show="sidebarOpen && manriskDropdownOpen"
                            class="pl-3.5 pr-1 space-y-1 border-l border-amber-400/25 ml-4 my-1 transition-all"
                        >
                            <Link
                                v-for="sub in manriskMenuItems"
                                :key="sub.name"
                                :href="sub.href"
                                @click="mobileOpen = false"
                                :class="[
                                    'group flex items-center justify-between px-3 py-1.5 rounded-lg text-[11px] font-medium transition-all duration-150',
                                    sub.active
                                        ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-md shadow-amber-500/25 font-bold border border-amber-400/30'
                                        : 'text-blue-200/90 hover:bg-white/10 hover:text-white'
                                ]"
                            >
                                <span class="truncate leading-tight">{{ sub.name }}</span>
                                <span v-if="sub.active" class="w-1.5 h-1.5 rounded-full bg-amber-200 flex-shrink-0 shadow-[0_0_6px_rgba(254,240,138,0.8)]"></span>
                            </Link>
                        </div>
                    </transition>
                </div>

                <!-- ═════════════════════════════════════════════════════════════ -->
                <!-- IV. HAZARD VULNERABILITY ANALYSIS (HVA) -->
                <!-- ═════════════════════════════════════════════════════════════ -->
                <div class="space-y-1">
                    <!-- Dropdown Trigger Menu Utama: HVA -->
                    <button
                        @click="toggleHvaDropdown"
                        type="button"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200',
                            isHvaActive
                                ? 'bg-indigo-500/20 text-white border border-indigo-400/40 shadow-sm backdrop-blur-sm'
                                : 'text-blue-200/90 hover:bg-white/[0.08] hover:text-white'
                        ]"
                        :title="!sidebarOpen ? 'Kajian Bahaya (HVA)' : ''"
                    >
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <span :class="['flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-lg', isHvaActive ? 'text-indigo-400' : 'text-indigo-400/80']">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </span>
                            <span v-if="sidebarOpen" class="truncate tracking-wide text-left">
                                Kajian Bahaya (HVA)
                            </span>
                        </div>

                        <div v-if="sidebarOpen" class="flex items-center gap-1.5 flex-shrink-0">
                            <span v-if="isHvaActive" class="w-1.5 h-1.5 rounded-full bg-indigo-400 shadow-[0_0_8px_rgba(129,140,248,0.9)] animate-pulse"></span>
                            <span :class="['px-1.5 py-0.5 rounded text-[9px] font-mono border', isHvaActive ? 'bg-indigo-400/20 text-indigo-200 border-indigo-400/30 font-bold' : 'bg-white/10 text-blue-200 border-white/15']">
                                {{ hvaMenuItems.length }}
                            </span>
                            <svg
                                :class="['w-3.5 h-3.5 text-blue-200 transition-transform duration-200', hvaDropdownOpen ? 'rotate-90 text-white' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>

                    <!-- Sub-menu HVA (a s.d d) -->
                    <transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div
                            v-show="sidebarOpen && hvaDropdownOpen"
                            class="pl-3.5 pr-1 space-y-1 border-l border-indigo-400/25 ml-4 my-1 transition-all"
                        >
                            <Link
                                v-for="sub in hvaMenuItems"
                                :key="sub.name"
                                :href="sub.href"
                                @click="mobileOpen = false"
                                :class="[
                                    'group flex items-center justify-between px-3 py-1.5 rounded-lg text-[11px] font-medium transition-all duration-150',
                                    sub.active
                                        ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md shadow-indigo-500/25 font-bold border border-indigo-400/30'
                                        : 'text-blue-200/90 hover:bg-white/10 hover:text-white'
                                ]"
                            >
                                <span class="truncate leading-tight">{{ sub.name }}</span>
                                <span v-if="sub.active" class="w-1.5 h-1.5 rounded-full bg-indigo-200 flex-shrink-0 shadow-[0_0_6px_rgba(199,210,254,0.8)]"></span>
                            </Link>
                        </div>
                    </transition>
                </div>

                <!-- ═════════════════════════════════════════════════════════════ -->
                <!-- SYSTEM SETTINGS SECTION -->
                <!-- ═════════════════════════════════════════════════════════════ -->
                <div v-if="systemLinks.length > 0" class="pt-4 space-y-1">
                    <div v-if="sidebarOpen" class="px-2 pb-1">
                        <span class="text-[11px] font-semibold text-blue-300 uppercase tracking-wider font-mono">
                            PENGATURAN SISTEM
                        </span>
                    </div>

                    <Link
                        v-for="item in systemLinks"
                        :key="item.name"
                        :href="item.href"
                        @click="mobileOpen = false"
                        :class="[
                            'group flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium transition-all duration-200',
                            item.active 
                                ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-md shadow-blue-500/25 font-bold border border-blue-400/30' 
                                : 'text-blue-200/90 hover:bg-white/10 hover:text-white'
                        ]"
                        :title="!sidebarOpen ? item.name : ''"
                    >
                        <span :class="['flex-shrink-0 w-5 h-5 flex items-center justify-center', item.active ? 'text-white' : 'text-blue-300']">
                            <svg v-if="item.icon === 'periods'" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <svg v-else-if="item.icon === 'units'" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <svg v-else-if="item.icon === 'users'" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </span>
                        <span v-if="sidebarOpen" class="truncate">{{ item.name }}</span>
                    </Link>
                </div>
            </nav>

            <!-- User Profile & Logout Footer -->
            <div class="relative z-10 border-t border-white/10 p-3 bg-blue-950/60 backdrop-blur-md">
                <Link :href="route('profile.edit')" class="flex items-center gap-3 p-2 rounded-xl hover:bg-white/10 transition-colors">
                    <div :class="['flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center text-white text-xs font-extrabold shadow-sm border border-white/20', roleColor]">
                        {{ user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div v-if="sidebarOpen" class="overflow-hidden">
                        <p class="text-white text-xs font-bold truncate leading-tight">{{ user?.name }}</p>
                        <p v-if="user?.nama_lengkap" class="text-blue-200 text-[10px] truncate">{{ user?.nama_lengkap }}</p>
                        <p class="text-blue-300 text-[10px] truncate font-medium">{{ user?.role_label }}</p>
                    </div>
                </Link>
                <button
                    @click="confirmLogout"
                    type="button"
                    :class="['mt-1 w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-rose-200 hover:text-white hover:bg-rose-500/20 hover:border-rose-500/30 transition-colors text-xs border border-transparent', !sidebarOpen && 'justify-center']"
                >
                    <svg class="w-4 h-4 flex-shrink-0 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span v-if="sidebarOpen" class="font-semibold text-rose-200">Keluar Sesi</span>
                </button>
            </div>
        </aside>

        <!-- ─── Main Content Area ──────────────────────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Bar -->
            <header class="flex-shrink-0 bg-white border-b border-gray-200 shadow-xs">
                <div class="flex items-center gap-4 px-4 h-14">
                    <button
                        @click="sidebarOpen = !sidebarOpen; mobileOpen = !mobileOpen"
                        class="p-2 rounded-xl hover:bg-gray-100 text-gray-600 transition-colors"
                        title="Perluas / Perkecil Sidebar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="flex-1">
                        <slot name="header" />
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="hidden sm:block text-xs text-gray-500 font-medium">
                            {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                        </span>
                        <div :class="['px-3 py-1 rounded-full text-xs font-bold text-white shadow-xs', roleColor]">
                            {{ user?.role_label }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Password Change Warning Banner -->
            <div v-if="user?.must_change_password" class="px-6 pt-4">
                <div class="flex items-center justify-between p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs shadow-sm">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>
                            <strong class="font-semibold">Keamanan Akun:</strong> Anda masih menggunakan kata sandi bawaan sistem. Segera perbarui kata sandi akun Anda untuk melindungi kerahasiaan data mutu rumah sakit.
                        </span>
                    </div>
                    <Link :href="route('profile.edit')" class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium transition-colors text-xs ml-4 flex-shrink-0 shadow-sm">
                        Ganti Password
                    </Link>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success || $page.props.flash?.error || $page.props.flash?.warning || $page.props.flash?.info" class="px-6 pt-4">
                <div v-if="$page.props.flash?.success"
                    class="flex items-center gap-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
                <div v-if="$page.props.flash?.error"
                    class="flex items-center gap-3 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
                <div v-if="$page.props.flash?.warning"
                    class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ $page.props.flash.warning }}</span>
                </div>
                <div v-if="$page.props.flash?.info"
                    class="flex items-center gap-3 p-3 bg-sky-50 border border-sky-200 rounded-xl text-sky-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 text-sky-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $page.props.flash.info }}</span>
                </div>
            </div>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="p-6">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Seamless Custom Floating Scrollbar matching Login Glassmorphism */
.seamless-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(147, 197, 253, 0.22) transparent;
}
.seamless-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.seamless-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.seamless-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(147, 197, 253, 0.18);
    border-radius: 9999px;
    transition: background 0.2s ease-in-out;
}
.seamless-scrollbar:hover::-webkit-scrollbar-thumb {
    background: rgba(147, 197, 253, 0.42);
}
</style>
