/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        'primea-blue': '#272d63',
        'primea-yellow': '#fab511',
        'primea-white': '#ffffff',
      },
      fontFamily: {
        'primea': ['Myriad Pro', 'sans-serif'],
        'sans': ['Myriad Pro', 'Inter', 'ui-sans-serif', 'system-ui'],
      },
      fontSize: {
        'xs': ['0.75rem', '1rem'],
        'sm': ['0.875rem', '1.25rem'],
        'base': ['1rem', '1.5rem'],
        'lg': ['1.125rem', '1.75rem'],
        'xl': ['1.25rem', '1.75rem'],
        '2xl': ['1.5rem', '2rem'],
        '3xl': ['1.875rem', '2.25rem'],
        '4xl': ['2.25rem', '2.5rem'],
        '5xl': ['3rem', '1'],
        '6xl': ['3.75rem', '1'],
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
      },
      borderRadius: {
        'primea': '12px',
        'primea-lg': '16px',
        'primea-xl': '20px',
      },
      boxShadow: {
        'primea': '0 4px 20px rgba(39, 45, 99, 0.1)',
        'primea-lg': '0 8px 30px rgba(39, 45, 99, 0.15)',
        'primea-yellow': '0 4px 20px rgba(250, 181, 17, 0.2)',
      },
      backgroundImage: {
        'primea-gradient': 'linear-gradient(135deg, #272d63 0%, #1a1f4a 100%)',
        'primea-yellow-gradient': 'linear-gradient(135deg, #fab511 0%, #e09a0a 100%)',
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'slide-up': 'slideUp 0.6s ease-out',
        'bounce-slow': 'bounce 2s infinite',
        'wiggle': 'wiggle 1s ease-in-out infinite',
        'pulse-scale': 'pulseScale 2s ease-in-out infinite',
        'bounce-gentle': 'bounceGentle 2.5s ease-in-out infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(30px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        wiggle: {
          '0%, 100%': { transform: 'rotate(-3deg)' },
          '50%': { transform: 'rotate(3deg)' },
        },
        pulseScale: {
          '0%, 100%': { transform: 'scale(1)' },
          '50%': { transform: 'scale(1.05)' },
        },
        bounceGentle: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-5px)' },
        },
      },
    },
  },
  plugins: [],
}