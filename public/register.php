<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Inicializa variáveis
$error = '';
$success = '';
$username = '';

// Processa o formulário de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');
    $terms = isset($_POST['terms']);

    // Validação no servidor
    if (empty($username) || empty($password) || empty($confirmPassword)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!$terms) {
        $error = 'Por favor, aceite os Termos e Condições para continuar.';
    } elseif (strlen($username) < 3) {
        $error = 'O nome de utilizador deve ter pelo menos 3 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $error = 'O nome de utilizador só pode conter letras e números, sem espaços ou caracteres especiais.';
    } elseif (strlen($password) < 8) {
        $error = 'A palavra-passe deve ter pelo menos 8 caracteres.';
    } elseif ($password !== $confirmPassword) {
        $error = 'As palavras-passe não coincidem. Por favor, verifique e tente novamente.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'A palavra-passe deve conter pelo menos uma letra maiúscula, uma minúscula e um número.';
    } else {
        $conn = getDBConnection();

        // Verifica se o username já existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $error = 'O nome de utilizador já está em uso. Escolha outro.';
        } else {
            // Criptografa a senha
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insere o novo usuário
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, 'voter', NOW())");
            $stmt->bind_param("ss", $username, $hashedPassword);
            if ($stmt->execute()) {
                $success = 'Conta criada com sucesso! Redirecionando para o login...';
                // Redireciona após 2 segundos
                header('Refresh: 2; URL=login.php');
            } else {
                $error = 'Erro ao criar a conta. Tente novamente.';
            }
            $stmt->close();
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Conta - VoteSeguro</title>
    
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
<body class="bg-gray-100 min-h-screen">
    <!-- Main Content -->
    <main class="min-h-screen flex items-center justify-center px-4 py-8 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 via-white to-green-50">
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

            <!-- Registration Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-white/20 p-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl sm:text-xl font-bold text-gray-900 mb-2">Criar nova conta</h2>
                    <p class="text-sm text-gray-600">Junte-se à plataforma VoteSeguro</p>
                </div>

                <!-- Error or Success Message -->
                <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-800 rounded-xl text-sm">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php elseif (!empty($success)): ?>
                <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-800 rounded-xl text-sm">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form id="registerForm" action="register.php" method="POST" class="space-y-6">
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
                            placeholder="Escolha um nome de utilizador"
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
                            placeholder="Crie uma palavra-passe segura"
                        >
                    </div>

                    <!-- Confirm Password Field -->
                    <div>
                        <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirmar Palavra-passe
                        </label>
                        <input 
                            type="password" 
                            id="confirmPassword" 
                            name="confirmPassword" 
                            required
                            class="w-full px-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-800 focus:border-transparent transition-all duration-200 text-base bg-gray-50/50 hover:bg-white focus:bg-white"
                            placeholder="Confirme a sua palavra-passe"
                        >
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="flex items-start">
                        <input 
                            type="checkbox" 
                            id="terms" 
                            name="terms" 
                            required
                            class="mt-1 h-4 w-4 text-blue-800 focus:ring-blue-800 border-gray-300 rounded"
                        >
                        <label for="terms" class="ml-3 text-sm text-gray-600">
                            Aceito os <a href="#" class="text-blue-800 hover:text-blue-700 font-medium">Termos e Condições</a> e a <a href="#" class="text-blue-800 hover:text-blue-700 font-medium">Política de Privacidade</a>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full bg-gradient-to-r from-blue-800 to-blue-700 text-white py-4 px-4 rounded-xl font-medium hover:from-blue-700 hover:to-blue-600 focus:ring-2 focus:ring-blue-800 focus:ring-offset-2 transition-all duration-200 text-base shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                    >
                        Registar
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Já tem conta? 
                        <a href="login.php" class="text-blue-800 hover:text-blue-700 font-medium transition-colors duration-200">
                            Inicie sessão
                        </a>
                    </p>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="bg-white/60 backdrop-blur-sm border border-green-100 rounded-xl p-4 text-center">
                <p class="text-sm text-green-700 flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Registo seguro e protegido
                </p>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            // Get form values
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirmPassword').value.trim();
            const termsAccepted = document.getElementById('terms').checked;
            
            // Basic client-side validation
            if (!username || !password || !confirmPassword) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            if (!termsAccepted) {
                e.preventDefault();
                alert('Por favor, aceite os Termos e Condições para continuar.');
                return;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                alert('O nome de utilizador deve ter pelo menos 3 caracteres.');
                return;
            }
            
            if (!/^[a-zA-Z0-9]+$/.test(username)) {
                e.preventDefault();
                alert('O nome de utilizador só pode conter letras e números, sem espaços ou caracteres especiais.');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('A palavra-passe deve ter pelo menos 8 caracteres.');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('As palavras-passe não coincidem. Por favor, verifique e tente novamente.');
                return;
            }
            
            if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password)) {
                e.preventDefault();
                alert('A palavra-passe deve conter pelo menos uma letra maiúscula, uma minúscula e um número.');
                return;
            }
            
            // Show loading state
            const submitButton = document.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.textContent = 'A registar...';
            submitButton.disabled = true;
            
            // Allow form submission to PHP
            setTimeout(() => {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }, 2000);
        });

        // Real-time password confirmation validation
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('As palavras-passe não coincidem');
                this.classList.add('border-red-300');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-300');
            }
        });

        // Add input focus effects
        const inputs = document.querySelectorAll('input, select');
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