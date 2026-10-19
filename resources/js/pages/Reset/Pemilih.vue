<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    BarChart3,
    CheckCircle,
    RefreshCw,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ArchiveItem, ArsipStats } from '@/types';

defineOptions({
    layout: AppLayout,
});

type Props = {
    stats: ArsipStats;
    archives: ArchiveItem[];
};

defineProps<Props>();

const dialogOpen = ref(false);
const confirmation = ref('');

const form = useForm({
    confirmation: '',
});

const openDialog = () => {
    confirmation.value = '';
    form.clearErrors();
    dialogOpen.value = true;
};

const closeDialog = () => {
    dialogOpen.value = false;
    confirmation.value = '';
};

const handleReset = () => {
    form.confirmation = confirmation.value;
    form.post('/reset-pemilih', {
        preserveScroll: true,
        onSuccess: () => {
            closeDialog();
        },
    });
};
</script>

<template>
    <Head title="Reset Voting" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Reset Voting"
                description="Bersihkan data voting pemilih dan arsipkan hasil ke tabel arsip"
            />
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 sm:grid-cols-4">
            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30"
                    >
                        <Users
                            class="h-6 w-6 text-blue-600 dark:text-blue-400"
                        />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">
                            Total Pemilih
                        </p>
                        <p class="text-2xl font-bold">
                            {{ stats.total_pemilih }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30"
                    >
                        <CheckCircle
                            class="h-6 w-6 text-emerald-600 dark:text-emerald-400"
                        />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Total Suara</p>
                        <p class="text-2xl font-bold">
                            {{ stats.total_votes }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30"
                    >
                        <BarChart3
                            class="h-6 w-6 text-amber-600 dark:text-amber-400"
                        />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Total Log</p>
                        <p class="text-2xl font-bold">
                            {{ stats.total_vote_logs }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-900/30"
                    >
                        <Archive
                            class="h-6 w-6 text-violet-600 dark:text-violet-400"
                        />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">
                            Total Kandidat
                        </p>
                        <p class="text-2xl font-bold">
                            {{ stats.total_kandidat }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Reset Card -->
        <Card>
            <CardContent class="pt-6">
                <div class="flex flex-col gap-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30"
                        >
                            <AlertTriangle
                                class="h-5 w-5 text-red-600 dark:text-red-400"
                            />
                        </div>
                        <div>
                            <h3
                                class="font-semibold text-slate-800 dark:text-white"
                            >
                                Reset Semua Data Voting
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Data voting, log, pemilih, dan kandidat akan
                                diarsipkan ke tabel arsip dengan tahun saat ini,
                                lalu dihapus dari tabel aktif. Anda dapat
                                mengimpor pemilih baru dengan NIS yang sama
                                setelah reset.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Button variant="destructive" @click="openDialog">
                            <RefreshCw class="mr-2 h-4 w-4" />
                            Reset Voting
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Arsip Card -->
        <Card>
            <CardContent class="pt-6">
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-900/30"
                    >
                        <Archive
                            class="h-5 w-5 text-violet-600 dark:text-violet-400"
                        />
                    </div>
                    <div>
                        <h3
                            class="font-semibold text-slate-800 dark:text-white"
                        >
                            Arsip Data
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Data hasil voting sebelumnya yang tersimpan
                        </p>
                    </div>
                </div>

                <div v-if="archives.length > 0" class="space-y-2">
                    <div
                        v-for="archive in archives"
                        :key="archive.tahun"
                        class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-700"
                    >
                        <div class="flex items-center gap-3">
                            <Badge variant="secondary" class="font-semibold">{{
                                archive.tahun
                            }}</Badge>
                            <span class="text-sm text-muted-foreground"
                                >{{ archive.votes }} suara</span
                            >
                            <span class="text-sm text-muted-foreground"
                                >{{ archive.users }} pemilih</span
                            >
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <a :href="`/hasil?tahun=${archive.tahun}`"
                                >Lihat Hasil</a
                            >
                        </Button>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 py-10 text-center dark:border-slate-700"
                >
                    <Archive
                        class="mb-3 h-10 w-10 text-slate-300 dark:text-slate-600"
                    />
                    <p
                        class="text-sm font-medium text-slate-500 dark:text-slate-400"
                    >
                        Belum ada data arsip
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Dialog Konfirmasi -->
        <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Reset Voting</DialogTitle>
                    <DialogDescription>
                        Tindakan ini akan mengarsipkan dan menghapus semua data
                        voting. Ketik
                        <span class="font-mono font-bold">RESET</span> untuk
                        melanjutkan.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2">
                    <Label for="confirmation"
                        >Ketik RESET untuk konfirmasi</Label
                    >
                    <Input
                        id="confirmation"
                        v-model="confirmation"
                        placeholder="RESET"
                        autocomplete="off"
                        class="font-mono"
                    />
                    <p
                        v-if="form.errors.confirmation"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.confirmation }}
                    </p>
                </div>

                <DialogFooter class="gap-3">
                    <Button variant="secondary" @click="closeDialog"
                        >Batal</Button
                    >
                    <Button
                        variant="destructive"
                        :disabled="confirmation !== 'RESET' || form.processing"
                        @click="handleReset"
                    >
                        <RefreshCw
                            v-if="form.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Reset Sekarang
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
