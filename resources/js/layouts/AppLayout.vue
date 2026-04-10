<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Toaster } from '@/components/ui/sonner';
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Toast data typing for TypeScript validation
type ToastTypes = 'success' | 'error' | 'info' | 'warning';

interface ToastData {
    type?: ToastTypes;
    message?: string;
}

const page = usePage();
const toastData = computed(() => page?.props?.toast as ToastData | undefined);

/* Watches for the 'toast' prop in Inertia page props shared through HandleInertiaRequests.
* Uses the data in toast to create the appropriate notification with Sonner.
*/
watch(
    toastData,
    (data) => {
        if(!data) return;
        toast[data.type ?? 'info'](data.message ?? '');
    },
    { immediate: true }
)
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster position="top-right" expand rich-colors />
        <slot />
    </AppLayout>
</template>
