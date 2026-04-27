<template>
    <div class="min-h-screen flex items-center justify-center bg-[#0A0A0A] px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#F59E0B]">BLL Admin</h1>
                <p class="text-[#A0A0A0] mt-2">Belgrade Luggage Locker</p>
            </div>
            <form @submit.prevent="handleLogin" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8">
                <div v-if="error" class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm">{{ error }}</div>

                <div class="mb-4">
                    <label class="block text-sm text-[#A0A0A0] mb-1">Username</label>
                    <input v-model="username" type="text" autocomplete="username"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-3 text-white focus:border-[#F59E0B] focus:outline-none" required>
                </div>

                <div class="mb-6">
                    <label class="block text-sm text-[#A0A0A0] mb-1">Password</label>
                    <div class="relative">
                        <input v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password"
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-3 pr-12 text-white focus:border-[#F59E0B] focus:outline-none" required>
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-[#A0A0A0] hover:text-[#F59E0B]"
                            :title="showPassword ? 'Hide password' : 'Show password'">
                            <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" :disabled="loading"
                    class="w-full bg-[#F59E0B] text-black font-bold py-3 rounded-lg hover:bg-[#D97706] transition disabled:opacity-50">
                    {{ loading ? 'Logging in...' : 'Log In' }}
                </button>

                <div class="text-center mt-4">
                    <router-link to="/admin/forgot-password" class="text-sm text-[#A0A0A0] hover:text-[#F59E0B]">
                        Forgot password?
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>
<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const { login } = useAuth();
const router = useRouter();
const username = ref('');
const password = ref('');
const showPassword = ref(false);
const error = ref('');
const loading = ref(false);

async function handleLogin() {
    error.value = '';
    loading.value = true;
    try {
        await login(username.value, password.value);
        router.push('/admin');
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}
</script>
