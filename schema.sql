CREATE DATABASE voteseguro;

USE voteseguro;

CREATE TABLE
    users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM ('admin', 'voter') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

CREATE TABLE
    elections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        type ENUM ('public', 'private') NOT NULL,
        token VARCHAR(32) UNIQUE,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users (id)
    );

CREATE TABLE
    candidates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        election_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        FOREIGN KEY (election_id) REFERENCES elections (id)
    );

CREATE TABLE
    votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        election_id INT NOT NULL,
        candidate_id INT NOT NULL,
        user_id INT NOT NULL,
        voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (election_id) REFERENCES elections (id),
        FOREIGN KEY (candidate_id) REFERENCES candidates (id),
        FOREIGN KEY (user_id) REFERENCES users (id),
        UNIQUE (election_id, user_id)
    );