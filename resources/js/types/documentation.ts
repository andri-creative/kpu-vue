export interface DocumentationNavItem {
    slug: string;
    title: string;
}

export interface DocumentationNavGroup {
    group: string;
    items: DocumentationNavItem[];
}

export interface DocumentationPage {
    slug: string;
    title: string;
    group: string;
    order: number;
    body: string;
}

export interface DocumentationProps {
    sidebar: DocumentationNavGroup[];
    activeSlug: string | null;
    page: DocumentationPage | null;
}