<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Download, FileSpreadsheet, Trash2, UploadCloud } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import type { PreviewResult } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Manajemen User', href: '/users' },
            { title: 'Import Data Siswa', href: '/users/import' },
        ],
    },
});

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const isDragging = ref(false);

const props = defineProps<{ preview?: PreviewResult | null }>();
const isProcessing = ref(false);
const preview = ref<PreviewResult | null>(props.preview ?? null);

watch(
    () => props.preview,
    (value) => {
        preview.value = value ?? null;
    },
);

const page = usePage();
const storeError = ref<string | undefined>(undefined);

watch(
    () => page.props.errors,
    (errors) => {
        storeError.value = (errors as { file?: string } | undefined)?.file;
    },
    { immediate: true },
);

const formattedSize = computed(() => {
    if (!selectedFile.value) {
        return '';
    }

    const kb = selectedFile.value.size / 1024;

    return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.round(kb)} KB`;
});

function openFilePicker() {
    fileInput.value?.click();
}

function selectFile(file: File | undefined) {
    if (!file) {
        return;
    }

    selectedFile.value = file;
    preview.value = null;
    storeError.value = undefined;
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    selectFile(target.files?.[0]);
}

function onDrop(event: DragEvent) {
    isDragging.value = false;
    selectFile(event.dataTransfer?.files?.[0]);
}

function removeFile() {
    selectedFile.value = null;
    preview.value = null;
    storeError.value = undefined;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function previewData() {
    if (!selectedFile.value) {
        return;
    }

    router.post(
        '/users/import/preview',
        { file: selectedFile.value },
        {
            forceFormData: true,
            onStart: () => {
                isProcessing.value = true;
            },
            onFinish: () => {
                isProcessing.value = false;
            },
        },
    );
}

function submitImport() {
    if (!selectedFile.value) {
        return;
    }

    router.post(
        '/users/import',
        { file: selectedFile.value },
        {
            forceFormData: true,
            onStart: () => {
                isProcessing.value = true;
            },
            onFinish: () => {
                isProcessing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Import Data Siswa" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Import Data Siswa"
                description="Import data siswa secara massal menggunakan file Excel."
            />
            <a href="/users/import/template">
                <Button variant="outline">
                    <Download />
                    Download Template Excel
                </Button>
            </a>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Upload File Excel</CardTitle>
                <CardDescription>
                    Format kolom harus sesuai template: NIS, Nama, Kelas.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <input
                    ref="fileInput"
                    type="file"
                    accept=".xlsx,.xls"
                    class="hidden"
                    @change="onFileChange"
                />

                <div
                    v-if="!selectedFile"
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-sidebar-border/70 p-10 text-center transition-colors hover:bg-accent/50 dark:border-sidebar-border"
                    :class="{ 'bg-accent/50': isDragging }"
                    @click="openFilePicker"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="onDrop"
                >
                    <UploadCloud class="size-8 text-muted-foreground" />
                    <p class="text-sm font-medium">
                        Drag &amp; Drop file Excel atau Pilih File
                    </p>
                    <p class="text-xs text-muted-foreground">.xlsx / .xls (maks. 5 MB)</p>
                </div>

                <div
                    v-else
                    class="flex items-center justify-between rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div class="flex items-center gap-3">
                        <FileSpreadsheet class="size-8 text-muted-foreground" />
                        <div>
                            <p class="text-sm font-medium">{{ selectedFile.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ formattedSize }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" @click="removeFile">
                            <Trash2 />
                            Hapus
                        </Button>
                        <Button size="sm" :disabled="isProcessing" @click="previewData">
                            Preview Data
                        </Button>
                    </div>
                </div>

                <InputError :message="storeError" />
            </CardContent>
        </Card>

        <Alert v-if="preview && !preview.header_valid" variant="destructive">
            <AlertTitle>Format tidak sesuai</AlertTitle>
            <AlertDescription>
                {{ preview.message }}
            </AlertDescription>
        </Alert>

        <template v-if="preview && preview.header_valid">
            <div class="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardDescription>Total Data</CardDescription>
                        <CardTitle class="text-2xl">{{ preview.total }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Data Valid</CardDescription>
                        <CardTitle class="text-2xl text-emerald-600">{{ preview.valid }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Data Error</CardDescription>
                        <CardTitle class="text-2xl text-red-600">{{ preview.invalid }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Alert v-if="preview.total === 0">
                <AlertDescription>{{ preview.message }}</AlertDescription>
            </Alert>

            <Alert v-else-if="preview.invalid > 0" variant="destructive">
                <AlertTitle>Terdapat {{ preview.invalid }} data error</AlertTitle>
                <AlertDescription>
                    Perbaiki file Excel terlebih dahulu. Data tidak akan disimpan jika masih ada error.
                </AlertDescription>
            </Alert>

            <div
                v-if="preview.rows.length"
                class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-16 text-right">No</TableHead>
                            <TableHead>NIS</TableHead>
                            <TableHead>Nama</TableHead>
                            <TableHead>Kelas</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Keterangan</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(row, index) in preview.rows" :key="row.row">
                            <TableCell class="text-right">{{ index + 1 }}</TableCell>
                            <TableCell>{{ row.nis }}</TableCell>
                            <TableCell>{{ row.nama }}</TableCell>
                            <TableCell>{{ row.kelas }}</TableCell>
                            <TableCell>
                                <Badge :variant="row.status === 'valid' ? 'default' : 'destructive'">
                                    {{ row.status === 'valid' ? 'Valid' : 'Error' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <ul v-if="row.errors.length" class="list-inside list-disc text-sm text-red-600">
                                    <li v-for="error in row.errors" :key="error">
                                        Row {{ row.row }} — {{ error }}
                                    </li>
                                </ul>
                                <span v-else class="text-sm text-muted-foreground">-</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div v-if="preview.ok" class="flex items-center gap-4">
                <Button :disabled="isProcessing" @click="submitImport">
                    Import {{ preview.valid }} Data
                </Button>
                <Link href="/users">
                    <Button type="button" variant="outline">Batal</Button>
                </Link>
            </div>
        </template>
    </div>
</template>