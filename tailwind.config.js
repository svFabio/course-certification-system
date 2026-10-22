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
                    'gray-200': '#E5E5E5',
                    'gray-300': '#D6D6D6',
                    'gray-700': '#4A4A4A',
                    black: '#121212',
                    green: '#16A34A',
                    'green-dark': '#15803D',
                    'green-light': '#DCFCE7',
                    amber: '#D97706',
                    'amber-dark': '#B45309',
                    'amber-light': '#FEF3C7',
                    sky: '#0284C7',
                    'sky-dark': '#0369A1',
                    'sky-light': '#E0F2FE',
                },
            },
        },
    },

    plugins: [],
};
