import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    dark: '#0D192B',
                    primary: '#0070F3',
                    card: '#1F2937',
                    slate: '#2A4365',
                    muted: '#5A6A80',
                    ice: '#EEF4FC',
                    success: '#00C48C',
                    mint: '#10B981',
                }
            }
        },
    },

    plugins: [forms],
};
