<template>
    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <div class="min-w-0">
            <router-link
                v-if="backTo"
                :to="backTo"
                class="inline-flex items-center gap-1 text-xs text-[#A0A0A0] hover:text-[#F59E0B] transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                {{ backLabel }}
            </router-link>
            <h1 class="text-2xl font-bold mt-1 truncate">{{ title }}</h1>
        </div>
        <div class="flex gap-2 items-center">
            <Btn
                v-if="showDelete"
                variant="danger"
                size="md"
                :disabled="saving"
                @click="$emit('delete')"
            >
                {{ deleteLabel }}
            </Btn>
            <Btn
                variant="secondary"
                size="md"
                :disabled="saving"
                @click="onCancel"
            >
                Cancel
            </Btn>
            <Btn
                variant="primary"
                size="md"
                :loading="saving"
                @click="$emit('save')"
            >
                {{ saving ? 'Saving…' : 'Save' }}
            </Btn>
        </div>
    </div>
</template>
<script setup>
import { useRouter } from 'vue-router';
import Btn from './Btn.vue';

const props = defineProps({
    title: { type: String, required: true },
    backTo: { type: [String, Object], default: null },
    backLabel: { type: String, default: 'Back' },
    saving: { type: Boolean, default: false },
    showDelete: { type: Boolean, default: false },
    deleteLabel: { type: String, default: 'Delete' },
});
const emit = defineEmits(['save', 'cancel', 'delete']);

const router = useRouter();
const onCancel = () => {
    emit('cancel');
    if (props.backTo) router.push(props.backTo);
};
</script>
