import { onMounted, ref } from 'vue';

type Appearance = 'light';

export function updateTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always use light mode
    document.documentElement.classList.remove('dark');
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always use light mode
    updateTheme();
}

const appearance = ref<Appearance>('light');

export function useAppearance() {
    onMounted(() => {
        // Always set to light mode
        appearance.value = 'light';
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        updateTheme();
    }

    return {
        appearance,
        updateAppearance,
    };
}
