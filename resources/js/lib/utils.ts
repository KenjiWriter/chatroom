import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function resolveAsset(path: string | null | undefined, type: 'avatar' | 'banner' = 'avatar', name: string = ''): string | null {
    if (!path) {
        if (type === 'avatar' && name) {
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=7F9CF5&background=EBF4FF`;
        }
        return type === 'avatar' ? '' : null;
    }
    if (path.startsWith('http') || path.startsWith('data:')) {
        return path;
    }
    const resolved = path.startsWith('/storage') ? path : `/storage/${path}`;
    return resolved;
}
