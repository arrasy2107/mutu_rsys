<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({ units: Object, filters: Object });

const search = ref(props.filters?.search || '');

const applyFilter = () => {
    router.get(route('units.index'), { search: search.value }, { preserveScroll: true, replace: true });
};

const confirmDelete = async (id, nama) => {
    const confirmed = await window.notify.dangerConfirm({
        title: 'Hapus Unit / Instalasi?',
        text: `Apakah Anda yakin ingin menghapus unit "${nama || 'ini'}"? Tindakan ini tidak dapat dibatalkan.`,
        confirmButtonText: 'Ya, Hapus Unit',
    });
    if (confirmed) {
        router.delete(route('units.destroy', id), {
            onSuccess: () => {
                window.notify?.toast?.success('Unit Dihapus', 'Unit/instalasi berhasil dihapus.');
            }
        });
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-800">Unit / Instalasi</h1>
        </template>

        <div class="flex items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-3">
                <input v-model="search" @keyup.enter="applyFilter" type="search"
                    placeholder="Cari unit..."
                    class="text-sm border-gray-200 rounded-lg px-3 py-2 w-64 focus:ring-indigo-500 focus:border-indigo-500" />
                <button @click="applyFilter" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">Cari</button>
            </div>
            <Link :href="route('units.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Unit
            </Link>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Unit</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Deskripsi</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Pengguna</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="unit in units.data" :key="unit.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-bold text-indigo-700">{{ unit.kode }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ unit.nama }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ unit.deskripsi || '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">
                                {{ unit.users_count || 0 }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="['inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium', unit.aktif ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500']">
                                <span :class="['w-1.5 h-1.5 rounded-full', unit.aktif ? 'bg-emerald-400' : 'bg-gray-300']"></span>
                                {{ unit.aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <Link :href="route('units.edit', unit.id)" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </Link>
                                <button @click="confirmDelete(unit.id, unit.nama)"
                                    class="p-1.5 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!units.data?.length">
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">Tidak ada unit ditemukan.</td>
                    </tr>
                </tbody>
            </table>

            <div v-if="units.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
                <p class="text-xs text-gray-500">{{ units.from }}–{{ units.to }} dari {{ units.total }}</p>
                <div class="flex gap-1">
                    <Link v-for="link in units.links" :key="link.label" :href="link.url || '#'"
                        :class="['px-3 py-1.5 text-xs rounded-lg', link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100', !link.url && 'opacity-40 cursor-not-allowed']"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
