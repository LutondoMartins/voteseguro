<?php
// Evitar acesso direto em produção
if (!defined('VOTESEGURO') && (!defined('ENVIRONMENT') || ENVIRONMENT === 'production')) {
    exit('Acesso direto não permitido.');
}

require_once 'db.php';

/**
 * Verifica se o usuário está logado e a sessão é válida.
 * @return bool
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        return false;
    }
    // Verificar tempo de expiração da sessão (1 hora)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Verifica se o usuário é administrador.
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Gera um token seguro para uso em links de eleição ou outras finalidades.
 * @return string
 */
function generateToken() {
    try {
        return bin2hex(random_bytes(16));
    } catch (Exception $e) {
        error_log("Erro ao gerar token: " . $e->getMessage());
        if (ENVIRONMENT === 'production') {
            exit('Erro interno do servidor.');
        } else {
            exit('Erro ao gerar token seguro.');
        }
    }
}

/**
 * Verifica se o usuário já votou em uma eleição.
 * @param int $user_id
 * @param int $election_id
 * @return bool
 */
function hasVoted($user_id, $election_id) {
    // Validar entradas
    if (!is_numeric($user_id) || !is_numeric($election_id)) {
        return false;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ? AND election_id = ?");
    if (!$stmt) {
        error_log("Erro ao preparar consulta em hasVoted: " . $conn->error);
        return false;
    }
    $stmt->bind_param("ii", $user_id, $election_id);
    if (!$stmt->execute()) {
        error_log("Erro ao executar consulta em hasVoted: " . $stmt->error);
        $stmt->close();
        return false;
    }
    $result = $stmt->get_result();
    $has_voted = $result->num_rows > 0;
    $stmt->close();
    return $has_voted;
}

/**
 * Escapa dados para evitar XSS.
 * @param string $data
 * @return string
 */
function sanitize($data) {
    if (is_null($data)) {
        return '';
    }
    return htmlspecialchars(trim(strip_tags($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza e valida entradas específicas.
 * @param mixed $data
 * @param string $type (string, int, email)
 * @return mixed
 */
function sanitizeInput($data, $type = 'string') {
    if (is_null($data)) {
        return '';
    }
    switch ($type) {
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT) !== false ? (int)$data : 0;
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        case 'string':
        default:
            return sanitize($data);
    }
}
?>