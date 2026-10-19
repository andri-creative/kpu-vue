import '@inertiajs/core';
import type { Directive } from 'vue';
import type { Auth } from '@/types/auth';
import type { Branding } from '@/types/branding';
import type { FlashToast } from '@/types/ui';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            branding: Branding;
            auth: Auth;
            sidebarOpen: boolean;
            voting: {
                deadline: string | null;
                remaining_seconds: number | null;
                expired: boolean;
            } | null;
        };
        flashDataType: {
            toast?: FlashToast;
        };
    }
}

declare module 'vue' {
    interface GlobalDirectives {
        vFocus: Directive<HTMLElement, boolean | undefined>;
    }
}
