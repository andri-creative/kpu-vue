<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Copy,
    Eye,
    File as FileIcon,
    FileArchive,
    FileSpreadsheet,
    FileText,
    FolderOpen,
    HardDrive,
    Image as ImageIcon,
    Images,
    Layers,
    RefreshCw,
    Search,
    Trash2,
    TriangleAlert,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit as editProfile } from '@/routes/profile';
import {
    destroy as filesDestroy,
    index as filesIndex,
    preview as filesPreview,
} from '@/routes/settings/files';

interface FileRow {
    name: string;
    path: string;
    url: string | null;
    collection: string;
    collection_label: string;
    extension: string;
    type: string;
    size: number;
    size_formatted: string;
    modified_at: string;
    modified_timestamp: number;
}

interface Summary {
    total_files: number;
    total_bytes: number;
    total_size_formatted: string;
    images: number;
    pdf: number;
    documents: number;
    other: number;
}

interface CollectionStat {
    key: string;
    label: string;
    description: string;
    file_count: number;
    bytes: number;
    bytes_formatted: string;
}

interface Filters {
    collection: string;
    search: string;
    type: string;
}

const props = defineProps<{
    files: FileRow[];
    summary: Summary;
    collections: CollectionStat[];
    filters: Filters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: editProfile(),
            },
            {
                title: 'Storage',
                href: filesIndex.url(),
            },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const collectionFilter = ref(props.filters.collection ?? '');
const typeFilter = ref(props.filters.type ?? '');

const previewFile = ref<FileRow | null>(null);
const deleteTarget = ref<FileRow | null>(null);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const typeOptions = [
    { value: 'image', label: 'Gambar' },
    { value: 'pdf', label: 'PDF' },
    { value: 'document', label: 'Dokumen' },
    { value: 'archive', label: 'Arsip' },
    { value: 'other', label: 'Lainnya' },
];

const hasFilters = computed(
    () =>
        search.value !== '' ||
        collectionFilter.value !== '' ||
        typeFilter.value !== '',
);

const videoExtensions = ['mp4', 'webm', 'ogv', 'mov', 'm4v'];
const audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];

function previewUrl(file: FileRow): string {
    return filesPreview.url({ query: { path: file.path } });
}

function isVideo(file: FileRow | null): boolean {
    return (
        file !== null &&
        file.url !== null &&
        videoExtensions.includes(file.extension)
    );
}

function isAudio(file: FileRow | null): boolean {
    return (
        file !== null &&
        file.url !== null &&
        audioExtensions.includes(file.extension)
    );
}

