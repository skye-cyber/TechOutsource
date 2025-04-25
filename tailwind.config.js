/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class', /*'[data-mode="dark"]'],*/
  content: ['*.php', 'draft/*.html', '*.js', './src/*.php', './src/js/*.js'],
  //content: ['loading.html'],
  theme: {
    screens: {
      sm: '640px',
      md: '768px',
      lg: '1024px',
      xl: '1280px',
      '2xl': '1536px',
    },

    fontFamily: {
      display: ['Source Serif Pro', 'Georgia', 'serif'],
      body: ['Synonym', 'system-ui', 'sans-serif'],
    },

    extend: {
      fontSize: {
        'h1': '36', // Adjust as needed
        'h2': '2rem',   // Adjust as needed
        'h3': '1.75rem', // Adjust as needed
        'h4': '1.5rem',  // Adjust as needed
        'h5': '1.25rem', // Adjust as needed
        'h6': '1rem',    // Adjust as needed
      },
      fontWeight: {
        'h1': '700',  // Adjust as needed
        'h2': '600',  // Adjust as needed
        'h3': '500',  // Adjust as needed
        'h4': '400',  // Adjust as needed
        'h5': '300',  // Adjust as needed
        'h6': '200',  // Adjust as needed
      },
    },

      animation: {
        'bounce': 'bounce 0.5s infinite',
        'bounce-100': 'bounce 0.5s 100ms infinite',
        'bounce-200': 'bounce 0.5s 200ms infinite',
        'bounce-300': 'bounce 0.5s 300ms infinite',
        'bounce-400': 'bounce 0.5s 400ms infinite',
        'bounce-500': 'bounce 0.5s 500ms infinite',
        'bounce-600': 'bounce 0.5s 600ms infinite',
        'heartpulse': 'heartpulse 1s infinite',
        'spin': 'spin 2s linear infinite',
        'spin-200': 'spin 0.5s linear infinite',
        'fadeInCubic': 'fadeInCubic 2s cubic-bezier(0.25, 1, 0.5, 1)',
        'singleRipple': 'singleRipple 1.8s infinite',
        'fadeIn': 'fadeIn 2s ease forwards',
        'slideInUp': 'slideInUp 1s ease forwards',
        'slideInDown': 'slideInDown 1s ease forwards',
        'slideInRight': 'slideInRight 1s ease forwards',
        'slideInLeft': 'slideInLeft 1s ease forwards',
        'gradientSlow': 'gradientSlow 15s ease infinite',
      },

      keyframes: {
        fadeIn:{
          '0%': {opacity: 0},
          '50%': {opacity: 0.5},
          '100%': {opacity: 1},
        },
        fadeInCubic: {
          '0%': { opacity: 0, transform: 'translateY(-300px)' },
          '10%': { opacity: 0.1 },
          '20%': { opacity: 0.2 },
          '30%': { opacity: 0.3 },
          '40%': { opacity: 0.4 },
          '50%': { opacity: 0.5, transform: 'translateY(-150px)' },
          '60%': { opacity: 0.6 },
          '70%': { opacity: 0.7 },
          '80%': { opacity: 0.8 },
          '90%': { opacity: 0.9 },
          '100%': { opacity: 1, transform: 'translateY(0)' }
        },
        bounce: {
          '0%': { opacity: 1 },
          '50%': { opacity: 0.5 },
          '100%': { opacity: 1 },
        },
        heartpulse: {
          '0%': { transform: 'scale(1)' },
          '50%': { transform: 'scale(1.2)' },
          '100%': { transform: 'scale(1)' },
        },
        spin: {
          '0%': { transform: 'rotate(0deg)' },
          '100%': { transform: 'rotate(360deg)' },
        },
        singleRipple:{
          '0%': {transform:'scale(0.8)', opacity: '1'},
          '100%': {transform:'scale(2.5)', opacity: '0'},
        },
        slideInUp: {
          '0%': { transform: 'translateY(30px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        slideInRight: {
          '0%': { transform: 'translateX(-30px)', opacity: '0' },
          '100%': { transform: 'translateX(0)', opacity: '1' },
        },
        slideInLeft: {
          '0%':  { transform: 'translateX(30px)', opacity: '0' },
          '100%': { transform: 'translateX(0)', opacity: '1' },
        },
        slideInDown: {
          '0%':  { transform: 'translateY(0)', opacity: '0' },
          '50%': { transform: 'translateY(30px)', opacity: '0.5' },
          '100%': { transform: 'translateY(0px)', opacity: '1' },
        },
        gradientSlow: {
          '0%': { 'background-position': '0% 50%' },
          '50%': { 'background-position': '100% 50%' },
          '100%': { 'background-position': '0% 50%' },
        }
      }
    },
  plugins: [],
};
