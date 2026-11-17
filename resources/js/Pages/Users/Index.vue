<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({ users: Object, units: Array, filters: Object, roles: Array });

const page = usePage();
const currentUserId = page.props.auth?.user?.id;

const search = ref(props.filters?.search || '');
const role = ref(props.filters?.role || '');
const unitId = ref(props.filters?.unit_id || '');

const applyFilter = () => {
    router.get(route('users.index'), { search: search.value, role: role.value, unit_id: unitId.value }, { preserveScroll: true, replace: true });
};

const confirmDelete = async (id, name, username) => {
    const confirmed = await window.notify.dangerConfirm({
        title: 'Hapus Akun Pengguna?',
        html: `Apakah Anda yakin ingin menghapus akun <b>@${username}</b> (${name})?<br/><br/><span class="text-xs text-rose-600">Tindakan ini permanen dan menghapus hak akses pengguna ke sistem.</span>`,
        confirmButtonText: 'Ya, Hapus Pengguna',
    });
    if (confirmed) {
        router.delete(route('users.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                window.notify?.toast?.success('Pengguna Dihapus', `Akun @${username} berhasil dihapus.`);
            }
        });
    }
};

const roleColors = {
    superadmin:  'bg-purple-100 text-purple-700 border-purple-200',
    komite_mutu: 'bg-blue-100 text-blue-700 border-blue-200',
    kepala_unit: 'bg-teal-100 text-teal-700 border-teal-200',
    pic_unit:    'bg-emerald-100 text-emerald-700 border-emerald-200',
    direksi:     'bg-amber-100 text-amber-700 border-amber-200',
};

const avatarColors = {
    superadmin:  'bg-purple-700',
    komite_mutu: 'bg-blue-600',
    kepala_unit: 'bg-teal-600',
    pic_unit:    'bg-emerald-600',
    direksi:     'bg-amber-600',
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <h1 class="text-base font-bold text-gray-800 leading-none">Manajemen Pengguna</h1>
                <span class="text-xs text-gray-400">• Akun & Profil Pejabat</span>
            </div>
        </template>

        <div class="space-y-5">
            <!-- Header Banner Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 tracking-tight">Manajemen Pengguna & Profil Pejabat</h2>
                    <p class="text-xs text-gray-500 mt-1 max-w-2xl">
                        Kelola akun struktural rumah sakit dan identitas personil pejabat aktif (Nama Lengkap & NIP). Jika terjadi pergantian pejabat, Admin cukup memperbarui profil akun tanpa membuat akun baru. Password sementara dapat ditetapkan melalui formulir edit.
                    </p>
                </div>

                <Link :href="route('users.create')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Pengguna</span>
                </Link>
            </div>

            <!-- Filters Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="flex flex-wrap items-center gap-3 flex-1">
                    <div class="relative min-w-[260px] flex-1 max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilter"
                            type="search"
                            placeholder="Cari jabatan, nama pejabat, NIP, username..."
                            class="text-xs pl-9 pr-3 py-2 w-full border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <select v-model="role" @change="applyFilter" class="text-xs border-gray-200 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50/50">
                        <option value="">Semua Role Sistem</option>
                        <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>

                    <select v-model="unitId" @change="applyFilter" class="text-xs border-gray-200 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50/50">
                        <option value="">Semua Unit Kerja</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                    </select>

                    <button
                        @click="applyFilter"
                        class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors inline-flex items-center gap-1.5"
                    >
                        Cari
                    </button>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jabatan / Posisi</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pejabat Aktif (Profil)</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Username & Kontak</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Unit Kerja</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-indigo-50/20 transition-colors">
                                <!-- Jabatan / Akun -->
                                <td class="px-4 py-3.5 align-top">
                                    <div class="flex items-center gap-3">
                                        <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-sm', avatarColors[user.role] || 'bg-gray-500']">
                                            {{ (user.name || user.username)?.charAt(0)?.toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900 leading-snug">{{ user.name }}</p>
                                            <span class="inline-flex items-center gap-1 text-[11px] text-gray-400 mt-0.5">
                                                ID Akun: #{{ user.id }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Pejabat Aktif (Profil) -->
                                <td class="px-4 py-3.5 align-top">
                                    <div v-if="user.nama_lengkap">
                                        <div class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            {{ user.nama_lengkap }}
                                        </div>
                                        <div class="text-xs text-gray-500 font-mono mt-0.5">
                                            NIP: {{ user.nip || '-' }}
                                        </div>
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                        <span>⚠️ Pejabat belum diatur</span>
                                    </div>
                                </td>

                                <!-- Username & Kontak -->
                                <td class="px-4 py-3.5 align-top">
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                            @{{ user.username }}
                                        </span>
                                        <span v-if="user.email" class="text-xs text-gray-500">
                                            {{ user.email }}
                                        </span>
                                        <span v-else class="text-[11px] text-gray-400 italic">
                                            Tanpa email
                                        </span>
                                    </div>
                                </td>

                                <!-- Role Sistem -->
                                <td class="px-4 py-3.5 align-top text-center">
                                    <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border', roleColors[user.role] || 'bg-gray-100 text-gray-600 border-gray-200']">
                                        {{ roles.find(r => r.value === user.role)?.label || user.role }}
                                    </span>
                                </td>

                                <!-- Unit Kerja -->
                                <td class="px-4 py-3.5 align-top">
                                    <span v-if="user.unit" class="inline-flex items-center gap-1 text-xs font-medium text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        {{ user.unit.nama }}
                                    </span>
                                    <span v-else class="text-xs text-purple-700 bg-purple-50 px-2 py-0.5 rounded font-medium border border-purple-100">
                                        🏛️ Tingkat RS
                                    </span>
                                </td>

                                <!-- Status Aktif -->
                                <td class="px-4 py-3.5 align-top text-center">
                                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold', user.aktif ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500 border border-gray-200']">
                                        <span :class="['w-1.5 h-1.5 rounded-full', user.aktif ? 'bg-emerald-500' : 'bg-gray-400']"></span>
                                        {{ user.aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3.5 align-top text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Edit Profil & Akun -->
                                        <Link
                                            :href="route('users.edit', user.id)"
                                            class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit Profil & Akun"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </Link>

                                        <!-- Hapus User -->
                                        <button
                                            v-if="user.id !== currentUserId"
                                            @click="confirmDelete(user.id, user.name, user.username)"
                                            class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Hapus Pengguna"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!users.data?.length">
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">
                                    Tidak ada pengguna ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t border-gray-100 gap-3 bg-gray-50/50">
                    <p class="text-xs text-gray-500">
                        Menampilkan <span class="font-semibold text-gray-800">{{ users.from }}</span>–<span class="font-semibold text-gray-800">{{ users.to }}</span> dari <span class="font-semibold text-gray-800">{{ users.total }}</span> pengguna
                    </p>
                    <div class="flex gap-1 flex-wrap">
                        <Link
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-lg transition-colors font-medium',
                                link.active ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-40 cursor-not-allowed pointer-events-none'
                            ]"
                            v-html="link.label"
                            :preserve-scroll="true"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
