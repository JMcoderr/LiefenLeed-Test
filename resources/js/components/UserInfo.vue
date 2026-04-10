<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';

interface Props {
    user: string;
}

defineProps<Props>();

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = false; // computed(() => props.user && props.user !== '');
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="user" :alt="user" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(user) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate text-xs text-muted-foreground">{{ user }}</span>
    </div>

</template>
