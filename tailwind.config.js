/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/**/*.ts",
  ],
  theme: {
    extend: {},
    colors: {
        'soft-white': '#ffffff80', // alias untuk putih semi-transparan
      },
  },
  plugins: [],
}
