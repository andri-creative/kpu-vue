<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BookOpen, Check, Target, Users } from '@lucide/vue';
import PemilihLayout from '@/layouts/PemilihLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import type { Kandidat } from '@/types';

defineOptions({
    layout: PemilihLayout,
});

type Props = {
    kandidat: Kandidat[];
};

const props = defineProps<Props>();

// Dialog konfirmasi voting
const dialogOpen = ref(false);
const selectedKandidat = ref<Kandidat | null>(null);
const isSubmitting = ref(false);

const openVoteDialog = (kandidat: Kandidat) => {
    selectedKandidat.value = kandidat;
    dialogOpen.value = true;
};

const handleDialogChange = (open: boolean) => {
    dialogOpen.value = open;

    if (!open) {
        selectedKandidat.value = null;
    }
};

const isSelected = (kandidat: Kandidat) =>
    selectedKandidat.value?.id === kandidat.id;

const confirmVote = () => {
    if (!selectedKandidat.value || isSubmitting.value) return;

    isSubmitting.value = true;
    router.post(
        '/pemilih/vote',
        { candidate_id: selectedKandidat.value.id },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Server mengarahkan ke halaman terima kasih (PemilihDone).
                dialogOpen.value = false;
                selectedKandidat.value = null;
            },
            onError: () => {
                isSubmitting.value = false;
                dialogOpen.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

const warnaBadge = (index: number) => {
    const palette = [
        'from-blue-500 to-indigo-600',
        'from-violet-500 to-purple-600',
        'from-rose-500 to-pink-600',
        'from-amber-500 to-orange-600',
        'from-teal-500 to-emerald-600',
    ];
    return palette[index % palette.length];
};
</script>

<template>
    <Head title="Halaman Pemilih" />

    <div class="space-y-8">
        <!-- Header Daftar Kandidat -->
        <div class="flex items-center gap-3">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30"
            >
                <Users class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                    Daftar Calon
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ props.kandidat.length }} kandidat terdaftar &mdash; pilih
                    satu
                </p>
            </div>
        </div>

        <!-- Grid Card Kandidat -->
        <div
            v-if="props.kandidat.length > 0"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
        >
            <Card
                v-for="(item, index) in props.kandidat"
                :key="item.id"
                :class="[
                    'group relative flex h-full flex-col overflow-hidden border shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg',
                    isSelected(item)
                        ? 'border-blue-400 bg-blue-50/70 ring-2 ring-blue-500 dark:border-blue-600 dark:bg-blue-950/40'
                        : 'border-slate-200/80 dark:border-slate-700/80',
                ]"
            >
                <div
                    :class="`absolute top-0 left-0 h-1 w-full bg-gradient-to-r ${warnaBadge(index)}`"
                ></div>

                <div
                    v-if="isSelected(item)"
                    class="absolute top-3 right-3 z-10 flex items-center gap-1 rounded-full bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white shadow-md"
                >
                    <Check class="h-3.5 w-3.5" />
                    Dipilih
                </div>

                <CardHeader class="pt-6 pb-4">
                    <div class="flex items-start gap-4">
                        <div class="relative shrink-0">
                            <div
                                v-if="item.foto_url"
                                class="h-16 w-16 overflow-hidden rounded-2xl shadow-md"
                            >
                                <img
                                    :src="item.foto_url"
                                    :alt="item.nama_lengkap"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div
                                v-else
                                :class="`flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-md ${warnaBadge(index)}`"
                            >
                                <span class="text-2xl font-black">
                                    {{
                                        String(item.nomor_urut).padStart(2, '0')
                                    }}
                                </span>
                            </div>
                            <div
                                class="absolute -right-1 -bottom-1 flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-slate-700 shadow dark:bg-slate-800 dark:text-slate-200"
                            >
                                #{{ item.nomor_urut }}
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3
                                class="truncate text-base font-bold text-slate-800 dark:text-white"
                            >
                                {{ item.nama_lengkap }}
                            </h3>
                            <div
                                class="mt-1 flex flex-wrap items-center gap-1.5"
                            >
                                <Badge
                                    class="border-transparent bg-blue-100 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                                >
                                    Kelas {{ item.kelas }}
                                </Badge>
                                <Badge
                                    class="border-transparent bg-amber-100 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                                >
                                    NIS {{ item.nis }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="flex flex-1 flex-col space-y-4 pb-6">
                    <Separator class="bg-slate-100 dark:bg-slate-700/50" />

                    <div class="space-y-1.5">
                        <div
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-blue-600 uppercase dark:text-blue-400"
                        >
                            <Target class="h-3.5 w-3.5" />
                            <span>Visi</span>
                        </div>
                        <div
                            class="rich-content text-sm leading-relaxed text-slate-600 dark:text-slate-300"
                            v-html="item.visi"
                        ></div>
                    </div>

                    <div class="space-y-1.5">
                        <div
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-indigo-600 uppercase dark:text-indigo-400"
                        >
                            <BookOpen class="h-3.5 w-3.5" />
                            <span>Misi</span>
                        </div>
                        <div
                            class="rich-content text-sm leading-relaxed text-slate-600 dark:text-slate-300"
                            v-html="item.misi"
                        ></div>
                    </div>

                    <Separator class="bg-slate-100 dark:bg-slate-700/50" />

                    <Button
                        class="mt-auto w-full"
                        size="lg"
                        @click="openVoteDialog(item)"
                    >
                        Pilih Kandidat Ini
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- State kosong -->
        <div
            v-else
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 py-20 text-center dark:border-slate-700"
        >
            <Users class="mb-4 h-12 w-12 text-slate-300 dark:text-slate-600" />
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                Belum ada kandidat terdaftar
            </p>
        </div>
    </div>

    <!-- Dialog Konfirmasi Voting -->
    <Dialog :open="dialogOpen" @update:open="handleDialogChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Konfirmasi Pilihan</DialogTitle>
                <DialogDescription>
                    Apakah Anda yakin ingin memilih kandidat berikut?
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="selectedKandidat"
                class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50"
            >
                <div class="flex items-center gap-3">
                    <div
                        :class="`flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-md ${warnaBadge(selectedKandidat.nomor_urut - 1)}`"
                    >
                        <span class="text-lg font-black">
                            {{
                                String(selectedKandidat.nomor_urut).padStart(
                                    2,
                                    '0',
                                )
                            }}
                        </span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800 dark:text-white">
                            {{ selectedKandidat.nama_lengkap }}
                        </p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <Badge
                                class="border-transparent bg-blue-100 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                            >
                                Kelas {{ selectedKandidat.kelas }}
                            </Badge>
                            <Badge
                                class="border-transparent bg-amber-100 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                            >
                                NIS {{ selectedKandidat.nis }}
                            </Badge>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-sm text-amber-600 dark:text-amber-400">
                Pilihan ini tidak dapat diubah setelah dikonfirmasi.
            </p>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    variant="outline"
                    @click="dialogOpen = false"
                    :disabled="isSubmitting"
                >
                    Batal
                </Button>
                <Button @click="confirmVote" :disabled="isSubmitting">
                    {{ isSubmitting ? 'Memproses...' : 'Ya, Pilih' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
