<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Eraser,
    Pause,
    Play,
    RotateCcw,
    Terminal,
    Trash2,
    Wifi,
    WifiOff,
} from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { edit as editProfile } from '@/routes/profile';
import {
    index as logsIndex,
    reset as logsReset,
    tail as logsTail,
} from '@/routes/settings/logs';
import {
    clean as storageClean,
    cleanAll as storageCleanAll,
} from '@/routes/settings/storage';

interface LogSource {
    key: string;
    label: string;
    path: string;
    exists: boolean;
    size: number;
    size_formatted: string;
    resettable: boolean;
}

interface StorageCategory {
    key: string;
    label: string;
    description: string;
    file_count: number;
    bytes: number;
    bytes_formatted: string;
}

const props = defineProps<{
    sources: LogSource[];
    activeSource: string;
    tailLines: number;
    refreshIntervalMs: number;
    storage: StorageCategory[];
}>();

interface TailResponse {
    exists: boolean;
    reset: boolean;
    lines: string[];
    cursor: number;
    size: number;
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: editProfile(),
            },
            {
                title: 'Logs',
                href: logsIndex.url(),
            },
        ],
    },
});

const MAX_LINES = 1000;

const activeSource = ref(props.activeSource);
const lines = ref<string[]>([]);
const isStreaming = ref(false);
const isConnected = ref(false);
const autoScroll = ref(true);
const isResetDialogOpen = ref(false);
const liveSizeBytes = ref<number | null>(null);
const isCleanAllDialogOpen = ref(false);
const cleaningKey = ref<string | null>(null);

const terminal = ref<HTMLElement | null>(null);
let cursor = -1;
let pollTimer: ReturnType<typeof setTimeout> | null = null;

const currentSource = computed<LogSource | undefined>(() =>
    props.sources.find((source) => source.key === activeSource.value),
);

const isSourceMissing = computed(
    () => currentSource.value !== undefined && !currentSource.value.exists,
);

const sizeLabel = computed(() =>
    formatBytes(liveSizeBytes.value ?? currentSource.value?.size ?? 0),
);

const storageTotalFiles = computed(() =>
    props.storage.reduce((total, item) => total + item.file_count, 0),
);

const storageTotalSize = computed(() =>
    formatBytes(props.storage.reduce((total, item) => total + item.bytes, 0)),
);

function formatBytes(bytes: number): string {
    if (bytes <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );

    return `${(bytes / 1024 ** i).toFixed(2)} ${units[i]}`;
}

function stopPolling() {
    if (pollTimer !== null) {
        clearTimeout(pollTimer);
        pollTimer = null;
    }
}

function scrollToBottom() {
    if (!autoScroll.value || !terminal.value) {
        return;
    }

    void nextTick(() => {
        if (terminal.value) {
            terminal.value.scrollTop = terminal.value.scrollHeight;
        }
    });
}

function schedulePoll() {
    stopPolling();
    pollTimer = setTimeout(() => {
        void poll();
    }, props.refreshIntervalMs);
}

async function poll() {
    if (!isStreaming.value || isSourceMissing.value) {
        return;
    }

    try {
        const response = await fetch(
            logsTail.url({
                query: { source: activeSource.value, cursor },
            }),
            {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            isConnected.value = false;

            return;
        }

        const data = (await response.json()) as TailResponse;

        cursor = data.cursor;
        liveSizeBytes.value = data.size;
        isConnected.value = true;

        if (data.reset) {
            lines.value = [];
        }

        if (data.lines.length > 0) {
            lines.value.push(...data.lines);

            if (lines.value.length > MAX_LINES) {
                lines.value.splice(0, lines.value.length - MAX_LINES);
            }

            scrollToBottom();
        }
    } catch {
        isConnected.value = false;
    } finally {
        if (isStreaming.value) {
            schedulePoll();
        }
    }
}

function startPolling() {
    stopPolling();
    lines.value = [];
    cursor = -1;
    liveSizeBytes.value = null;

    if (isSourceMissing.value) {
        isStreaming.value = false;

        return;
    }

    isStreaming.value = true;
    void poll();
}

function pauseStream() {
    stopPolling();
    isStreaming.value = false;
    isConnected.value = false;
}

function toggleStream() {
    if (isStreaming.value) {
        pauseStream();
    } else {
        startPolling();
    }
}

function switchSource(key: string) {
    if (key === activeSource.value) {
        return;
    }

    activeSource.value = key;
    lines.value = [];
    cursor = -1;
    liveSizeBytes.value = null;

    if (isStreaming.value) {
        void poll();
    }
}

function clearTerminal() {
    lines.value = [];
}

function handleScroll() {
    if (!terminal.value) {
        return;
    }

    const { scrollTop, scrollHeight, clientHeight } = terminal.value;
    autoScroll.value = scrollHeight - scrollTop - clientHeight < 48;
}

function handleReset() {
    router.post(
        logsReset.url(),
        { source: activeSource.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                isResetDialogOpen.value = false;
                lines.value = [];
                cursor = -1;
                liveSizeBytes.value = null;
            },
        },
    );
}

