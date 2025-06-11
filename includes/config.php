<?php
session_start();

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voteseguro');

// Configuração do caminho base
define('BASE_PATH', '/voteseguro/public');
define('SITE_URL', 'http://localhost' . BASE_PATH);
?>