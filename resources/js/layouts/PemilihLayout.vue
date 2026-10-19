<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Timer, Vote } from '@lucide/vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { getInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import type { User } from '@/types';

type Props = {
    title?: string;
};

withDefaults(defineProps<Props>(), {
    title: 'E-Voting KPU',
});

const page = usePage();
const auth = computed(() => page.props.auth as { user: User });

const isSudahVoting = computed(() => auth.value.user.status === 'sudah_voting');

const handleLogout = () => {
    router.flushAll();
};

// Timer countdown hanya aktif di halaman pemilih.
// Sisa waktu diambil dari server (voting.remaining_seconds) agar tidak bisa
// direset dengan me-refresh halaman. Fallback ke durasi branding bila kosong.
const votingDurationSeconds = computed(
    () => (page.props.branding?.voting_duration ?? 3) * 60,
);
const serverRemainingSeconds = computed<number | null>(
    () => page.props.voting?.remaining_seconds ?? null,
);

const remainingSeconds = ref(
    serverRemainingSeconds.value ?? votingDurationSeconds.value,
);
let timerInterval: ReturnType<typeof setInterval> | null = null;
let isExpiring = false;

const isVotingPage = computed(() => page.component === 'Pemilih');

const formattedTime = computed(() => {
    const minutes = Math.floor(remainingSeconds.value / 60);
    const seconds = remainingSeconds.value % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

const isTimerWarning = computed(() => remainingSeconds.value <= 30);

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
};

// Saat waktu habis, minta server memproses kedaluwarsa. Middleware server akan
// menandai status, mengakhiri sesi, dan mengarahkan ke halaman login.
const handleExpired = () => {
    if (isExpiring) {
        return;
    }

    isExpiring = true;
    stopTimer();

    // Tanpa waktu mulai di server, tidak ada otoritas penguncian: cukup hentikan.
    if (serverRemainingSeconds.value === null) {
        return;
    }

    // Minta server memproses kedaluwarsa. Middleware server akan menandai
    // status, mengakhiri sesi, dan mengarahkan ke halaman login.
    router.reload();
};

const startTimer = () => {
    stopTimer();
    isExpiring = false;
    remainingSeconds.value =
        serverRemainingSeconds.value ?? votingDurationSeconds.value;

    timerInterval = setInterval(() => {
        if (remainingSeconds.value <= 0) {
            handleExpired();
            return;
        }

        remainingSeconds.value--;
    }, 1000);
};

watch(
    isVotingPage,
    (active) => {
        if (active) {
            startTimer();
        } else {
            stopTimer();
        }
    },
    { immediate: true },
);

watch(serverRemainingSeconds, (value) => {
    if (value !== null && !isExpiring) {
        remainingSeconds.value = value;
    }
});

onUnmounted(stopTimer);
</script>

<template>
    <Head :title="title" />

    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-950 dark:to-blue-950"
    >
        <!-- Navbar -->
        <header
            class="sticky top-0 z-50 border-b border-blue-100 bg-white/80 shadow-sm backdrop-blur-md dark:border-blue-900/30 dark:bg-slate-900/80"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 md:px-6"
            >
                <!-- Logo & Judul -->
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 shadow-md"
                    >
                        <Vote class="h-5 w-5 text-white" />
                    </div>
                    <div class="hidden sm:block">
                        <p
                            class="text-xs leading-none font-medium text-slate-500 dark:text-slate-400"
                        >
                            Sistem Pemilihan
                        </p>
                        <p
                            class="text-sm leading-tight font-bold text-slate-800 dark:text-white"
                        >
                            KPU E-Voting
                        </p>
                    </div>
                </div>

                <!-- Timer Countdown -->
                <div
                    v-if="isVotingPage"
                    :class="[
                        'flex items-center gap-2 rounded-xl px-3 py-2 font-mono text-base font-bold shadow-inner transition-colors sm:px-4 sm:text-lg',
                        isTimerWarning
                            ? 'animate-pulse bg-red-500/15 text-red-600 dark:bg-red-500/20 dark:text-red-300'
                            : 'bg-blue-50 text-blue-700 dark:bg-slate-800 dark:text-blue-300',
                    ]"
                    data-test="voting-timer"
                >
                    <Timer class="h-5 w-5" />
                    <span>{{ formattedTime }}</span>
                </div>

                <!-- Status & User -->
                <div class="flex items-center gap-3">
                    <!-- Badge status voting -->
                    <Badge
                        :variant="isSudahVoting ? 'default' : 'outline'"
                        :class="[
                            'hidden items-center gap-1.5 px-3 py-1 text-xs font-semibold sm:flex',
                            isSudahVoting
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-400'
                                : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-400',
                        ]"
                    >
                        <span
                            :class="[
                                'h-1.5 w-1.5 rounded-full',
                                isSudahVoting
                                    ? 'bg-emerald-500'
                                    : 'bg-amber-500',
                            ]"
                        ></span>
                        {{ isSudahVoting ? 'Sudah Voting' : 'Aktif' }}
                    </Badge>

                    <!-- User Menu Dropdown -->
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                class="flex h-auto items-center gap-2 rounded-full px-2 py-1 hover:bg-slate-100 dark:hover:bg-slate-800"
                            >
                                <Avatar
                                    class="h-8 w-8 ring-2 ring-blue-200 dark:ring-blue-800"
                                >
                                    <AvatarFallback
                                        class="bg-gradient-to-br from-blue-500 to-indigo-600 text-xs font-bold text-white"
                                    >
                                        {{
                                            getInitials(
                                                auth.user.profile
                                                    ?.nama_lengkap ??
                                                    auth.user.nis,
                                            )
                                        }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="hidden text-left md:block">
                                    <p
                                        class="text-sm leading-none font-medium text-slate-800 dark:text-white"
                                    >
                                        {{
                                            auth.user.profile?.nama_lengkap ??
                                            auth.user.nis
                                        }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs leading-none text-slate-500 dark:text-slate-400"
                                    >
                                        {{ auth.user.nis }}
                                    </p>
                                </div>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-64">
                            <DropdownMenuLabel class="p-0 font-normal">
                                <div class="flex items-center gap-2 px-2 py-2">
                                    <UserInfo
                                        :user="auth.user"
                                        :show-nis="true"
                                    />
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <!-- Status voting di dropdown (mobile) -->
                            <div
                                class="flex items-center justify-between px-2 py-2 sm:hidden"
                            >
                                <span class="text-xs text-slate-500"
                                    >Status:</span
                                >
                                <Badge
                                    :class="[
                                        'text-xs',
                                        isSudahVoting
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border-amber-200 bg-amber-50 text-amber-700',
                                    ]"
                                >
                                    {{
                                        isSudahVoting ? 'Sudah Voting' : 'Aktif'
                                    }}
                                </Badge>
                            </div>
                            <DropdownMenuSeparator class="sm:hidden" />
                            <DropdownMenuItem :as-child="true">
                                <Link
                                    class="flex w-full cursor-pointer items-center text-red-600 dark:text-red-400"
                                    :href="logout()"
                                    @click="handleLogout"
                                    as="button"
                                    data-test="logout-button"
                                >
                                    <LogOut class="mr-2 h-4 w-4" />
                                    Keluar
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 md:px-6">
            <slot />
        </main>
    </div>
</template>
