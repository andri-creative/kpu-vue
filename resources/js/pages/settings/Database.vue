<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Activity,
    CheckCircle2,
    Clock,
    Database,
    HardDrive,
    Layers,
    Lock,
    RefreshCw,
    RotateCcw,
    Sparkles,
    TriangleAlert,
    Users,
    Vote,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { edit as editProfile } from '@/routes/profile';
import {
    reset as resetTableRoute,
    resetAll as resetAllRoute,
} from '@/routes/settings/database';

interface DatabaseSummary {
    file_size_bytes: number;
    file_size_formatted: string;
    database_path: string;
    full_path: string;
    driver: string;
    page_size: number;
    page_count: number;
    freelist_count: number;
    free_size_formatted: string;
    total_rows: number;
    total_logs: number;
    total_votes: number;
    total_users: number;
    total_archives: number;
}

interface TableStat {
    name: string;
    label: string;
    category: string;
    description: string;
    row_count: number;
    size_bytes: number;
    size_formatted: string;
    percentage: number;
    resettable: boolean;
}

interface RecentLog {
    id: number;
    action: string;
    ip_address: string;
    created_at: string;
    waktu_relatif: string;
    pemilih_nama: string;
    pemilih_nis: string;
    pemilih_kelas: string;
    kandidat_nomor: number | null;
    kandidat_nama: string;
}

const props = defineProps<{
    summary: DatabaseSummary;
    tables: TableStat[];
    recentLogs: RecentLog[];
    lastUpdated: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: editProfile(),
            },
            {
                title: 'Database & Log',
                href: '/settings/database',
            },
        ],
    },
});

const isRefreshing = ref(false);
const autoRefresh = ref(false);
let autoRefreshTimer: ReturnType<typeof setInterval> | null = null;

const isVacuumDialogOpen = ref(false);
const vacuumForm = useForm({});

const resetTarget = ref<string | null>(null);
const resetConfirmation = ref('');
const resetForm = useForm({
    table: '',
    confirmation: '',
});

const isResetAll = computed(() => resetTarget.value === 'all');

const resetTargetRow = computed(() =>
    props.tables.find((table) => table.name === resetTarget.value),
);

const resetDialogTitle = computed(() =>
    isResetAll.value
        ? 'Reset Seluruh Data'
        : `Reset Tabel ${resetTargetRow.value?.label ?? ''}`,
);

const resetDialogRowCount = computed(() =>
    isResetAll.value
        ? props.tables.reduce((total, table) => total + table.row_count, 0)
        : (resetTargetRow.value?.row_count ?? 0),
);

const resetWarning = computed(() => {
    if (isResetAll.value) {
        return 'Semua data aktif (voting, log, kandidat, sesi, dan akun non-admin) akan diarsipkan lalu dihapus. Akun role admin tetap dipertahankan.';
    }

    switch (resetTarget.value) {
        case 'users':
            return 'Semua akun non-admin (pemilih & kandidat) beserta data terkait akan diarsipkan lalu dihapus. Akun role admin tetap dipertahankan.';
        case 'profiles':
            return 'Profil milik user non-admin akan dihapus. Profil admin tetap dipertahankan.';
        case 'candidates':
            return 'Semua kandidat beserta suara terkait akan diarsipkan lalu dihapus.';
        case 'sessions':
            return 'Semua sesi login akan dihapus — seluruh pengguna (termasuk Anda) akan logout.';
        case 'votes':
        case 'vote_logs':
            return 'Data pada tabel ini akan diarsipkan lalu dihapus dari tabel aktif.';
        default:
            return 'Seluruh isi tabel akan dihapus. Tindakan ini tidak dapat dibatalkan.';
    }
});

function openReset(target: string) {
    resetTarget.value = target;
    resetConfirmation.value = '';
    resetForm.clearErrors();
    resetForm.table = target === 'all' ? '' : target;
    resetForm.confirmation = '';
}

function closeReset() {
    resetTarget.value = null;
    resetConfirmation.value = '';
    resetForm.reset();
    resetForm.clearErrors();
}

function handleReset() {
    resetForm.confirmation = resetConfirmation.value;

    resetForm.post(
        isResetAll.value ? resetAllRoute.url() : resetTableRoute.url(),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeReset();
                refreshData();
            },
        },
    );
}

