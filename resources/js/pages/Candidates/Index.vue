<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import Heading from '@/components/Heading.vue';
import ImageUpload from '@/components/ImageUpload.vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    Plus,
    Edit2,
    Trash2,
    LoaderCircle,
    X,
    Check,
    GripVertical,
    Eye,
    EyeOff,
} from '@lucide/vue';
import type { Candidate, CandidatesPaginator } from '@/types';

interface Props {
    candidates: CandidatesPaginator;
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Kandidat', href: '/candidates' },
        ],
    },
});

const showAddForm = ref(false);
const editingId = ref<number | null>(null);
const selectedCandidate = ref<Candidate | null>(null);
const deleteOpen = ref(false);
const pendingDeleteCandidate = ref<Candidate | null>(null);

// Drag-and-drop state
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);
const pendingOrder = ref<Array<{ id: number; nomor_urut: number }> | null>(
    null,
);
const gridRef = ref<HTMLElement | null>(null);
const expandedCandidateId = ref<number | null>(null);

const toggleVisiMisi = (id: number) => {
    expandedCandidateId.value = expandedCandidateId.value === id ? null : id;
};

const handleDragStart = (index: number, event: DragEvent) => {
    if (!event.dataTransfer) return;
    draggedIndex.value = index;
    dragOverIndex.value = index;
    pendingOrder.value = null;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(index));
};

// Cari kartu di bawah kursor saat dragover
const getCardIndexFromEvent = (event: DragEvent): number | null => {
    const x = event.clientX;
    const y = event.clientY;
    const el = document.elementFromPoint(x, y) as HTMLElement | null;
    const card = el?.closest('[data-drag-card]');
    if (!card) return null;
    const cards = document.querySelectorAll('[data-drag-card]');
    return Array.from(cards).indexOf(card);
};

const handleGridDragOver = (event: DragEvent) => {
    event.preventDefault();
    event.dataTransfer!.dropEffect = 'move';
    const index = getCardIndexFromEvent(event);
    if (
        index === null ||
        draggedIndex.value === null ||
        draggedIndex.value === index
    )
        return;
    dragOverIndex.value = index;

    const card = document
        .elementFromPoint(event.clientX, event.clientY)
        ?.closest('[data-drag-card]') as HTMLElement | null;
    if (!card) return;
    const rect = card.getBoundingClientRect();
    const isAboveMiddle = event.clientY - rect.top < rect.height / 2;
    const calculatedDropIndex = isAboveMiddle ? index : index + 1;

    const data = [...props.candidates.data];
    const [moved] = data.splice(draggedIndex.value, 1);
    data.splice(calculatedDropIndex, 0, moved);
    pendingOrder.value = data.map((c, i) => ({ id: c.id, nomor_urut: i + 1 }));
};

const handleGridDragEnd = () => {
    if (pendingOrder.value && pendingOrder.value.length > 0) {
        router.post(
            '/candidates/reorder',
            { order: pendingOrder.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Toast akan muncul otomatis dari backend flash
                },
                onError: (errors) => {
                    alert('Gagal menyimpan urutan: ' + JSON.stringify(errors));
                },
            },
        );
    }
    draggedIndex.value = null;
    dragOverIndex.value = null;
    pendingOrder.value = null;
};

const formTambah = useForm({
    nomor_urut: '',
    nama_lengkap: '',
    kelas: '',
    nis: '',
    foto: null as File | null,
    visi: '',
    misi: '',
});

const formEdit = useForm({
    nomor_urut: '',
    nama_lengkap: '',
    kelas: '',
    nis: '',
    foto: null as File | null,
    visi: '',
    misi: '',
    _method: 'put',
});

const formHapus = useForm({});

// Tampilkan urutan dengan animasi saat drag
const displayOrder = computed(() => {
    if (pendingOrder.value) return pendingOrder.value;
    return null;
});

