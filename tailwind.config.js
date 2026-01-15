/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  // Activar dark mode basado en clase (permite control manual)
  darkMode: 'class',
  theme: {
    extend: {
      // Colores personalizados para el tema oscuro si los necesitas
      colors: {
        dark: {
          bg: '#1a1a2e',
          card: '#16213e',
          border: '#0f3460',
        }
      }
    },
  },
  plugins: [],
}
