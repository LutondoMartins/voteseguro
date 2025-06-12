VoteSeguro - Plataforma Segura de Votação Digital
Visão Geral
O VoteSeguro é uma plataforma web desenvolvida para realizar eleições digitais de forma segura, transparente e acessível. O sistema permite a criação de eleições públicas e privadas, votação por usuários autenticados, gerenciamento de candidatos, e visualização de resultados parciais (apenas para administradores ou criadores da eleição). Construído com PHP, MySQL, Tailwind CSS e JavaScript, o VoteSeguro incorpora medidas de segurança como proteção contra XSS (Cross-Site Scripting) e CSRF (Cross-Site Request Forgery) para garantir a integridade do processo eleitoral.
Funcionalidades Principais

Autenticação de Usuários:
Cadastro e login seguros com validação de senha robusta.
Suporte a dois papéis: voter (eleitor) e admin (administrador).


Criação de Eleições:
Eleições públicas ou privadas com token de acesso para eleições privadas.
Configuração de título, descrição e candidatos.
Geração de links compartilháveis para eleições privadas.


Votação:
Eleitores podem votar uma única vez por eleição.
Interface intuitiva com seleção de candidatos via rádio buttons.


Gerenciamento de Eleições:
Criadores podem editar, encerrar ou excluir eleições.
Visualização de resultados parciais em tempo real (apenas para criadores).


Segurança:
Proteção contra XSS com sanitização de entradas e saídas.
Proteção contra CSRF com tokens em formulários.
Sessões seguras com configurações de cookies (httponly, samesite=Strict).
Logs de erros em produção para depuração segura.



Requisitos
Tecnologias

Servidor: PHP 7.4 ou superior
Banco de Dados: MySQL 5.7 ou superior
Web Server: Apache (recomendado com XAMPP para desenvolvimento)
Frontend: Tailwind CSS (via CDN), Google Fonts (Inter)
JavaScript: Vanilla JS (sem dependências externas)

Ambiente

XAMPP (para desenvolvimento local)
Navegador moderno (Chrome, Firefox, Edge, etc.)
Conexão com a internet para carregar Tailwind CSS e Google Fonts

Instalação

Clonar o Repositório:
git clone https://github.com/lutondomartins/voteseguro.git
cd voteseguro


Configurar o Ambiente:

Copie o projeto para C:\xampp\htdocs\voteseguro (ou equivalente no seu servidor).
Inicie o Apache e MySQL no XAMPP Control Panel.


Configurar o Banco de Dados:

Crie um banco de dados chamado voteseguro no MySQL:
CREATE DATABASE voteseguro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


Importe o esquema do banco (se disponível em database.sql) ou crie as tabelas manualmente:
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('voter', 'admin') NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE elections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('public', 'private') NOT NULL,
    token VARCHAR(32),
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    status ENUM('active', 'closed') NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    FOREIGN KEY (election_id) REFERENCES elections(id)
);

CREATE TABLE votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    candidate_id INT NOT NULL,
    user_id INT NOT NULL,
    voted_at DATETIME NOT NULL,
    FOREIGN KEY (election_id) REFERENCES elections(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE (election_id, user_id)
);

CREATE TABLE election_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (election_id) REFERENCES elections(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE (election_id, user_id)
);




Configurar o Arquivo config.php:

Abra includes/config.php e ajuste as credenciais do banco:
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voteseguro');


Defina ENVIRONMENT como 'development' para testes locais:
define('ENVIRONMENT', 'development');


Ajuste SITE_URL para o caminho do seu servidor:
define('SITE_URL', 'http://localhost/voteseguro/public');




Ajustar Permissões:

Certifique-se de que a pasta voteseguro tem permissões de leitura/escrita para o servidor web.


Testar o Sistema:

Acesse http://localhost/voteseguro/public no navegador.
Registre um novo usuário em register.php.
Faça login e crie uma eleição privada em create_private_election.php.



Uso

Registro e Login:

Acesse register.php para criar uma conta de eleitor (role: voter).
Faça login em login.php com suas credenciais.


Criação de Eleição:

Após o login, vá para create_private_election.php (eleitores) ou create_election.php (administradores).
Preencha o título, descrição e adicione pelo menos dois candidatos.
Para eleições privadas, compartilhe o link gerado com os eleitores.


Votação:

Acesse uma eleição via election.php?id={id}&token={token} (para privadas).
Selecione um candidato e envie o voto (apenas uma vez por eleição).


Gerenciamento:

Criadores podem editar, encerrar ou excluir eleições em election.php.
Resultados parciais são visíveis apenas para criadores.



Segurança

XSS: Entradas são sanitizadas com sanitizeInput() e saídas escapadas com sanitize().
CSRF: Tokens CSRF são gerados e validados em todos os formulários POST.
Sessão: Configurações seguras de cookies (httponly, samesite=Strict).
Banco de Dados: Uso de prepared statements para prevenir SQL Injection.
Logs: Erros são registrados em produção sem exibir detalhes sensíveis.

Contribuição

Fork o repositório.
Crie uma branch para sua feature (git checkout -b feature/nova-funcionalidade).
Commit suas alterações (git commit -m 'Adiciona nova funcionalidade').
Push para a branch (git push origin feature/nova-funcionalidade).
Abra um Pull Request.

Licença
Este projeto está licenciado sob a MIT License.
Contato
Para dúvidas ou suporte, entre em contato via lutondomartinsdev@gmail.com.
