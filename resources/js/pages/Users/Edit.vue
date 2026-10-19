<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import type { RoleEnum, UserData } from '@/types';

const props = defineProps<{
    user: UserData;
    assignableRoles: RoleEnum[];
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
            {
                title: 'Edit User',
            },
        ],
    },
});

const form = useForm({
    nis: props.user.nis,
    role: props.user.role,
});

function submit() {
    form.put(`/users/${props.user.id}`);
}
</script>

<template>
    <Head title="Edit User" />

    <div class="flex flex-col space-y-6 p-4 max-w-lg">
        <Heading
            variant="small"
            title="Edit User"
            description="Perbarui data login user"
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

            <p class="rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                Kode login 6 angka tidak dapat diubah dari halaman ini. Gunakan tombol
                Reset Kode di halaman Manajemen User untuk membuat kode baru.
            </p>

            <div class="grid gap-2">
                <Label for="role">Role</Label>
                <Select v-model="form.role">
                    <SelectTrigger>
                        <SelectValue placeholder="Pilih role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="role in assignableRoles"
                            :key="role.value"
                            :value="role.value"
                        >
                            {{ role.label || role.value }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.role" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing">Simpan</Button>
                <Link href="/users">
                    <Button type="button" variant="outline">Batal</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
