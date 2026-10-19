<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Heading2,
    Heading3,
    Italic,
    List,
    ListOrdered,
    Redo2,
    Strikethrough,
    Undo2,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

interface Props {
    modelValue?: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Tulis visi/misi di sini...',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const lineNumbersRef = ref<HTMLElement | null>(null);
const editorContentRef = ref<HTMLElement | null>(null);

const lineNumbers = computed(() => {
    if (!props.modelValue) return 1;
    // Hitung jumlah block node (p, h2, h3, li) dari HTML
    const parser = new DOMParser();
    const doc = parser.parseFromString(props.modelValue, 'text/html');
    const blocks = doc.body.querySelectorAll('p, h2, h3, h4, h5, h6, li');
    return Math.max(blocks.length, 1);
});

function syncScroll() {
    if (!lineNumbersRef.value || !editorContentRef.value) return;
    lineNumbersRef.value.scrollTop = editorContentRef.value.scrollTop;
}

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [2, 3],
            },
        }),
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
    editorProps: {
        attributes: {
            class: 'focus:outline-none prose-sm max-w-none min-h-32 p-3 text-sm',
        },
    },
});

watch(
    () => props.modelValue,
    (newValue) => {
        if (editor.value && newValue !== editor.value.getHTML()) {
            editor.value.commands.setContent(newValue);
        }
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div v-if="editor" class="overflow-hidden rounded-md border">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-1 border-b bg-muted/50 p-1">
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'bg-accent': editor.isActive('bold') }"
                title="Tebal (B)"
            >
                <Bold class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="{ 'bg-accent': editor.isActive('italic') }"
                title="Miring (I)"
            >
                <Italic class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                @click="editor.chain().focus().toggleStrike().run()"
                :class="{ 'bg-accent': editor.isActive('strike') }"
                title="Coret"
            >
                <Strikethrough class="h-4 w-4" />
            </Button>
            <div class="mx-1 my-0.5 w-px bg-border" />
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                @click="
                    editor.chain().focus().toggleHeading({ level: 2 }).run()
                "
                :class="{
                    'bg-accent': editor.isActive('heading', { level: 2 }),
                }"
                title="Heading 2"
            >
                <Heading2 class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                @click="
                    editor.chain().focus().toggleHeading({ level: 3 }).run()
                "
                :class="{
                    'bg-accent': editor.isActive('heading', { level: 3 }),
                }"
                title="Heading 3"
            >
                <Heading3 class="h-4 w-4" />
            </Button>
            <div class="mx-1 my-0.5 w-px bg-border" />
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'bg-accent': editor.isActive('bulletList') }"
                title="Daftar titik"
            >
                <List class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{ 'bg-accent': editor.isActive('orderedList') }"
                title="Daftar bernomor (1, 2, 3...)"
            >
                <ListOrdered class="h-4 w-4" />
            </Button>
            <div class="ml-auto flex items-center gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    @click="editor.chain().focus().undo().run()"
                    title="Undo"
                >
                    <Undo2 class="h-4 w-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    @click="editor.chain().focus().redo().run()"
                    title="Redo"
                >
                    <Redo2 class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Editor + Line Numbers -->
        <div class="relative flex">
            <!-- Line numbers gutter -->
            <div
                ref="lineNumbersRef"
                class="line-gutter flex-shrink-0 bg-muted/20 pt-3 pr-2 text-right text-xs text-muted-foreground/50 select-none"
                aria-hidden="true"
            >
                <div v-for="n in lineNumbers" :key="n" class="leading-relaxed">
                    {{ n }}
                </div>
            </div>

            <!-- Editor content -->
            <div ref="editorContentRef" class="flex-1 overflow-hidden">
                <EditorContent
                    :editor="editor"
                    class="h-full"
                    @scroll="syncScroll"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-gutter {
    min-width: 2.5rem;
    padding-right: 0.75rem;
}

.line-gutter div {
    line-height: 1.75;
}

:deep(.ProseMirror) {
    outline: none;
    min-height: 8rem;
    padding: 0.75rem !important;
}

:deep(.ProseMirror h2) {
    font-size: 1.25rem;
    font-weight: 600;
    line-height: 1.75rem;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

:deep(.ProseMirror h3) {
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.75rem;
    margin-top: 0.75rem;
    margin-bottom: 0.5rem;
}

:deep(.ProseMirror p) {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
    line-height: 1.75;
}

:deep(.ProseMirror ul) {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

:deep(.ProseMirror ol) {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

:deep(.ProseMirror li > p) {
    margin-top: 0;
    margin-bottom: 0;
}

:deep(.ProseMirror strong) {
    font-weight: 600;
}

:deep(.ProseMirror em) {
    font-style: italic;
}

:deep(.ProseMirror s) {
    text-decoration: line-through;
}
</style>
