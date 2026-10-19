export interface Branding {
    name: string;
    tagline: string | null;
    logo_url: string | null;
    secondary_logo_url: string | null;
    favicon_url: string | null;
    brand_color: string;
    brand_color_alt: string;
    background_image_url: string | null;
    background_image_width: number | null;
    background_image_height: number | null;
    login_card_color: string | null;
    voting_duration: number;
}
