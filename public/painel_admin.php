<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verifica se o usuário está logado e é administrador
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $conn = getDBConnection();
    header('Content-Type: application/json');

    if ($_POST['action'] === 'edit_election') {
        $election_id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $candidates = $_POST['candidates'] ?? [];
        $candidate_descriptions = $_POST['candidate_descriptions'] ?? [];

        // Validação
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'O título é obrigatório.']);
            exit;
        }
        if (count($candidates) < 2) {
            echo json_encode(['success' => false, 'message' => 'É necessário pelo menos dois candidatos.']);
            exit;
        }
        foreach ($candidates as $candidate) {
            if (empty(trim($candidate))) {
                echo json_encode(['success' => false, 'message' => 'Todos os nomes dos candidatos devem ser preenchidos.']);
                exit;
            }
        }

        // Atualizar eleição
        $stmt = $conn->prepare("UPDATE elections SET title = ?, description = ? WHERE id = ? AND created_by = ?");
        $stmt->bind_param("ssii", $title, $description, $election_id, $_SESSION['user_id']);
        if ($stmt->execute()) {
            // Deletar candidatos antigos
            $stmt = $conn->prepare("DELETE FROM candidates WHERE election_id = ?");
            $stmt->bind_param("i", $election_id);
            $stmt->execute();

            // Inserir novos candidatos
            for ($i = 0; $i < count($candidates); $i++) {
                $candidate_name = trim($candidates[$i]);
                $candidate_desc = trim($candidate_descriptions[$i] ?? '');
                $stmt = $conn->prepare("INSERT INTO candidates (election_id, name, description) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $election_id, $candidate_name, $candidate_desc);
                $stmt->execute();
            }
            echo json_encode(['success' => true, 'message' => 'Eleição atualizada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar a eleição.']);
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'delete_election') {
        $election_id = (int)($_POST['id'] ?? 0);
        // Deletar candidatos
        $stmt = $conn->prepare("DELETE FROM candidates WHERE election_id = ?");
        $stmt->bind_param("i", $election_id);
        $stmt->execute();

        // Deletar eleição
        $stmt = $conn->prepare("DELETE FROM elections WHERE id = ? AND created_by = ?");
        $stmt->bind_param("ii", $election_id, $_SESSION['user_id']);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Eleição excluída com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir a eleição.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit;
}

// Listar eleições
$conn = getDBConnection();
$elections = $conn->query("SELECT id, title, type, status, created_at FROM elections ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteSeguro - Painel do Administrador</title>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/index.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
                        <a href="<?php echo BASE_PATH; ?>/my_elections.php" class="px-4 py-2 text-slate-700 hover:text-blue-600 font-medium rounded-lg hover:bg-white/50 transition-all duration-200">
                            Minhas Eleições
                        </a>
                        <a href="<?php echo BASE_PATH; ?>/create_election.php" class="px-4 py-2 text-blue-600 font-medium rounded-lg bg-white/50">
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
                    <a href="<?php echo BASE_PATH; ?>/create_election.php" class="block px-3 py-2 text-blue-600 font-medium rounded-lg bg-white/30">
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
            <div class="text-center mb-12 animate-slide-in">
                <h1 class="text-4xl md:text-5xl font-bold mb-4 hero-text">
                    Painel do Administrador
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Gerencie todas as eleições do sistema VoteSeguro.
                </p>
            </div>

            <!-- Elections Section -->
            <section class="voting-card rounded-2xl p-8">
                <h2 class="text-2xl font-semibold mb-4">Eleições</h2>
                <?php if (empty($elections)): ?>
                    <div class="p-4 bg-yellow-100 text-yellow-700 rounded-lg">
                        Nenhuma eleição encontrada.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-3 text-left text-sm font-medium text-slate-700">Título</th>
                                    <th class="p-3 text-left text-sm font-medium text-slate-700">Tipo</th>
                                    <th class="p-3 text-left text-sm font-medium text-slate-700">Status</th>
                                    <th class="p-3 text-left text-sm font-medium text-slate-700">Criada em</th>
                                    <th class="p-3 text-left text-sm font-medium text-slate-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($elections as $election): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-3"><?php echo htmlspecialchars($election['title']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(ucfirst($election['type'])); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(ucfirst($election['status'])); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($election['created_at']))); ?></td>
                                        <td class="p-3">
                                            <button onclick="openEditModal(<?php echo $election['id']; ?>, '<?php echo htmlspecialchars(addslashes($election['title'])); ?>', '<?php echo htmlspecialchars(addslashes($election['description'] ?? '')); ?>')" class="text-blue-600 hover:underline">Editar</button>
                                            <button onclick="confirmDelete(<?php echo $election['id']; ?>)" class="text-red-600 hover:underline ml-2">Excluir</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Edit Election Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-2xl p-8 max-w-3xl w-full mx-4">
            <h2 class="text-2xl font-semibold mb-6">Editar Eleição</h2>
            <form id="editElectionForm" class="space-y-6">
                <input type="hidden" name="id" id="editElectionId">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700">Título da Eleição</label>
                    <input type="text" name="title" id="editTitle" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">Descrição</label>
                    <textarea name="description" id="editDescription" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                
                <!-- Candidates -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Candidatos</label>
                    <div id="editCandidatesContainer" class="space-y-4"></div>
                    <button type="button" onclick="addEditCandidate()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                        Adicionar Candidato
                    </button>
                </div>
                
                <!-- Buttons -->
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeEditModal()" class="px-6 py-2 bg-gray-300 text-slate-700 rounded-lg hover:bg-gray-400 transition-all duration-200">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

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

        // Open edit modal
        async function openEditModal(id, title, description) {
            const modal = document.getElementById('editModal');
            document.getElementById('editElectionId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = description;

            // Carregar candidatos via AJAX
            try {
                const response = await fetch('<?php echo BASE_PATH; ?>/get_candidates.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `election_id=${id}`
                });
                const candidates = await response.json();
                const container = document.getElementById('editCandidatesContainer');
                container.innerHTML = '';
                candidates.forEach(candidate => {
                    const div = document.createElement('div');
                    div.className = 'candidate-group flex flex-col md:flex-row gap-4';
                    div.innerHTML = `
                        <input type="text" name="candidates[]" value="${candidate.name}" placeholder="Nome do Candidato" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                        <input type="text" name="candidate_descriptions[]" value="${candidate.description || ''}" placeholder="Descrição (opcional)" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Remover</button>
                    `;
                    container.appendChild(div);
                });
                // Adicionar pelo menos dois candidatos
                if (candidates.length < 2) {
                    for (let i = candidates.length; i < 2; i++) {
                        addEditCandidate();
                    }
                }
                modal.classList.remove('hidden');
            } catch (error) {
                console.error('Erro ao carregar candidatos.');
            }
        }

        // Close edit modal
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Add candidate to edit modal
        function addEditCandidate() {
            const container = document.getElementById('editParticipantsContainer');
            const div = document.createElement('div');
            div.classList.add('candidate-group', 'flex', 'flex-col', 'md:flex-row', 'gap-4');
            div.innerHTML = `
                <input type="text" name="candidates[]" placeholder="Nome do Candidato" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                <input type="text" name="candidate_descriptions[]" placeholder="Descrição (opcional)" class="flex-1 rounded-lg border border-slate-300 p-3 focus:ring-blue-500 focus:border-blue-500">
                <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 bg-blue-600 text-red">
                    Remover text-white rounded-lg hover:bg-red-700
                </button>
            `;
            container.appendChild(div);
        }

        // Handle form submission via AJAX
        document.getElementById('editElectionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'edit_election');
            try {
                const response = fetch('<?php echo basename(__FILE__); ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success === true) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } catch {
                    Swal.fire({
                        title: 'Erro!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (error) {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro ao processar a solicitação.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });

        // Confirm deletion via SweetAlert
        async function confirmDelete(id) {
            const result = await Swal.fire({
                title: 'Tem certeza de que deseja excluir esta eleição?',
                text: 'Essa ação não poderá ser revertida!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sim, excluir!'
            });
            if (result.isConfirmed === true) {
                try {
                    const response = await fetch('<?php echo basename(__FILE__); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete_election_id=${id}`
                    });
                    const data = await response.json();
                    if (data.success === true) {
                        Swal.fire({
                            title: 'Sucesso!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Erro!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao excluir a eleição.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            }
        }
    </script>
</body>
</html>