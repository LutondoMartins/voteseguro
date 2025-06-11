<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verifica se o usuário está logado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Obtém conexão com o banco
$conn = getDBConnection();

// Consulta eleições criadas pelo usuário
$stmt = $conn->prepare("SELECT id, title, description, type FROM elections WHERE created_by = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$created_elections = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Consulta eleições privadas onde o usuário tem acesso
$stmt = $conn->prepare("SELECT DISTINCT e.id, e.title, e.description, e.type FROM elections e JOIN election_access ea ON e.id = ea.election_id WHERE ea.user_id = ? AND e.type = 'private'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$invited_elections = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Função para calcular votos
function getVoteCount($conn, $election_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM votes WHERE election_id = ?");
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['vote_count'];
}

// Função para calcular dias restantes
function getDaysRemaining($election_id) {
    return "Indeterminado";
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteSeguro - Minhas Eleições</title>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/index.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind Config -->
    <script src="../assets/js/tailwind_cnfig.js"></script>
</head>
<body class="font-inter bg-gradient-to-br from-slate-50 via-blue-50 to-emerald-50 min-h-screen relative">
    
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 animate-fade-in">
        <div class="glassmorphism border-b border-white/20 shadow-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo -->
                    <div class="flex-shrink-0 group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <a href="index.php" class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-emerald-600 bg-clip-text text-transparent">
                                VoteSeguro
                            </a>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="my_elections.php" class="px-4 py-2 text-blue-600 font-medium rounded-lg bg-white/50">
                            Minhas Eleições
                        </a>
                        <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'create_election.php' : 'create_private_election.php'; ?>" class="px-4 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/50 transition-all duration-200">
                            Criar Eleição
                        </a>
                        <button onclick="logout()" class="ml-4 px-6 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-full hover:from-red-600 hover:to-red-700 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            Sair
                        </button>
                    </nav>
                    
                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button onclick="toggleMobileMenu()" class="p-2 text-slate-700 hover:text-blue-600 hover:bg-white/50 rounded-lg transition-all duration-200">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile navigation -->
            <div id="mobile-menu" class="hidden md:hidden border-t border-white/20">
                <div class="px-4 pt-2 pb-3 space-y-1 bg-white/10">
                    <a href="my_elections.php" class="block px-3 py-2 text-blue-600 font-medium rounded-lg bg-white/30">
                        Minhas Eleições
                    </a>
                    <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'create_election.php' : 'create_private_election.php'; ?>" class="block px-3 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/30 transition-all duration-200">
                        Criar Eleição
                    </a>
                    <button onclick="logout()" class="w-full mt-2 px-3 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-300 font-medium text-left">
                        Sair
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-24 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Hero Section -->
            <div class="text-center mb-16 animate-slide-in">
                <div class="inline-flex items-center px-4 py-2 rounded-full glassmorphism-dark text-blue-700 text-sm font-medium mb-6">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse"></span>
                    Sistema de Votação Seguro
                </div>
                
                <h1 class="text-5xl md:text-7xl font-bold mb-6 hero-text leading-tight">
                    Minhas
                    <br>
                    <span class="relative">
                        Eleições
                        <div class="absolute -bottom-4 left-0 right-0 h-1 bg-gradient-to-r from-blue-600 to-emerald-600 rounded-full transform scale-x-0 animate-[scale-x-100_1s_ease-out_1s_forwards]"></div>
                    </span>
                </h1>
                
                <p class="text-xl md:text-2xl text-slate-600 max-w-3xl mx-auto mb-8 leading-relaxed">
                    Gerencie suas eleições criadas e participe das eleições privadas para as quais foi convidado.
                    <span class="text-emerald-600 font-semibold">VoteSeguro</span> garante segurança e transparência.
                </p>
            </div>

            <!-- Created Elections Section -->
            <section class="mb-12">
                <h2 class="text-3xl font-bold text-slate-800 mb-8">Eleições Criadas</h2>
                <?php if (empty($created_elections)): ?>
                    <p class="text-slate-600 text-center">Você ainda não criou nenhuma eleição.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php foreach ($created_elections as $election): ?>
                            <!-- Election Card -->
                            <div class="voting-card rounded-2xl card-hover p-8 group animate-slide-in">
                                <div class="flex items-start justify-between mb-6">
                                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-3 0H9m1.5-12H18m-6.75 0H9m-3.75 0L3 8.25M7.5 12H18m-10.5 0H9"></path>
                                        </svg>
                                    </div>
                                    <div class="status-badge text-xs font-semibold text-white px-3 py-1 rounded-full">
                                        <?php echo htmlspecialchars(getDaysRemaining($election['id'])); ?>
                                    </div>
                                </div>
                                
                                <h3 class="text-2xl font-bold text-slate-800 mb-4 group-hover:text-blue-600 transition-colors">
                                    <?php echo htmlspecialchars($election['title']); ?>
                                </h3>
                                
                                <p class="text-slate-600 mb-6 leading-relaxed">
                                    <?php echo htmlspecialchars($election['description']); ?>
                                </p>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3 text-sm text-slate-500">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                            </svg>
                                            <span><?php echo getVoteCount($conn, $election['id']); ?> votos</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span><?php echo $election['type'] === 'public' ? 'Pública' : 'Privada'; ?></span>
                                        </div>
                                    </div>
                                    <a href="election.php?id=<?php echo $election['id']; ?>" class="btn-primary text-white px-6 py-3 rounded-xl font-medium inline-flex items-center space-x-2">
                                        <span>Gerir</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Invited Elections Section -->
            <section>
                <h2 class="text-3xl font-bold text-slate-800 mb-8">Eleições Convidadas</h2>
                <?php if (empty($invited_elections)): ?>
                    <p class="text-slate-600 text-center">Você não foi convidado para nenhuma eleição privada.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php foreach ($invited_elections as $election): ?>
                            <!-- Election Card -->
                            <div class="voting-card rounded-2xl card-hover p-8 group animate-slide-in">
                                <div class="flex items-start justify-between mb-6">
                                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-3 0H9m1.5-12H18m-6.75 0H9m-3.75 0L3 8.25M7.5 12H18m-10.5 0H9"></path>
                                        </svg>
                                    </div>
                                    <div class="status-badge text-xs font-semibold text-white px-3 py-1 rounded-full">
                                        <?php echo htmlspecialchars(getDaysRemaining($election['id'])); ?>
                                    </div>
                                </div>
                                
                                <h3 class="text-2xl font-bold text-slate-800 mb-4 group-hover:text-blue-600 transition-colors">
                                    <?php echo htmlspecialchars($election['title']); ?>
                                </h3>
                                
                                <p class="text-slate-600 mb-6 leading-relaxed">
                                    <?php echo htmlspecialchars($election['description']); ?>
                                </p>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3 text-sm text-slate-500">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                            </svg>
                                            <span><?php echo getVoteCount($conn, $election['id']); ?> votos</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span>Privada</span>
                                        </div>
                                    </div>
                                    <a href="election.php?id=<?php echo $election['id']; ?>" class="btn-primary text-white px-6 py-3 rounded-xl font-medium inline-flex items-center space-x-2">
                                        <span>Participar</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative">
        <div class="glassmorphism-dark border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <!-- Bottom -->
                <div class="border-t border-white/10 pt-8 text-center">
                    <p class="text-slate-600">
                        © 2025 VoteSeguro. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Logout function
        function logout() {
            event.target.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> A sair...';
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 1000);
        }

        // Toggle mobile menu
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobile-menu');
            const menuButton = event.target.closest('button[onclick="toggleMobileMenu()"]');
            if (!menuButton && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Animate cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.voting-card');
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s ease-out';
                observer.observe(card);
            });
        });

        // Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add click animation to buttons
        document.querySelectorAll('.btn-primary').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    </script>
</body>
</html>