// Class untuk badge nomor urut saat drag
const nomorBadgeClass = (candidate: Candidate) => {
    if (!displayOrder.value) return '';
    const item = displayOrder.value.find((o) => o.id === candidate.id);
    if (!item) return '';
    const newIndex = displayOrder.value.indexOf(item);
    // Jika item ini sedang di-drag, highlight biru; jika item lain bergeser, highlight hijau
    return newIndex === draggedIndex.value
        ? 'bg-blue-500 text-white scale-110'
        : 'bg-green-100 text-green-700';
};

const startTambah = () => {
    formTambah.reset();
    showAddForm.value = true;
};

const cancelTambah = () => {
    showAddForm.value = false;
    formTambah.reset();
};

const startEdit = (candidate: Candidate) => {
    editingId.value = candidate.id;
    selectedCandidate.value = candidate;
    formEdit.reset();
    formEdit.nomor_urut = String(candidate.nomor_urut);
    formEdit.nama_lengkap = candidate.nama_lengkap;
    formEdit.kelas = candidate.kelas;
    formEdit.nis = candidate.nis;
    formEdit.visi = candidate.visi;
    formEdit.misi = candidate.misi;
    formEdit.foto = null;
};

const cancelEdit = () => {
    editingId.value = null;
    selectedCandidate.value = null;
    formEdit.reset();
};

const handleHapus = (candidate: Candidate) => {
    pendingDeleteCandidate.value = candidate;
    deleteOpen.value = true;
};

const confirmDelete = () => {
    const candidate = pendingDeleteCandidate.value;
    if (!candidate) return;
    deleteOpen.value = false;
    formHapus.delete(`/candidates/${candidate.id}`, {
        onSuccess: () => {
            pendingDeleteCandidate.value = null;
            cancelEdit();
        },
    });
};

const submitTambah = () => {
    formTambah.post('/candidates', {
        forceFormData: true,
        onSuccess: () => {
            showAddForm.value = false;
            formTambah.reset();
        },
    });
};

const submitEdit = () => {
    if (!selectedCandidate.value) return;
    formEdit.post(`/candidates/${selectedCandidate.value.id}`, {
        forceFormData: true,
        onSuccess: () => {
            cancelEdit();
        },
    });
};

const goTo = (url: string | null) => {
    if (!url) return;
    router.visit(url);
};

// Drag-and-drop handlers
</script>

