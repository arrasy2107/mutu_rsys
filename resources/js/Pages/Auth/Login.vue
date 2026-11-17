<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { appUrl } from '@/Utils/appUrl';

defineProps({ status: String });

const page = usePage();
const form = useForm({ username: '', password: '', remember: false });
const showPass = ref(false);
const brandingBackgroundImage = `url("${appUrl('images/pic_rsys.jpeg')}")`;

const submit = () =>
    form.post(route('login'), {
        onError: (errors) => {
            const errorMsg =
                errors.password ||
                errors.username ||
                'Periksa kembali data masukan Anda.';
            window.notify?.toast?.error('Gagal Masuk', errorMsg);
        },
        onFinish: () => form.reset('password'),
    });

onMounted(() => {
    const flash = page.props.flash;
    if (flash?.info) {
        window.notify?.toast?.info('Sesi Ditutup', flash.info);
    } else if (flash?.success) {
        window.notify?.toast?.success('Sukses', flash.success);
    } else if (flash?.error) {
        window.notify?.toast?.error('Perhatian', flash.error);
    } else if (flash?.warning) {
        window.notify?.toast?.warning('Peringatan', flash.warning);
    }
});
</script>

<template>
    <Head title="Login" />

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

            <!-- Center Content -->
            <div class="relative space-y-8">
                <div>
                    <h1 class="text-4xl font-bold text-white leading-tight">
                        Pantau & Tingkatkan<br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-300 to-blue-200">Mutu Layanan RS</span>
                    </h1>
                    <p class="mt-4 text-blue-200 text-base leading-relaxed max-w-md">
                        Platform terintegrasi untuk pengelolaan indikator mutu, validasi data, analisis PDSA, dan pelaporan akreditasi KARS/JCI.
                    </p>
                </div>

                <!-- Feature pills -->
                <div class="flex flex-wrap gap-2">
                    <span v-for="f in ['13 Indikator Nasional Mutu', 'Validasi Data', 'Analisis PDSA', 'Laporan PDF/Excel', 'Multi Role Akses']" :key="f"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 border border-white/15 text-white/85 text-xs backdrop-blur-sm">
                        <svg class="w-3 h-3 text-sky-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ f }}
                    </span>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4">
                    <div v-for="s in [{ n: '13', l: 'Indikator INM' }, { n: '17', l: 'Unit Kerja' }, { n: '24/7', l: 'Monitoring' }]" :key="s.n"
                        class="bg-white/8 border border-white/15 rounded-xl p-4 text-center backdrop-blur-sm">
                        <p class="text-2xl font-bold text-white">{{ s.n }}</p>
                        <p class="text-xs text-blue-300 mt-1">{{ s.l }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative">
                <p class="text-blue-400 text-xs">© 2026 MUTU RSYS. Sesuai standar KARS & PMK 30/2022.</p>
            </div>
        </div>

        <!-- ─── Right Panel: Login Form ────────────────────────────────────── -->
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
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-white">Selamat Datang</h2>
                        <p class="text-blue-300 text-sm mt-1">Masuk ke sistem untuk melanjutkan</p>
                    </div>

                    <!-- Status / Flash Info / Success message -->
                    <div v-if="status || $page.props.flash?.info || $page.props.flash?.success" class="mb-5 p-3.5 bg-sky-500/10 border border-sky-500/25 rounded-xl flex items-center gap-3">
                        <svg class="w-5 h-5 text-sky-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sky-200 text-xs leading-relaxed">{{ status || $page.props.flash?.info || $page.props.flash?.success }}</p>
                    </div>

                    <!-- Validation Error alert banner -->
                    <div v-if="form.errors.username || form.errors.password" class="mb-5 p-3.5 bg-rose-500/20 border border-rose-500/35 rounded-xl flex items-start gap-3 shadow-sm">
                        <svg class="w-5 h-5 text-rose-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div class="text-xs text-rose-200 font-medium space-y-1">
                            <p v-if="form.errors.username" class="leading-relaxed">{{ form.errors.username }}</p>
                            <p v-if="form.errors.password" class="leading-relaxed">{{ form.errors.password }}</p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-xs font-semibold text-blue-200 mb-2 uppercase tracking-wider">Username</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </span>
                                <input id="username" v-model="form.username" type="text" autocomplete="username" autofocus
                                    placeholder="username"
                                    :class="['w-full pl-10 pr-4 py-3 bg-white border rounded-xl text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-2 transition-all',
                                        form.errors.username ? 'border-rose-500 focus:ring-rose-500/30 focus:border-rose-500' : 'border-gray-300 focus:ring-blue-500/30 focus:border-blue-500']"
                                />
                            </div>
                            <p v-if="form.errors.username" class="mt-1.5 text-xs text-rose-300">{{ form.errors.username }}</p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-xs font-semibold text-blue-200 mb-2 uppercase tracking-wider">Password</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                                <input id="password" v-model="form.password" :type="showPass ? 'text' : 'password'"
                                    autocomplete="current-password" placeholder="••••••••"
                                    :class="['w-full pl-10 pr-12 py-3 bg-white border rounded-xl text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-2 transition-all',
                                        form.errors.password ? 'border-rose-500 focus:ring-rose-500/30 focus:border-rose-500' : 'border-gray-300 focus:ring-blue-500/30 focus:border-blue-500']"
                                />
                                <button type="button" @click="showPass = !showPass"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg v-if="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-300">{{ form.errors.password }}</p>
                        </div>

                        <!-- Remember -->
                        <div class="flex items-center">
                            <input id="remember" v-model="form.remember" type="checkbox"
                                class="w-4 h-4 rounded border-white/20 bg-white/8 text-blue-500 focus:ring-blue-400/40" />
                            <label for="remember" class="ml-2.5 text-sm text-blue-300 cursor-pointer">Ingat sesi saya</label>
                        </div>

                        <!-- Submit -->
                        <button type="submit" :disabled="form.processing"
                            class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-sky-400 disabled:opacity-60 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-blue-500/25 hover:shadow-blue-400/40 hover:-translate-y-0.5 active:translate-y-0">
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Masuk...
                            </span>
                            <span v-else>Masuk ke Sistem</span>
                        </button>
                    </form>

                </div>

                <p class="mt-6 text-center text-blue-500/60 text-xs">
                    Hubungi IT RSYS jika lupa kredensial atau akun terkunci.
                </p>
            </div>
        </div>
    </div>
</template>
