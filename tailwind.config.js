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
                // The product pair: Archivo draws Latin, Kantumruy Pro draws Khmer.
                sans: ['Archivo', 'Kantumruy Pro', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
