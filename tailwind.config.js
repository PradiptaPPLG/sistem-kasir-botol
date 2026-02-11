/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./login.php", "./src/**/*.{html,js,php}"],
  theme: {
    extend: {
      fontSize: {
        'xl': '1.25rem',
        '2xl': '1.5rem',
        '3xl': '1.875rem',
        '4xl': '2.25rem',
      }
    },
  },
  plugins: [],
}