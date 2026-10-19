<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { Copy, Eye, EyeOff, Printer, RefreshCw, Search } from '@lucide/vue';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import type { PaginatedUsers } from '@/types';

const props = defineProps<{
    users: PaginatedUsers;
    filters?: {
        search: string;
        kelas: string;
        status: string;
    };
    kelasList?: string[];
    generatedCodes?: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Manajemen User',
                href: '/users',
            },
        ],
    },
});

const selectedIds = ref<string[]>([]);
const isPrinting = ref(false);
const printForm = ref<HTMLFormElement | null>(null);

const search = ref(props.filters?.search ?? '');
const kelasFilter = ref(props.filters?.kelas ?? '');
const statusFilter = ref(props.filters?.status ?? '');

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    const query: Record<string, string> = {};
    if (search.value.trim()) query.search = search.value.trim();
    if (kelasFilter.value) query.kelas = kelasFilter.value;
    if (statusFilter.value) query.status = statusFilter.value;

    router.get('/users', query, {
        // Partial reload: hanya data tabel & filter yang di-refetch,
        // layout/sidebar tidak dihitung ulang sehingga tidak ada kedipan.
        only: ['users', 'filters', 'kelasList'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

function onKelasChange(value: unknown) {
    const v = value ? String(value) : '';
    kelasFilter.value = v === 'all' ? '' : v;
    applyFilters();
}

function onStatusChange(value: unknown) {
    const v = value ? String(value) : '';
    statusFilter.value = v === 'all' ? '' : v;
    applyFilters();
}

function resetFilters() {
    search.value = '';
    kelasFilter.value = '';
    statusFilter.value = '';
    applyFilters();
}

const hasFilters = computed(
    () =>
        search.value !== '' ||
        kelasFilter.value !== '' ||
        statusFilter.value !== '',
);

const statusLabels: Record<string, string> = {
    aktif: 'Aktif',
    sudah_voting: 'Sudah Voting',
    kedaluwarsa: 'Kedaluwarsa',
    nonaktif: 'Nonaktif',
};

function statusLabel(status: string): string {
    return statusLabels[status] ?? status;
}

const csrfToken = computed(
    () =>
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '',
);

const allCurrentIds = computed(() =>
    props.users.data.filter((u) => u.role === 'pemilih').map((u) => u.id),
);

const selectableCount = computed(() => allCurrentIds.value.length);

const isAllSelected = computed(
    () =>
        selectableCount.value > 0 &&
        allCurrentIds.value.every((id) => selectedIds.value.includes(id)),
);

const isIndeterminate = computed(
    () => selectedIds.value.length > 0 && !isAllSelected.value,
);

function toggleAll() {
    if (isAllSelected.value) {
        selectedIds.value = selectedIds.value.filter(
            (id) => !allCurrentIds.value.includes(id),
        );
    } else {
        const newIds = allCurrentIds.value.filter(
            (id) => !selectedIds.value.includes(id),
        );
        selectedIds.value = [...selectedIds.value, ...newIds];
    }
}

function toggleSelection(id: string) {
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
    console.log('Selected IDs:', selectedIds.value);
}

function printCards() {
    if (selectedIds.value.length === 0) {
        return;
    }

    isPrinting.value = true;

    console.log('Submitting form with IDs:', selectedIds.value);
    console.log(
        'Form inputs count:',
        printForm.value?.querySelectorAll('input[name="student_ids[]"]').length,
    );

    nextTick(() => {
        printForm.value?.submit();
        setTimeout(() => {
            isPrinting.value = false;
        }, 1500);
    });
}

const deletingUserId = ref<string | null>(null);
const deleteOpen = ref(false);

function deleteUser(id: string) {
    deletingUserId.value = id;
    deleteOpen.value = true;
}

function confirmDeleteUser() {
    if (!deletingUserId.value) return;
    router.delete(`/users/${deletingUserId.value}`, {
        onFinish: () => {
            deletingUserId.value = null;
        },
    });
}

// Kode baru muncul sekali lewat dialog setelah dibuat/reset, tapi tetap bisa
// dilihat kapan saja dari kolom Kode di tabel.
const resettingKodeId = ref<string | null>(null);
const codesOpen = ref<Record<string, string> | null>(null);
const revealedKode = ref<Record<string, boolean>>({});

function toggleKodeReveal(id: string) {
    revealedKode.value[id] = !revealedKode.value[id];
}

// Dialog dibuka otomatis setiap kali backend mengirim kode baru.
watch(
    () => props.generatedCodes,
    (codes) => {
        if (codes && Object.keys(codes).length > 0) {
            codesOpen.value = { ...codes };
        }
    },
    { immediate: true },
);

const kodeEntries = computed(() => Object.entries(codesOpen.value ?? {}));
const kodeCount = computed(() => kodeEntries.value.length);
const kodeText = computed(() =>
    kodeEntries.value.map(([nis, kode]) => `${nis} — kode: ${kode}`).join('\n'),
);
const missingKodeCount = computed(
    () => props.users.data.filter((user) => user.kode === null).length,
);

function resetKode(userId: string) {
    resettingKodeId.value = userId;
    router.patch(
        `/users/${userId}/reset-kode`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                resettingKodeId.value = null;
            },
        },
    );
}

function resetKodeKosong() {
    router.patch('/users/reset-kode-kosong', {}, { preserveScroll: true });
}

async function copyAllCodes() {
    await navigator.clipboard.writeText(kodeText.value);
}

function printCodes() {
    window.print();
}
</script>

<template>
    <Head title="Manajemen User" />

    <!-- Hidden form untuk print PDF -->
    <form
        ref="printForm"
        method="POST"
        action="/users/cards/print"
        target="_blank"
        class="hidden"
    >
        <input type="hidden" name="_token" :value="csrfToken" />
        <input
            v-for="id in selectedIds"
            :key="id"
            type="hidden"
            name="student_ids[]"
            :value="id"
        />
    </form>

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Manajemen User"
                description="Kelola daftar pemilih dan kandidat"
            />
            <div class="flex gap-2">
                <Link href="/users/import">
                    <Button variant="outline">Import Excel</Button>
                </Link>
                <Link href="/users/create">
                    <Button>Tambah User</Button>
                </Link>
            </div>
        </div>

        <div
            v-if="selectedIds.length > 0"
            class="flex items-center gap-3 rounded-lg border border-primary/50 bg-primary/5 p-3"
        >
            <p class="text-sm font-medium">
                {{ selectedIds.length }} siswa dipilih
            </p>
            <Button size="sm" :disabled="isPrinting" @click="printCards">
                {{ isPrinting ? 'Membuat PDF...' : 'Cetak Kartu' }}
            </Button>
        </div>

        <div
            class="flex flex-wrap items-center gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
        >
            <div class="relative min-w-56 flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Cari NIS atau nama..."
                    class="pl-9"
                />
            </div>

            <Select
                :model-value="kelasFilter || 'all'"
                @update:model-value="onKelasChange"
            >
                <SelectTrigger class="w-44">
                    <SelectValue placeholder="Kelas" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Semua Kelas</SelectItem>
                    <SelectItem
                        v-for="kelas in kelasList ?? []"
                        :key="kelas"
                        :value="kelas"
                    >
                        {{ kelas }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select
                :model-value="statusFilter || 'all'"
                @update:model-value="onStatusChange"
            >
                <SelectTrigger class="w-48">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Semua Status</SelectItem>
                    <SelectItem value="aktif">Aktif</SelectItem>
                    <SelectItem value="sudah_voting">Sudah Voting</SelectItem>
                    <SelectItem value="kedaluwarsa">Kedaluwarsa</SelectItem>
                    <SelectItem value="nonaktif">Nonaktif</SelectItem>
                </SelectContent>
            </Select>

            <Button
                v-if="hasFilters"
                variant="ghost"
                size="sm"
                @click="resetFilters"
            >
                Reset
            </Button>

            <Button
                variant="outline"
                size="sm"
                class="ml-auto"
                :disabled="missingKodeCount === 0"
                @click="resetKodeKosong"
            >
                <RefreshCw class="h-4 w-4" />
                Buat Kode untuk yang belum ada
            </Button>
        </div>

        <div
            class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-12">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                @change="toggleAll"
                                class="rounded border-gray-300"
                            />
                        </TableHead>
                        <TableHead class="w-12">No</TableHead>
                        <TableHead>NIS</TableHead>
                        <TableHead>Nama</TableHead>
                        <TableHead>Kode</TableHead>
                        <TableHead>Kelas</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-center">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="users.data.length === 0">
                        <TableCell
                            colspan="8"
                            class="text-center text-muted-foreground"
                        >
                            {{
                                hasFilters
                                    ? 'Tidak ada data yang cocok dengan filter.'
                                    : 'Belum ada data user.'
                            }}
                        </TableCell>
                    </TableRow>
                    <TableRow
                        v-for="(user, index) in users.data"
                        :key="user.id"
                    >
                        <TableCell>
                            <input
                                v-if="user.role === 'pemilih'"
                                type="checkbox"
                                :checked="selectedIds.includes(user.id)"
                                @change="() => toggleSelection(user.id)"
                                class="rounded border-gray-300"
                            />
                        </TableCell>
                        <TableCell>{{ (users.from ?? 1) + index }}</TableCell>
                        <TableCell>{{ user.nis }}</TableCell>
                        <TableCell>{{ user.nama_lengkap ?? '-' }}</TableCell>
                        <TableCell>
                            <Badge
                                v-if="user.kode === null"
                                variant="outline"
                                class="text-muted-foreground"
                            >
                                Belum ada
                            </Badge>
                            <div v-else class="flex items-center gap-2">
                                <span class="font-mono tracking-widest">
                                    {{
                                        revealedKode[user.id]
                                            ? user.kode
                                            : '••••••'
                                    }}
                                </span>
                                <button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground"
                                    :title="
                                        revealedKode[user.id]
                                            ? 'Sembunyikan kode'
                                            : 'Tampilkan kode'
                                    "
                                    @click="toggleKodeReveal(user.id)"
                                >
                                    <EyeOff
                                        v-if="revealedKode[user.id]"
                                        class="h-4 w-4"
                                    />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                        </TableCell>
                        <TableCell>{{ user.kelas }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    user.status === 'aktif'
                                        ? 'default'
                                        : 'outline'
                                "
                            >
                                {{ statusLabel(user.status) }}
                            </Badge>
                        </TableCell>
                        <TableCell class="flex justify-center">
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="resettingKodeId === user.id"
                                    title="Reset kode"
                                    @click="resetKode(user.id)"
                                >
                                    <RefreshCw class="h-4 w-4" />
                                </Button>
                                <Link :href="`/users/${user.id}/edit`">
                                    <Button variant="outline" size="sm"
                                        >Edit</Button
                                    >
                                </Link>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="deleteUser(user.id)"
                                >
                                    Hapus
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div
            v-if="users.last_page > 1"
            class="flex items-center justify-between px-2"
        >
            <p class="text-sm text-muted-foreground">
                Menampilkan {{ users.from }} sampai {{ users.to }} dari
                {{ users.total }} data
            </p>
            <div class="flex gap-1">
                <template v-for="(link, index) in users.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'rounded-md border px-3 py-1 text-sm transition-colors',
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-accent',
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="cursor-not-allowed rounded-md border px-3 py-1 text-sm text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>

    <DeleteConfirmDialog
        v-model:open="deleteOpen"
        title="Hapus User?"
        description="Data user yang dihapus tidak dapat dikembalikan kembali."
        @confirm="confirmDeleteUser"
    />

    <Dialog
        :open="codesOpen !== null"
        @update:open="
            (v) => {
                if (!v) codesOpen = null;
            }
        "
    >
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Kode Login ({{ kodeCount }})</DialogTitle>
                <DialogDescription>
                    Kode baru berhasil dibuat. Catat atau cetak sekarang — kode
                    sebelumnya tidak berlaku lagi.
                </DialogDescription>
            </DialogHeader>

            <ul class="max-h-72 divide-y overflow-y-auto rounded-md border">
                <li
                    v-for="[nis, kode] in kodeEntries"
                    :key="nis"
                    class="flex items-center justify-between gap-4 px-3 py-2 text-sm"
                >
                    <span class="font-medium">{{ nis }}</span>
                    <span class="font-mono text-base tracking-widest">{{
                        kode
                    }}</span>
                </li>
            </ul>

            <DialogFooter class="gap-2 sm:justify-between">
                <Button variant="outline" size="sm" @click="copyAllCodes">
                    <Copy class="h-4 w-4" />
                    Salin semua
                </Button>
                <Button size="sm" @click="printCodes">
                    <Printer class="h-4 w-4" />
                    Cetak
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
