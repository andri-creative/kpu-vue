<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { RotateCcw, Upload } from '@lucide/vue';
import { ref } from 'vue';
import BrandingController from '@/actions/App/Http/Controllers/Settings/BrandingController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/settings/branding';

interface BrandingData {
    name: string;
    tagline: string | null;
    logo_url: string | null;
    logo_source: string | null;
    secondary_logo_url: string | null;
    secondary_logo_source: string | null;
    favicon_url: string;
    favicon_source: string | null;
    brand_color: string;
    brand_color_alt: string;
    background_image_url: string | null;
    background_image_source: string | null;
    background_image_width: number;
    background_image_height: number;
    login_card_color: string | null;
    voting_duration: number;
}

const props = defineProps<{
    branding: BrandingData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Branding',
                href: edit(),
            },
        ],
    },
});

const logoInput = ref<HTMLInputElement | null>(null);
const secondaryLogoInput = ref<HTMLInputElement | null>(null);
const faviconInput = ref<HTMLInputElement | null>(null);
const backgroundImageInput = ref<HTMLInputElement | null>(null);
const logoPreview = ref<string | null>(null);
const secondaryLogoPreview = ref<string | null>(null);
const faviconPreview = ref<string | null>(null);
const backgroundImagePreview = ref<string | null>(null);

const brandColor = ref(props.branding.brand_color);
const brandColorAlt = ref(props.branding.brand_color_alt);
const loginCardColor = ref(props.branding.login_card_color ?? '#ffffff');

const colorPresets = [
    { from: '#4f46e5', to: '#9333ea' },
    { from: '#0284c7', to: '#059669' },
    { from: '#059669', to: '#65a30d' },
    { from: '#e11d48', to: '#f97316' },
    { from: '#d97706', to: '#dc2626' },
    { from: '#0f172a', to: '#475569' },
];

function applyPreset(preset: { from: string; to: string }) {
    brandColor.value = preset.from;
    brandColorAlt.value = preset.to;
}

function onLogoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
}

function onFaviconChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        faviconPreview.value = URL.createObjectURL(file);
    }
}

function onSecondaryLogoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        secondaryLogoPreview.value = URL.createObjectURL(file);
    }
}

function onBackgroundImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        backgroundImagePreview.value = URL.createObjectURL(file);
    }
}
</script>

