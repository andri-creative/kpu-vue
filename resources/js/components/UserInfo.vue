<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showNis?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showNis: false,
});

const { getInitials } = useInitials();

const displayName = computed((): string => {
    return props.user.profile?.nama_lengkap ?? props.user.nis;
});

const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="user.avatar as string" :alt="displayName" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(displayName) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ displayName }}</span>
        <span
            v-if="showNis"
            class="truncate text-xs text-muted-foreground"
            >{{ user.nis }}</span
        >
    </div>
</template>
