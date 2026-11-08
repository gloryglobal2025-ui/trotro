/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./resources/views/website/**/*.blade.php'],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
};
