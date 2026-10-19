<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FolderOpen, Palette, Terminal } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Security',
        href: editSecurity(),
    },
    ...(isAdmin.value
        ? [
              {
                  title: 'Admin',
                  href: '/settings/admins',
              },
              {
                  title: 'Branding',
                  href: '/settings/branding',
                  icon: Palette,
              },
              {
                  title: 'Database & Log',
                  href: '/settings/database',
              },
              {
                  title: 'Logs',
                  href: '/settings/logs',
                  icon: Terminal,
              },
              {
                  title: 'Storage',
                  href: '/settings/files',
                  icon: FolderOpen,
              },
          ]
        : []),
    {
        title: 'Appearance',
        href: editAppearance(),
    },
]);

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48 lg:shrink-0">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Settings"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="min-w-0 flex-1">
                <slot />
            </div>
        </div>
    </div>
</template>
