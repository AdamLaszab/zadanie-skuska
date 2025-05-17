// resources/js/types/index.d.ts (alebo inertia.d.ts)

import type { PageProps as InertiaPageProps } from '@inertiajs/core'; // Premenujeme, aby sme sa vyhli konfliktu
import type { Config as ZiggyConfig } from 'ziggy-js'; // Premenujeme, ak je potrebné, alebo použijeme priamo

// ---------------------------------------------------------------
// Vaše vlastné typy (User, Auth, BreadcrumbItem, NavItem)
// Tieto sú v poriadku tak, ako sú.
// ---------------------------------------------------------------
export interface User {
    // Export je tu v poriadku, ak ho chcete importovať inde
    id: number;
    name: string;
    username: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    // created_at: string; // Tieto zvyčajne nie sú potrebné na frontende, pokiaľ ich vyslovene nepoužívate
    // updated_at: string;
}

export interface Auth {
    user: User;
    // Môžete tu pridať ďalšie auth-related props, ak nejaké máte
    // napr. roles?: string[]; permissions?: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}
export type BreadcrumbItemType = BreadcrumbItem; // Tento alias je v poriadku

export type NavItem = {
    label: string;
    href: string;
    permission?: string;
    role?: string;
    icon?: string;
};

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps {
        // Rozširujeme pôvodné InertiaPageProps
        // Tu pridajte props, ktoré zdieľate globálne cez HandleInertiaRequests middleware
        appName: string; // Príklad: Názov aplikácie z configu
        quote?: { message: string; author: string }; // Dávam ako voliteľné, ak nie je vždy prítomné
        auth: Auth; // Váš vlastný typ Auth
        ziggy: ZiggyConfig & { location: string }; // Kombinácia ZiggyConfig a vašej property location

        // Ak máte flash správy
        flash?: {
            success?: string;
            error?: string;
            info?: string;
            warning?: string;
        };

        // Ak máte chyby validácie
        errors?: Record<string, string>;

        // Ak používate sidebarOpen ako globálnu zdieľanú prop
        // sidebarOpen?: boolean; // Otázka: je toto naozaj globálne zdieľané z backendu, alebo je to stav frontendu?
        // Ak je to stav frontendu, nemalo by to byť tu.
        // Ak áno, tak by to tu mohlo byť.

        // Pridajte akékoľvek ďalšie vlastné zdieľané dáta
        // napr. currentLocale?: string;
        //       csrf_token?: string;
    }
}

// Ak chcete typ `SharedData` stále používať ako alias pre vaše rozšírené PageProps,
// môžete ho definovať takto, ale pre usePage<PageProps>() budete používať priamo PageProps z '@inertiajs/core'.
// export type SharedData = import('@inertiajs/core').PageProps;

// Aby sa súbor bral ako TypeScript modul
export {};
