<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, LogOut } from '@lucide/vue';
import PemilihLayout from '@/layouts/PemilihLayout.vue';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';

defineOptions({
    layout: PemilihLayout,
});

const AUTO_LOGOUT_SECONDS = 5;
const TICK_MS = 50;
const RING_RADIUS = 44;
const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS;

const remaining = ref(AUTO_LOGOUT_SECONDS);
const isLoggingOut = ref(false);
let countdown: ReturnType<typeof setInterval> | null = null;

const progress = computed(() =>
    Math.max(0, Math.min(1, remaining.value / AUTO_LOGOUT_SECONDS)),
);
const secondsLeft = computed(() => Math.ceil(remaining.value));
const ringOffset = computed(() => RING_CIRCUMFERENCE * (1 - progress.value));
const ringColor = computed(() => {
    if (progress.value > 0.5) {
        return '#10b981';
    }

    if (progress.value > 0.2) {
        return '#f59e0b';
    }

    return '#ef4444';
});

const handleLogout = () => {
    if (isLoggingOut.value) {
        return;
    }

    isLoggingOut.value = true;

    if (countdown) {
        clearInterval(countdown);
        countdown = null;
    }

    router.post(logout(), {}, { onSuccess: () => router.flushAll() });
};

onMounted(() => {
    countdown = setInterval(() => {
        remaining.value = Math.max(0, remaining.value - TICK_MS / 1000);

        if (remaining.value <= 0) {
            handleLogout();
        }
    }, TICK_MS);
});

onUnmounted(() => {
    if (countdown) {
        clearInterval(countdown);
        countdown = null;
    }
});
</script>

<template>
    <Head title="Terima Kasih" />

    <div
        class="flex min-h-[60vh] flex-col items-center justify-center text-center"
    >
        <div
            class="mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30"
        >
            <CheckCircle
                class="h-12 w-12 text-emerald-600 dark:text-emerald-400"
            />
        </div>

        <h1
            class="mb-3 text-3xl font-bold tracking-tight text-slate-800 dark:text-white"
        >
            Terima Kasih
        </h1>

        <p class="mb-2 max-w-md text-lg text-slate-600 dark:text-slate-300">
            Suara Anda telah berhasil tercatat.
        </p>

        <p class="mb-6 max-w-sm text-sm text-slate-500 dark:text-slate-400">
            Partisipasi Anda sangat berarti untuk kemajuan sekolah kita.
        </p>

        <div class="relative mb-6 h-28 w-28">
            <svg class="h-full w-full -rotate-90" viewBox="0 0 100 100">
                <circle
                    cx="50"
                    cy="50"
                    :r="RING_RADIUS"
                    fill="none"
                    stroke-width="8"
                    class="stroke-slate-200 dark:stroke-slate-700"
                />
                <circle
                    cx="50"
                    cy="50"
                    :r="RING_RADIUS"
                    fill="none"
                    stroke-width="8"
                    stroke-linecap="round"
                    :stroke="ringColor"
                    :stroke-dasharray="RING_CIRCUMFERENCE"
                    :stroke-dashoffset="ringOffset"
                    class="transition-[stroke-dashoffset,stroke] duration-100 ease-linear"
                />
            </svg>
            <div
                class="absolute inset-0 flex flex-col items-center justify-center"
            >
                <span
                    class="text-3xl font-bold tabular-nums"
                    :style="{ color: ringColor }"
                >
                    {{ secondsLeft }}
                </span>
                <span
                    class="text-[10px] tracking-wide text-slate-400 uppercase"
                >
                    detik
                </span>
            </div>
        </div>

        <p class="mb-6 text-sm font-medium text-slate-500 dark:text-slate-400">
            Anda akan keluar otomatis setelah hitungan selesai.
        </p>

        <Button
            variant="outline"
            size="lg"
            class="gap-2"
            :disabled="isLoggingOut"
            @click="handleLogout"
        >
            <LogOut class="h-4 w-4" />
            Keluar Sekarang
        </Button>
    </div>
</template>
