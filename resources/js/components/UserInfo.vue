<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed, ref, watch } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Compute whether we should attempt to show the avatar image
const showAvatar = computed(() => props.user.avatar && props.user.avatar !== '');
const imageFailed = ref(false);

watch(
    () => props.user.avatar,
    () => {
        // reset error state when avatar value changes
        imageFailed.value = false;
    },
);
</script>

<template>
    <Avatar class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-orange-200 bg-orange-100 text-sm font-semibold text-orange-700">
        <AvatarImage v-if="showAvatar && !imageFailed" :src="user.avatar" :alt="user.name" class="h-full w-full object-cover" @error="imageFailed = true" />
        <AvatarFallback v-else class="flex h-full w-full items-center justify-center rounded-full bg-orange-100 text-sm font-semibold text-orange-700">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
    </div>
</template>
