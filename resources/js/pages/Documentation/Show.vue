<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DocumentationMarkdown from '@/components/DocumentationMarkdown.vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import DocumentationLayout from '@/layouts/DocumentationLayout.vue';
import documentation from '@/routes/documentation';
import type { DocumentationProps } from '@/types';

defineOptions({ layout: DocumentationLayout });

const props = defineProps<DocumentationProps>();

const flatPages = computed(() => props.sidebar.flatMap((group) => group.items));

const activeIndex = computed(() =>
    flatPages.value.findIndex((item) => item.slug === props.activeSlug),
);

const previousPage = computed(() =>
    activeIndex.value > 0 ? flatPages.value[activeIndex.value - 1] : null,
);

const nextPage = computed(() =>
    activeIndex.value >= 0 && activeIndex.value < flatPages.value.length - 1
        ? flatPages.value[activeIndex.value + 1]
        : null,
);

const pageUrl = (slug: string) => documentation.show.url({ slug });
</script>

<template>
    <Head :title="page?.title ?? 'Dokumentasi'" />

    <div class="mx-auto w-full max-w-4xl">
        <Breadcrumb class="mb-4">
            <BreadcrumbList>
                <BreadcrumbItem>
                    <BreadcrumbLink as-child>
                        <Link :href="documentation.index()"> Dokumentasi </Link>
                    </BreadcrumbLink>
                </BreadcrumbItem>
                <template v-if="page">
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>
                            {{ page.title }}
                        </BreadcrumbPage>
                    </BreadcrumbItem>
                </template>
            </BreadcrumbList>
        </Breadcrumb>

        <Card>
            <CardHeader>
                <CardTitle class="text-2xl">
                    {{ page?.title ?? 'Dokumentasi' }}
                </CardTitle>
                <CardDescription>
                    Halaman ini terbuka untuk umum dan tidak memerlukan login.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <DocumentationMarkdown v-if="page" :content="page.body" />
                <p v-else class="text-sm text-muted-foreground">
                    Belum ada halaman dokumentasi yang tersedia.
                </p>
            </CardContent>
        </Card>

        <Separator class="my-6" />

        <div class="flex items-center justify-between gap-3">
            <Button
                as-child
                variant="outline"
                size="sm"
                :disabled="!previousPage"
            >
                <Link v-if="previousPage" :href="pageUrl(previousPage.slug)">
                    <span>&larr;</span>
                    <span>{{ previousPage.title }}</span>
                </Link>
            </Button>

            <Button
                as-child
                variant="outline"
                size="sm"
                :disabled="!nextPage"
                class="ml-auto"
            >
                <Link v-if="nextPage" :href="pageUrl(nextPage.slug)">
                    <span>{{ nextPage.title }}</span>
                    <span>&rarr;</span>
                </Link>
            </Button>
        </div>
    </div>
</template>
