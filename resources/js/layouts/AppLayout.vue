<template>
    <div>
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-4">
                <a href="/dashboard" class="flex items-center gap-2" @click.prevent="$router.push('/dashboard')">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600">
                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-slate-800">1on1 Tool</span>
                </a>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-600">{{ auth.user?.name }}</span>
                    <button type="button" class="text-sm text-slate-500 transition hover:text-slate-700" @click="handleLogout">ログアウト</button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const auth = useAuthStore();

async function handleLogout() {
    try {
        await auth.logout();
    } catch {
        // ログアウト失敗でもログイン画面へ遷移
    }
    router.push({ name: 'login' });
}
</script>
