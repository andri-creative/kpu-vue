<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    open: boolean;
    title?: string;
    description?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Konfirmasi Hapus',
    description:
        'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
}>();

function close() {
    emit('update:open', false);
}

function confirm() {
    emit('confirm');
    close();
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    <slot>{{ description }}</slot>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-3">
                <Button variant="secondary" @click="close"> Batal </Button>
                <Button variant="destructive" @click="confirm"> Hapus </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
