<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verifica se o usuário está logado
if (!isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

// Verifica se o ID da eleição foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$election_id = (int)$_GET['id'];
$conn = getDBConnection();
$errors = [];
$success = '';
$show_token_form = false;
$edit_mode = false;

// Consulta a eleição
$stmt = $conn->prepare("SELECT id, title, description, type, token, created_by, status FROM elections WHERE id = ?");
$stmt->bind_param("i", $election_id);
$stmt->execute();
$election = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$election) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

// Verifica acesso para eleição privada
$has_access = $election['type'] === 'public' || $election['created_by'] === $_SESSION['user_id'];
if ($election['type'] === 'private' && !$has_access) {
    if (isset($_GET['token']) || (isset($_POST['token']) && $_SERVER['REQUEST_METHOD'] === 'POST')) {
        $submitted_token = trim($_GET['token'] ?? $_POST['token']);
        if ($submitted_token === $election['token']) {
            // Registrar acesso
            $stmt = $conn->prepare("INSERT IGNORE INTO election_access (election_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $election_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            $_SESSION['election_access'][$election_id] = true;
            $has_access = true;
        } else {
            $errors[] = 'Token inválido.';
            $show_token_form = true;
        }
    } else {
        if (!isset($_SESSION['election_access'][$election_id])) {
            $show_token_form = true;
        } else {
            $has_access = true;
        }
    }
}

if (!$has_access && !$show_token_form) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

// Consulta candidatos
$stmt = $conn->prepare("SELECT id, name, description FROM candidates WHERE election_id = ?");
$stmt->bind_param("i", $election_id);
$stmt->execute();
$candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Verifica se o usuário já votou
$stmt = $conn->prepare("SELECT id FROM votes WHERE election_id = ? AND user_id = ?");
$stmt->bind_param("ii", $election_id, $_SESSION['user_id']);
$stmt->execute();
$has_voted = $stmt->get_result()->num_rows > 0;
$stmt->close();

// Processa voto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id']) && !$has_voted && $has_access && $election['status'] === 'active') {
    $candidate_id = (int)$_POST['candidate_id'];
    
    // Verifica se o candidato pertence à eleição
    $stmt = $conn->prepare("SELECT id FROM candidates WHERE id = ? AND election_id = ?");
    $stmt->bind_param("ii", $candidate_id, $election_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        
        // Insere o voto
        $stmt = $conn->prepare("INSERT INTO votes (election_id, candidate_id, user_id, voted_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iii", $election_id, $candidate_id, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $success = 'Voto registrado com sucesso!';
            $has_voted = true;
        } else {
            $errors[] = 'Erro ao registrar o voto.';
        }
        $stmt->close();
    } else {
        $errors[] = 'Candidato inválido.';
        $stmt->close();
    }
}

// Processa encerramento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_election']) && $election['created_by'] === $_SESSION['user_id']) {
    $stmt = $conn->prepare("UPDATE elections SET status = 'closed' WHERE id = ?");
    $stmt->bind_param("i", $election_id);
    if ($stmt->execute()) {
        $success = 'Eleição encerrada com sucesso!';
        $election['status'] = 'closed';
    } else {
        $errors[] = 'Erro ao encerrar a eleição.';
    }
    $stmt->close();
}

// Processa exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_election']) && $election['created_by'] === $_SESSION['user_id']) {
    $conn->begin_transaction();
    try {
        // Exclui votos
        $stmt = $conn->prepare("DELETE FROM votes WHERE election_id = ?");
        $stmt->bind_param("i", $election_id);
        $stmt->execute();
        $stmt->close();
        
        // Exclui candidatos
        $stmt = $conn->prepare("DELETE FROM candidates WHERE election_id = ?");
        $stmt->bind_param("i", $election_id);
        $stmt->execute();
        $stmt->close();
        
        // Exclui acesso
        $stmt = $conn->prepare("DELETE FROM election_access WHERE election_id = ?");
        $stmt->bind_param("i", $election_id);
        $stmt->execute();
        $stmt->close();
        
        // Exclui eleição
        $stmt = $conn->prepare("DELETE FROM elections WHERE id = ?");
        $stmt->bind_param("i", $election_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        header('Location: ' . BASE_PATH . '/my_elections.php');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = 'Erro ao excluir a eleição.';
    }
}

