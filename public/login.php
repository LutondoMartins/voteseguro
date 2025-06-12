<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar se já está logado
if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

// Inicializa variáveis
$error = '';
$username = '';

// Inicializar contador de tentativas de login
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Verificar bloqueio por tentativas
if ($_SESSION['login_attempts'] >= 5 && time() < $_SESSION['lockout_time'] + 300) {
    $error = 'Conta bloqueada temporariamente. Tente novamente em ' . (300 - (time() - $_SESSION['lockout_time'])) . ' segundos.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf_token)) {
        $error = 'Erro de validação de formulário. Tente novamente.';
        error_log('Tentativa de login com CSRF token inválido: ' . $username);
    } else {
        $username = sanitizeInput(trim($_POST['username'] ?? ''), 'string');
        $password = trim($_POST['password'] ?? '');

        // Validação no servidor
        if (empty($username) || empty($password)) {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        } elseif (strlen($username) < 3 || strlen($password) < 6) {
            $error = 'Credenciais inválidas.';
        } else {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            if (!$stmt) {
                error_log("Erro ao preparar consulta em login: " . $conn->error);
                $error = 'Erro interno do servidor.';
            } else {
                $stmt->bind_param("s", $username);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();

                    if ($user && password_verify($password, $user['password'])) {
                        // Sucesso: resetar tentativas e iniciar sessão
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['lockout_time'] = 0;
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['last_activity'] = time();
                        error_log("Login bem-sucedido: " . $username);
                        header('Location: ' . BASE_PATH . '/index.php');
                        exit;
                    } else {
                        // Falha: incrementar tentativas
                        $_SESSION['login_attempts']++;
                        if ($_SESSION['login_attempts'] >= 5) {
                            $_SESSION['lockout_time'] = time();
                            $error = 'Conta bloqueada temporariamente. Tente novamente em 5 minutos.';
                        } else {
                            $error = 'Credenciais inválidas.';
                        }
                        error_log("Tentativa de login falhou: " . $username);
                    }
                } else {
                    error_log("Erro ao executar consulta em login: " . $stmt->error);
                    $error = 'Erro interno do servidor.';
                }
                $stmt->close();
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
        .focused input {
            box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Main Content -->
    <main class="min-h-screen flex items-center justify-center px-4 py-8 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 via-white to-blue-50">
        <div class="w-full max-w-md space-y-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                </div>
                <h1 class="text-3xl font-bold text-blue-800 mb-2">VoteSeguro</h1>
                <p class="text-sm text-gray-600">Plataforma segura de votação digital</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-white/20 p-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-2">Bem-vindo</h2>
                    <p class="text-sm text-gray-600">Entre na sua conta para continuar</p>
                </div>

                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 border border-red-200 bg-red-100 rounded-xl text-sm text-red-600">
                    <?php echo sanitize($error); ?>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form id="loginForm" action="<?php echo htmlspecialchars(BASE_PATH . '/login.php'); ?>" method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                    
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome de utilizador
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo sanitize($username); ?>"
                            required
                            autocomplete="username"
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
                            autocomplete="current-password"
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
                        <a href="<?php echo htmlspecialchars(BASE_PATH . '/register.php'); ?>" class="text-blue-800 hover:text-blue-700 font-medium transition-colors duration-200">
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
            e.preventDefault(); // Prevenir envio padrão

            // Obter valores do formulário
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const submitButton = document.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;

            // Validação no cliente
            if (!username || !password) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            if (username.length < 3) {
                alert('O nome de utilizador deve ter pelo menos 3 caracteres.');
                return;
            }
            if (password.length < 6) {
                alert('A palavra-passe deve ter pelo menos 6 caracteres.');
                return;
            }

            // Mostrar estado de carregamento
            submitButton.textContent = 'A iniciar sessão...';
            submitButton.disabled = true;

            // Enviar formulário
            this.submit();

            // Restaurar botão após um tempo (caso haja erro no servidor)
            setTimeout(() => {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }, 1500);
        });

        // Efeitos de foco nos inputs
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
?>