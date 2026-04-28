<template>
    <div>
        <!-- Title + desktop actions -->
        <div class="flex items-start justify-between mb-6 gap-4">
            <div class="min-w-0 flex-1">
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
                <h1 class="text-xl sm:text-2xl font-bold mt-1 truncate">{{ title }}</h1>
            </div>
            <!-- Desktop: inline actions -->
            <div class="hidden sm:flex gap-2 items-center shrink-0">
                <Btn v-if="showDelete" variant="danger" size="md" :disabled="saving" @click="$emit('delete')">{{ deleteLabel }}</Btn>
                <Btn variant="secondary" size="md" :disabled="saving" @click="onCancel">Cancel</Btn>
                <Btn variant="primary" size="md" :loading="saving" @click="$emit('save')">{{ saving ? 'Saving…' : 'Save' }}</Btn>
            </div>
        </div>

        <!-- Mobile: sticky bottom action bar -->
        <div class="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-[#0F0F0F] border-t border-[#2A2A2A] px-4 py-3 grid gap-2"
            :class="showDelete ? 'grid-cols-3' : 'grid-cols-2'"
            style="padding-bottom: max(12px, env(safe-area-inset-bottom));">
            <Btn v-if="showDelete" variant="danger" size="md" :block="true" :disabled="saving" @click="$emit('delete')">{{ deleteLabel }}</Btn>
            <Btn variant="secondary" size="md" :block="true" :disabled="saving" @click="onCancel">Cancel</Btn>
            <Btn variant="primary" size="md" :block="true" :loading="saving" @click="$emit('save')">{{ saving ? 'Saving…' : 'Save' }}</Btn>
        </div>
        <!-- Mobile: spacer so content isn't hidden behind sticky bar -->
        <div class="sm:hidden h-20"></div>
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
