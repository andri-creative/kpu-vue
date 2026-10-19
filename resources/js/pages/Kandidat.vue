<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle, ShieldCheck } from '@lucide/vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import kandidat from '@/routes/kandidat';
import type { Candidate } from '@/types';

const props = defineProps<{
    candidate: Candidate;
}>();

const form = useForm({
    visi: props.candidate.visi,
    misi: props.candidate.misi,
});

const submit = () => {
    form.patch(kandidat.update.url());
};
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <h1 class="text-3xl font-bold tracking-tight">
                Profil & Visi Misi Kandidat
            </h1>
            <p class="mt-2 text-muted-foreground">
                Perbarui visi dan misi Anda. Data ini tampil ke pemilih saat
                voting berlangsung.
            </p>
        </div>

        <Card class="mx-auto w-full max-w-2xl">
            <CardHeader>
                <CardTitle>Informasi Kandidat</CardTitle>
                <CardDescription>
                    Data berikut diisi oleh admin dan tidak dapat diubah sendiri
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-muted-foreground">NIS</div>
                    <div class="col-span-2">
                        {{ candidate.nis }}
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-muted-foreground">
                        Nama Lengkap
                    </div>
                    <div class="col-span-2">
                        {{ candidate.nama_lengkap }}
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-muted-foreground">Kelas</div>
                    <div class="col-span-2">
                        {{ candidate.kelas }}
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-muted-foreground">
                        Nomor Urut
                    </div>
                    <div class="col-span-2">
                        {{ candidate.nomor_urut }}
                    </div>
                </div>

                <div v-if="candidate.foto_url" class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-muted-foreground">Foto</div>
                    <div class="col-span-2">
                        <img
                            :src="candidate.foto_url"
                            :alt="candidate.nama_lengkap"
                            class="h-32 w-32 rounded-md border object-cover"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card class="mx-auto w-full max-w-2xl">
            <CardHeader>
                <CardTitle>Visi & Misi</CardTitle>
                <CardDescription>
                    Bagian ini bisa Anda ubah sendiri
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label>Visi</Label>
                        <RichTextEditor
                            v-model="form.visi"
                            placeholder="Tulis visi Anda di sini..."
                        />
                        <InputError :message="form.errors.visi" />
                    </div>

                    <div class="space-y-2">
                        <Label>Misi</Label>
                        <RichTextEditor
                            v-model="form.misi"
                            placeholder="Tulis misi Anda di sini..."
                        />
                        <InputError :message="form.errors.misi" />
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <p
                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <ShieldCheck class="size-3.5" />
                            Perubahan langsung tersimpan dan tampil ke pemilih.
                        </p>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle
                                v-if="form.processing"
                                class="mr-2 size-4 animate-spin"
                            />
                            Simpan
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