function applyFilters() {
    const query: Record<string, string> = {};

    if (search.value.trim()) {
        query.search = search.value.trim();
    }

    if (collectionFilter.value) {
        query.collection = collectionFilter.value;
    }

    if (typeFilter.value) {
        query.type = typeFilter.value;
    }

    router.get(filesIndex.url(), query, {
        only: ['files', 'filters', 'summary', 'collections'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watch(search, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => applyFilters(), 400);
});

function onCollectionChange(value: unknown) {
    const current = value ? String(value) : '';
    collectionFilter.value = current === 'all' ? '' : current;
    applyFilters();
}

function onTypeChange(value: unknown) {
    const current = value ? String(value) : '';
    typeFilter.value = current === 'all' ? '' : current;
    applyFilters();
}

function resetFilters() {
    search.value = '';
    collectionFilter.value = '';
    typeFilter.value = '';
    applyFilters();
}

function iconFor(type: string) {
    switch (type) {
        case 'image':
            return ImageIcon;
        case 'pdf':
            return FileText;
        case 'document':
            return FileSpreadsheet;
        case 'archive':
            return FileArchive;
        default:
            return FileIcon;
    }
}

function typeLabel(type: string): string {
    return (
        typeOptions.find((option) => option.value === type)?.label ?? 'Lainnya'
    );
}

function isImage(file: FileRow): boolean {
    return file.type === 'image' && file.url !== null;
}

function copyLink(file: FileRow) {
    if (!file.url) {
        return;
    }

    void navigator.clipboard.writeText(file.url);
    toast.success('Link disalin ke clipboard.');
}

function confirmDelete() {
    if (!deleteTarget.value) {
        return;
    }

    const file = deleteTarget.value;

    router.delete(filesDestroy.url(), {
        data: { collection: file.collection, path: file.path },
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <div class="space-y-4">
        <Head title="Storage File" />

        <h1 class="sr-only">Storage File</h1>

        <!-- Header -->
        <div
            class="flex flex-col gap-2 border-b border-border/40 pb-1.5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-md bg-primary/10 text-primary"
                    >
                        <FolderOpen class="h-3 w-3" />
                    </div>
                    <h2
                        class="text-sm font-semibold tracking-tight text-foreground"
                    >
                        Storage File
                    </h2>
                    <Badge
                        variant="outline"
                        class="bg-muted/50 font-mono text-[10px] tracking-wider uppercase"
                    >
                        {{ summary.total_files }} file
                    </Badge>
                </div>
                <p class="text-[11px] text-muted-foreground">
                    Berkas di folder public/uploads — gambar, PDF, dokumen, dan
                    lainnya.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 text-xs"
                    @click="applyFilters"
                >
                    <RefreshCw class="mr-1.5 h-3.5 w-3.5" />
                    Segarkan
                </Button>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <Card class="p-3.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-muted-foreground"
                        >Total File</span
                    >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400"
                    >
                        <Layers class="h-3.5 w-3.5" />
                    </div>
                </div>
                <div
                    class="mt-3 text-lg font-bold tracking-tight text-foreground"
                >
                    {{ summary.total_files.toLocaleString('id-ID') }}
                </div>
            </Card>

            <Card class="p-3.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-muted-foreground"
                        >Total Ukuran</span
                    >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                    >
                        <HardDrive class="h-3.5 w-3.5" />
                    </div>
                </div>
                <div
                    class="mt-3 text-lg font-bold tracking-tight text-foreground"
                >
                    {{ summary.total_size_formatted }}
                </div>
            </Card>

            <Card class="p-3.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-muted-foreground"
                        >Gambar</span
                    >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-purple-500/10 text-purple-600 dark:text-purple-400"
                    >
                        <Images class="h-3.5 w-3.5" />
                    </div>
                </div>
                <div
                    class="mt-3 text-lg font-bold tracking-tight text-foreground"
                >
                    {{ summary.images.toLocaleString('id-ID') }}
                </div>
            </Card>

            <Card class="p-3.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-muted-foreground"
                        >PDF & Dokumen</span
                    >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400"
                    >
                        <FileText class="h-3.5 w-3.5" />
                    </div>
                </div>
                <div
                    class="mt-3 text-lg font-bold tracking-tight text-foreground"
                >
                    {{
                        (summary.pdf + summary.documents).toLocaleString(
                            'id-ID',
                        )
                    }}
                </div>
            </Card>
        </div>

        <!-- Toolbar -->
        <div
            class="flex flex-wrap items-center gap-2 rounded-lg border border-border/50 p-3"
        >
            <div class="relative min-w-56 flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Cari nama berkas..."
                    class="h-9 pl-9 text-sm"
                />
            </div>

            <Select
                :model-value="collectionFilter || 'all'"
                @update:model-value="onCollectionChange"
            >
                <SelectTrigger class="h-9 w-44 text-sm">
                    <SelectValue placeholder="Sumber" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Semua Sumber</SelectItem>
                    <SelectItem
                        v-for="collection in collections"
                        :key="collection.key"
                        :value="collection.key"
                    >
                        {{ collection.label }}
                        ({{ collection.file_count }})
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select
                :model-value="typeFilter || 'all'"
                @update:model-value="onTypeChange"
            >
                <SelectTrigger class="h-9 w-40 text-sm">
                    <SelectValue placeholder="Tipe" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Semua Tipe</SelectItem>
                    <SelectItem
                        v-for="option in typeOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button
                v-if="hasFilters"
                variant="ghost"
                size="sm"
                class="h-9 text-xs"
                @click="resetFilters"
            >
                <X class="mr-1.5 h-3.5 w-3.5" />
                Reset
            </Button>
        </div>

        <!-- Files Grid -->
        <div
            v-if="files.length > 0"
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <Card
                v-for="file in files"
                :key="file.path"
                class="flex flex-col overflow-hidden transition-colors hover:border-border/80"
            >
                <button
                    type="button"
                    class="group relative flex h-36 items-center justify-center border-b border-border/40 bg-muted/30"
                    @click="previewFile = file"
                >
                    <img
                        v-if="isImage(file)"
                        :src="file.url ?? ''"
                        :alt="file.name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <component
                        v-else
                        :is="iconFor(file.type)"
                        class="h-10 w-10 text-muted-foreground"
                    />
                    <span
                        class="absolute inset-0 flex items-center justify-center bg-background/60 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <Eye class="h-5 w-5 text-foreground" />
                    </span>
                </button>

                <CardContent class="flex flex-1 flex-col gap-2 p-3">
                    <div class="min-w-0">
                        <p
                            class="truncate text-sm font-medium text-foreground"
                            :title="file.name"
                        >
                            {{ file.name }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-muted-foreground">
                            {{ file.modified_at }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <Badge
                            variant="outline"
                            class="font-mono text-[10px] font-normal uppercase"
                        >
                            {{ file.extension || 'file' }}
                        </Badge>
                        <Badge
                            variant="secondary"
                            class="font-mono text-[10px] font-normal"
                        >
                            {{ file.size_formatted }}
                        </Badge>
                        <Badge
                            variant="outline"
                            class="text-[10px] font-normal"
                        >
                            {{ file.collection_label }}
                        </Badge>
                    </div>

                    <div class="mt-auto flex items-center gap-1 pt-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7"
                            title="Preview"
                            @click="previewFile = file"
                        >
                            <Eye class="h-3.5 w-3.5" />
                        </Button>
                        <Button
                            v-if="file.url"
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7"
                            title="Salin link"
                            @click="copyLink(file)"
                        >
                            <Copy class="h-3.5 w-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="ml-auto h-7 w-7 text-destructive hover:bg-destructive/10 hover:text-destructive"
                            title="Hapus"
                            @click="deleteTarget = file"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Empty State -->
        <Card
            v-else
            class="flex flex-col items-center justify-center gap-2 py-16"
        >
            <div
                class="flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
            >
                <FolderOpen class="h-6 w-6" />
            </div>
            <p class="text-sm font-medium text-foreground">
                {{
                    hasFilters
                        ? 'Tidak ada berkas yang cocok'
                        : 'Belum ada berkas'
                }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{
                    hasFilters
                        ? 'Coba ubah kata kunci atau filter.'
                        : 'Berkas yang diunggah ke public/uploads akan muncul di sini.'
                }}
            </p>
        </Card>

        <!-- Preview Dialog -->
        <Dialog
            :open="previewFile !== null"
            @update:open="!$event && (previewFile = null)"
        >
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 break-all">
                        <component
                            :is="iconFor(previewFile?.type ?? 'other')"
                            class="h-4 w-4 shrink-0 text-primary"
                        />
                        {{ previewFile?.name }}
                    </DialogTitle>
                    <DialogDescription class="flex flex-wrap gap-1.5 pt-1.5">
                        <Badge
                            variant="outline"
                            class="text-[10px] font-normal"
                        >
                            {{ previewFile?.size_formatted }}
                        </Badge>
                        <Badge
                            variant="outline"
                            class="text-[10px] font-normal"
                        >
                            {{ typeLabel(previewFile?.type ?? 'other') }}
                        </Badge>
                        <Badge
                            variant="outline"
                            class="text-[10px] font-normal"
                        >
                            {{ previewFile?.collection_label }}
                        </Badge>
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="flex max-h-[70vh] min-h-40 items-center justify-center overflow-auto rounded-lg border border-border bg-muted/30 p-2"
                >
                    <img
                        v-if="previewFile?.type === 'image' && previewFile.url"
                        :src="previewFile.url"
                        :alt="previewFile.name"
                        class="max-h-[64vh] w-auto rounded"
                    />
                    <video
                        v-else-if="isVideo(previewFile)"
                        :src="previewFile?.url ?? ''"
                        class="max-h-[64vh] w-full rounded"
                        controls
                    />
                    <audio
                        v-else-if="isAudio(previewFile)"
                        :src="previewFile?.url ?? ''"
                        class="w-full"
                        controls
                    />
                    <iframe
                        v-else-if="previewFile"
                        :src="previewUrl(previewFile)"
                        class="h-[64vh] w-full rounded"
                        title="Pratinjau berkas"
                    />
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 py-10 text-center"
                    >
                        <component
                            :is="iconFor('other')"
                            class="h-10 w-10 text-muted-foreground"
                        />
                        <p class="text-sm text-muted-foreground">
                            Pratinjau tidak tersedia.
                        </p>
                    </div>
                </div>

                <DialogFooter class="gap-2 pt-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button variant="outline">Tutup</Button>
                    </DialogClose>
                    <Button v-if="previewFile" variant="default" as-child>
                        <a
                            :href="previewUrl(previewFile)"
                            target="_blank"
                            rel="noopener"
                        >
                            <Eye class="mr-1.5 h-3.5 w-3.5" />
                            Buka di Tab Baru
                        </a>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            :open="deleteTarget !== null"
            @update:open="!$event && (deleteTarget = null)"
        >
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <TriangleAlert class="h-5 w-5 text-destructive" />
                        Hapus Berkas
                    </DialogTitle>
                    <DialogDescription class="pt-1.5 leading-relaxed">
                        Berkas
                        <span class="font-medium text-foreground">{{
                            deleteTarget?.name
                        }}</span>
                        akan dihapus permanen. Tindakan ini tidak dapat
                        dibatalkan.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2 pt-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button variant="outline">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmDelete">
                        <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
