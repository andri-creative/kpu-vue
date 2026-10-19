<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    FolderGit2,
    LayoutGrid,
    ListOrdered,
    RefreshCw,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const isKandidat = computed(() => page.props.auth?.user?.role === 'kandidat');

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(isAdmin.value
        ? [
              {
                  title: 'Manajemen User',
                  href: '/users' as string,
                  icon: Users,
              },
              {
                  title: 'Kandidat',
                  href: '/candidates' as string,
                  icon: ListOrdered,
              },
              {
                  title: 'Hasil Votes',
                  href: '/hasil' as string,
                  icon: BarChart3,
              },
              {
                  title: 'Reset Voting',
                  href: '/reset-pemilih' as string,
                  icon: RefreshCw,
              },
          ]
        : isKandidat.value
          ? [
                {
                    title: 'Profil Kandidat',
                    href: '/kandidat' as string,
                    icon: ListOrdered,
                },
            ]
          : []),
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: '/documentation' as string,
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
