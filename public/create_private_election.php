<?php
// Definir constante para verificar inclusão
// define('VOTESEGURO', true);

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verifica se o usuário está logado e é eleitor
if (!isLoggedIn() || $_SESSION['role'] !== 'voter') {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$errors = [];
$success = '';
$access_link = '';
$title = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Token CSRF inválido.';
    } else {
        $title = sanitize(trim($_POST['title'] ?? ''));
        $description = sanitize(trim($_POST['description'] ?? ''));
        $candidates = array_map(function($c) { return sanitize(trim($c)); }, $_POST['candidates'] ?? []);
        $candidate_descriptions = array_map(function($c) { return sanitize(trim($c)); }, $_POST['candidate_descriptions'] ?? []);

        // Validação
        if (empty($title)) {
            $errors[] = 'O título é obrigatório.';
        }
        if (count($candidates) < 2) {
            $errors[] = 'É necessário pelo menos dois candidatos.';
        }
        foreach ($candidates as $candidate) {
            if (empty(trim($candidate))) {
                $errors[] = 'Todos os nomes dos candidatos devem ser preenchidos.';
                break;
            }
        }

        if (empty($errors)) {
            $conn = getDBConnection();
            
            // Gerar token único
            $token = generateToken();
            if (empty($token)) {
                error_log("Falha ao gerar token em create_private_election.php");
                $errors[] = 'Erro ao gerar token. Tente novamente.';
            } else {
                // Log para debugging
                error_log("Token gerado: $token");
                
                // Inserir eleição
                $stmt = $conn->prepare("INSERT INTO elections (title, description, type, token, created_by, created_at, status) VALUES (?, ?, 'private', ?, ?, NOW(), 'active')");
                if (!$stmt) {
                    error_log("Erro ao preparar inserção da eleição: " . $conn->error);
                    $errors[] = 'Erro ao preparar a eleição.';
                } else {
                    $stmt->bind_param("sssi", $title, $description, $token, $_SESSION['user_id']);
                    if ($stmt->execute()) {
                        $election_id = $conn->insert_id;
                        
                        // Inserir candidatos
                        $stmt_candidates = $conn->prepare("INSERT INTO candidates (election_id, name, description) VALUES (?, ?, ?)");
                        if (!$stmt_candidates) {
                            error_log("Erro ao preparar inserção de candidato: " . $conn->error);
                            $errors[] = 'Erro ao adicionar candidato.';
                        } else {
                            for ($i = 0; $i < count($candidates); $i++) {
                                $candidate_name = trim($candidates[$i]);
                                $candidate_desc = trim($candidate_descriptions[$i] ?? '');
                                $stmt_candidates->bind_param("iss", $election_id, $candidate_name, $candidate_desc);
                                if (!$stmt_candidates->execute()) {
                                    error_log("Erro ao inserir candidato: " . $stmt_candidates->error);
                                    $errors[] = 'Erro ao adicionar candidato: ' . $candidate_name;
                                    break;
                                }
                            }
                            $stmt_candidates->close();
                        }
                        
                        if (empty($errors)) {
                            $access_link = SITE_URL . "/election.php?id=$election_id&token=" . urlencode($token);
                            $success = 'Eleição privada criada com sucesso! Compartilhe o link de acesso abaixo.';
                            // Log para confirmar sucesso
                            error_log("Eleição criada: ID=$election_id, Token=$token, Link=$access_link");
                        }
                        $stmt->close();
                    } else {
                        error_log("Erro ao executar inserção da eleição: " . $stmt->error);
                        $errors[] = 'Erro ao criar a eleição: ' . $stmt->error;
                    }
                }
            }
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>VoteSeguro - Criar Eleição Privada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/index.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/tailwind_config.js"></script>
</head>
<body class="font-inter bg-gradient-to-br from-slate-50 via-blue-50 to-emerald-50 min-h-screen relative">
    <header class="fixed top-0 left-0 right-0 z-50 animate-fade-in">
        <div class="glassmorphism border-b border-white/20 shadow-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex-shrink-0 group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <a href="<?php echo htmlspecialchars(BASE_PATH . '/index.php'); ?>" class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-emerald-600 bg-clip-text text-transparent">
                                VoteSeguro
                            </a>
                        </div>
                    </div>
                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="<?php echo htmlspecialchars(BASE_PATH . '/my_elections.php'); ?>" class="px-4 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/50 transition-all duration-200">
                            Minhas Eleições
                        </a>
                        <a href="<?php echo htmlspecialchars(BASE_PATH . '/create_private_election.php'); ?>" class="px-4 py-2 text-blue-600 font-medium rounded-lg bg-white/50">
                            Criar Eleição
                        </a>
                        <form action="<?php echo htmlspecialchars(BASE_PATH . '/logout.php'); ?>" method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                            <button type="submit" class="ml-4 px-6 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-full hover:from-red-600 hover:to-red-800 transition-all duration-300 font-medium shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                Sair
                            </button>
                        </form>
                    </nav>
                    <div class="md:hidden">
                        <button onclick="toggleMobileMenu()" class="p-2 text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div id="mobile-menu" class="hidden md:hidden border-t border-white/20">
                <div class="px-4 pt-2 pb-3 space-y-1 bg-white/10">
                    <a href="<?php echo htmlspecialchars(BASE_PATH . '/my_elections.php'); ?>" class="block px-3 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/30 transition-all duration-200">
                        Minhas Eleições
                    </a>
                    <a href="<?php echo htmlspecialchars(BASE_PATH . '/create_private_election.php'); ?>" class="block px-3 py-2 text-blue-600 font-medium rounded-lg bg-white/30">
                        Criar Eleição
                    </a>
                    <form action="<?php echo htmlspecialchars(BASE_PATH . '/logout.php'); ?>" method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                        <button type="submit" class="w-full mt-2 px-3 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-600 hover:to-red-800 transition-all duration-300 font-medium text-left">
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="pt-24 pb-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 animate-slide-in">
                <h1 class="text-4xl md:text-5xl font-bold mb-4 hero-text">
                    Criar Eleição Privada
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Configure sua eleição privada e gere um link para compartilhar com os eleitores.
                </p>
            </div>

            <section class="voting-card rounded-2xl p-8">
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo sanitize($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                        <?php echo sanitize($success); ?>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-green-700">Link de Acesso</label>
                            <div class="flex items-center mt-1">
                                <input type="text" value="<?php echo htmlspecialchars($access_link); ?>" readonly class="flex-1 rounded-l-lg border border-green-300 p-3 bg-white">
                                <button type="button" onclick="copyLink(this)" class="btn-primary text-white px-4 py-3 rounded-r-lg">
                                    Copiar
                                </button>
                            </div>
                        </div>
                        <p class="mt-2">Você será redirecionado em 5 segundos ou <a href="<?php echo htmlspecialchars(BASE_PATH . '/my_elections.php'); ?>" class="text-blue-600 hover:underline">clique aqui</a>.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                        <div>
                            <label for="title" class="block text-sm font-medium text-slate-700">Título da Eleição</label>
                            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700">Descrição</label>
                            <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Candidatos</label>
                            <div id="candidates-container" class="space-y-4">
                                <div class="candidate-group flex flex-col md:flex-row gap-4">
                                    <input type="text" name="candidates[]" placeholder="Nome do Candidato" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                                    <input type="text" name="candidate_descriptions[]" placeholder="Descrição (opcional)" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="candidate-group flex flex-col md:flex-row gap-4">
                                    <input type="text" name="candidates[]" placeholder="Nome do Candidato" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                                    <input type="text" name="candidate_descriptions[]" placeholder="Descrição (opcional)" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            <button type="button" onclick="addCandidate()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                                Adicionar Candidato
                            </button>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn-primary text-white px-8 py-3 rounded-xl font-medium inline-flex items-center space-x-2">
                                <span>Criar Eleição</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="relative">
        <div class="glassmorphism-dark border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="border-t border-white/10 pt-8 text-center">
                    <p class="text-sm text-slate-600">
                        © 2025 VoteSeguro. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobile-menu');
            const menuButton = event.target.closest('button[onclick="toggleMobileMenu()"]');
            if (!menuButton && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        function addCandidate() {
            const container = document.getElementById('candidates-container');
            const div = document.createElement('div');
            div.className = 'candidate-group flex flex-col md:flex-row gap-4';
            div.innerHTML = `
                <input type="text" name="candidates[]" placeholder="Nome do Candidato" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                <input type="text" name="candidate_descriptions[]" placeholder="Descrição (opcional)" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
            `;
            container.appendChild(div);
        }

        function copyLink(button) {
            const input = button.previousElementSibling;
            input.select();
            document.execCommand('copy');
            button.textContent = 'Copiado!';
            setTimeout(() => {
                button.textContent = 'Copiar';
            }, 2000);
        }

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
            anchor.addEventListener('click', function(e) {
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
                
                ripple.style.width = ripple.style.height = `${size}px`;
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
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