<template>
    <GuestLayout>
        <div class="flex min-h-screen items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <!-- ロゴ・タイトル -->
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-800">1on1 Tool</h1>
                    <p class="mt-1 text-sm text-slate-500">日々の気持ちを、チームの力に</p>
                </div>

                <!-- ログインフォーム -->
                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <form class="space-y-5" @submit.prevent="handleLogin">
                        <!-- エラー表示 -->
                        <div v-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ errorMessage }}
                        </div>

                        <!-- メールアドレス -->
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">メールアドレス</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="例: tanaka@example.com"
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                        </div>

                        <!-- パスワード -->
                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">パスワード</label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="パスワードを入力"
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                        </div>

                        <!-- ログインボタン -->
                        <button
                            type="submit"
                            :disabled="isLoading"
                            class="flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span>{{ isLoading ? 'ログイン中…' : 'ログイン' }}</span>
                            <svg v-if="isLoading" class="ml-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- テストユーザー情報 -->
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">テストアカウント</p>
                    <div class="space-y-3">
                        <button
                            v-for="account in testAccounts"
                            :key="account.email"
                            type="button"
                            class="group flex w-full items-center gap-3 rounded-lg border border-slate-100 px-3 py-2.5 text-left transition hover:border-indigo-200 hover:bg-indigo-50/50"
                            @click="loginWithTestAccount(account)"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                                :class="account.badgeClass"
                            >{{ account.badge }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-700">{{ account.label }}</p>
                                <p class="truncate text-xs text-slate-400">{{ account.email }}</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import GuestLayout from '../layouts/GuestLayout.vue';

const router = useRouter();
const auth = useAuthStore();

const form = reactive({
    email: '',
    password: '',
});

const isLoading = ref(false);
const errorMessage = ref('');

const testAccounts = [
    { email: 'admin@example.com', password: 'password', label: 'システム管理者', badge: '管', badgeClass: 'bg-amber-100 text-amber-700' },
    { email: 'subordinate@example.com', password: 'password', label: '田中 花子（部下）', badge: '部', badgeClass: 'bg-sky-100 text-sky-700' },
    { email: 'manager@example.com', password: 'password', label: '山田 太郎（上司）', badge: '上', badgeClass: 'bg-emerald-100 text-emerald-700' },
];

async function handleLogin() {
    errorMessage.value = '';
    isLoading.value = true;

    try {
        await auth.login(form.email, form.password);
        router.push(auth.isAdmin ? { name: 'admin.dashboard' } : { name: 'dashboard' });
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.email?.[0]
            || error.response?.data?.message
            || 'ログインに失敗しました。もう一度お試しください。';
    } finally {
        isLoading.value = false;
    }
}

function loginWithTestAccount(account) {
    form.email = account.email;
    form.password = account.password;
    handleLogin();
}
</script>
