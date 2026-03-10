import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);

    const isAuthenticated = computed(() => user.value !== null);
    const isAdmin = computed(() => user.value?.is_admin === true);

    async function fetchUser() {
        try {
            const response = await axios.get('/api/user');
            user.value = response.data.user;
        } catch {
            user.value = null;
        }
    }

    async function login(email, password) {
        const response = await axios.post('/api/login', { email, password });
        user.value = response.data.user;
    }

    async function logout() {
        await axios.post('/api/logout');
        user.value = null;
    }

    return { user, isAuthenticated, isAdmin, fetchUser, login, logout };
});
