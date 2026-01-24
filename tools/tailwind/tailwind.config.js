/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    jit: true,
    content: ['../../resources/views/**/*.blade.php', '../../resources/**/*.{vue,js,ts,jsx,tsx}'],
    plugins: [require('tailwindcss-animate')],
    theme: {
        extend: {
            screens: {
                xs: '360px'
            },
            fontSize: {
                // Use rem for scalability, define as tokens
                xxs: ['0.5625rem', { lineHeight: '0.75rem' }], // 9px
                xs: ['0.75rem', { lineHeight: '1rem' }],       // 12px
            },
            borderRadius: {
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
            },
            colors: {
                sidebar: {
                    DEFAULT: 'var(--sidebar-background)',
                    foreground: 'var(--sidebar-foreground)',
                    primary: 'var(--sidebar-primary)',
                    'primary-foreground': 'var(--sidebar-primary-foreground)',
                    accent: 'var(--sidebar-accent)',
                    'accent-foreground': 'var(--sidebar-accent-foreground)',
                    border: 'var(--sidebar-border)',
                    ring: 'var(--sidebar-ring)',
                },
            },
        },
    },
};
