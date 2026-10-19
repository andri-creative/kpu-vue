<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

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
            {
                title: 'Tambah User',
                href: '/users/create',
            },
        ],
    },
});

const form = useForm({
    nis: '',
    nama_lengkap: '',
    kelas: '',
});

function submit() {
    form.post('/users');
}
</script>

<template>
    <Head title="Tambah User" />

    <div class="flex flex-col space-y-6 p-4 max-w-lg">
        <Heading
            variant="small"
            title="Tambah User"
            description="Buat akun pemilih baru"
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="nis">NIS</Label>
                <Input
                    id="nis"
                    v-model="form.nis"
                    type="text"
                    required
                    placeholder="Nomor Induk Siswa"
                />
                <InputError :message="form.errors.nis" />
            </div>

            <div class="grid gap-2">
                <Label for="nama_lengkap">Nama lengkap</Label>
                <Input
                    id="nama_lengkap"
                    v-model="form.nama_lengkap"
                    type="text"
                    required
                    placeholder="Nama lengkap siswa"
                />
                <InputError :message="form.errors.nama_lengkap" />
            </div>

            <div class="grid gap-2">
                <Label for="kelas">Kelas</Label>
                <Input
                    id="kelas"
                    v-model="form.kelas"
                    type="text"
                    required
                    placeholder="Contoh: X IPA 1"
                />
                <InputError :message="form.errors.kelas" />
            </div>

            <p class="rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                Role otomatis sebagai pemilih dan kode login 6 angka dibuat otomatis
                oleh sistem. Kode hanya ditampilkan satu kali setelah user disimpan.
            </p>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing">Simpan</Button>
                <Link href="/users">
                    <Button type="button" variant="outline">Batal</Button>
                </Link>
            </div>
        </form>
    </div>
</template>