<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Inicializa variáveis
$error = '';
$username = '';

// Processa o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validação básica no servidor
    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Credenciais válidas, inicia sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Nome de utilizador ou palavra-passe incorretos.';
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
    <title>Iniciar Sessão - VoteSeguro</title>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Main Content -->
    <main class="min-h-screen flex items-center justify-center px-4 py-8 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 via-white to-blue-50">
        <div class="w-full max-w-md space-y-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 mb-6 bg-blue-800 rounded-2xl">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-blue-800 mb-2">VoteSeguro</h1>
                <p class="text-gray-600">Plataforma segura de votação digital</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-white/20 p-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl sm:text-xl font-bold text-gray-900 mb-2">Bem-vindo de volta</h2>
                    <p class="text-sm text-gray-600">Entre na sua conta para continuar</p>
                </div>

                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-800 rounded-xl text-sm">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form id="loginForm" action="login.php" method="POST" class="space-y-6">
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome de utilizador
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo htmlspecialchars($username); ?>"
                            required
                            class="w-full px-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-800 focus:border-transparent transition-all duration-200 text-base bg-gray-50/50 hover:bg-white focus:bg-white"
                            placeholder="Insira o seu nome de utilizador"
                        >
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Palavra-passe
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-800 focus:border-transparent transition-all duration-200 text-base bg-gray-50/50 hover:bg-white focus:bg-white"
                            placeholder="Insira a sua palavra-passe"
                        >
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full bg-gradient-to-r from-blue-800 to-blue-700 text-white py-4 px-4 rounded-xl font-medium hover:from-blue-700 hover:to-blue-600 focus:ring-2 focus:ring-blue-800 focus:ring-offset-2 transition-all duration-200 text-base shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                    >
                        Entrar
                    </button>
                </form>

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Não tem conta? 
                        <a href="register.php" class="text-blue-800 hover:text-blue-700 font-medium transition-colors duration-200">
                            Registe-se
                        </a>
                    </p>
                </div>

                <!-- Forgot Password Link -->
                <div class="mt-4 text-center">
                    <a href="#" class="text-sm text-blue-800 hover:text-blue-700 transition-colors duration-200">
                        Esqueceu-se da palavra-passe?
                    </a>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="bg-white/60 backdrop-blur-sm border border-blue-100 rounded-xl p-4 text-center">
                <p class="text-sm text-blue-800 flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                    </svg>
                    Conexão segura e encriptada
                </p>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // Get form values
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            // Basic client-side validation
            if (!username || !password) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                alert('O nome de utilizador deve ter pelo menos 3 caracteres.');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('A palavra-passe deve ter pelo menos 6 caracteres.');
                return;
            }
            
            // Show loading state
            const submitButton = document.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.textContent = 'A iniciar sessão...';
            submitButton.disabled = true;
            
            // Allow form submission to PHP
            setTimeout(() => {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }, 1500);
        });

        // Add input focus effects
        const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>