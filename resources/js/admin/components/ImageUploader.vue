<template>
    <div :class="square ? 'space-y-2' : 'space-y-3'">
        <!-- Square mode: single fixed square dropzone (image fills it, or empty state) -->
        <label v-if="square"
            class="relative block aspect-square w-full rounded-lg overflow-hidden cursor-pointer group transition border-2"
            :class="modelValue
                ? 'border-[#2A2A2A] hover:border-[#F59E0B]'
                : (uploading
                    ? 'border-dashed border-[#F59E0B] bg-[#F59E0B]/5'
                    : 'border-dashed border-[#2A2A2A] hover:border-[#F59E0B] hover:bg-[#F59E0B]/5 bg-[#111]')">
            <input type="file" accept="image/*" class="hidden" @change="onFileChange" :disabled="uploading">

            <img v-if="modelValue" :src="modelValue" alt="preview" class="absolute inset-0 w-full h-full object-cover">

            <!-- Empty / uploading overlay -->
            <div v-if="!modelValue || uploading"
                class="absolute inset-0 flex flex-col items-center justify-center gap-2 px-3 text-center">
                <svg v-if="!uploading" class="w-7 h-7 text-[#A0A0A0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <svg v-else class="w-7 h-7 text-[#F59E0B] animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span class="text-xs" :class="uploading ? 'text-[#F59E0B]' : 'text-[#A0A0A0]'">
                    {{ uploading ? 'Uploading…' : 'Click to upload' }}
                </span>
            </div>

            <!-- Hover overlay when image present -->
            <div v-if="modelValue && !uploading"
                class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                <span class="text-xs text-white font-medium">Replace image</span>
            </div>

            <!-- Remove button -->
            <button v-if="modelValue && !uploading" type="button" @click.prevent="clear"
                class="absolute top-2 right-2 bg-black/70 hover:bg-[#EF4444] text-white rounded-full p-1.5 transition z-10"
                title="Remove image">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </label>

        <!-- Default (non-square) mode: original layout -->
        <template v-else>
            <div v-if="modelValue" class="relative bg-[#111] border border-[#2A2A2A] rounded-lg overflow-hidden">
                <img :src="modelValue" alt="preview" class="w-full max-h-64 object-cover">
                <button type="button" @click="clear"
                    class="absolute top-2 right-2 bg-black/70 hover:bg-[#EF4444] text-white rounded-full p-1.5 transition"
                    title="Remove image">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <label
                class="flex items-center justify-center gap-3 border-2 border-dashed rounded-lg py-6 cursor-pointer transition"
                :class="uploading
                    ? 'border-[#F59E0B] bg-[#F59E0B]/5'
                    : 'border-[#2A2A2A] hover:border-[#F59E0B] hover:bg-[#F59E0B]/5'">
                <input type="file" accept="image/*" class="hidden" @change="onFileChange" :disabled="uploading">
                <svg v-if="!uploading" class="w-5 h-5 text-[#A0A0A0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <svg v-else class="w-5 h-5 text-[#F59E0B] animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span class="text-sm" :class="uploading ? 'text-[#F59E0B]' : 'text-[#A0A0A0]'">
                    {{ uploading ? 'Uploading…' : (modelValue ? 'Replace image' : 'Click to upload (max 5MB)') }}
                </span>
            </label>
        </template>

        <p v-if="error" class="text-xs text-[#EF4444]">{{ error }}</p>
    </div>
</template>
<script setup>
import { ref } from 'vue';
import { useAuth } from '../composables/useAuth';

const props = defineProps({
    modelValue: { type: String, default: '' },
    folder: { type: String, default: 'misc' },
    square: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const { token } = useAuth();
const uploading = ref(false);
const error = ref('');

async function onFileChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    error.value = '';
    uploading.value = true;

    const body = new FormData();
    body.append('file', file);
    body.append('folder', props.folder);

    try {
        const res = await fetch('/api/admin/uploads', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token.value}`,
            },
            body,
        });
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.message || 'Upload failed');
        }
        emit('update:modelValue', data.url);
    } catch (err) {
        error.value = err.message;
    } finally {
        uploading.value = false;
        e.target.value = '';
    }
}

function clear() {
    emit('update:modelValue', '');
}
</script>
