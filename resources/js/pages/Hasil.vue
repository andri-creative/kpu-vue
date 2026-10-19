<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, Calendar, CheckCircle, TrendingUp, Users } from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { HasilStats, ResultItem, VotersPagination } from '@/types';

defineOptions({
    layout: AppLayout,
});

type Props = {
    stats: HasilStats;
    results: ResultItem[];
    voters: VotersPagination;
    tahun: string | null;
    tahun_list: string[];
};

const props = defineProps<Props>();

const handleTahunChange = (value: unknown) => {
    const tahun = value ? String(value) : null;
    router.visit(tahun ? `/hasil?tahun=${tahun}` : '/hasil', {
        preserveState: true,
        preserveScroll: true,
    });
};

const warnaHasil = (index: number) => {
    const colors = [
        'from-amber-400 to-orange-500',
        'from-slate-300 to-slate-400',
        'from-amber-700 to-amber-800',
        'from-blue-400 to-blue-500',
        'from-green-400 to-green-500',
        'from-purple-400 to-purple-500',
    ];
    return colors[index % colors.length];
};

const handlePrev = () => {
    if (props.voters.prev_page_url) {
        router.visit(props.voters.prev_page_url, { preserveState: true });
    }
};

const handleNext = () => {
    if (props.voters.next_page_url) {
        router.visit(props.voters.next_page_url, { preserveState: true });
    }
};
</script>

<template>
    <Head title="Hasil Votes" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading variant="small" title="Hasil Votes" description="Ringkasan perolehan suara dan daftar pemilih" />
            <div class="flex items-center gap-2">
                <Calendar class="h-4 w-4 text-muted-foreground" />
                <Select :model-value="tahun ?? tahun_list[0]" @update:model-value="handleTahunChange">
                    <SelectTrigger class="w-48">
                        <SelectValue placeholder="Pilih tahun" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="y in tahun_list" :key="y" :value="y">
                            {{ y === String(new Date().getFullYear()) ? `${y} (Berjalan)` : `${y} (Arsip)` }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
        <!-- Stats Cards -->
        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardContent class="pt-6 flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30">
                        <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Total Pemilih</p>
                        <p class="text-2xl font-bold">{{ stats.total_pemilih }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6 flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                        <CheckCircle class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Sudah Voting</p>
                        <p class="text-2xl font-bold">{{ stats.sudah_voting }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6 flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-900/30">
                        <TrendingUp class="h-6 w-6 text-violet-600 dark:text-violet-400" />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Partisipasi</p>
                        <p class="text-2xl font-bold">{{ stats.partisipasi }}%</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Perolehan Suara -->
        <div>
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <BarChart3 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">Perolehan Suara</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ results.length }} kandidat terdaftar &bull; {{ stats.sudah_voting }} suara masuk
                    </p>
                </div>
            </div>

            <div v-if="results.length > 0" class="grid gap-4 sm:grid-cols-2">
                <Card
                    v-for="(item, index) in results"
                    :key="item.id"
                    class="relative overflow-hidden"
                >
                    <div :class="`absolute top-0 left-0 h-1 w-full bg-gradient-to-r ${warnaHasil(index)}`"></div>
                    <CardContent class="pt-6">
                        <div class="flex items-start gap-4">
                            <!-- Foto + Nomor Urut -->
                            <div class="relative shrink-0">
                                <div
                                    v-if="item.foto_url"
                                    class="h-16 w-16 overflow-hidden rounded-xl shadow-md"
                                >
                                    <img :src="item.foto_url" :alt="item.nama_lengkap" class="h-full w-full object-cover" />
                                </div>
                                <div
                                    v-else
                                    :class="`flex h-16 w-16 items-center justify-center rounded-xl font-black text-xl text-white shadow-md ${warnaHasil(index)}`"
                                >
                                    #{{ item.nomor_urut }}
                                </div>
                                <div class="absolute -bottom-1 -right-1 flex h-7 w-7 items-center justify-center rounded-full bg-white text-xs font-black text-slate-700 shadow dark:bg-slate-800 dark:text-slate-200">
                                    #{{ item.nomor_urut }}
                                </div>
                            </div>
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold truncate text-slate-800 dark:text-white">{{ item.nama_lengkap }}</h3>
                                <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                    <div
                                        :class="`h-full rounded-full bg-gradient-to-r ${warnaHasil(index)}`"
                                        :style="{ width: `${Math.max(item.percentage, 1)}%` }"
                                    />
                                </div>
                                <div class="mt-2 flex items-baseline gap-1">
                                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ item.votes_count }}</span>
                                    <span class="text-sm text-muted-foreground">suara</span>
                                    <Badge variant="secondary" class="ml-auto text-xs font-semibold">{{ item.percentage }}%</Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 py-16 text-center dark:border-slate-700">
                <BarChart3 class="mb-4 h-12 w-12 text-slate-300 dark:text-slate-600" />
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Belum ada suara masuk</p>
            </div>
        </div>

        <Separator />

        <!-- Daftar Pemilih -->
        <div>
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <CheckCircle class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">Daftar Pemilih</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Riwayat pemungutan suara
                    </p>
                </div>
            </div>

            <Card>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-slate-50 dark:border-slate-700 dark:bg-slate-800/50">
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground w-12">No</th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Pemilih</th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Kelas</th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Kandidat Pilihan</th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(voter, idx) in voters.data"
                                :key="voter.id"
                                class="border-b last:border-b-0 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/30"
                            >
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ (voters.current_page - 1) * 20 + idx + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-xs font-bold text-white">
                                            {{ voter.user_name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-800 dark:text-white">{{ voter.user_name }}</p>
                                            <p class="text-xs text-muted-foreground">NIS: {{ voter.nis }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge variant="secondary" class="text-xs">{{ voter.kelas }}</Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div
                                            v-if="voter.candidate_foto_url"
                                            class="h-7 w-7 overflow-hidden rounded-lg"
                                        >
                                            <img :src="voter.candidate_foto_url" :alt="voter.candidate_name" class="h-full w-full object-cover" />
                                        </div>
                                        <div
                                            v-else
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-slate-400 to-slate-500 text-xs font-bold text-white"
                                        >
                                            #{{ voter.candidate_nomor }}
                                        </div>
                                        <span class="text-slate-700 dark:text-slate-200">{{ voter.candidate_name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ voter.voted_at }}</td>
                            </tr>
                            <tr v-if="voters.data.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <p class="text-sm text-muted-foreground">Belum ada pemilih yang terdaftar</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="voters.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t dark:border-slate-700">
                    <p class="text-sm text-muted-foreground">
                        Halaman {{ voters.current_page }} dari {{ voters.last_page }}
                    </p>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!voters.prev_page_url"
                            @click="handlePrev"
                        >
                            Sebelumnya
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!voters.next_page_url"
                            @click="handleNext"
                        >
                            Selanjutnya
                        </Button>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>
