<script setup lang="ts">
import { marked } from 'marked';
import { computed } from 'vue';

const props = defineProps<{
    content: string;
}>();

const html = computed<string>(() =>
    marked.parse(props.content, { async: false }),
);
</script>

<template>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div class="doc-prose" v-html="html" />
</template>

<style scoped>
.doc-prose {
    color: var(--foreground);
    font-size: 0.875rem;
    line-height: 1.7;
}

.doc-prose :deep(h1),
.doc-prose :deep(h2),
.doc-prose :deep(h3),
.doc-prose :deep(h4) {
    font-weight: 600;
    letter-spacing: -0.01em;
    scroll-margin-top: 5rem;
}

.doc-prose :deep(h1) {
    margin: 0 0 1rem;
    font-size: 1.5rem;
}

.doc-prose :deep(h2) {
    margin: 2rem 0 0.75rem;
    padding-top: 1rem;
    font-size: 1.25rem;
    border-top: 1px solid var(--border);
}

.doc-prose :deep(h3) {
    margin: 1.5rem 0 0.5rem;
    font-size: 1.05rem;
}

.doc-prose :deep(h4) {
    margin: 1.25rem 0 0.5rem;
    font-size: 0.95rem;
}

.doc-prose :deep(p) {
    margin: 0.75rem 0;
}

.doc-prose :deep(ul),
.doc-prose :deep(ol) {
    margin: 0.75rem 0;
    padding-left: 1.5rem;
}

.doc-prose :deep(ul) {
    list-style: disc;
}

.doc-prose :deep(ol) {
    list-style: decimal;
}

.doc-prose :deep(li) {
    margin: 0.25rem 0;
}

.doc-prose :deep(a) {
    color: var(--primary);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.doc-prose :deep(strong) {
    font-weight: 600;
}

.doc-prose :deep(blockquote) {
    margin: 1rem 0;
    padding: 0.5rem 0 0.5rem 1rem;
    color: var(--muted-foreground);
    border-left: 3px solid var(--border);
}

.doc-prose :deep(code) {
    padding: 0.15rem 0.35rem;
    font-family: var(--font-mono, ui-monospace, monospace);
    font-size: 0.85em;
    background-color: var(--muted);
    border-radius: calc(var(--radius) - 2px);
}

.doc-prose :deep(pre) {
    margin: 1rem 0;
    padding: 0.85rem 1rem;
    overflow-x: auto;
    background-color: var(--muted);
    border: 1px solid var(--border);
    border-radius: var(--radius);
}

.doc-prose :deep(pre code) {
    padding: 0;
    background: transparent;
}

.doc-prose :deep(table) {
    width: 100%;
    margin: 1rem 0;
    font-size: 0.8125rem;
    border-collapse: collapse;
}

.doc-prose :deep(th),
.doc-prose :deep(td) {
    padding: 0.5rem 0.75rem;
    text-align: left;
    border: 1px solid var(--border);
}

.doc-prose :deep(th) {
    font-weight: 600;
    background-color: var(--muted);
}

.doc-prose :deep(hr) {
    margin: 1.5rem 0;
    border-color: var(--border);
}

.doc-prose :deep(img) {
    max-width: 100%;
    border-radius: var(--radius);
}
</style>