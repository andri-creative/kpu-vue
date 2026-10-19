<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { IdCard, KeyRound } from '@lucide/vue';
import { computed, watch } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const page = usePage();

const branding = computed(() => ({
    name: page.props.branding?.name ?? 'Welcome',
    tagline: page.props.branding?.tagline ?? null,
    logo_url: page.props.branding?.logo_url ?? null,
    secondary_logo_url: page.props.branding?.secondary_logo_url ?? null,
    brand_color: page.props.branding?.brand_color || '#4f46e5',
    brand_color_alt: page.props.branding?.brand_color_alt || '#9333ea',
    background_image_url: page.props.branding?.background_image_url ?? null,
    background_image_width: page.props.branding?.background_image_width ?? null,
    background_image_height:
        page.props.branding?.background_image_height ?? null,
    login_card_color: page.props.branding?.login_card_color ?? null,
}));

const gradient = computed(
    () =>
        `linear-gradient(135deg, ${branding.value.brand_color}, ${branding.value.brand_color_alt})`,
);

const hasCustomSize = computed(
    () =>
        branding.value.background_image_width !== null &&
        branding.value.background_image_height !== null,
);

const backgroundImageStyle = computed(() => {
    if (!hasCustomSize.value) {
        return {};
    }

    return {
        width: `${branding.value.background_image_width}px`,
        height: `${branding.value.background_image_height}px`,
    };
});

const cardStyle = computed(() => ({
    backgroundColor: branding.value.login_card_color ?? undefined,
}));

const form = useForm({
    nis: '',
    kode: '',
    remember: false,
});

const submitForm = () => {
    if (form.processing) {
        return;
    }

    form.post('/login', {
        preserveState: false,
        onFinish: () => {
            form.kode = '';
        },
    });
};

// v-model menangani sinkronisasi input, watcher ini hanya memfilter non-digit
// dan membatasi kode maksimal 6 karakter.
watch(
    () => form.kode,
    (value) => {
        const digits = value.replace(/\D/g, '').slice(0, 6);

        if (digits !== value) {
            form.kode = digits;
        }
    },
);
</script>

<template>
    <Head title="Login" />

    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-6 text-white"
        :style="{ backgroundImage: gradient }"
    >
        <img
            v-if="branding.background_image_url"
            :src="branding.background_image_url"
            alt=""
            class="pointer-events-none absolute object-cover"
            :class="
                hasCustomSize
                    ? 'top-1/2 left-1/2 max-w-none -translate-x-1/2 -translate-y-1/2'
                    : 'inset-0 h-full w-full'
            "
            :style="backgroundImageStyle"
        />
        <div
            v-if="branding.background_image_url"
            class="pointer-events-none absolute inset-0 bg-black/35"
        />
        <PlaceholderPattern class="opacity-10" />
        <div
            class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"
        />
        <div
            class="pointer-events-none absolute -bottom-24 -left-16 h-80 w-80 rounded-full bg-black/10 blur-3xl"
        />

        <!-- Card -->
        <div
            class="relative w-full max-w-md animate-in rounded-2xl border border-white/10 bg-background p-7 text-foreground shadow-2xl duration-500 fade-in slide-in-from-bottom-4 sm:max-w-xl sm:p-12"
            :style="cardStyle"
        >
            <div class="mb-7 flex flex-col items-center gap-2 text-center">
                <div class="flex items-center justify-center gap-4">
                    <img
                        v-if="branding.logo_url"
                        :src="branding.logo_url"
                        alt="Logo"
                        class="h-14 w-14 object-contain"
                    />
                    <img
                        v-if="branding.secondary_logo_url"
                        :src="branding.secondary_logo_url"
                        alt="Logo kedua"
                        class="h-14 w-14 object-contain"
                    />
                    <div
                        v-if="
                            !branding.logo_url && !branding.secondary_logo_url
                        "
                        class="flex h-14 w-14 items-center justify-center rounded-2xl text-white"
                        :style="{ backgroundImage: gradient }"
                    >
                        <AppLogoIcon class="h-7 w-7 text-white" />
                    </div>
                </div>
                <span class="text-lg font-semibold text-foreground">{{
                    branding.name
                }}</span>
            </div>

            <div class="space-y-1.5">
                <h2
                    class="text-2xl font-semibold tracking-tight text-foreground"
                >
                    Masuk
                </h2>
                <p class="text-base text-muted-foreground">
                    {{
                        branding.tagline ??
                        'Masukkan NIS dan kode 6 angka untuk melanjutkan.'
                    }}
                </p>
            </div>

            <form @submit.prevent="submitForm" class="mt-6 space-y-4">
                <!-- NIS -->
                <div class="space-y-2">
                    <Label for="nis">NIS</Label>
                    <div class="relative">
                        <IdCard
                            class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            id="nis"
                            name="nis"
                            type="text"
                            required
                            autocomplete="username"
                            v-model="form.nis"
                            :disabled="form.processing"
                            class="h-11 pl-10 text-base md:text-base"
                            placeholder="Masukkan NIS"
                        />
                    </div>
                    <InputError :message="form.errors.nis" />
                </div>

                <!-- Kode -->
                <div class="space-y-2">
                    <Label for="kode">Kode 6 angka</Label>
                    <div class="relative">
                        <KeyRound
                            class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            id="kode"
                            name="kode"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            autocomplete="one-time-code"
                            v-model="form.kode"
                            :disabled="form.processing"
                            class="h-11 pl-10 text-center font-mono text-lg tracking-[0.4em] md:text-lg"
                            placeholder="000000"
                        />
                    </div>
                    <InputError :message="form.errors.kode" />
                    <p class="text-xs text-muted-foreground">
                        Kode dibuat oleh admin dan hanya bisa dipakai sekali.
                    </p>
                </div>

                <!-- Remember -->
                <div class="flex items-center justify-between">
                    <Label
                        for="remember"
                        class="flex cursor-pointer items-center gap-2"
                    >
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            v-model="form.remember"
                            class="size-4 rounded border-input"
                            :style="{
                                accentColor: branding.brand_color,
                            }"
                        />
                        <span class="text-sm text-muted-foreground"
                            >Ingat saya</span
                        >
                    </Label>
                </div>

                <!-- Submit -->
                <Button
                    type="submit"
                    size="lg"
                    class="h-11 w-full border-0 text-base text-white hover:opacity-90"
                    :style="{ backgroundImage: gradient }"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" class="mr-2 h-4 w-4" />
                    Masuk
                </Button>
            </form>
        </div>

        <div class="relative mt-5 text-xs text-white/70">
            © {{ new Date().getFullYear() }} {{ branding.name }}
        </div>
    </div>
</template>
