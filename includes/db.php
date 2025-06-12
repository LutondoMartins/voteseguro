<?php
// Evitar acesso direto em produção
if (!defined('VOTESEGURO') && (!defined('ENVIRONMENT') || ENVIRONMENT === 'production')) {
    exit('Acesso direto não permitido.');
}

require_once 'config.php';

function getDBConnection() {
    // Criar conexão com o banco de dados
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Verificar falha na conexão
    if ($conn->connect_error) {
        // Logar erro em produção, sem exibir detalhes sensíveis
        error_log("Falha na conexão com o banco de dados: " . $conn->connect_error);
        if (ENVIRONMENT === 'production') {
            http_response_code(500);
            exit('Erro interno do servidor. Por favor, tente novamente mais tarde.');
        } else {
            exit('Falha na conexão com o banco de dados. Verifique as configurações.');
        }
    }

    // Definir conjunto de caracteres para utf8mb4
    if (!$conn->set_charset('utf8mb4')) {
        error_log("Erro ao definir charset utf8mb4: " . $conn->error);
    }

    return $conn;
}
?>