import { usePage } from '@inertiajs/vue3';

export default {
    install: (app: any) => {
        app.config.globalProperties.__ = (key: string, replacements: Record<string, string> = {}) => {
            const translations = usePage().props.translations as Record<string, string> | undefined;
            let translation = translations?.[key] || key;

            Object.keys(replacements).forEach((replaceKey) => {
                translation = translation.replace(new RegExp(':' + replaceKey, 'g'), replacements[replaceKey]);
            });

            return translation;
        };

        app.provide('__', app.config.globalProperties.__);
    },
};