// Processa edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_election']) && $election['created_by'] === $_SESSION['user_id']) {
    $edit_mode = true;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit']) && $election['created_by'] === $_SESSION['user_id']) {
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
        $conn->begin_transaction();
        try {
            // Atualiza eleição
            $stmt = $conn->prepare("UPDATE elections SET title = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $title, $description, $election_id);
            $stmt->execute();
            $stmt->close();
            
            // Exclui candidatos existentes
            $stmt = $conn->prepare("DELETE FROM candidates WHERE election_id = ?");
            $stmt->bind_param("i", $election_id);
            $stmt->execute();
            $stmt->close();
            
            // Insere novos candidatos
            for ($i = 0; $i < count($candidates); $i++) {
                $candidate_name = trim($candidates[$i]);
                $candidate_desc = trim($candidate_descriptions[$i] ?? '');
                $stmt = $conn->prepare("INSERT INTO candidates (election_id, name, description) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $election_id, $candidate_name, $candidate_desc);
                $stmt->execute();
            }
            
            $conn->commit();
            $success = 'Eleição atualizada com sucesso!';
            $edit_mode = false;
            
            // Atualiza dados da eleição
            $stmt = $conn->prepare("SELECT id, title, description, type, token, created_by, status FROM elections WHERE id = ?");
            $stmt->bind_param("i", $election_id);
            $stmt->execute();
            $election = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Atualiza candidatos
            $stmt = $conn->prepare("SELECT id, name, description FROM candidates WHERE election_id = ?");
            $stmt->bind_param("i", $election_id);
            $stmt->execute();
            $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Erro ao atualizar a eleição.';
        }
    }
}