function cleanCategory(key: string) {
    if (cleaningKey.value !== null) {
        return;
    }

    cleaningKey.value = key;

    router.post(
        storageClean.url(),
        { category: key },
        {
            preserveScroll: true,
            onFinish: () => {
                cleaningKey.value = null;
            },
        },
    );
}

function cleanAllStorage() {
    router.post(
        storageCleanAll.url(),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isCleanAllDialogOpen.value = false;
            },
        },
    );
}

function lineClass(line: string): string {
    if (/\.(ERROR|CRITICAL|ALERT|EMERGENCY):/.test(line)) {
        return 'text-red-400';
    }

    if (/\.WARNING:/.test(line)) {
        return 'text-amber-300';
    }

    if (/\.INFO:/.test(line)) {
        return 'text-sky-300';
    }

    if (/\.DEBUG:/.test(line)) {
        return 'text-zinc-500';
    }

    return 'text-zinc-200';
}

watch(
    () => props.activeSource,
    (value) => {
        activeSource.value = value;
    },
);

watch(
    () => lines.value.length,
    () => {
        scrollToBottom();
    },
);

onMounted(() => {
    startPolling();
});

onBeforeUnmount(() => {
    stopPolling();
});
</script>

<template>
    <div class="space-y-6">
        <Head title="Terminal Log" />

        <h1 class="sr-only">Terminal Log</h1>

        <div class="max-w-3xl">
            <Heading
                variant="small"
                title="Terminal Log"
                description="Pantau log aplikasi secara real-time seperti terminal. Read-only."
            />
        </div>

        <!-- Source Tabs + Controls -->
        <div
            class="flex flex-col gap-3 border-b border-border/40 pb-2 lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    v-for="source in sources"
                    :key="source.key"
                    size="sm"
                    :variant="
                        source.key === activeSource ? 'default' : 'outline'
                    "
                    @click="switchSource(source.key)"
                >
                    {{ source.label }}
                    <span
                        v-if="!source.exists"
                        class="ml-1.5 text-[10px] opacity-70"
                    >
                        (kosong)
                    </span>
                </Button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" @click="toggleStream">
                    <component
                        :is="isStreaming ? Pause : Play"
                        class="mr-1.5 h-3.5 w-3.5"
                    />
                    {{ isStreaming ? 'Jeda' : 'Mulai' }}
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="lines.length === 0"
                    @click="clearTerminal"
                >
                    <Eraser class="mr-1.5 h-3.5 w-3.5" />
                    Bersihkan
                </Button>

                <Button
                    variant="destructive"
                    size="sm"
                    :disabled="!currentSource?.resettable"
                    @click="isResetDialogOpen = true"
                >
                    <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                    Reset Log
                </Button>
            </div>
        </div>

        <!-- Terminal Panel -->
        <div
            class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-950 shadow-lg"
        >
            <div
                class="flex items-center justify-between border-b border-zinc-800 bg-zinc-900/80 px-4 py-2.5"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-full bg-red-500/80" />
                        <span class="h-3 w-3 rounded-full bg-amber-500/80" />
                        <span class="h-3 w-3 rounded-full bg-emerald-500/80" />
                    </div>
                    <div
                        class="flex min-w-0 items-center gap-1.5 text-xs text-zinc-400"
                    >
                        <Terminal class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate font-mono">
                            {{ currentSource?.path ?? 'log' }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <Badge
                        variant="outline"
                        class="border-zinc-700 bg-zinc-800/60 font-mono text-[10px] text-zinc-300"
                    >
                        {{ lines.length.toLocaleString('id-ID') }} baris
                    </Badge>
                    <Badge
                        variant="outline"
                        class="border-zinc-700 bg-zinc-800/60 font-mono text-[10px] text-zinc-300"
                    >
                        {{ sizeLabel }}
                    </Badge>
                    <span
                        class="flex items-center gap-1.5 font-mono text-[10px]"
                        :class="
                            isConnected ? 'text-emerald-400' : 'text-zinc-500'
                        "
                    >
                        <component
                            :is="isConnected ? Wifi : WifiOff"
                            class="h-3 w-3"
                        />
                        {{ isConnected ? 'tersambung' : 'terputus' }}
                    </span>
                </div>
            </div>

            <div
                ref="terminal"
                class="h-[60vh] overflow-auto p-4 font-mono text-xs leading-relaxed"
                @scroll="handleScroll"
            >
                <template v-if="lines.length === 0">
                    <p class="text-zinc-600">
                        <span v-if="isSourceMissing">
                            File log belum tersedia.
                        </span>
                        <span v-else-if="isStreaming">Menunggu log...</span>
                        <span v-else>Stream dijeda.</span>
                    </p>
                </template>
                <div
                    v-for="(line, index) in lines"
                    :key="index"
                    class="break-all whitespace-pre-wrap"
                    :class="lineClass(line)"
                >
                    <span
                        class="mr-3 inline-block w-10 text-right text-zinc-700 select-none"
                    >
                        {{ index + 1 }}
                    </span>
                    <span>{{ line || '\u00A0' }}</span>
                </div>
            </div>
        </div>

        <!-- Storage Cleanup Card -->
        <Card class="overflow-hidden">
            <CardHeader
                class="flex flex-col gap-2 border-b border-border/40 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle
                        class="flex items-center gap-2 text-sm font-semibold"
                    >
                        <Trash2 class="h-3.5 w-3.5 text-destructive" />
                        Pembersihan Storage
                    </CardTitle>
                    <CardDescription class="mt-0.5 text-xs">
                        Hapus log & cache yang menumpuk agar web tetap ringan
                        dan storage tidak penuh.
                    </CardDescription>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        variant="outline"
                        class="w-fit bg-muted/40 font-mono text-[11px] font-normal"
                    >
                        {{ storageTotalFiles }} file · {{ storageTotalSize }}
                    </Badge>
                    <Button
                        variant="destructive"
                        size="sm"
                        class="h-8 text-xs"
                        :disabled="storageTotalFiles === 0"
                        @click="isCleanAllDialogOpen = true"
                    >
                        <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                        Bersihkan Semua
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div class="divide-y divide-border/40">
                    <div
                        v-for="item in storage"
                        :key="item.key"
                        class="flex items-center justify-between gap-4 px-4 py-2.5"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="text-sm font-medium text-foreground"
                                    >{{ item.label }}</span
                                >
                                <Badge
                                    variant="outline"
                                    class="font-mono text-[10px] font-normal"
                                >
                                    {{ item.bytes_formatted }}
                                </Badge>
                                <Badge
                                    variant="secondary"
                                    class="font-mono text-[10px] font-normal"
                                >
                                    {{ item.file_count }} file
                                </Badge>
                            </div>
                            <p
                                class="mt-0.5 truncate text-[11px] text-muted-foreground"
                            >
                                {{ item.description }}
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 shrink-0 border-destructive/30 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                            :disabled="
                                item.file_count === 0 || cleaningKey !== null
                            "
                            @click="cleanCategory(item.key)"
                        >
                            <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                            Bersihkan
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Reset Confirmation Dialog -->
        <Dialog
            :open="isResetDialogOpen"
            @update:open="isResetDialogOpen = $event"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <RotateCcw class="h-5 w-5 text-destructive" />
                        Reset {{ currentSource?.label }} Log
                    </DialogTitle>
                    <DialogDescription class="pt-1.5 leading-relaxed">
                        Semua isi file log akan dikosongkan secara permanen.
                        Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="min-w-0 space-y-2 rounded-lg border border-border bg-muted/40 p-4 text-xs"
                >
                    <div class="flex items-center justify-between gap-4">
                        <span class="shrink-0 text-muted-foreground"
                            >File Log:</span
                        >
                        <span
                            class="min-w-0 flex-1 truncate text-right font-mono text-[11px] text-foreground"
                            :title="currentSource?.path"
                        >
                            {{ currentSource?.path }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="shrink-0 text-muted-foreground"
                            >Ukuran Saat Ini:</span
                        >
                        <span
                            class="shrink-0 font-mono font-semibold text-foreground"
                        >
                            {{ sizeLabel }}
                        </span>
                    </div>
                </div>

                <DialogFooter class="gap-2 pt-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button variant="outline">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="handleReset">
                        <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                        Kosongkan Log
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Clean All Storage Confirmation Dialog -->
        <Dialog
            :open="isCleanAllDialogOpen"
            @update:open="isCleanAllDialogOpen = $event"
        >
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Trash2 class="h-5 w-5 text-destructive" />
                        Bersihkan Semua Storage
                    </DialogTitle>
                    <DialogDescription class="pt-1.5 leading-relaxed">
                        Seluruh file log dan cache ({{ storageTotalFiles }} file
                        · {{ storageTotalSize }}) akan dihapus. Cache akan
                        dibuat ulang otomatis saat dibutuhkan.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2 pt-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button variant="outline">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="cleanAllStorage">
                        <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                        Bersihkan Sekarang
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
