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
                    <label class="block text-sm text-[#A0A0A0] mb-1">Email</label>
                    <input v-model="email" type="email" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-3 text-white focus:border-[#F59E0B] focus:outline-none" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm text-[#A0A0A0] mb-1">Password</label>
                    <input v-model="password" type="password" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-3 text-white focus:border-[#F59E0B] focus:outline-none" required>
                </div>
                <button type="submit" :disabled="loading" class="w-full bg-[#F59E0B] text-black font-bold py-3 rounded-lg hover:bg-[#D97706] transition disabled:opacity-50">
                    {{ loading ? 'Logging in...' : 'Log In' }}
                </button>
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
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function handleLogin() {
    error.value = '';
    loading.value = true;
    try {
        await login(email.value, password.value);
        router.push('/admin');
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}
</script>
