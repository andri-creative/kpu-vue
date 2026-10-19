<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

type Props = {
    title?: string;
};

const props = withDefaults(defineProps<Props>(), {
    title: 'Kandidat',
});
</script>

<template>
    <Head :title="props.title" />

    <div class="min-h-screen bg-background">
        <div class="container mx-auto px-4 py-8">
            <slot />
        </div>
    </div>
</template>
