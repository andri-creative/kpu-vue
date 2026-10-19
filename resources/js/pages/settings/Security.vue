<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';
import { update } from '@/routes/user-kode';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Keamanan',
                href: edit(),
            },
        ],
    },
});

const form = useForm({
    kode_lama: '',
    kode_baru: '',
    kode_baru_confirmation: '',
});

function submit() {
    form.patch(update.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Keamanan" />

    <h1 class="sr-only">Keamanan</h1>

    <div class="max-w-xl space-y-6">
        <Heading
            variant="small"
            title="Ganti Kode Login"
            description="Ganti kode 6 angka untuk masuk ke akun Anda"
        />

        <form class="space-y-4 rounded-lg border p-4" @submit.prevent="submit">
            <p class="text-sm text-muted-foreground">
                Masukkan kode lama Anda, lalu buat kode baru sebanyak 6 angka.
                Kode lama langsung tidak berlaku setelah kode baru disimpan.
            </p>

            <div class="grid gap-2">
                <Label for="kode_lama">Kode lama</Label>
                <Input
                    id="kode_lama"
                    v-model="form.kode_lama"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="000000"
                    autocomplete="current-password"
                    :disabled="form.processing"
                />
                <InputError :message="form.errors.kode_lama" />
            </div>

            <div class="grid gap-2">
                <Label for="kode_baru">Kode baru</Label>
                <Input
                    id="kode_baru"
                    v-model="form.kode_baru"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="6 angka baru"
                    autocomplete="new-password"
                    :disabled="form.processing"
                />
                <InputError :message="form.errors.kode_baru" />
            </div>

            <div class="grid gap-2">
                <Label for="kode_baru_confirmation">Konfirmasi kode baru</Label>
                <Input
                    id="kode_baru_confirmation"
                    v-model="form.kode_baru_confirmation"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="Ulangi kode baru"
                    autocomplete="new-password"
                    :disabled="form.processing"
                />
                <InputError :message="form.errors.kode_baru_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Kode Baru' }}
                </Button>
            </div>
        </form>
    </div>
</template>