// Consulta resultados (apenas para criador)
$results = [];
if ($election['created_by'] === $_SESSION['user_id']) {
    $stmt = $conn->prepare("SELECT c.name, COUNT(v.id) as vote_count FROM candidates c LEFT JOIN votes v ON c.id = v.candidate_id WHERE c.election_id = ? GROUP BY c.id");
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
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
    <title>VoteSeguro - <?php echo htmlspecialchars($election['title']); ?></title>
    
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
                        <a href="<?php echo BASE_PATH; ?>/my_elections.php" class="px-4 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/50 transition-all duration-200">
                            Minhas Eleições
                        </a>
                        <a href="<?php echo ($_SESSION['role'] === 'admin') ? BASE_PATH . '/create_election.php' : BASE_PATH . '/create_private_election.php'; ?>" class="px-4 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/50 transition-all duration-200">
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
                    <a href="<?php echo BASE_PATH; ?>/my_elections.php" class="block px-3 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/30 transition-all duration-200">
                        Minhas Eleições
                    </a>
                    <a href="<?php echo ($_SESSION['role'] === 'admin') ? BASE_PATH . '/create_election.php' : BASE_PATH . '/create_private_election.php'; ?>" class="block px-3 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/30 transition-all duration-200">
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
                    <?php echo htmlspecialchars($election['title']); ?>
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    <?php echo htmlspecialchars($election['description'] ?? 'Sem descrição.'); ?>
                </p>
                <div class="mt-4 flex justify-center space-x-4 text-sm text-slate-500">
                    <span><?php echo $election['type'] === 'public' ? 'Pública' : 'Privada'; ?></span>
                    <span>|</span>
                    <span><?php echo getDaysRemaining($election_id); ?></span>
                    <span>|</span>
                    <span><?php echo $election['status'] === 'active' ? 'Ativa' : 'Encerrada'; ?></span>
                </div>
            </div>

            <!-- Token Form (for private elections) -->
            <?php if ($show_token_form): ?>
                <section class="voting-card rounded-2xl p-8 mb-8">
                    <h2 class="text-2xl font-bold text-slate-800 mb-4">Acessar Eleição Privada</h2>
                    <?php if (!empty($errors)): ?>
                        <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="token" class="block text-sm font-medium text-slate-700">Token da Eleição</label>
                            <input type="text" name="token" id="token" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn-primary text-white px-8 py-3 rounded-xl font-medium inline-flex items-center space-x-2">
                                <span>Acessar</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </section>
            <?php else: ?>
                <!-- Election Details -->
                <section class="voting-card rounded-2xl p-8 mb-8">
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
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($edit_mode): ?>
                        <h2 class="text-2xl font-bold text-slate-800 mb-4">Editar Eleição</h2>
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="save_edit" value="1">
                            <!-- Title -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-slate-700">Título da Eleição</label>
                                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($election['title']); ?>" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-slate-700">Descrição</label>
                                <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($election['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Candidates -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Candidatos</label>
                                <div id="candidates-container" class="space-y-4">
                                    <?php foreach ($candidates as $candidate): ?>
                                        <div class="candidate-group flex flex-col md:flex-row gap-4">
                                            <input type="text" name="candidates[]" value="<?php echo htmlspecialchars($candidate['name']); ?>" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                                            <input type="text" name="candidate_descriptions[]" value="<?php echo htmlspecialchars($candidate['description'] ?? ''); ?>" placeholder="Descrição (opcional)" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" onclick="addCandidate()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                                    Adicionar Candidato
                                </button>
                            </div>
                            
                            <!-- Submit -->
                            <div class="text-center space-x-4">
                                <button type="submit" class="btn-primary text-white px-8 py-3 rounded-xl font-medium inline-flex items-center space-x-2">
                                    <span>Salvar</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                                <a href="<?php echo BASE_PATH; ?>/election.php?id=<?php echo $election_id; ?>" class="px-8 py-3 bg-gray-500 text-white rounded-xl font-medium inline-flex items-center space-x-2">
                                    <span>Cancelar</span>
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <h2 class="text-2xl font-bold text-slate-800 mb-4">Candidatos</h2>
                        <?php if (empty($candidates)): ?>
                            <p class="text-slate-600">Nenhum candidato registrado.</p>
                        <?php else: ?>
                            <?php if (!$has_voted && $has_access && $election['created_by'] !== $_SESSION['user_id'] && $election['status'] === 'active'): ?>
                                <form method="POST" class="space-y-4">
                                    <?php foreach ($candidates as $candidate): ?>
                                        <div class="flex items-center space-x-3 p-4 border border-slate-200 rounded-lg">
                                            <input type="radio" name="candidate_id" value="<?php echo $candidate['id']; ?>" id="candidate_<?php echo $candidate['id']; ?>" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                            <label for="candidate_<?php echo $candidate['id']; ?>" class="flex-1">
                                                <p class="text-slate-800 font-medium"><?php echo htmlspecialchars($candidate['name']); ?></p>
                                                <p class="text-sm text-slate-600"><?php echo htmlspecialchars($candidate['description'] ?? 'Sem descrição.'); ?></p>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center">
                                        <button type="submit" class="btn-primary text-white px-8 py-3 rounded-xl font-medium inline-flex items-center space-x-2">
                                            <span>Votar</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <?php foreach ($candidates as $candidate): ?>
                                    <div class="p-4 border border-slate-200 rounded-lg mb-4">
                                        <p class="text-slate-800 font-medium"><?php echo htmlspecialchars($candidate['name']); ?></p>
                                        <p class="text-sm text-slate-600"><?php echo htmlspecialchars($candidate['description'] ?? 'Sem descrição.'); ?></p>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($has_voted): ?>
                                    <p class="text-center text-slate-600 mt-4">Você já votou nesta eleição.</p>
                                <?php elseif ($election['status'] === 'closed'): ?>
                                    <p class="text-center text-slate-600 mt-4">Esta eleição está encerrada.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
                
                <!-- Management (for creator) -->
                <?php if ($election['created_by'] === $_SESSION['user_id'] && !$edit_mode): ?>
                    <section class="voting-card rounded-2xl p-8 mb-8">
                        <h2 class="text-2xl font-bold text-slate-800 mb-4">Gerenciar Eleição</h2>
                        <div class="space-y-4">
                            <!-- Results -->
                            <div>
                                <h3 class="text-lg font-medium text-slate-800 mb-2">Resultados Parciais</h3>
                                <?php if (empty($results)): ?>
                                    <p class="text-slate-600">Nenhum voto registrado.</p>
                                <?php else: ?>
                                    <div class="space-y-2">
                                        <?php foreach ($results as $result): ?>
                                            <div class="flex justify-between items-center p-4 border border-slate-200 rounded-lg">
                                                <span class="text-slate-800"><?php echo htmlspecialchars($result['name']); ?></span>
                                                <span class="text-slate-600"><?php echo $result['vote_count']; ?> voto(s)</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex flex-col md:flex-row gap-4">
                                <form method="POST">
                                    <input type="hidden" name="edit_election" value="1">
                                    <button type="submit" class="btn-primary text-white px-6 py-3 rounded-xl font-medium inline-flex items-center space-x-2">
                                        <span>Editar</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="end_election" value="1">
                                    <button type="submit" class="btn-primary text-white px-6 py-3 rounded-xl font-medium inline-flex items-center space-x-2" <?php echo $election['status'] === 'closed' ? 'disabled' : ''; ?>>
                                        <span>Encerrar</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta eleição? Esta ação não pode ser desfeita.');">
                                    <input type="hidden" name="delete_election" value="1">
                                    <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-xl font-medium inline-flex items-center space-x-2 hover:bg-red-700">
                                        <span>Excluir</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
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
                window.location.href = '<?php echo BASE_PATH; ?>/logout.php';
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
    </script>
</body>
</html>