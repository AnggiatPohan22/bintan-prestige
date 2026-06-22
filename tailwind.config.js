import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Support/BlockStyle.php',
    ],

    // Responsive visibility classes emitted at runtime by App\Support\BlockStyle
    // (Advanced tab → hide on desktop/tablet/mobile) — keep them in the bundle.
    safelist: ['hidden', 'md:block', 'md:hidden', 'lg:block', 'lg:hidden'],

    theme: {
        extend: {
            fontFamily: {
                title: ['Forum', 'Georgia', 'Times New Roman', 'serif'],
                body: ['Montserrat', ...defaultTheme.fontFamily.sans],
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                'frontend-hero': ['clamp(3.5rem, 10vw, 9.75rem)', { lineHeight: '0.92', letterSpacing: '0' }],
                'frontend-section': ['clamp(2rem, 4vw, 3.25rem)', { lineHeight: '1.08', letterSpacing: '0' }],
                'frontend-card': ['clamp(1.125rem, 1.8vw, 1.5rem)', { lineHeight: '1.2', letterSpacing: '0' }],
                'frontend-body': ['1rem', { lineHeight: '1.75', letterSpacing: '0' }],
            },
        },
    },

    plugins: [forms],
};