<template>
    <Head title="Kandidat" />

    <div class="px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <Heading
                title="Kandidat"
                description="Kelola data calon kandidat"
            />
            <Button @click="startTambah">
                <Plus class="mr-2 h-4 w-4" />
                Tambah Kandidat
            </Button>
        </div>

        <!-- Form Tambah -->
        <div
            v-if="showAddForm"
            class="mb-6 space-y-4 rounded-lg border bg-card p-4"
        >
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Tambah Kandidat</h3>
                <Button variant="ghost" size="icon" @click="cancelTambah">
                    <X class="h-4 w-4" />
                </Button>
            </div>
            <form @submit.prevent="submitTambah" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="nomor_urut">No. Urut</Label>
                        <Input
                            id="nomor_urut"
                            type="number"
                            min="1"
                            v-model="formTambah.nomor_urut"
                            placeholder="1"
                            :disabled="formTambah.processing"
                        />
                        <InputError :message="formTambah.errors.nomor_urut" />
                    </div>
                    <div class="space-y-2">
                        <Label for="nis">NIS</Label>
                        <Input
                            id="nis"
                            v-model="formTambah.nis"
                            placeholder="123456"
                            :disabled="formTambah.processing"
                        />
                        <InputError :message="formTambah.errors.nis" />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="nama_lengkap">Nama Lengkap</Label>
                    <Input
                        id="nama_lengkap"
                        v-model="formTambah.nama_lengkap"
                        placeholder="Nama lengkap kandidat"
                        :disabled="formTambah.processing"
                    />
                    <InputError :message="formTambah.errors.nama_lengkap" />
                </div>
                <div class="space-y-2">
                    <Label for="kelas">Kelas</Label>
                    <Input
                        id="kelas"
                        v-model="formTambah.kelas"
                        placeholder="XII IPA 1"
                        :disabled="formTambah.processing"
                    />
                    <InputError :message="formTambah.errors.kelas" />
                </div>
                <div class="space-y-2">
                    <Label>Foto</Label>
                    <ImageUpload
                        v-model="formTambah.foto"
                        :error="formTambah.errors.foto"
                        :disabled="formTambah.processing"
                    />
                </div>
                <div class="space-y-2">
                    <Label>Visi</Label>
                    <RichTextEditor v-model="formTambah.visi" />
                    <InputError :message="formTambah.errors.visi" />
                </div>
                <div class="space-y-2">
                    <Label>Misi</Label>
                    <RichTextEditor v-model="formTambah.misi" />
                    <InputError :message="formTambah.errors.misi" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelTambah"
                        :disabled="formTambah.processing"
                    >
                        Batal
                    </Button>
                    <Button type="submit" :disabled="formTambah.processing">
                        <LoaderCircle
                            v-if="formTambah.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Simpan
                    </Button>
                </div>
            </form>
        </div>

        <!-- Form Edit -->
        <div
            v-if="editingId"
            class="mt-4 space-y-4 rounded-lg border bg-card p-4"
        >
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Edit Kandidat</h3>
                <Button variant="ghost" size="icon" @click="cancelEdit">
                    <X class="h-4 w-4" />
                </Button>
            </div>
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="edit_nomor_urut">No. Urut</Label>
                        <Input
                            id="edit_nomor_urut"
                            type="number"
                            min="1"
                            v-model="formEdit.nomor_urut"
                            :disabled="formEdit.processing"
                        />
                        <InputError :message="formEdit.errors.nomor_urut" />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_nis">NIS</Label>
                        <Input
                            id="edit_nis"
                            v-model="formEdit.nis"
                            :disabled="formEdit.processing"
                        />
                        <InputError :message="formEdit.errors.nis" />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="edit_nama_lengkap">Nama Lengkap</Label>
                    <Input
                        id="edit_nama_lengkap"
                        v-model="formEdit.nama_lengkap"
                        :disabled="formEdit.processing"
                    />
                    <InputError :message="formEdit.errors.nama_lengkap" />
                </div>
                <div class="space-y-2">
                    <Label for="edit_kelas">Kelas</Label>
                    <Input
                        id="edit_kelas"
                        v-model="formEdit.kelas"
                        :disabled="formEdit.processing"
                    />
                    <InputError :message="formEdit.errors.kelas" />
                </div>
                <div class="space-y-2">
                    <Label>Foto</Label>
                    <ImageUpload
                        v-model="formEdit.foto"
                        :existing-url="selectedCandidate?.foto_url ?? null"
                        :error="formEdit.errors.foto"
                        :disabled="formEdit.processing"
                    />
                </div>
                <div class="space-y-2">
                    <Label>Visi</Label>
                    <RichTextEditor v-model="formEdit.visi" />
                    <InputError :message="formEdit.errors.visi" />
                </div>
                <div class="space-y-2">
                    <Label>Misi</Label>
                    <RichTextEditor v-model="formEdit.misi" />
                    <InputError :message="formEdit.errors.misi" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelEdit"
                        :disabled="formEdit.processing"
                    >
                        Batal
                    </Button>
                    <Button type="submit" :disabled="formEdit.processing">
                        <LoaderCircle
                            v-if="formEdit.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Perbarui
                    </Button>
                </div>
            </form>
        </div>

        <!-- Grid 卡片 -->
        <div
            ref="gridRef"
            @dragover="handleGridDragOver"
            @dragend.prevent="handleGridDragEnd"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <template v-if="candidates.data.length === 0">
                <div
                    class="col-span-full py-12 text-center text-muted-foreground"
                >
                    Belum ada data kandidat
                </div>
            </template>
            <div
                v-for="(candidate, index) in candidates.data"
                :key="candidate.id"
                draggable="true"
                data-drag-card="true"
                @dragstart="handleDragStart(index, $event)"
                :class="[
                    'overflow-hidden rounded-xl border bg-card shadow-sm transition-all duration-200',
                    draggedIndex === index
                        ? 'scale-[0.97] opacity-50 ring-2 ring-blue-400'
                        : '',
                    dragOverIndex === index && draggedIndex !== null
                        ? 'bg-blue-50/50 ring-2 ring-blue-500'
                        : '',
                    'hover:shadow-md',
                ]"
            >
                <!-- Header: Foto + Info -->
                <div class="relative flex items-start gap-3 px-4 pt-4">
                    <div class="relative flex-shrink-0">
                        <Avatar class="h-20 w-20 rounded-lg">
                            <AvatarImage
                                v-if="candidate.foto_url"
                                :src="candidate.foto_url"
                            />
                            <AvatarFallback class="bg-muted text-lg font-bold">
                                {{
                                    candidate.nama_lengkap
                                        .charAt(0)
                                        .toUpperCase()
                                }}
                            </AvatarFallback>
                        </Avatar>
                        <span
                            class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white shadow-sm"
                        >
                            {{ candidate.nomor_urut }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1 pt-1">
                        <h3
                            class="text-base leading-tight font-semibold break-words"
                        >
                            {{ candidate.nama_lengkap }}
                        </h3>
                        <div
                            class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-sm text-muted-foreground"
                        >
                            <span>Kelas: {{ candidate.kelas }}</span>
                            <span>NIS: {{ candidate.nis }}</span>
                        </div>
                    </div>
                    <!-- Grip drag handle -->
                    <div
                        draggable="false"
                        class="flex flex-shrink-0 cursor-grab items-center justify-center text-muted-foreground/50 transition-colors hover:text-muted-foreground active:cursor-grabbing"
                        :title="
                            draggedIndex !== null
                                ? 'Lepas untuk menjatuhkan'
                                : 'Seret untuk mengubah urutan'
                        "
                    >
                        <GripVertical
                            class="h-5 w-5"
                            :class="{
                                'scale-110 text-blue-500':
                                    draggedIndex === index,
                            }"
                        />
                    </div>
                </div>

                <!-- Content: Toggle Visi Misi -->
                <CardContent class="px-4 pb-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 w-full justify-between text-sm"
                        @click="toggleVisiMisi(candidate.id)"
                    >
                        <span class="flex items-center gap-1">
                            <Eye
                                v-if="expandedCandidateId !== candidate.id"
                                class="h-3.5 w-3.5"
                            />
                            <EyeOff v-else class="h-3.5 w-3.5" />
                            {{
                                expandedCandidateId === candidate.id
                                    ? 'Tutup Visi & Misi'
                                    : 'Baca Visi & Misi'
                            }}
                        </span>
                    </Button>

                    <div
                        v-if="expandedCandidateId === candidate.id"
                        class="mt-4 mb-1 space-y-3 text-sm"
                    >
                        <div v-if="candidate.visi" class="space-y-1">
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Visi
                            </p>
                            <div
                                v-html="candidate.visi"
                                class="rich-content text-card-foreground"
                            />
                        </div>
                        <div v-if="candidate.misi" class="space-y-1">
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Misi
                            </p>
                            <div
                                v-html="candidate.misi"
                                class="rich-content text-card-foreground"
                            />
                        </div>
                    </div>
                </CardContent>

                <!-- Footer Actions -->
                <div
                    class="flex items-center justify-between border-t px-4 pt-3 pb-4"
                >
                    <div class="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="startEdit(candidate)"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="handleHapus(candidate)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="candidates.links.length > 3"
            class="mt-4 flex justify-center"
        >
            <nav class="flex items-center gap-1">
                <Button
                    v-for="link in candidates.links"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="goTo(link.url)"
                    class="min-w-8"
                >
                    <span v-html="link.label" />
                </Button>
            </nav>
        </div>
    </div>

    <DeleteConfirmDialog
        v-if="pendingDeleteCandidate"
        v-model:open="deleteOpen"
        :title="`Hapus ${pendingDeleteCandidate.nama_lengkap}?`"
        description="Data kandidat yang dihapus tidak dapat dikembalikan kembali."
        @confirm="confirmDelete"
    />
</template>

<style scoped>
/* Smooth card drag transition */
div[data-drag-card] {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease,
        box-shadow 0.2s ease,
        ring-color 0.2s ease;
}
div[data-drag-card]:active {
    cursor: grabbing;
}
</style>
