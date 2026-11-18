import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

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
                blue: {
                    50: '#FDF2F3',
                    100: '#FBE7E8',
                    200: '#F5C9CC',
                    300: '#EDA2A7',
                    400: '#E2757B',
                    500: '#D95B62',
                    600: '#D34B52',
                    700: '#B33C43',
                    800: '#943139',
                    900: '#7A2A31'
                },
            },
        },
    },

    plugins: [forms],
};
