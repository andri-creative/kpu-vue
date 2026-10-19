<script setup lang="ts">
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { compressToWebp } from '@/lib/compressImage';
import { useObjectUrl } from '@vueuse/core';
import { LoaderCircle, RotateCcw, Trash2, Upload } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

interface Props {
    modelValue?: File | null;
    existingUrl?: string | null;
    error?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    existingUrl: null,
    error: undefined,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: File | null): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const isCompressing = ref(false);
const originalName = ref<string | null>(null);
const originalSizeKb = ref<number | null>(null);

// Pratinjau seketika: object URL dibuat dari file asli sebelum kompresi selesai.
const previewSource = ref<File | null>(props.modelValue);
const objectUrl = useObjectUrl(previewSource);

// Foto yang ditampilkan: file baru kalau ada, kalau tidak foto lama saat edit.
const previewUrl = computed(() => objectUrl.value ?? props.existingUrl ?? null);
const hasPreview = computed(() => previewUrl.value !== null);
const isUsingExisting = computed(() => !objectUrl.value && props.existingUrl !== null);

watch(
    () => props.modelValue,
    (file) => {
        previewSource.value = file;
    },
);

const formatKb = (bytes: number) => Math.round(bytes / 1024);

const pickFile = () => {
    if (props.disabled || isCompressing.value) return;
    inputRef.value?.click();
};

const onFileChange = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    // Reset agar memilih file yang sama dua kali tetap memicu perubahan.
    target.value = '';

    if (!file) return;

    originalName.value = file.name;
    originalSizeKb.value = formatKb(file.size);

    // Tampilkan gambar yang dipilih seketika, tanpa menunggu kompresi selesai.
    previewSource.value = file;
    isCompressing.value = true;

    try {
        emit('update:modelValue', await compressToWebp(file));
    } catch (error) {
        console.error(error);
        previewSource.value = null;
        originalName.value = null;
        originalSizeKb.value = null;
    } finally {
        isCompressing.value = false;
    }
};

const clearFile = () => {
    previewSource.value = null;
    originalName.value = null;
    originalSizeKb.value = null;
    emit('update:modelValue', null);
};

// Saat foto tersimpan masih ada, buang pilihan baru dan kembali ke foto lama.
// Kalau tidak ada, tombol ini hanya menghapus pilihan baru.
const clearLabel = computed(() => (props.existingUrl ? 'Kembali ke foto lama' : 'Hapus'));
</script>

<template>
    <div class="space-y-3">
        <input
            ref="inputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onFileChange"
        />

        <Button
            type="button"
            variant="outline"
            :disabled="disabled || isCompressing"
            @click="pickFile"
        >
            <Upload class="size-4" />
            Pilih Foto
        </Button>

        <div
            v-if="hasPreview"
            class="flex flex-col gap-4 rounded-md border p-4 sm:flex-row sm:items-start"
        >
            <div class="relative shrink-0">
                <img
                    :src="previewUrl ?? undefined"
                    alt="Pratinjau foto kandidat"
                    class="h-40 w-40 rounded-md border object-cover"
                />
                <div
                    v-if="isCompressing"
                    class="absolute inset-0 flex items-center justify-center rounded-md bg-background/70"
                >
                    <LoaderCircle class="size-6 animate-spin text-muted-foreground" />
                </div>
            </div>

            <div class="min-w-0 flex-1 space-y-3">
                <div class="space-y-0.5 text-sm">
                    <p
                        v-if="originalName"
                        class="truncate font-medium"
                        :title="originalName"
                    >
                        {{ originalName }}
                    </p>
                    <p
                        v-if="isCompressing"
                        class="text-xs text-muted-foreground"
                    >
                        Mengompres foto...
                    </p>
                    <p
                        v-else-if="originalSizeKb !== null"
                        class="text-xs text-muted-foreground"
                    >
                        Ukuran asli {{ originalSizeKb }} KB
                        <template v-if="modelValue">
                            → {{ formatKb(modelValue.size) }} KB setelah kompresi
                        </template>
                    </p>
                    <p
                        v-if="isUsingExisting"
                        class="text-xs text-muted-foreground"
                    >
                        Foto tersimpan saat ini. Pilih foto baru untuk menggantinya.
                    </p>
                </div>

                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    :disabled="disabled || isCompressing"
                    @click="clearFile"
                >
                    <RotateCcw v-if="isUsingExisting" class="size-4" />
                    <Trash2 v-else class="size-4" />
                    {{ clearLabel }}
                </Button>
            </div>
        </div>

        <p class="text-muted-foreground text-xs">
            Format JPG atau PNG, maksimal 1 MB. Foto dikompres ke WebP saat
            disimpan.
        </p>

        <InputError :message="error" />
    </div>
</template>