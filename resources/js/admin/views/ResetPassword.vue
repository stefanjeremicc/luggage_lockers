<template>
    <div class="min-h-screen flex items-center justify-center bg-[#0A0A0A] px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#F59E0B]">Reset password</h1>
                <p class="text-[#A0A0A0] mt-2">Choose a new password.</p>
            </div>
            <form @submit.prevent="submit" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8">
                <div v-if="message" class="mb-4 p-3 bg-[#F59E0B]/10 border border-[#F59E0B]/30 rounded-lg text-[#F59E0B] text-sm">{{ message }}</div>
                <div v-if="error" class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm">{{ error }}</div>

                <div class="mb-4">
                    <label class="block text-sm text-[#A0A0A0] mb-1">Email</label>
                    <input v-model="email" type="email" readonly
                        class="w-full bg-[#0A0A0A] border border-[#2A2A2A] rounded-lg px-4 py-3 text-[#A0A0A0] focus:outline-none">
                </div>

                <PasswordInput v-model="password" label="New password" class="mb-4" />
                <PasswordInput v-model="passwordConfirmation" label="Confirm new password" class="mb-6" />

                <button type="submit" :disabled="loading || !canSubmit"
                    class="w-full bg-[#F59E0B] text-black font-bold py-3 rounded-lg hover:bg-[#D97706] transition disabled:opacity-50">
                    {{ loading ? 'Resetting…' : 'Reset password' }}
                </button>

                <div class="text-center mt-4">
                    <router-link to="/admin/login" class="text-sm text-[#A0A0A0] hover:text-[#F59E0B]">
                        Back to login
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PasswordInput from '../components/PasswordInput.vue';

const route = useRoute();
const router = useRouter();

const token = ref(route.query.token || '');
const email = ref(route.query.email || '');
const password = ref('');
const passwordConfirmation = ref('');
const message = ref('');
const error = ref('');
const loading = ref(false);

const canSubmit = computed(() =>
    token.value && email.value && password.value.length >= 8 && password.value === passwordConfirmation.value
);

async function submit() {
    message.value = '';
    error.value = '';
    if (!canSubmit.value) {
        error.value = 'Password must be at least 8 characters and match.';
        return;
    }
    loading.value = true;
    try {
        const res = await fetch('/api/auth/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                token: token.value,
                email: email.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Reset failed');
        message.value = data.message;
        setTimeout(() => router.push('/admin/login'), 1500);
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}
</script>
