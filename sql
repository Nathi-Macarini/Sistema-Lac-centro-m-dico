CREATE DATABASE IF NOT EXISTS lac_centro_medico DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lac_centro_medico;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL, -- Em produção utilize password_hash()
    tipo ENUM('comum', 'admin') DEFAULT 'comum',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    especialidade VARCHAR(100) NOT NULL,
    modalidade VARCHAR(50) NOT NULL,
    data_hora DATETIME NOT NULL,
    status VARCHAR(50) DEFAULT 'Agendada',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Inserindo um usuário comum e um admin de teste (Senha padrão: 123456)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('testecomum', 'testecomum@gmail.com', '123456', 'comum'),
('testeadm', 'testeadm@gmail.com', '123456', 'admin');
