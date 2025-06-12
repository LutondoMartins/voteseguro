<?php
// Definir constante para verificar inclusão
define('VOTESEGURO', true);

// Evitar acesso direto em produção
if (!defined('VOTESEGURO') && (!defined('ENVIRONMENT') || ENVIRONMENT === 'development')) {
    exit('Acesso direto não permitido.');
}

// Configurações de sessão seguras
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Mude para 1 em produção com HTTPS
ini_set('session.cookie_samesite', 'Strict');
session_start([
    'cookie_lifetime' => 3600, // 1 hora
    'gc_maxlifetime' => 3600,
]);

// Verificar ambiente (desenvolvimento ou produção)
$environment = 'development'; // Mude para 'production' em produção
define('ENVIRONMENT', $environment);

// Configurações do banco de dados
if ($environment === 'production') {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'seu_usuario_producao');
    define('DB_PASS', 'sua_senha_segura');
    define('DB_NAME', 'voteseguro');
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'voteseguro');
}

// Configuração do caminho base
define('BASE_PATH', '/voteseguro/public');
define('SITE_URL', 'http://localhost' . BASE_PATH); // Ajuste para HTTPS em produção

// Função para gerar token CSRF
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function validateCsrfToken($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Configurar cabeçalhos de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Evitar exposição de informações do PHP
ini_set('expose_php', 'off');
?>