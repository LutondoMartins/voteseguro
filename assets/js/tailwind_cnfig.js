tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#1E3A8A',
                        secondary: '#F3F4F6',
                        accent: '#10B981',
                        text: '#111827',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'slide-in': 'slide-in 0.5s ease-out',
                        'fade-in': 'fade-in 0.8s ease-out',
                    },
                    backdropBlur: {
                        'xs': '2px',
                    }
                }
            }
        }