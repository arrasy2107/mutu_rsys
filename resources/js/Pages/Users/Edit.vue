<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, router } from '@inertiajs/vue3';

const props = defineProps({ user: Object, units: Array, roles: Array });

const form = useForm({
    username: props.user.username || '',
    name: props.user.name || '',
    nama_lengkap: props.user.nama_lengkap || '',
    nip: props.user.nip || '',
    email: props.user.email || '',
    password: '',
    password_confirmation: '',
    role: props.user.role,
    unit_id: props.user.unit_id || '',
    aktif: props.user.aktif ?? true,
});

const submit = () => form.put(route('users.update', props.user.id), {
    onSuccess: () => {
        window.notify?.toast?.success('Data Disimpan', 'Profil pengguna berhasil diperbarui.');
    }
});

const confirmResetPassword = async () => {
    const confirmed = await window.notify.confirm({
        title: 'Reset Password Akun?',
        html: `Apakah Anda yakin ingin me-reset password untuk akun <b>@${props.user.username}</b> (${props.user.name}) ke default: <strong class="font-mono text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">12345678</strong>?<br/><br/><span class="text-xs text-slate-500">Pengguna wajib membuat password baru saat login berikutnya.</span>`,
        icon: 'warning',
        confirmButtonText: 'Ya, Reset Password',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d97706',
    });
    if (confirmed) {
        router.post(route('users.reset-password', props.user.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                window.notify?.toast?.success('Password Direset', `Password akun @${props.user.username} berhasil di-reset ke 12345678.`);
            }
        });
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('users.index')" class="hover:text-indigo-600">Pengguna</Link>
                <span>/</span>
                <span class="text-gray-800 font-medium">Edit Profil: @{{ user.username }}</span>
            </nav>
        </template>

        <div class="max-w-2xl mx-auto space-y-5">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Edit Profil & Akun Pengguna</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Akun: <span class="font-mono font-bold text-indigo-600">@{{ user.username }}</span> — {{ user.name }}
                        </p>
                    </div>

                    <!-- Tombol Reset Password Cepat -->
                    <button
                        type="button"
                        @click="confirmResetPassword"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold transition-colors"
                        title="Kembalikan password ke default 12345678"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <span>Reset Password (12345678)</span>
                    </button>
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
                                        class="w-full text-sm pl-7 border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                                    />
                                </div>
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
                                class="w-full text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1">{{ form.errors.email }}</p>
                        </div>

                        <!-- Manual Password Change -->
                        <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                            <p class="text-xs font-bold text-gray-700">Ubah Password Manual (Opsional):</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <input
                                        v-model="form.password"
                                        type="password"
                                        placeholder="Password baru (min. 8 karakter)"
                                        class="w-full text-xs border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                    <p v-if="form.errors.password" class="text-xs text-rose-500 mt-1">{{ form.errors.password }}</p>
                                </div>
                                <div>
                                    <input
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="Ulangi password baru"
                                        class="w-full text-xs border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 2: PROFIL PEJABAT AKTIF -->
                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                            <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center">2</span>
                            <div>
                                <h3 class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Profil Pejabat / Personil Aktif</h3>
                                <p class="text-[11px] text-gray-500">Ubah data di bawah ini saat terjadi pergantian pejabat yang memegang akun ini.</p>
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
                            {{ form.processing ? 'Menyimpan...' : 'Perbarui Profil Pengguna' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
