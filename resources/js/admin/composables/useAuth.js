import { ref } from 'vue';
import { useRouter } from 'vue-router';

const token = ref(localStorage.getItem('admin_token') || null);
const user = ref(JSON.parse(localStorage.getItem('admin_user') || 'null'));

export function useAuth() {
    const router = useRouter();

    const isAuthenticated = () => !!token.value;

    const login = async (email, password) => {
        const res = await fetch('/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email, password }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Login failed');

        token.value = data.token;
        user.value = data.user;
        localStorage.setItem('admin_token', data.token);
        localStorage.setItem('admin_user', JSON.stringify(data.user));
    };

    const logout = async () => {
        try {
            await fetch('/api/auth/logout', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token.value}`, 'Accept': 'application/json' },
            });
        } catch {}
        token.value = null;
        user.value = null;
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
        router.push('/admin/login');
    };

    const apiFetch = async (url, options = {}) => {
        const res = await fetch(url, {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token.value}`,
                ...(options.headers || {}),
            },
        });
        if (res.status === 401) {
            logout();
            throw new Error('Unauthorized');
        }
        return res;
    };

    return { token, user, isAuthenticated, login, logout, apiFetch };
}
