<?php
// Definir constante para verificar inclusão
// define('VOTESEGURO', true);
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verifica se o usuário está logado e é administrador
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

// Validar CSRF para requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = sanitizeInput($_POST['csrf_token'] ?? '', 'string');
    if (!validateCsrfToken($csrf_token)) {
        header('HTTP/1.1 403 Forbidden');
        exit('Token CSRF inválido.');
    }
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '', 'string');
    $description = sanitizeInput($_POST['description'] ?? '', 'string');
    $candidates = array_map(function($c) { return sanitizeInput($c, 'string'); }, $_POST['candidates'] ?? []);
    $candidate_descriptions = array_map(function($c) { return sanitizeInput($c, 'string'); }, $_POST['candidate_descriptions'] ?? []);

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
        
        $stmt = $conn->prepare("INSERT INTO elections (title, description, type, created_by, created_at, status) VALUES (?, ?, 'public', ?, NOW(), 'active')");
        $stmt->bind_param("ssi", $title, $description, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $election_id = $conn->insert_id;
            
            for ($i = 0; $i < count($candidates); $i++) {
                $candidate_name = $candidates[$i];
                $candidate_desc = $candidate_descriptions[$i] ?? '';
                $stmt = $conn->prepare("INSERT INTO candidates (election_id, name, description) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $election_id, $candidate_name, $candidate_desc);
                $stmt->execute();
                $stmt->close();
            }
            
            $success = 'Eleição pública criada com sucesso!';
            header('Refresh: 5; URL=' . SITE_URL . '/my_elections.php');
        } else {
            $errors[] = 'Erro ao criar a eleição. Tente novamente.';
            if (ENVIRONMENT === 'production') {
                error_log("Erro ao criar eleição: " . $stmt->error);
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteSeguro - Criar Eleição Pública</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/index.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/tailwind_cnfig.js"></script>
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
                            <a href="index.php" class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-emerald-600 bg-clip-text text-transparent">
                                VoteSeguro
                            </a>
                        </div>
                    </div>
                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="<?php echo SITE_URL; ?>/my_elections.php" class="px-4 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/50 transition-all duration-200">
                            Minhas Eleições
                        </a>
                        <a href="<?php echo SITE_URL; ?>/create_election.php" class="px-4 py-2 text-blue-600 font-medium rounded-lg bg-white/50">
                            Criar Eleição
                        </a>
                        <button onclick="logout()" class="ml-4 px-6 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-full hover:from-red-600 hover:to-red-700 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            Sair
                        </button>
                    </nav>
                    <div class="md:hidden">
                        <button onclick="toggleMobileMenu()" class="p-2 text-slate-700 hover:text-blue-600 hover:bg-white/50 rounded-lg transition-all duration-200">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div id="mobile-menu" class="hidden md:hidden border-t border-white/20">
                <div class="px-4 pt-2 pb-3 space-y-1 bg-white/10">
                    <a href="<?php echo SITE_URL; ?>/my_elections.php" class="block px-3 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/30 transition-all duration-200">
                        Minhas Eleições
                    </a>
                    <a href="<?php echo SITE_URL; ?>/create_election.php" class="block px-3 py-2 text-blue-600 font-medium rounded-lg bg-white/30">
                        Criar Eleição
                    </a>
                    <button onclick="logout()" class="w-full mt-2 px-3 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-300 font-medium text-left">
                        Sair
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="pt-24 pb-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 animate-slide-in">
                <h1 class="text-4xl md:text-5xl font-bold mb-4 hero-text">
                    Criar Eleição Pública
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Configure sua eleição pública para votação aberta a todos os eleitores.
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
                        <p class="mt-2">Redirecionando em 5 segundos...</p>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <div>
                            <label for="title" class="block text-sm font-medium text-slate-700">Título da Eleição</label>
                            <input type="text" name="title" id="title" value="<?php echo sanitize($title ?? ''); ?>" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700">Descrição</label>
                            <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500"><?php echo sanitize($description ?? ''); ?></textarea>
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
                    <p class="text-slate-600">
                        © 2025 VoteSeguro. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function logout() {
            event.target.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> A sair...';
            setTimeout(() => {
                window.location.href = '<?php echo SITE_URL; ?>/logout.php';
            }, 1000);
        }

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
    </script>
</body>
</html>