import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Montserrat', 'Arial', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                umss: {
                    navy: '#0E2E5F',
                    'navy-dark': '#0A2247',
                    red: '#E01D2E',
                    'red-dark': '#8B0000',
                    white: '#FFFFFF',
                    'gray-100': '#F5F5F5',
                    'gray-700': '#4A4A4A',
                    black: '#121212',
                },
            },
        },
    },

    plugins: [],
};