<template>
    <Head title="Branding" />

    <h1 class="sr-only">Branding</h1>

    <div class="max-w-5xl space-y-8">
        <div class="flex flex-col space-y-6">
            <Heading
                variant="small"
                title="Branding"
                description="Sesuaikan nama, tagline, logo, latar, warna, dan timer aplikasi"
            />

            <Form
                v-bind="BrandingController.update.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="app_name">Nama aplikasi</Label>
                        <Input
                            id="app_name"
                            name="app_name"
                            :default-value="branding.name"
                            required
                            maxlength="60"
                            placeholder="Nama aplikasi"
                        />
                        <InputError :message="errors.app_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tagline">Tagline</Label>
                        <Input
                            id="tagline"
                            name="tagline"
                            :default-value="branding.tagline ?? ''"
                            maxlength="160"
                            placeholder="Deskripsi singkat (opsional)"
                        />
                        <InputError :message="errors.tagline" />
                    </div>
                </div>

                <div class="grid items-start gap-6 md:grid-cols-2">
                    <!-- Logo -->
                    <div class="space-y-3 rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label>Logo aplikasi</Label>
                            <p class="text-xs text-muted-foreground">
                                Dipakai di sidebar, header, dan halaman login.
                            </p>
                        </div>

                        <div
                            class="flex h-20 w-20 items-center justify-center rounded-md border bg-muted/30"
                        >
                            <img
                                v-if="logoPreview ?? branding.logo_url"
                                :src="logoPreview ?? branding.logo_url ?? ''"
                                alt="Pratinjau logo"
                                class="max-h-16 max-w-16 object-contain"
                            />
                            <span v-else class="text-xs text-muted-foreground">
                                Default
                            </span>
                        </div>

                        <input
                            ref="logoInput"
                            type="file"
                            name="logo"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="hidden"
                            @change="onLogoChange"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="logoInput?.click()"
                        >
                            <Upload class="size-4" />
                            Pilih Logo
                        </Button>

                        <div class="grid gap-2">
                            <Label for="logo_url" class="text-xs"
                                >atau tempel URL logo</Label
                            >
                            <Input
                                id="logo_url"
                                name="logo_url"
                                :default-value="branding.logo_source ?? ''"
                                placeholder="https://contoh.com/logo.png"
                            />
                            <InputError :message="errors.logo_url" />
                        </div>

                        <InputError :message="errors.logo" />
                        <p class="text-xs text-muted-foreground">
                            PNG, JPG, SVG, atau WebP · maksimal 2 MB. Unggahan
                            menggantikan URL.
                        </p>
                    </div>

                    <!-- Secondary logo -->
                    <div class="space-y-3 rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label>Logo kedua (mis. OSIS)</Label>
                            <p class="text-xs text-muted-foreground">
                                Ditampilkan di samping logo utama pada halaman
                                login.
                            </p>
                        </div>

                        <div
                            class="flex h-20 w-20 items-center justify-center rounded-md border bg-muted/30"
                        >
                            <img
                                v-if="
                                    secondaryLogoPreview ??
                                    branding.secondary_logo_url
                                "
                                :src="
                                    secondaryLogoPreview ??
                                    branding.secondary_logo_url ??
                                    ''
                                "
                                alt="Pratinjau logo kedua"
                                class="max-h-16 max-w-16 object-contain"
                            />
                            <span v-else class="text-xs text-muted-foreground">
                                Kosong
                            </span>
                        </div>

                        <input
                            ref="secondaryLogoInput"
                            type="file"
                            name="secondary_logo"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="hidden"
                            @change="onSecondaryLogoChange"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="secondaryLogoInput?.click()"
                        >
                            <Upload class="size-4" />
                            Pilih Logo Kedua
                        </Button>

                        <div class="grid gap-2">
                            <Label for="secondary_logo_url" class="text-xs"
                                >atau tempel URL logo kedua</Label
                            >
                            <Input
                                id="secondary_logo_url"
                                name="secondary_logo_url"
                                :default-value="
                                    branding.secondary_logo_source ?? ''
                                "
                                placeholder="https://contoh.com/logo-osis.png"
                            />
                            <InputError :message="errors.secondary_logo_url" />
                        </div>

                        <InputError :message="errors.secondary_logo" />
                        <p class="text-xs text-muted-foreground">
                            PNG, JPG, SVG, atau WebP · maksimal 2 MB. Unggahan
                            menggantikan URL.
                        </p>
                    </div>

                    <!-- Favicon -->
                    <div class="space-y-3 rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label>Favicon</Label>
                            <p class="text-xs text-muted-foreground">
                                Ikon kecil pada tab browser.
                            </p>
                        </div>

                        <div
                            class="flex h-20 w-20 items-center justify-center rounded-md border bg-muted/30"
                        >
                            <img
                                v-if="faviconPreview ?? branding.favicon_url"
                                :src="faviconPreview ?? branding.favicon_url"
                                alt="Pratinjau favicon"
                                class="max-h-12 max-w-12 object-contain"
                            />
                        </div>

                        <input
                            ref="faviconInput"
                            type="file"
                            name="favicon"
                            accept="image/png,image/jpeg,image/svg+xml,image/x-icon,image/vnd.microsoft.icon,.ico"
                            class="hidden"
                            @change="onFaviconChange"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="faviconInput?.click()"
                        >
                            <Upload class="size-4" />
                            Pilih Favicon
                        </Button>

                        <div class="grid gap-2">
                            <Label for="favicon_url" class="text-xs"
                                >atau tempel URL favicon</Label
                            >
                            <Input
                                id="favicon_url"
                                name="favicon_url"
                                :default-value="branding.favicon_source ?? ''"
                                placeholder="https://contoh.com/favicon.ico"
                            />
                            <InputError :message="errors.favicon_url" />
                        </div>

                        <InputError :message="errors.favicon" />
                        <p class="text-xs text-muted-foreground">
                            PNG, JPG, SVG, ICO, atau WebP · maksimal 1 MB.
                        </p>
                    </div>

                    <!-- Latar halaman login -->
                    <div class="space-y-3 rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label>Gambar latar halaman login</Label>
                            <p class="text-xs text-muted-foreground">
                                Foto sekolah atau tema. Jika kosong, latar
                                memakai gradasi warna.
                            </p>
                        </div>

                        <div
                            class="flex h-32 w-full items-center justify-center overflow-hidden rounded-md border bg-muted/30"
                        >
                            <img
                                v-if="
                                    backgroundImagePreview ??
                                    branding.background_image_url
                                "
                                :src="
                                    backgroundImagePreview ??
                                    branding.background_image_url ??
                                    ''
                                "
                                alt="Pratinjau gambar latar"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="text-xs text-muted-foreground">
                                Memakai gradasi warna
                            </span>
                        </div>

                        <input
                            ref="backgroundImageInput"
                            type="file"
                            name="background_image"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="hidden"
                            @change="onBackgroundImageChange"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="backgroundImageInput?.click()"
                        >
                            <Upload class="size-4" />
                            Pilih Gambar Latar
                        </Button>

                        <div class="grid gap-2">
                            <Label for="background_image_url" class="text-xs"
                                >atau tempel URL gambar latar</Label
                            >
                            <Input
                                id="background_image_url"
                                name="background_image_url"
                                :default-value="
                                    branding.background_image_source ?? ''
                                "
                                placeholder="https://contoh.com/sekolah.jpg"
                            />
                            <InputError
                                :message="errors.background_image_url"
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label
                                    for="background_image_width"
                                    class="text-xs"
                                    >Lebar gambar (px)</Label
                                >
                                <Input
                                    id="background_image_width"
                                    name="background_image_width"
                                    type="number"
                                    min="1"
                                    max="10000"
                                    :default-value="
                                        branding.background_image_width
                                    "
                                    placeholder="1920"
                                />
                                <InputError
                                    :message="errors.background_image_width"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label
                                    for="background_image_height"
                                    class="text-xs"
                                    >Tinggi gambar (px)</Label
                                >
                                <Input
                                    id="background_image_height"
                                    name="background_image_height"
                                    type="number"
                                    min="1"
                                    max="10000"
                                    :default-value="
                                        branding.background_image_height
                                    "
                                    placeholder="1080"
                                />
                                <InputError
                                    :message="errors.background_image_height"
                                />
                            </div>
                        </div>

                        <InputError :message="errors.background_image" />
                        <p class="text-xs text-muted-foreground">
                            PNG, JPG, SVG, atau WebP · maksimal 4 MB. Ukuran
                            kosong berarti gambar menutupi seluruh layar.
                        </p>
                    </div>

                    <!-- Warna -->
                    <div class="space-y-3 rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label>Warna</Label>
                            <p class="text-xs text-muted-foreground">
                                Dipakai pada gradasi halaman login.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="brand_color" class="text-xs"
                                    >Warna utama</Label
                                >
                                <div class="flex items-center gap-2">
                                    <input
                                        type="color"
                                        :value="brandColor"
                                        class="h-9 w-10 shrink-0 cursor-pointer rounded border"
                                        @input="
                                            brandColor = (
                                                $event.target as HTMLInputElement
                                            ).value
                                        "
                                    />
                                    <Input
                                        id="brand_color"
                                        name="brand_color"
                                        v-model="brandColor"
                                        class="font-mono text-xs"
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="brand_color_alt" class="text-xs"
                                    >Warna gradasi</Label
                                >
                                <div class="flex items-center gap-2">
                                    <input
                                        type="color"
                                        :value="brandColorAlt"
                                        class="h-9 w-10 shrink-0 cursor-pointer rounded border"
                                        @input="
                                            brandColorAlt = (
                                                $event.target as HTMLInputElement
                                            ).value
                                        "
                                    />
                                    <Input
                                        id="brand_color_alt"
                                        name="brand_color_alt"
                                        v-model="brandColorAlt"
                                        class="font-mono text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="login_card_color" class="text-xs"
                                >Warna card login</Label
                            >
                            <div class="flex items-center gap-2">
                                <input
                                    type="color"
                                    :value="loginCardColor"
                                    class="h-9 w-10 shrink-0 cursor-pointer rounded border"
                                    @input="
                                        loginCardColor = (
                                            $event.target as HTMLInputElement
                                        ).value
                                    "
                                />
                                <Input
                                    id="login_card_color"
                                    name="login_card_color"
                                    v-model="loginCardColor"
                                    class="font-mono text-xs"
                                    placeholder="#ffffff"
                                />
                            </div>
                            <InputError :message="errors.login_card_color" />
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="preset in colorPresets"
                                :key="`${preset.from}-${preset.to}`"
                                type="button"
                                class="h-7 w-7 rounded-full border shadow-sm transition-transform hover:scale-110"
                                :title="`${preset.from} → ${preset.to}`"
                                :style="{
                                    backgroundImage: `linear-gradient(135deg, ${preset.from}, ${preset.to})`,
                                }"
                                @click="applyPreset(preset)"
                            />
                        </div>

                        <div
                            class="flex h-16 items-center justify-center rounded-md border text-xs font-medium text-white"
                            :style="{
                                backgroundImage: `linear-gradient(135deg, ${brandColor}, ${brandColorAlt})`,
                            }"
                        >
                            Pratinjau gradasi
                        </div>

                        <InputError :message="errors.brand_color" />
                        <InputError :message="errors.brand_color_alt" />
                    </div>

                    <!-- Timer pemilihan -->
                    <div class="space-y-3 rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label for="voting_duration"
                                >Durasi timer pemilihan</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Batas waktu memilih di halaman pemilih (dalam
                                menit). Pemilih otomatis keluar saat waktu
                                habis.
                            </p>
                        </div>

                        <div class="grid max-w-xs gap-2">
                            <div class="flex items-center gap-2">
                                <Input
                                    id="voting_duration"
                                    name="voting_duration"
                                    type="number"
                                    min="1"
                                    max="120"
                                    :default-value="branding.voting_duration"
                                    placeholder="3"
                                />
                                <span class="text-sm text-muted-foreground"
                                    >menit</span
                                >
                            </div>
                            <InputError :message="errors.voting_duration" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing"
                        data-test="update-branding-button"
                    >
                        Simpan
                    </Button>
                </div>
            </Form>
        </div>

        <div
            class="flex flex-col gap-4 rounded-lg border border-destructive/30 bg-destructive/5 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-0.5">
                <p class="text-sm font-medium">Reset ke default</p>
                <p class="text-xs text-muted-foreground">
                    Kembalikan nama, tagline, logo, latar, warna, dan timer ke
                    pengaturan awal.
                </p>
            </div>

            <Form
                v-bind="BrandingController.reset.form()"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="processing"
                    data-test="reset-branding-button"
                >
                    <RotateCcw class="size-4" />
                    Reset ke Default
                </Button>
            </Form>
        </div>
    </div>
</template>
