import { ref } from 'vue';
import { useRouter } from 'vue-router';

const token = ref(localStorage.getItem('admin_token') || null);
const user = ref(JSON.parse(localStorage.getItem('admin_user') || 'null'));

export function useAuth() {
    const router = useRouter();

    const isAuthenticated = () => !!token.value;

    const login = async (username, password) => {
        const res = await fetch('/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ username, password }),
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
        // LiteSpeed + Imunify on the staging host strip non-standard HTTP verbs
        // (PUT, PATCH, DELETE) and respond 500 with empty body before the
        // request hits PHP. Tunnel them through POST with the
        // X-HTTP-Method-Override header — Laravel/Symfony respects it and
        // dispatches to the original verb's route. Behaviour on real PHP-FPM
        // boxes is identical, so this works in any environment.
        const method = (options.method || 'GET').toUpperCase();
        const tunnel = ['PUT', 'PATCH', 'DELETE'].includes(method);
        const res = await fetch(url, {
            ...options,
            method: tunnel ? 'POST' : method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token.value}`,
                ...(tunnel ? { 'X-HTTP-Method-Override': method } : {}),
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
