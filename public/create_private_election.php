<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verifica se o usuário está logado e é eleitor
if (!isLoggedIn() || $_SESSION['role'] !== 'voter') {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = '';
$access_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $candidates = $_POST['candidates'] ?? [];
    $candidate_descriptions = $_POST['candidate_descriptions'] ?? [];

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
        $token = md5(uniqid(rand(), true));
        
        // Inserir eleição
        $stmt = $conn->prepare("INSERT INTO elections (title, description, type, token, created_by, created_at, status) VALUES (?, ?, 'private', ?, ?, NOW(), 'active')");
        $stmt->bind_param("sssi", $title, $description, $token, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $election_id = $conn->insert_id;
            
            // Inserir candidatos
            for ($i = 0; $i < count($candidates); $i++) {
                $candidate_name = trim($candidates[$i]);
                $candidate_desc = trim($candidate_descriptions[$i] ?? '');
                $stmt = $conn->prepare("INSERT INTO candidates (election_id, name, description) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $election_id, $candidate_name, $candidate_desc);
                $stmt->execute();
            }
            
            // Gerar link de acesso
            $access_link = SITE_URL . "/election.php?id=$election_id&token=" . urlencode($token);
            $success = 'Eleição criada com sucesso! Compartilhe o link de acesso abaixo.';
            // Redirecionar após 5 segundos
            header('Refresh: 5; URL=my_elections.php');
        } else {
            $errors[] = 'Erro ao criar a eleição. Tente novamente.';
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
    <title>VoteSeguro - Criar Eleição Privada</title>
    
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
                            <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-emerald-600 bg-clip-text text-transparent">
                                VoteSeguro
                            </h1>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="my_elections.php" class="px-4 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/50 transition-all duration-200">
                            Minhas Eleições
                        </a>
                        <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'create_election.php' : 'create_private_election.php'; ?>" class="px-4 py-2 text-blue-600 font-medium rounded-lg bg-white/50">
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
                    <a href="my_elections.php" class="block px-3 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/30 transition-all duration-200">
                        Minhas Eleições
                    </a>
                    <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'create_election.php' : 'create_private_election.php'; ?>" class="block px-3 py-2 text-blue-600 font-medium rounded-lg bg-white/30">
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
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Hero Section -->
            <div class="text-center mb-12 animate-slide-in">
                <h1 class="text-4xl md:text-5xl font-bold mb-4 hero-text">
                    Criar Eleição Privada
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Configure sua eleição privada e gere um link para compartilhar com os eleitores.
                </p>
            </div>

            <!-- Form Section -->
            <section class="voting-card rounded-2xl p-8">
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                        <?php echo $success; ?>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-green-700">Link de Acesso</label>
                            <div class="flex items-center mt-1">
                                <input type="text" value="<?php echo htmlspecialchars($access_link); ?>" readonly class="flex-1 rounded-l-lg border border-green-300 p-3 bg-white">
                                <button onclick="copyLink(this)" class="btn-primary text-white px-4 py-3 rounded-r-lg">
                                    Copiar
                                </button>
                            </div>
                        </div>
                        <p class="mt-2">Redirecionando em 5 segundos...</p>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-slate-700">Título da Eleição</label>
                            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700">Descrição</label>
                            <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Candidates -->
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
                        
                        <!-- Submit -->
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

    <!-- Footer -->
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

        // Add candidate input fields
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

        // Copy link to clipboard
        function copyLink(button) {
            const input = button.previousElementSibling;
            input.select();
            document.execCommand('copy');
            button.textContent = 'Copiado!';
            setTimeout(() => {
                button.textContent = 'Copiar';
            }, 2000);
        }
    </script>
</body>
</html>