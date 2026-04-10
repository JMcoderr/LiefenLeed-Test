<script setup lang="ts">
import {
    Pagination,
    PaginationItem,
    PaginationNext,
    PaginationPrevious
} from '@/components/ui/pagination';
import type { PaginatedResponse } from '@/types';
import { router } from '@inertiajs/vue3';

interface Props {
    resource: PaginatedResponse;
}

const props = withDefaults(defineProps<Props>(), {
    resource: null,
});

const options = {
    preserveState: true,
    preserveScroll: true,
    replace: true,
}
</script>

<template>
    <Pagination :items-per-page="props.resource.per_page" :total="props.resource.total" :default-page="2">
        <PaginationPrevious
            :disabled="props.resource.prev_page_url === null"
            v-on:click="() => router.visit(props.resource?.prev_page_url, options)"
        >
            Vorige
        </PaginationPrevious>

        <template v-for="(link, index) in props.resource.links" :key="index">
            <PaginationItem
                v-if="index > 0 && index < props.resource.links.length - 1"
                :is-active="link.active"
                v-on:click="() => router.visit(link.url, options)"
                :value="index"
            >
                {{ link.label }}
            </PaginationItem>
        </template>

        <PaginationNext
            :disabled="props.resource.next_page_url === null"
            v-on:click="() => router.visit(props.resource?.next_page_url, options)"
        >
            Volgende
        </PaginationNext>
    </Pagination>
</template>
