<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteSeguro - Plataforma de Votação Digital Segura</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        /* Animações personalizadas */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
        
        .animate-slide-in-left {
            animation: slideInLeft 0.8s ease-out forwards;
        }
        
        .animate-slide-in-right {
            animation: slideInRight 0.8s ease-out forwards;
        }
        
        /* Glassmorphism */
        .glass {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Loading state inicialmente oculto */
        .opacity-0 {
            opacity: 0;
        }
        
        /* Parallax subtle effect */
        .parallax {
            transform: translateZ(0);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-emerald-50 min-h-screen">
    
    <!-- Header/Navbar -->
    <header class="fixed top-0 left-0 right-0 z-50 glass bg-white/70 border-b border-white/20 shadow-lg">
        <nav class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                    </div>
                    <span class="text-xl font-bold bg-gradient-to-r from-blue-800 to-emerald-600 bg-clip-text text-transparent">VoteSeguro</span>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#funcionalidades" class="text-gray-700 hover:text-blue-600 transition-colors duration-300 font-medium">Funcionalidades</a>
                    <a href="#sobre" class="text-gray-700 hover:text-blue-600 transition-colors duration-300 font-medium">Sobre</a>
                    <a href="#contato" class="text-gray-700 hover:text-blue-600 transition-colors duration-300 font-medium">Contato</a>
                </div>
                
                <!-- Desktop Auth Buttons -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="/voteseguro/public/login.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-300 font-medium">Login</a>
                    <a href="/voteseguro/public/register.php" class="bg-gradient-to-r from-blue-600 to-emerald-500 text-white px-6 py-2 rounded-full font-semibold hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300">Registrar</a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors duration-300">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden mt-4 py-4 border-t border-gray-200">
                <div class="flex flex-col space-y-4">
                    <a href="#funcionalidades" class="text-gray-700 hover:text-blue-600 transition-colors duration-300 font-medium">Funcionalidades</a>
                    <a href="#sobre" class="text-gray-700 hover:text-blue-600 transition-colors duration-300 font-medium">Sobre</a>
                    <a href="#contato" class="text-gray-700 hover:text-blue-600 transition-colors duration-300 font-medium">Contato</a>
                    <hr class="border-gray-200">
                    <a href="/voteseguro/public/login.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-300 font-medium">Login</a>
                    <a href="/voteseguro/public/register.php" class="bg-gradient-to-r from-blue-600 to-emerald-500 text-white px-6 py-2 rounded-full font-semibold text-center">Registrar</a>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-6">
        <div class="container mx-auto text-center">
            <div class="opacity-0 animate-fade-in max-w-4xl mx-auto">
                <h1 class="text-5xl md:text-7xl font-extrabold mb-6 bg-gradient-to-r from-blue-800 via-blue-600 to-emerald-600 bg-clip-text text-transparent leading-tight">
                    Vote com Segurança,<br>Vote com VoteSeguro
                </h1>
                <p class="text-xl md:text-2xl text-gray-600 mb-8 font-medium leading-relaxed">
                    Plataforma digital para eleições transparentes e acessíveis
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="/voteseguro/public/register.php" class="bg-gradient-to-r from-blue-600 to-emerald-500 text-white px-8 py-4 rounded-full text-lg font-bold hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 min-w-48">
                        Comece Agora
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Funcionalidades Section -->
    <section id="funcionalidades" class="py-20 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 bg-gradient-to-r from-blue-800 to-emerald-600 bg-clip-text text-transparent">
                    Funcionalidades
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Descubra por que o VoteSeguro é a escolha ideal para eleições digitais modernas
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Card 1 -->
                <div class="opacity-0 animate-slide-in-left glass bg-white/60 border border-white/30 p-8 rounded-2xl shadow-lg hover:scale-105 hover:shadow-2xl transition-all duration-500 group">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Eleições Seguras</h3>
                    <p class="text-gray-600 leading-relaxed">Proteção avançada contra XSS e CSRF, garantindo a integridade de cada voto</p>
                </div>
                
                <!-- Card 2 -->
                <div class="opacity-0 animate-slide-in-left glass bg-white/60 border border-white/30 p-8 rounded-2xl shadow-lg hover:scale-105 hover:shadow-2xl transition-all duration-500 group" style="animation-delay: 0.1s;">
                    <div class="w-16 h-16 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 8h12a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Fácil de Usar</h3>
                    <p class="text-gray-600 leading-relaxed">Interface intuitiva que torna a votação simples para todos os usuários</p>
                </div>
                
                <!-- Card 3 -->
                <div class="opacity-0 animate-slide-in-right glass bg-white/60 border border-white/30 p-8 rounded-2xl shadow-lg hover:scale-105 hover:shadow-2xl transition-all duration-500 group" style="animation-delay: 0.2s;">
                    <div class="w-16 h-16 bg-gradient-to-r from-purple-600 to-purple-700 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Privadas ou Públicas</h3>
                    <p class="text-gray-600 leading-relaxed">Flexibilidade total para criar eleições públicas ou privadas conforme sua necessidade</p>
                </div>
                
                <!-- Card 4 -->
                <div class="opacity-0 animate-slide-in-right glass bg-white/60 border border-white/30 p-8 rounded-2xl shadow-lg hover:scale-105 hover:shadow-2xl transition-all duration-500 group" style="animation-delay: 0.3s;">
                    <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Resultados em Tempo Real</h3>
                    <p class="text-gray-600 leading-relaxed">Acompanhe os resultados instantaneamente com atualizações em tempo real</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Sobre Section -->
    <section id="sobre" class="py-20 px-6 bg-gradient-to-r from-blue-50 to-emerald-50">
        <div class="container mx-auto">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="opacity-0 animate-slide-in-left">
                    <h2 class="text-4xl md:text-5xl font-bold mb-8 bg-gradient-to-r from-blue-800 to-emerald-600 bg-clip-text text-transparent">
                        Sobre o VoteSeguro
                    </h2>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                        O VoteSeguro é uma plataforma revolucionária de votação digital que combina segurança de nível empresarial com uma experiência de usuário excepcional. Nossa missão é democratizar o acesso a eleições transparentes e confiáveis.
                    </p>
                    <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                        Com proteções avançadas contra ameaças cibernéticas e uma interface intuitiva, o VoteSeguro é a escolha ideal para organizações, escolas, empresas e comunidades que valorizam a transparência e a participação democrática.
                    </p>
                    <a href="#contato" class="inline-flex items-center bg-gradient-to-r from-blue-600 to-emerald-500 text-white px-8 py-4 rounded-full font-semibold hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300">
                        Saiba Mais
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                </div>
                
                <div class="opacity-0 animate-slide-in-right">
                    <div class="glass bg-white/60 border border-white/30 p-8 rounded-3xl shadow-2xl">
                        <!-- SVG Placeholder para Votação Digital -->
                        <svg class="w-full h-80" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="400" height="300" rx="20" fill="url(#grad1)"/>
                            <defs>
                                <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#E0F2FE;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#ECFDF5;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            
                            <!-- Computador/Tablet -->
                            <rect x="80" y="60" width="240" height="160" rx="12" fill="#374151" stroke="#6B7280" stroke-width="2"/>
                            <rect x="90" y="70" width="220" height="130" rx="8" fill="#F9FAFB"/>
                            
                            <!-- Tela de Votação -->
                            <rect x="110" y="90" width="180" height="20" rx="4" fill="#3B82F6"/>
                            <text x="115" y="105" font-family="Inter" font-size="12" fill="white" font-weight="600">ELEIÇÃO 2025</text>
                            
                            <!-- Candidatos -->
                            <rect x="110" y="120" width="180" height="15" rx="3" fill="#E5E7EB"/>
                            <circle cx="120" cy="127" r="4" fill="#10B981"/>
                            <text x="130" y="132" font-family="Inter" font-size="10" fill="#374151">Candidato A</text>
                            
                            <rect x="110" y="145" width="180" height="15" rx="3" fill="#E5E7EB"/>
                            <circle cx="120" cy="152" r="4" fill="#6B7280"/>
                            <text x="130" y="157" font-family="Inter" font-size="10" fill="#374151">Candidato B</text>
                            
                            <!-- Botão Votar -->
                            <rect x="230" y="170" width="50" height="20" rx="6" fill="url(#btnGrad)"/>
                            <text x="245" y="183" font-family="Inter" font-size="10" fill="white" font-weight="600">VOTAR</text>
                            
                            <defs>
                                <linearGradient id="btnGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#10B981;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            
                            <!-- Ícones de Segurança -->
                            <circle cx="350" cy="80" r="15" fill="#10B981" opacity="0.2"/>
                            <path d="M343 80l3 3 6-6" stroke="#10B981" stroke-width="2" fill="none" stroke-linecap="round"/>
                            
                            <circle cx="50" cy="120" r="12" fill="#3B82F6" opacity="0.2"/>
                            <path d="M44 120h12M50 114v12" stroke="#3B82F6" stroke-width="2" stroke-linecap="round"/>
                            
                            <!-- Elementos decorativos -->
                            <circle cx="320" cy="200" r="8" fill="#F59E0B" opacity="0.3"/>
                            <circle cx="60" cy="220" r="6" fill="#EF4444" opacity="0.3"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contato Section -->
    <section id="contato" class="py-20 px-6">
        <div class="container mx-auto max-w-4xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 bg-gradient-to-r from-blue-800 to-emerald-600 bg-clip-text text-transparent">
                    Entre em Contato
                </h2>
                <p class="text-xl text-gray-600">
                    Tem alguma dúvida? Estamos aqui para ajudar!
                </p>
            </div>
            <div class="opacity-0 animate-fade-in glass bg-white/60 border border-white/30 p-8 md:p-12 rounded-3xl shadow-2xl">
                <form id="contact-form" method="POST" action="https://formspree.io/f/movwdyqr" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 bg-white/80">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                            <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 bg-white/80">
                        </div>
                    </div>
                    <div>
                        <label for="mensagem" class="block text-sm font-semibold text-gray-700 mb-2">Mensagem *</label>
                        <textarea id="mensagem" name="mensagem" rows="6" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 bg-white/80 resize-none"></textarea>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="bg-gradient-to-r from-blue-600 to-emerald-500 text-white px-8 py-4 rounded-full text-lg font-bold hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300 min-w-48">
                            Enviar Mensagem
                        </button>
                    </div>
                    <div id="form-message" class="hidden text-center p-4 rounded-xl"></div>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="glass bg-white/70 border-t border-white/20 py-12 px-6">
        <div class="container mx-auto">
            <div class="grid md:grid-cols-3 gap-8 items-center">
                <div class="text-center md:text-left">
                    <div class="flex items-center justify-center md:justify-start space-x-3 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-emerald-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-lg font-bold bg-gradient-to-r from-blue-800 to-emerald-600 bg-clip-text text-transparent">VoteSeguro</span>
                    </div>
                    <p class="text-gray-600 text-sm">© 2025 VoteSeguro. Todos os direitos reservados.</p>
                </div>
                
                <div class="text-center">
                    <div class="flex justify-center space-x-6">
                        <a href="#" class="text-gray-600 hover:text-blue-600 transition-colors duration-300 text-sm font-medium">Termos</a>
                        <a href="#" class="text-gray-600 hover:text-blue-600 transition-colors duration-300 text-sm font-medium">Privacidade</a>
                    </div>
                </div>
                
                <div class="text-center md:text-right">
                    <div class="flex justify-center md:justify-end space-x-4">
                        <!-- Redes Sociais - Placeholders -->
                        <a href="#" class="w-10 h-10 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full flex items-center justify-center text-white hover:-translate-y-0.5 transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full flex items-center justify-center text-white hover:-translate-y-0.5 transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gradient-to-r from-purple-600 to-purple-700 rounded-full flex items-center justify-center text-white hover:-translate-y-0.5 transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.748.097.118.112.222.083.343-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.746-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.986C24.007 5.367 18.641.001.012.001z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // [Código existente para animações, mobile menu, smooth scroll, parallax, navbar transparência permanece inalterado]

            // Validação e envio do formulário de contato via AJAX
            const contactForm = document.getElementById('contact-form');
            const formMessage = document.getElementById('form-message');

            contactForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Validação básica
                const nome = document.getElementById('nome').value.trim();
                const email = document.getElementById('email').value.trim();
                const mensagem = document.getElementById('mensagem').value.trim();

                // Validar campos obrigatórios
                if (!nome || !email || !mensagem) {
                    showMessage('Por favor, preencha todos os campos obrigatórios.', 'error');
                    console.error('Erro de validação: Campos obrigatórios não preenchidos', { nome, email, mensagem });
                    return;
                }

                // Validar email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showMessage('Por favor, insira um email válido.', 'error');
                    console.error('Erro de validação: Email inválido', { email });
                    return;
                }

                // Feedback visual
                const submitBtn = contactForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Enviando...';
                submitBtn.disabled = true;

                try {
                    const formData = new FormData(contactForm);
                    const response = await fetch('https://formspree.io/f/movwdyqr', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (response.ok) {
                        showMessage('Mensagem enviada com sucesso! Entraremos em contato em breve.', 'success');
                        console.log('Formulário enviado com sucesso', { status: response.status, data: result });
                        contactForm.reset();
                    } else {
                        throw new Error(result.error || 'Erro ao enviar o formulário');
                    }
                } catch (error) {
                    showMessage('Erro ao enviar a mensagem. Tente novamente mais tarde.', 'error');
                    console.error('Erro ao enviar formulário', { error: error.message, status: response?.status });
                } finally {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            });

            function showMessage(text, type) {
                formMessage.textContent = text;
                formMessage.className = `text-center p-4 rounded-xl ${
                    type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'
                }`;
                formMessage.classList.remove('hidden');
                setTimeout(() => formMessage.classList.add('hidden'), 5000);
            }
        });
    </script>
</body>
</html>