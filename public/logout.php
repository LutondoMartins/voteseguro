<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

// Validar token CSRF para logout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf_token)) {
        error_log('Tentativa de logout com CSRF token inválido');
        header('Location: ' . BASE_PATH . '/index.php');
        exit;
    }

    // Destruir sessão
    session_unset();
    session_destroy();
    session_write_close();

    // Redirecionar para login
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
} else {
    // Se não for POST, redirecionar para index
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}
?>