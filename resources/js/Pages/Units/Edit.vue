<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ unit: Object });
const form = useForm({ kode: props.unit.kode, nama: props.unit.nama, deskripsi: props.unit.deskripsi || '', aktif: props.unit.aktif });
const submit = () => form.put(route('units.update', props.unit.id));
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('units.index')" class="hover:text-indigo-600">Unit</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Edit</span>
            </nav>
        </template>

        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Edit Unit</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ unit.kode }} — {{ unit.nama }}</p>
                </div>
                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kode Unit *</label>
                        <input v-model="form.kode" type="text" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        <p v-if="form.errors.kode" class="text-xs text-rose-500 mt-1">{{ form.errors.kode }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Unit *</label>
                        <input v-model="form.nama" type="text" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        <p v-if="form.errors.nama" class="text-xs text-rose-500 mt-1">{{ form.errors.nama }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Deskripsi</label>
                        <textarea v-model="form.deskripsi" rows="3" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div class="flex items-center gap-3">
                        <input v-model="form.aktif" type="checkbox" id="aktif" class="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                        <label for="aktif" class="text-sm text-gray-700">Unit aktif</label>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('units.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Batal</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : 'Perbarui Unit' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
