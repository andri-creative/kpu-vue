<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Clock, TrendingUp, Users } from '@lucide/vue';
import { VisAxis, VisDonut, VisGroupedBar, VisSingleContainer, VisXYContainer } from '@unovis/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    ChartContainer,
    ChartCrosshair,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
    type ChartConfig,
} from '@/components/ui/chart';
import { dashboard } from '@/routes';
import dashboardExport from '@/routes/dashboard/export';
import type { DashboardProps, KelasDistribusi, PerolehanItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<DashboardProps>();

type DataKelas = KelasDistribusi & { index: number };
type DataPerolehan = PerolehanItem & { index: number };

const warnaKandidat = (index: number) => `var(--chart-${(index % 5) + 1})`;

const labelAksi = (aksi: string) =>
    ({
        vote_cast: 'Memilih Kandidat',
    })[aksi] ?? aksi;

const formatWaktu = (waktu: string) =>
    new Date(waktu.replace(' ', 'T')).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

// 5 kelas teratas berdasarkan partisipasi (backend sudah urut persentase menurun).
const kelasTeratas = computed(() => props.kelas.slice(0, 5));

// Dibalik agar kelas dengan partisipasi tertinggi tampil di atas chart.
const dataKelas = computed<DataKelas[]>(() =>
    [...kelasTeratas.value].reverse().map((item, index) => ({ index, ...item })),
);

// Semua kelas untuk tabel rincian, sudah urut persentase menurun dari backend.
const daftarKelas = computed<DataKelas[]>(() =>
    props.kelas.map((item, index) => ({ index, ...item })),
);

const jumlahKelasBelumVoting = computed(
    () => props.kelas.filter((item) => item.sudah === 0).length,
);

// Index dipakai sebagai sumbu kategori, nama kandidat jadi label sumbu Y.
const dataPerolehan = computed<DataPerolehan[]>(() =>
    props.perolehan.map((item, index) => ({ index, ...item })),
);

// Posisi label persentase di tengah tiap irisan pie.
// Unovis mengurutkan irisan sesuai urutan data (nomor urut) dan menyaring nilai 0,
// sehingga sudut dihitung dengan urutan yang sama.
const segmenPie = computed(() => {
    const positif = props.perolehan.filter((item) => item.votes > 0);
    const total = positif.reduce((sum, item) => sum + item.votes, 0);

    if (total === 0) {
        return [];
    }

    const radius = 30; // persen dari ukuran container (pie penuh)
    let sudut = 0;

    return positif.map((item) => {
        const span = (item.votes / total) * Math.PI * 2;
        const tengah = sudut + span / 2;
        sudut += span;

        return {
            nomor_urut: item.nomor_urut,
            percentage: item.percentage,
            left: 50 + radius * Math.sin(tengah),
            top: 50 - radius * Math.cos(tengah),
        };
    });
});

const configPerolehan = {
    votes: { label: 'Suara', color: 'var(--chart-1)' },
} satisfies ChartConfig;

const configKelas = {
    sudah: { label: 'Sudah', color: 'var(--chart-2)' },
    belum: { label: 'Belum', color: 'var(--chart-3)' },
} satisfies ChartConfig;

const templateKelas = componentToString(configKelas, ChartTooltipContent, {
    labelFormatter: (x: number | Date) => dataKelas.value[Number(x)]?.kelas ?? '-',
});

const templatePerolehan = componentToString(configPerolehan, ChartTooltipContent, {
    labelFormatter: (x: number | Date) => dataPerolehan.value[Number(x)]?.nama_lengkap ?? '-',
});

const adaData = computed(() => props.stats.sudah_voting > 0);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Dashboard"
                :description="`Partisipasi ${stats.partisipasi}% dari ${stats.total_pemilih} pemilih`"
            />
            <div class="flex items-center gap-2">
                <Button as-child variant="outline" size="sm">
                    <a :href="dashboardExport.excel.url()">Export Excel</a>
                </Button>
                <Button as-child variant="outline" size="sm">
                    <a :href="dashboardExport.pdf.url()">Export PDF</a>
                </Button>
                <Button as-child variant="outline" size="sm">
                    <Link href="/users">Kelola Pemilih</Link>
                </Button>
                <Button as-child size="sm">
                    <Link href="/hasil">Lihat Hasil</Link>
                </Button>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Card class="gap-0 py-4">
                <CardContent class="flex items-center gap-3 px-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <Users class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Total Pemilih</p>
                        <p class="text-xl font-semibold">{{ stats.total_pemilih }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card class="gap-0 py-4">
                <CardContent class="flex items-center gap-3 px-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                        <CheckCircle class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Sudah Voting</p>
                        <p class="text-xl font-semibold">{{ stats.sudah_voting }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card class="gap-0 py-4">
                <CardContent class="flex items-center gap-3 px-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <Clock class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Belum Voting</p>
                        <p class="text-xl font-semibold">{{ stats.belum_voting }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card class="gap-0 py-4">
                <CardContent class="flex items-center gap-3 px-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-900/30">
                        <TrendingUp class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Partisipasi</p>
                        <p class="text-xl font-semibold">{{ stats.partisipasi }}%</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <template v-if="adaData">
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Distribusi Suara per Kandidat -->
                <Card class="gap-4 py-4">
                    <CardHeader class="px-4">
                        <CardTitle class="text-base">Distribusi Suara per Kandidat</CardTitle>
                        <CardDescription class="text-xs">{{ stats.sudah_voting }} suara masuk</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col items-center gap-4 px-4 sm:flex-row sm:justify-center sm:gap-6">
                        <div class="relative mx-auto aspect-square w-48 shrink-0 sm:mx-0">
                            <ChartContainer :config="configPerolehan" class="aspect-square h-full w-full">
                                <VisSingleContainer :data="perolehan">
                                    <VisDonut
                                        :value="(d: PerolehanItem) => d.votes"
                                        :color="(_: unknown, i: number) => warnaKandidat(i)"
                                        :arc-width="0"
                                    />
                                </VisSingleContainer>
                            </ChartContainer>

                            <!-- Persentase di dalam irisan -->
                            <div class="pointer-events-none absolute inset-0">
                                <span
                                    v-for="seg in segmenPie"
                                    :key="seg.nomor_urut"
                                    class="absolute -translate-x-1/2 -translate-y-1/2 text-sm font-semibold text-white [text-shadow:0_1px_2px_rgba(0,0,0,0.6)]"
                                    :style="{ left: `${seg.left}%`, top: `${seg.top}%` }"
                                >
                                    {{ seg.percentage }}%
                                </span>
                            </div>
                        </div>

                        <ul class="w-full space-y-1.5 sm:w-auto">
                            <li
                                v-for="(item, index) in perolehan"
                                :key="item.nomor_urut"
                                class="flex items-center gap-2 text-sm"
                            >
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-xs"
                                    :style="{ backgroundColor: warnaKandidat(index) }"
                                />
                                <span class="min-w-0 flex-1 truncate">
                                    {{ item.nomor_urut }}. {{ item.nama_lengkap }}
                                </span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <!-- Jumlah Suara per Kandidat -->
                <Card class="gap-4 py-4">
                    <CardHeader class="px-4">
                        <CardTitle class="text-base">Jumlah Suara per Kandidat</CardTitle>
                        <CardDescription class="text-xs">Total suara yang diperoleh tiap kandidat</CardDescription>
                    </CardHeader>
                    <CardContent class="px-4">
                        <ChartContainer :config="configPerolehan" class="h-[280px] w-full">
                            <VisXYContainer :data="dataPerolehan" :margin="{ left: 8, right: 24 }">
                                <VisGroupedBar
                                    orientation="horizontal"
                                    :x="(d: DataPerolehan) => d.index"
                                    :y="[(d: DataPerolehan) => d.votes]"
                                    :color="[configPerolehan.votes.color]"
                                    :rounded-corners="3"
                                    bar-padding="0.2"
                                />
                                <VisAxis
                                    type="y"
                                    :x="(d: DataPerolehan) => d.index"
                                    :tick-values="dataPerolehan.map((d) => d.index)"
                                    :tick-format="(tick: number) => dataPerolehan[tick]?.nama_lengkap ?? ''"
                                    :tick-line="false"
                                    :domain-line="false"
                                    :grid-line="false"
                                />
                                <VisAxis
                                    type="x"
                                    :tick-format="(tick: number) => String(tick)"
                                    :grid-line="true"
                                    :tick-line="false"
                                    :domain-line="false"
                                />
                                <ChartTooltip />
                                <ChartCrosshair
                                    orientation="horizontal"
                                    :template="templatePerolehan"
                                    :color="[configPerolehan.votes.color]"
                                />
                            </VisXYContainer>
                        </ChartContainer>
                    </CardContent>
                </Card>

                <!-- Partisipasi per Kelas (chart top-5 + rincian semua kelas) -->
                <Card v-if="props.kelas.length > 0" class="gap-4 py-4 lg:col-span-2">
                    <CardHeader class="px-4">
                        <CardTitle class="text-base">Partisipasi per Kelas</CardTitle>
                        <CardDescription class="text-xs">
                            {{ props.kelas.length }} kelas · {{ props.kelas.length - jumlahKelasBelumVoting }} sudah ada suara · {{ jumlahKelasBelumVoting }} belum voting
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-4">
                        <div class="grid gap-6 lg:grid-cols-2">
                            <!-- Chart: 5 kelas teratas -->
                            <div v-if="dataKelas.length > 0">
                                <p class="mb-2 text-xs font-medium text-muted-foreground">
                                    5 kelas dengan partisipasi tertinggi
                                </p>
                                <ChartContainer :config="configKelas" class="h-[280px] w-full">
                                    <VisXYContainer :data="dataKelas" :margin="{ left: 8, right: 24 }">
                                        <VisGroupedBar
                                            orientation="horizontal"
                                            :x="(d: DataKelas) => d.index"
                                            :y="[(d: DataKelas) => d.sudah, (d: DataKelas) => d.belum]"
                                            :color="[configKelas.sudah.color, configKelas.belum.color]"
                                            :rounded-corners="3"
                                            bar-padding="0.15"
                                            group-padding="0.25"
                                        />
                                        <VisAxis
                                            type="y"
                                            :x="(d: DataKelas) => d.index"
                                            :tick-values="dataKelas.map((d) => d.index)"
                                            :tick-format="(tick: number) => dataKelas[tick]?.kelas ?? ''"
                                            :tick-line="false"
                                            :domain-line="false"
                                            :grid-line="false"
                                        />
                                        <VisAxis
                                            type="x"
                                            :tick-format="(tick: number) => String(tick)"
                                            :grid-line="true"
                                            :tick-line="false"
                                            :domain-line="false"
                                        />
                                        <ChartTooltip />
                                        <ChartCrosshair
                                            orientation="horizontal"
                                            :template="templateKelas"
                                            :color="[configKelas.sudah.color, configKelas.belum.color]"
                                        />
                                    </VisXYContainer>
                                </ChartContainer>
                            </div>

                            <!-- Tabel: semua kelas -->
                            <div class="max-h-[320px] overflow-y-auto pr-1">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Kelas</TableHead>
                                            <TableHead class="text-right">Total</TableHead>
                                            <TableHead class="text-right">Sudah</TableHead>
                                            <TableHead class="text-right">Belum</TableHead>
                                            <TableHead>Partisipasi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="item in daftarKelas" :key="item.kelas">
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium">{{ item.kelas }}</span>
                                                    <Badge
                                                        v-if="item.sudah === 0"
                                                        variant="outline"
                                                        class="text-[10px]"
                                                    >
                                                        Belum voting
                                                    </Badge>
                                                </div>
                                            </TableCell>
                                            <TableCell class="text-right font-mono text-xs tabular-nums">
                                                {{ item.total }}
                                            </TableCell>
                                            <TableCell
                                                class="text-right font-mono text-xs tabular-nums"
                                                :class="item.sudah > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'"
                                            >
                                                {{ item.sudah }}
                                            </TableCell>
                                            <TableCell class="text-right font-mono text-xs tabular-nums text-muted-foreground">
                                                {{ item.belum }}
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <div class="h-1.5 w-20 overflow-hidden rounded-full bg-muted">
                                                        <div
                                                            class="h-full rounded-full"
                                                            :class="item.persentase > 0 ? 'bg-emerald-500' : 'bg-muted-foreground/30'"
                                                            :style="{ width: `${Math.min(item.persentase, 100)}%` }"
                                                        />
                                                    </div>
                                                    <span class="w-10 text-right text-xs tabular-nums">
                                                        {{ item.persentase }}%
                                                    </span>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>

        <!-- Empty State -->
        <div
            v-else
            class="flex flex-col items-center justify-center rounded-xl border border-dashed py-12 text-center"
        >
            <TrendingUp class="mb-3 h-8 w-8 text-muted-foreground" />
            <p class="text-sm font-medium">Belum ada data voting.</p>
            <p class="mt-1 text-xs text-muted-foreground">
                Statistik akan muncul setelah pemilih mulai memberikan suara.
            </p>
        </div>

        <!-- Riwayat Aktivitas Voting -->
        <Card v-if="aktivitas.length > 0" class="gap-4 py-4">
            <CardHeader class="px-4">
                <CardTitle class="text-base">Riwayat Aktivitas Voting</CardTitle>
                <CardDescription class="text-xs">10 aktivitas voting terbaru</CardDescription>
            </CardHeader>
            <CardContent class="px-4">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-12">No</TableHead>
                                <TableHead>Pemilih</TableHead>
                                <TableHead>Kelas</TableHead>
                                <TableHead>Aktivitas</TableHead>
                                <TableHead>Kandidat</TableHead>
                                <TableHead>Waktu</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, index) in aktivitas" :key="item.id">
                                <TableCell class="font-mono text-xs text-muted-foreground">
                                    {{ index + 1 }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-xs font-bold text-white"
                                        >
                                            {{ item.nama_pemilih.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="font-medium">{{ item.nama_pemilih }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="item.kelas" variant="secondary" class="text-xs">
                                        {{ item.kelas }}
                                    </Badge>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span>{{ labelAksi(item.aksi) }}</span>
                                        <span
                                            v-if="item.ip_address"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.ip_address }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span v-if="item.nomor_urut !== null" class="text-sm">
                                        {{ item.nomor_urut }}. {{ item.nama_kandidat }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ formatWaktu(item.waktu) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>