function refreshData() {
    isRefreshing.value = true;
    router.reload({
        only: ['summary', 'tables', 'recentLogs', 'lastUpdated'],
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

function toggleAutoRefresh() {
    autoRefresh.value = !autoRefresh.value;
    if (autoRefresh.value) {
        autoRefreshTimer = setInterval(() => {
            router.reload({
                only: ['summary', 'tables', 'recentLogs', 'lastUpdated'],
            });
        }, 8000);
    } else if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
}

onBeforeUnmount(() => {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
    }
});

function handleVacuum() {
    vacuumForm.post('/settings/database/vacuum', {
        preserveScroll: true,
        onSuccess: () => {
            isVacuumDialogOpen.value = false;
            refreshData();
        },
    });
}

function getCategoryBadgeClass(category: string): string {
    switch (category) {
        case 'Log & Aktivitas':
            return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20';
        case 'Voting':
            return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
        case 'Pengguna':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
        case 'Arsip':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        default:
            return 'bg-muted text-muted-foreground border-border';
    }
}
</script>

<template>
    <div class="space-y-4">
        <Head title="Statistik Database & Log" />

        <h1 class="sr-only">Statistik Database & Log</h1>

        <!-- Header Controls -->
        <div
            class="flex flex-col gap-3 border-b border-border/40 pb-1.5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-md bg-primary/10 text-primary"
                    >
                        <Database class="h-3 w-3" />
                    </div>
                    <h2
                        class="text-sm font-semibold tracking-tight text-foreground"
                    >
                        Penyimpanan Database & Log
                    </h2>
                    <Badge
                        variant="outline"
                        class="bg-muted/50 font-mono text-[10px] tracking-wider uppercase"
                    >
                        {{ summary.driver }}
                    </Badge>
                </div>
                <p class="text-[11px] text-muted-foreground">
                    Pantau kapasitas file SQLite, status tabel, alokasi
                    penyimpanan, serta audit aktivitas voting real-time.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 text-xs"
                    :class="{
                        'border-emerald-500/60 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400':
                            autoRefresh,
                    }"
                    @click="toggleAutoRefresh"
                >
                    <span
                        class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full"
                        :class="
                            autoRefresh
                                ? 'animate-pulse bg-emerald-500'
                                : 'bg-muted-foreground/50'
                        "
                    />
                    {{
                        autoRefresh
                            ? 'Auto-Update: Aktif (8s)'
                            : 'Auto-Update: Mati'
                    }}
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 text-xs"
                    :disabled="isRefreshing"
                    @click="refreshData"
                >
                    <RefreshCw
                        class="mr-1.5 h-3.5 w-3.5"
                        :class="{ 'animate-spin': isRefreshing }"
                    />
                    Segarkan
                </Button>

                <Button
                    variant="default"
                    size="sm"
                    class="h-8 text-xs"
                    @click="isVacuumDialogOpen = true"
                >
                    <Sparkles class="mr-1.5 h-3.5 w-3.5" />
                    Optimalkan (VACUUM)
                </Button>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <!-- File Size -->
            <Card
                class="flex flex-col justify-between p-3.5 transition-colors hover:border-border/80"
            >
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-muted-foreground"
                        >Ukuran File Database</span
                    >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400"
                    >
                        <HardDrive class="h-3.5 w-3.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <div
                        class="text-lg font-bold tracking-tight text-foreground"
                    >
                        {{ summary.file_size_formatted }}
                    </div>
                    <div
                        class="mt-1 flex items-center justify-between text-[11px] text-muted-foreground"
                    >
                        <span
                            class="truncate font-mono"
                            :title="summary.full_path"
                            >{{ summary.database_path }}</span
                        >
                        <span class="shrink-0 font-medium text-foreground/80"
                            >{{ summary.free_size_formatted }} bebas</span
                        >
                    </div>
                </div>
            </Card>

            <!-- Total Activity Logs -->
            <Card
                class="flex flex-col justify-between p-3.5 transition-colors hover:border-border/80"
            >
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-muted-foreground"
                        >Total Log Aktivitas</span
                    >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-purple-500/10 text-purple-600 dark:text-purple-400"
                    >
                        <Activity class="h-3.5 w-3.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <div
                        class="text-lg font-bold tracking-tight text-foreground"
                    >
                        {{ summary.total_logs.toLocaleString('id-ID') }}
                    </div>
                    <p class="mt-1 truncate text-[11px] text-muted-foreground">
                        Riwayat vote cast & aktivitas pemilih
                    </p>
                </div>
            </Card>

            <!-- Total Votes -->
            <Card
                class="flex flex-col justify-between p-3.5 transition-colors hover:border-border/80"
            >
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-muted-foreground"
                        >Surat Suara Masuk</span
                    >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                    >
                        <Vote class="h-3.5 w-3.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <div
                        class="text-lg font-bold tracking-tight text-foreground"
                    >
                        {{ summary.total_votes.toLocaleString('id-ID') }}
                    </div>
                    <p class="mt-1 truncate text-[11px] text-muted-foreground">
                        Surat suara sah dalam sistem aktif
                    </p>
                </div>
            </Card>

            <!-- Total Records -->
            <Card
                class="flex flex-col justify-between p-3.5 transition-colors hover:border-border/80"
            >
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-muted-foreground"
                        >Total Baris Data</span
                    >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400"
                    >
                        <Layers class="h-3.5 w-3.5" />
                    </div>
                </div>
                <div class="mt-3">
                    <div
                        class="text-lg font-bold tracking-tight text-foreground"
                    >
                        {{ summary.total_rows.toLocaleString('id-ID') }}
                    </div>
                    <p class="mt-1 truncate text-[11px] text-muted-foreground">
                        {{ summary.total_users.toLocaleString('id-ID') }} akun •
                        {{ summary.total_archives.toLocaleString('id-ID') }}
                        arsip
                    </p>
                </div>
            </Card>
        </div>

        <!-- Table Breakdown Card -->
        <Card class="overflow-hidden">
            <CardHeader
                class="flex flex-col gap-2 border-b border-border/40 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle
                        class="flex items-center gap-2 text-sm font-semibold"
                    >
                        <Database class="h-4 w-4 text-primary" />
                        Rincian Kapasitas Penyimpanan Tabel
                    </CardTitle>
                    <CardDescription class="mt-0.5 text-xs">
                        Daftar seluruh tabel database, jumlah baris data, dan
                        alokasi kapasitasnya
                    </CardDescription>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div
                        class="flex w-fit items-center gap-1.5 rounded-md border border-border/40 bg-muted/40 px-2 py-0.5 text-[11px] text-muted-foreground"
                    >
                        <Clock class="h-3.5 w-3.5" />
                        Diperbarui:
                        <span class="font-medium text-foreground">{{
                            lastUpdated
                        }}</span>
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        class="h-8 text-xs"
                        @click="openReset('all')"
                    >
                        <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                        Reset Semua Data
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table
                        class="[&_td]:px-3 [&_td]:py-1.5 [&_th]:h-8 [&_th]:px-3 [&_th]:text-xs"
                    >
                        <TableHeader>
                            <TableRow class="bg-muted/40 hover:bg-muted/40">
                                <TableHead class="pl-6 font-semibold"
                                    >Nama Tabel</TableHead
                                >
                                <TableHead class="font-semibold"
                                    >Kategori</TableHead
                                >
                                <TableHead class="text-right font-semibold"
                                    >Banyak Data</TableHead
                                >
                                <TableHead class="text-right font-semibold"
                                    >Estimasi Ukuran</TableHead
                                >
                                <TableHead class="w-48 font-semibold"
                                    >Porsi Data</TableHead
                                >
                                <TableHead class="font-semibold"
                                    >Keterangan</TableHead
                                >
                                <TableHead class="pr-6 text-right font-semibold"
                                    >Aksi</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="table in tables"
                                :key="table.name"
                                class="transition-colors hover:bg-muted/30"
                            >
                                <TableCell
                                    class="pl-6 font-mono text-[11px] font-medium text-foreground"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-primary/80"
                                        />
                                        {{ table.name }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        class="text-[11px] font-normal"
                                        :class="
                                            getCategoryBadgeClass(
                                                table.category,
                                            )
                                        "
                                    >
                                        {{ table.category }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right font-medium">
                                    {{
                                        table.row_count.toLocaleString('id-ID')
                                    }}
                                </TableCell>
                                <TableCell
                                    class="text-right font-mono text-[11px] font-medium text-muted-foreground"
                                >
                                    {{ table.size_formatted }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                                        >
                                            <div
                                                class="h-full rounded-full bg-primary transition-all duration-500"
                                                :style="{
                                                    width: `${Math.max(table.percentage, 2)}%`,
                                                }"
                                            />
                                        </div>
                                        <span
                                            class="w-12 shrink-0 text-right font-mono text-[11px] text-muted-foreground"
                                        >
                                            {{ table.percentage }}%
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell
                                    class="max-w-xs truncate text-[11px] text-muted-foreground"
                                    :title="table.description"
                                >
                                    {{ table.description }}
                                </TableCell>
                                <TableCell class="pr-6 text-right">
                                    <Button
                                        v-if="table.resettable"
                                        variant="outline"
                                        size="sm"
                                        class="h-7 border-destructive/30 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        @click="openReset(table.name)"
                                    >
                                        <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                                        Reset
                                    </Button>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-[11px] text-muted-foreground"
                                        title="Tabel ini dilindungi dan tidak dapat direset"
                                    >
                                        <Lock class="h-3.5 w-3.5" />
                                        Terkunci
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Recent Logs Section -->
        <Card class="overflow-hidden">
            <CardHeader
                class="flex flex-col gap-2 border-b border-border/40 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle
                        class="flex items-center gap-2 text-sm font-semibold"
                    >
                        <Activity class="h-4 w-4 text-purple-500" />
                        10 Log Aktivitas Voting Terbaru
                    </CardTitle>
                    <CardDescription class="mt-0.5 text-xs">
                        Catatan audit real-time setiap suara yang masuk ke
                        sistem
                    </CardDescription>
                </div>
                <Badge
                    variant="outline"
                    class="w-fit bg-muted/30 text-[11px] font-normal"
                >
                    {{ summary.total_logs.toLocaleString('id-ID') }} log
                    tersimpan
                </Badge>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table
                        class="[&_td]:px-3 [&_td]:py-1.5 [&_th]:h-8 [&_th]:px-3 [&_th]:text-xs"
                    >
                        <TableHeader>
                            <TableRow class="bg-muted/40 hover:bg-muted/40">
                                <TableHead class="w-16 pl-6">ID</TableHead>
                                <TableHead>Waktu</TableHead>
                                <TableHead>Pemilih</TableHead>
                                <TableHead>Kelas</TableHead>
                                <TableHead>Kandidat Dipilih</TableHead>
                                <TableHead>IP Address</TableHead>
                                <TableHead class="pr-6 text-center"
                                    >Status</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="recentLogs.length === 0">
                                <TableCell
                                    colspan="7"
                                    class="h-20 text-center text-muted-foreground"
                                >
                                    Belum ada log aktivitas voting.
                                </TableCell>
                            </TableRow>
                            <TableRow
                                v-for="log in recentLogs"
                                :key="log.id"
                                class="transition-colors hover:bg-muted/30"
                            >
                                <TableCell
                                    class="pl-6 font-mono text-[11px] text-muted-foreground"
                                >
                                    #{{ log.id }}
                                </TableCell>
                                <TableCell class="text-[11px]">
                                    <div class="font-medium text-foreground">
                                        {{ log.created_at }}
                                    </div>
                                    <div
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        {{ log.waktu_relatif }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div
                                        class="text-[13px] font-medium text-foreground"
                                    >
                                        {{ log.pemilih_nama }}
                                    </div>
                                    <div
                                        class="font-mono text-[11px] text-muted-foreground"
                                    >
                                        NIS: {{ log.pemilih_nis }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-[11px]">
                                    <Badge
                                        variant="secondary"
                                        class="text-[11px] font-normal"
                                    >
                                        {{ log.pemilih_kelas }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-[11px]">
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            variant="outline"
                                            class="text-[11px] font-bold"
                                        >
                                            No. {{ log.kandidat_nomor ?? '-' }}
                                        </Badge>
                                        <span
                                            class="font-medium text-foreground"
                                            >{{ log.kandidat_nama }}</span
                                        >
                                    </div>
                                </TableCell>
                                <TableCell
                                    class="font-mono text-[11px] text-muted-foreground"
                                >
                                    {{ log.ip_address }}
                                </TableCell>
                                <TableCell class="pr-6 text-center">
                                    <Badge
                                        variant="outline"
                                        class="border-emerald-500/20 bg-emerald-500/10 text-[11px] font-normal text-emerald-600 dark:text-emerald-400"
                                    >
                                        <CheckCircle2 class="mr-1 h-3 w-3" />
                                        Berhasil
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Reset Confirmation Dialog -->
        <Dialog
            :open="resetTarget !== null"
            @update:open="!$event && closeReset()"
        >
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <TriangleAlert class="h-5 w-5 text-destructive" />
                        {{ resetDialogTitle }}
                    </DialogTitle>
                    <DialogDescription class="pt-1.5 leading-relaxed">
                        {{ resetWarning }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="min-w-0 space-y-2 rounded-lg border border-border bg-muted/40 p-3 text-[11px]"
                >
                    <div class="flex items-center justify-between gap-4">
                        <span class="shrink-0 text-muted-foreground"
                            >Jumlah Baris Data:</span
                        >
                        <span
                            class="shrink-0 font-mono font-semibold text-foreground"
                        >
                            {{ resetDialogRowCount.toLocaleString('id-ID') }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="reset-confirmation">
                        Ketik
                        <span class="font-mono font-bold">RESET</span>
                        untuk konfirmasi
                    </Label>
                    <Input
                        id="reset-confirmation"
                        v-model="resetConfirmation"
                        placeholder="RESET"
                        autocomplete="off"
                        class="font-mono"
                    />
                    <p
                        v-if="resetForm.errors.confirmation"
                        class="text-sm text-red-600"
                    >
                        {{ resetForm.errors.confirmation }}
                    </p>
                    <p
                        v-if="resetForm.errors.table"
                        class="text-sm text-red-600"
                    >
                        {{ resetForm.errors.table }}
                    </p>
                </div>

                <DialogFooter class="gap-2 pt-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button
                            variant="outline"
                            :disabled="resetForm.processing"
                            @click="closeReset"
                        >
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        :disabled="
                            resetConfirmation !== 'RESET' ||
                            resetForm.processing
                        "
                        @click="handleReset"
                    >
                        <RotateCcw
                            class="mr-1.5 h-3.5 w-3.5"
                            :class="{ 'animate-spin': resetForm.processing }"
                        />
                        Reset Sekarang
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- VACUUM Confirmation Dialog -->
        <Dialog
            :open="isVacuumDialogOpen"
            @update:open="isVacuumDialogOpen = $event"
        >
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Sparkles class="h-5 w-5 text-primary" />
                        Optimalkan Database SQLite
                    </DialogTitle>
                    <DialogDescription class="pt-1.5 leading-relaxed">
                        Operasi <code>VACUUM</code> akan memadatkan file
                        database SQLite, membersihkan halaman memori yang tidak
                        lagi digunakan (freelist), serta menata ulang struktur
                        penyimpanan untuk kinerja maksimal.
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="space-y-2 rounded-lg border border-border bg-muted/40 p-3 text-[11px]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground"
                            >Ukuran Database Saat Ini:</span
                        >
                        <span class="font-mono font-semibold text-foreground">{{
                            summary.file_size_formatted
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground"
                            >Ruang Kosong (Freelist):</span
                        >
                        <span
                            class="font-mono font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            {{ summary.free_size_formatted }} ({{
                                summary.freelist_count
                            }}
                            halaman)
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between border-t border-border/40 pt-2"
                    >
                        <span class="text-muted-foreground">Lokasi File:</span>
                        <span
                            class="max-w-[220px] truncate font-mono text-[11px] text-muted-foreground"
                            :title="summary.full_path"
                        >
                            {{ summary.database_path }}
                        </span>
                    </div>
                </div>

                <DialogFooter class="gap-2 pt-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button
                            variant="outline"
                            :disabled="vacuumForm.processing"
                        >
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        variant="default"
                        :disabled="vacuumForm.processing"
                        @click="handleVacuum"
                    >
                        <Sparkles
                            class="mr-1.5 h-3.5 w-3.5"
                            :class="{ 'animate-spin': vacuumForm.processing }"
                        />
                        Jalankan VACUUM Sekarang
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
