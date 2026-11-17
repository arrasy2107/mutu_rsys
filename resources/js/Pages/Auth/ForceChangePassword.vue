<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { appUrl } from '@/Utils/appUrl';

defineProps({ userName: String });

const form = useForm({ password: '', password_confirmation: '' });
const showPass = ref(false);
const showConfirm = ref(false);
const brandingBackgroundImage = `url("${appUrl('images/pic_rsys.jpeg')}")`;

const strength = ref(0);
const checkStrength = (val) => {
    let s = 0;
    if (val.length >= 12) s++;
    if (/[A-Z]/.test(val)) s++;
    if (/[a-z]/.test(val)) s++;
    if (/[0-9]/.test(val)) s++;
    strength.value = s;
};

const strengthLabel = ['', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
const strengthColor = ['', 'bg-rose-500', 'bg-amber-400', 'bg-sky-400', 'bg-emerald-400'];

const submit = () => form.post(route('password.force-change.update'), {
    onError: () => {
        window.notify?.toast?.error('Gagal Memperbarui', 'Periksa kembali kesesuaian dan kekuatan kata sandi baru Anda.');
    }
});
</script>

<template>
    <Head title="Ganti Password — MUTU RSYS" />

    <div class="min-h-screen flex bg-gradient-to-br from-blue-950 via-blue-900 to-slate-900">

        <!-- ─── Left Panel: Branding ───────────────────────────────────────── -->
        <div
            class="relative hidden flex-col justify-between overflow-hidden bg-cover bg-center p-12 lg:flex lg:w-1/2"
            :style="{ backgroundImage: brandingBackgroundImage }"
        >
            <!-- Background decoration & Overlay -->
            <div class="absolute inset-0 bg-blue-950/80 mix-blend-multiply pointer-events-none"></div>
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-500/30 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-80 h-80 bg-sky-500/20 rounded-full blur-3xl"></div>
            </div>

            <!-- Logo RS -->
            <div class="relative">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-xl overflow-hidden">
                        <img
                            :src="appUrl('images/logo_rsys.png')"
                            alt="Logo Rumah Sakit"
                            class="h-full w-full object-contain p-1"
                        />
                    </div>
                    <div>
                        <p class="text-white font-bold text-xl leading-tight">MUTU RSYS</p>
                        <p class="text-blue-300 text-sm">Sistem Indikator Mutu Rumah Sakit</p>
                    </div>
                </div>
            </div>

            <!-- Center content -->
            <div class="relative space-y-6">
                <!-- Security icon -->
                <div class="w-20 h-20 bg-amber-400/10 border-2 border-amber-400/20 rounded-2xl flex items-center justify-center">
                    <svg class="w-10 h-10 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>

                <div>
                    <h1 class="text-4xl font-bold text-white leading-tight">
                        Keamanan Akun<br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-sky-300">Prioritas Utama</span>
                    </h1>
                    <p class="mt-4 text-blue-200 text-base leading-relaxed max-w-md">
                        Demi keamanan data mutu rumah sakit, Anda diwajibkan mengganti password sementara sebelum menggunakan sistem.
                    </p>
                </div>

                <!-- Tips -->
                <div class="space-y-3">
                    <p class="text-blue-100 text-sm font-semibold">Tips membuat password kuat:</p>
                    <div v-for="tip in ['Minimal 12 karakter', 'Gunakan huruf besar (A-Z)', 'Gunakan huruf kecil (a-z)', 'Tambahkan angka (0-9)']"
                        :key="tip" class="flex items-center gap-2.5 text-blue-300 text-sm">
                        <svg class="w-4 h-4 text-sky-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ tip }}
                    </div>
                </div>
            </div>

            <p class="relative text-blue-400/60 text-xs">© 2026 MUTU RSYS. Keamanan data terlindungi.</p>
        </div>

        <!-- ─── Right Panel: Change Password Form ─────────────────────────── -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">

                <!-- Mobile Logo -->
                <div class="flex items-center gap-3 mb-8 lg:hidden">
                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-md overflow-hidden">
                        <img
                            :src="appUrl('images/logo_rsys.png')"
                            alt="Logo RS"
                            class="h-full w-full object-contain p-0.5"
                        />
                    </div>
                    <div>
                        <p class="text-white font-bold leading-none">MUTU RSYS</p>
                        <p class="text-blue-300 text-xs">Sistem Indikator Mutu</p>
                    </div>
                </div>

                <!-- Card -->
                <div class="bg-white/8 backdrop-blur-xl border border-white/15 rounded-2xl p-8 shadow-2xl">
                    <!-- Header -->
                    <div class="mb-7">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-400/10 border border-amber-400/20 rounded-full text-amber-300 text-xs font-semibold mb-4">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            Wajib Ganti Password
                        </div>
                        <h2 class="text-2xl font-bold text-white">Halo, {{ userName }}</h2>
                        <p class="text-blue-300 text-sm mt-1">Buat password baru yang aman sebelum melanjutkan.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Password Baru -->
                        <div>
                            <label for="password" class="block text-xs font-semibold text-blue-200 mb-2 uppercase tracking-wider">Password Baru</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                                <input id="password" v-model="form.password" :type="showPass ? 'text' : 'password'"
                                    autocomplete="new-password" placeholder="Masukkan password baru"
                                    @input="checkStrength(form.password)"
                                    :class="['w-full pl-10 pr-12 py-3 bg-white border rounded-xl text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-2 transition-all',
                                        form.errors.password ? 'border-rose-500 focus:ring-rose-500/30 focus:border-rose-500' : 'border-gray-300 focus:ring-blue-500/30 focus:border-blue-500']"
                                />
                                <button type="button" @click="showPass = !showPass"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg v-if="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>

                            <!-- Strength indicator -->
                            <div v-if="form.password" class="mt-2 space-y-1.5">
                                <div class="flex gap-1">
                                    <div v-for="i in 4" :key="i"
                                        :class="['h-1 flex-1 rounded-full transition-all duration-300', i <= strength ? strengthColor[strength] : 'bg-white/15']">
                                    </div>
                                </div>
                                <p v-if="strength > 0" :class="['text-xs', { 'text-rose-400': strength === 1, 'text-amber-400': strength === 2, 'text-sky-400': strength === 3, 'text-emerald-400': strength === 4 }]">
                                    {{ strengthLabel[strength] }}
                                </p>
                            </div>
                            <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-300">{{ form.errors.password }}</p>
                        </div>

                        <!-- Konfirmasi Password -->
                        <div>
                            <label for="confirm" class="block text-xs font-semibold text-blue-200 mb-2 uppercase tracking-wider">Konfirmasi Password</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </span>
                                <input id="confirm" v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'"
                                    autocomplete="new-password" placeholder="Ulangi password baru"
                                    :class="['w-full pl-10 pr-12 py-3 bg-white border rounded-xl text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-2 transition-all',
                                        form.errors.password_confirmation ? 'border-rose-500 focus:ring-rose-500/30 focus:border-rose-500' :
                                        (form.password_confirmation && form.password === form.password_confirmation) ? 'border-emerald-500 focus:ring-emerald-500/30' :
                                        'border-gray-300 focus:ring-blue-500/30 focus:border-blue-500']"
                                />
                                <button type="button" @click="showConfirm = !showConfirm"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg v-if="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                                <span v-if="form.password_confirmation && form.password === form.password_confirmation"
                                    class="absolute right-10 top-1/2 -translate-y-1/2 text-emerald-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </div>
                            <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-rose-300">{{ form.errors.password_confirmation }}</p>
                        </div>

                        <!-- Submit -->
                        <button type="submit" :disabled="form.processing || !form.password || !form.password_confirmation"
                            class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-sky-400 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-blue-500/25 hover:shadow-blue-400/40 hover:-translate-y-0.5 active:translate-y-0 mt-2">
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Menyimpan...
                            </span>
                            <span v-else>🔐 Simpan Password Baru</span>
                        </button>
                    </form>
                </div>

                <!-- Logout link -->
                <p class="mt-5 text-center">
                    <button @click="$inertia.post(route('logout'))"
                        class="text-blue-400/60 text-sm hover:text-blue-300 transition-colors">
                        ← Keluar dari sistem
                    </button>
                </p>
            </div>
        </div>
    </div>
</template>
