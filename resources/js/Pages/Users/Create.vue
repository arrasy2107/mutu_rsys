<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ units: Array, roles: Array });

const form = useForm({
    username: '',
    name: '',
    nama_lengkap: '',
    nip: '',
    email: '',
    role: 'pic_unit',
    unit_id: '',
    aktif: true,
});

const submit = () => form.post(route('users.store'));
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('users.index')" class="hover:text-indigo-600">Pengguna</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Tambah Baru</span>
            </nav>
        </template>

        <div class="max-w-2xl mx-auto space-y-5">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Tambah Akun Pengguna & Profil Pejabat</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Daftarkan akun peran struktural baru serta personil yang menjabat.</p>
                    </div>
                    <Link :href="route('users.index')" class="text-xs text-gray-500 hover:text-gray-700">← Kembali</Link>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- SEKSI 1: AKUN SISTEM -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                            <span class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center">1</span>
                            <h3 class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Informasi Akun Sistem & Hak Akses</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Username -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                    Username Login <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 font-mono text-xs">@</span>
                                    <input
                                        v-model="form.username"
                                        type="text"
                                        placeholder="cth: pic_irj, karu_igd"
                                        class="w-full text-sm pl-7 border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                                    />
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Gunakan huruf kecil, angka, atau underscore.</p>
                                <p v-if="form.errors.username" class="text-xs text-rose-500 mt-1">{{ form.errors.username }}</p>
                            </div>

                            <!-- Nama Jabatan / Posisi -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                    Nama Jabatan / Posisi Akun <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="cth: PIC Mutu Rawat Jalan, Kepala Ruangan IGD"
                                    class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Role -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                    Role Akses <span class="text-rose-500">*</span>
                                </label>
                                <select v-model="form.role" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50/50">
                                    <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                                </select>
                                <p v-if="form.errors.role" class="text-xs text-rose-500 mt-1">{{ form.errors.role }}</p>
                            </div>

                            <!-- Unit -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Unit Kerja Terkait</label>
                                <select v-model="form.unit_id" class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50/50">
                                    <option value="">-- Tingkat RS / Tanpa Unit --</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option>
                                </select>
                                <p class="text-[11px] text-gray-400 mt-1">Pilih unit jika user adalah Kepala Unit atau PIC Unit.</p>
                                <p v-if="form.errors.unit_id" class="text-xs text-rose-500 mt-1">{{ form.errors.unit_id }}</p>
                            </div>
                        </div>

                        <!-- Email (Opsional) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Alamat Email <span class="text-gray-400 font-normal">(Opsional)</span>
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="cth: pic.irj@mutu-rsys.id"
                                class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <!-- SEKSI 2: PROFIL PEJABAT AKTIF -->
                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                            <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center">2</span>
                            <div>
                                <h3 class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Profil Pejabat / Personil Aktif</h3>
                                <p class="text-[11px] text-gray-500">Personil yang saat ini bertanggung jawab memegang akun/jabatan ini.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Nama Lengkap Pejabat -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                    Nama Lengkap Pejabat
                                </label>
                                <input
                                    v-model="form.nama_lengkap"
                                    type="text"
                                    placeholder="cth: dr. Hendra Setiawan, Sp.PK"
                                    class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                <p class="text-[11px] text-gray-400 mt-1">Lengkap dengan gelar akademik/profesi.</p>
                                <p v-if="form.errors.nama_lengkap" class="text-xs text-rose-500 mt-1">{{ form.errors.nama_lengkap }}</p>
                            </div>

                            <!-- NIP Pejabat -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                    NIP / NIK Pegawai
                                </label>
                                <input
                                    v-model="form.nip"
                                    type="text"
                                    placeholder="cth: 198504122010012003 atau RSYS-045"
                                    class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                                />
                                <p class="text-[11px] text-gray-400 mt-1">Nomor Induk Pegawai di RS.</p>
                                <p v-if="form.errors.nip" class="text-xs text-rose-500 mt-1">{{ form.errors.nip }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Note Password Default -->
                    <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="text-xs text-amber-800 leading-relaxed">
                            <span class="font-bold">Password Default Otomatis:</span> Akun baru akan dibuat dengan password bawaan <strong class="font-mono bg-white px-1.5 py-0.5 rounded border border-amber-300">12345678</strong>. Pengguna akan secara otomatis diwajibkan untuk mengganti password saat login pertama kali.
                        </div>
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center gap-3">
                        <input v-model="form.aktif" type="checkbox" id="aktif" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                        <label for="aktif" class="text-xs font-semibold text-gray-700 cursor-pointer">Akun dalam status aktif</label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('users.index')" class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengguna Baru' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
