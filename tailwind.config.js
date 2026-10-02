import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        'w-64', 'w-16',
        'lg:ml-64', 'lg:ml-16',
        '-translate-x-full', 'translate-x-0', 'lg:translate-x-0',
        // Activity log event themes (classes resolved server-side in ActivityLog::eventTheme()).
        'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-teal-500', 'bg-sky-500',
        'bg-slate-400', 'bg-violet-500', 'bg-red-500', 'bg-indigo-500',
        'bg-emerald-100', 'text-emerald-700', 'bg-amber-100', 'text-amber-700',
        'bg-rose-100', 'text-rose-700', 'bg-teal-100', 'text-teal-700',
        'bg-sky-100', 'text-sky-700', 'bg-slate-100', 'text-slate-600',
        'bg-violet-100', 'text-violet-700', 'bg-red-100', 'text-red-700',
        'bg-indigo-100', 'text-indigo-700',
        'dark:bg-emerald-900/40', 'dark:text-emerald-300', 'dark:bg-amber-900/40', 'dark:text-amber-300',
        'dark:bg-rose-900/40', 'dark:text-rose-300', 'dark:bg-teal-900/40', 'dark:text-teal-300',
        'dark:bg-sky-900/40', 'dark:text-sky-300', 'dark:bg-slate-700', 'dark:text-slate-300',
        'dark:bg-violet-900/40', 'dark:text-violet-300', 'dark:bg-red-900/40', 'dark:text-red-300',
        'dark:bg-indigo-900/40', 'dark:text-indigo-300',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
