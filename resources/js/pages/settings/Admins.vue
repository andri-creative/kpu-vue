<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import type { AdminRow, AdminsPagination } from '@/types';

const props = defineProps<{
    admins: AdminsPagination;
    currentUserId: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: editProfile(),
            },
            {
                title: 'Kelola Admin',
                href: '/settings/admins',
            },
        ],
    },
});

const form = useForm({
    nis: '',
    nama_lengkap: '',
});

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const editingId = ref<string | null>(null);
const deletingAdmin = ref<AdminRow | null>(null);

function openCreate() {
    form.reset();
    form.clearErrors();
    isCreateOpen.value = true;
}

function submitCreate() {
    form.post('/settings/admins', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateOpen.value = false;
            form.reset();
        },
    });
}

function openEdit(admin: AdminRow) {
    form.reset();
    form.clearErrors();
    editingId.value = admin.id;
    form.nis = admin.nis;
    form.nama_lengkap = admin.profile?.nama_lengkap ?? '';
    isEditOpen.value = true;
}

function submitEdit() {
    if (!editingId.value) {
        return;
    }

    form.put(`/settings/admins/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditOpen.value = false;
            form.reset();
        },
    });
}

function openDelete(admin: AdminRow) {
    deletingAdmin.value = admin;
    isDeleteOpen.value = true;
}

function submitDelete() {
    if (!deletingAdmin.value) {
        return;
    }

    router.delete(`/settings/admins/${deletingAdmin.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteOpen.value = false;
            deletingAdmin.value = null;
        },
    });
}

function isSelf(admin: AdminRow): boolean {
    return admin.id === props.currentUserId;
}
</script>

<template>
    <Head title="Kelola Admin" />

    <h1 class="sr-only">Kelola Admin</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Kelola Admin"
                description="Daftar akun admin dan pengelolaannya"
            />
            <Button @click="openCreate">Tambah Admin</Button>
        </div>

        <div
            class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>NIS</TableHead>
                        <TableHead>Nama lengkap</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="admins.data.length === 0">
                        <TableCell
                            colspan="5"
                            class="text-center text-muted-foreground"
                        >
                            Belum ada data admin.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="admin in admins.data" :key="admin.id">
                        <TableCell>{{ admin.nis }}</TableCell>
                        <TableCell>
                            {{ admin.profile?.nama_lengkap ?? '-' }}
                        </TableCell>
                        <TableCell>
                            <Badge variant="outline">{{ admin.status }}</Badge>
                        </TableCell>
                        <TableCell>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="openEdit(admin)"
                                >
                                    Edit
                                </Button>
                                <Button
                                    v-if="!isSelf(admin)"
                                    variant="destructive"
                                    size="sm"
                                    @click="openDelete(admin)"
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
            v-if="admins.links.length > 3"
            class="flex items-center justify-between px-2"
        >
            <p class="text-sm text-muted-foreground">
                Menampilkan {{ admins.from }} sampai {{ admins.to }} dari
                {{ admins.total }} data
            </p>
            <div class="flex gap-1">
                <template v-for="(link, index) in admins.links" :key="index">
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

    <!-- Dialog Tambah Admin -->
    <Dialog v-model:open="isCreateOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Admin</DialogTitle>
                <DialogDescription> Buat akun admin baru. </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitCreate">
                <div class="grid gap-2">
                    <Label for="create-nis">NIS</Label>
                    <Input
                        id="create-nis"
                        v-model="form.nis"
                        type="text"
                        required
                        placeholder="Nomor Induk Siswa"
                    />
                    <InputError :message="form.errors.nis" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-nama">Nama lengkap</Label>
                    <Input
                        id="create-nama"
                        v-model="form.nama_lengkap"
                        type="text"
                        required
                        placeholder="Nama lengkap"
                    />
                    <InputError :message="form.errors.nama_lengkap" />
                </div>

                <p
                    class="rounded-md border border-dashed p-3 text-sm text-muted-foreground"
                >
                    Kode login 6 angka dibuat otomatis oleh sistem dan hanya
                    ditampilkan satu kali setelah admin disimpan.
                </p>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Batal
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        Simpan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Dialog Edit Admin -->
    <Dialog v-model:open="isEditOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Admin</DialogTitle>
                <DialogDescription> Perbarui data admin. </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitEdit">
                <div class="grid gap-2">
                    <Label for="edit-nis">NIS</Label>
                    <Input
                        id="edit-nis"
                        v-model="form.nis"
                        type="text"
                        required
                        placeholder="Nomor Induk Siswa"
                    />
                    <InputError :message="form.errors.nis" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-nama">Nama lengkap</Label>
                    <Input
                        id="edit-nama"
                        v-model="form.nama_lengkap"
                        type="text"
                        required
                        placeholder="Nama lengkap"
                    />
                    <InputError :message="form.errors.nama_lengkap" />
                </div>

                <p
                    class="rounded-md border border-dashed p-3 text-sm text-muted-foreground"
                >
                    Kode login tidak dapat diubah dari sini. Gunakan form "Ganti
                    Kode Login" pada halaman Keamanan.
                </p>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Batal
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        Simpan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Dialog Hapus Admin -->
    <DeleteConfirmDialog
        v-model:open="isDeleteOpen"
        title="Hapus Admin"
        @confirm="submitDelete"
    >
        Apakah Anda yakin ingin menghapus admin
        <span class="font-medium">
            {{ deletingAdmin?.profile?.nama_lengkap ?? deletingAdmin?.nis }}
        </span>
        ? Tindakan ini tidak dapat dibatalkan.
    </DeleteConfirmDialog>
</template>
