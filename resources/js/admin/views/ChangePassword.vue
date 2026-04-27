<template>
    <div class="max-w-xl mx-auto">
        <EditPageHeader
            title="Change password"
            back-to="/admin"
            back-label="Back to dashboard"
            :saving="loading"
            @save="submit"
        />

        <form @submit.prevent="submit" class="space-y-4 bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
            <PasswordInput v-model="currentPassword" label="Current password" autocomplete="current-password" />
            <PasswordInput v-model="password" label="New password" autocomplete="new-password" />
            <PasswordInput v-model="passwordConfirmation" label="Confirm new password" autocomplete="new-password" />
            <p class="text-xs text-[#A0A0A0]">Minimum 8 characters.</p>
        </form>
    </div>
</template>
<script setup>
import { ref } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import EditPageHeader from '../components/EditPageHeader.vue';
import PasswordInput from '../components/PasswordInput.vue';

const { apiFetch } = useAuth();
const toast = useToast();

const currentPassword = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);

async function submit() {
    if (password.value.length < 8) {
        toast.error('New password must be at least 8 characters');
        return;
    }
    if (password.value !== passwordConfirmation.value) {
        toast.error('Passwords do not match');
        return;
    }
    loading.value = true;
    try {
        const res = await apiFetch('/api/auth/change-password', {
            method: 'POST',
            body: JSON.stringify({
                current_password: currentPassword.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Failed');
        toast.success(data.message);
        currentPassword.value = '';
        password.value = '';
        passwordConfirmation.value = '';
    } catch (e) {
        toast.error(e.message);
    } finally {
        loading.value = false;
    }
}
</script>
