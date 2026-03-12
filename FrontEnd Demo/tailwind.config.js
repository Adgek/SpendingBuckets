
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
  './index.html',
  './src/**/*.{js,ts,jsx,tsx}'
],
  theme: {
    extend: {
      colors: {
        charcoal: '#121212',
        navy: '#1A1E23',
        elevated: '#1E2228',
        surface: '#252A31',
        gold: '#C5A059',
        forest: '#4A7856',
        crimson: '#8B0000',
        'warm-white': '#F5F0E8',
        muted: '#8A8A8A',
        border: '#2A2F36'
      },
      fontFamily: {
        serif: ['"Playfair Display"', 'serif'],
        sans: ['Inter', 'sans-serif']
      }
    },
  },
  plugins: [],
}
