import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(urlToCheck: NonNullable<InertiaLinkProps['href']>, currentUrl: string) {
    const checkUrl = toUrl(urlToCheck);

    // Exact match first
    if (checkUrl === currentUrl) {
        return true;
    }

    // Check for category routes - if the nav item is /categories,
    // it should be active for /categories, /categories/create, /categories/{id}, /categories/{id}/edit
    if (checkUrl === '/categories' && currentUrl.match(/^\/categories(\/|$)/)) {
        return true;
    }

    // Check for category routes - if the nav item is /locations,
    // it should be active for /locations, /locations/create, /locations/{id}, /locations/{id}/edit
    if (checkUrl === '/locations' && currentUrl.match(/^\/locations(\/|$)/)) {
        return true;
    }

    // Check for category routes - if the nav item is /properties,
    // it should be active for /properties, /properties/create, /properties/{id}, /properties/{id}/edit
    if (checkUrl === '/properties' && currentUrl.match(/^\/properties(\/|$)/)) {
        return true;
    }

    // Check for admin routes - if the nav item is /admin/clients,
    // it should be active for /admin/clients, /admin/clients/create, /admin/clients/{id}, /admin/clients/{id}/edit
    if (checkUrl === '/admin/clients' && currentUrl.match(/^\/admin\/clients(\/|$)/)) {
        return true;
    }

    // Check for admin routes - if the nav item is /admin/rented,
    // it should be active for /admin/rented, /admin/rented/create, /admin/rented/{id}, /admin/rented/{id}/edit
    if (checkUrl === '/admin/rented' && currentUrl.match(/^\/admin\/rented(\/|$)/)) {
        return true;
    }

    // Check for admin routes - if the nav item is /admin/users,
    // it should be active for /admin/users, /admin/users/create, /admin/users/{id}, /admin/users/{id}/edit
    if (checkUrl === '/admin/users' && currentUrl.match(/^\/admin\/users(\/|$)/)) {
        return true;
    }

    // Check for admin routes - if the nav item is /admin/database-backup,
    // it should be active for /admin/database-backup, /admin/database-backup/create, /admin/database-backup/{id}, /admin/database-backup/{id}/edit
    // Also handles query parameters like ?search=&sort=latest&trash=true
    if (checkUrl === '/admin/database-backup' && currentUrl.match(/^\/admin\/database-backup(\/|$|\?)/)) {
        return true;
    }

    return false;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}
