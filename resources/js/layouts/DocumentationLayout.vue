<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FileText } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import documentation from '@/routes/documentation';
import type { DocumentationNavGroup } from '@/types';

const props = defineProps<{
    sidebar: DocumentationNavGroup[];
    activeSlug: string | null;
}>();

const pageUrl = (slug: string) => documentation.show.url({ slug });

const isActive = (slug: string) => slug === props.activeSlug;
</script>

<template>
    <SidebarProvider :default-open="true">
        <Sidebar collapsible="icon">
            <SidebarContent>
                <SidebarGroup v-for="group in sidebar" :key="group.group">
                    <SidebarGroupLabel>
                        {{ group.group }}
                    </SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem
                                v-for="item in group.items"
                                :key="item.slug"
                            >
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive(item.slug)"
                                    :tooltip="item.title"
                                >
                                    <Link :href="pageUrl(item.slug)">
                                        <FileText />
                                        <span>{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>
        </Sidebar>

        <SidebarInset class="min-w-0 overflow-x-clip">
            <header
                class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 bg-background/95 px-6 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
            >
                <SidebarTrigger class="-ml-1" />

                <Link :href="home()" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <span class="hidden text-sm text-muted-foreground sm:inline">
                    Dokumentasi KPU
                </span>

                <div class="ml-auto flex items-center gap-2">
                    <AppearanceTabs />
                    <Button as-child size="sm" variant="outline">
                        <Link :href="home()">
                            <BookOpen class="size-4" />
                            Aplikasi
                        </Link>
                    </Button>
                </div>
            </header>

            <div class="flex-1 px-4 py-6 md:px-8">
                <slot />
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>
