-- ============================================================
-- LAC CENTRO MÉDICO — Estrutura completa do banco de dados
-- Atualizado em: 2026
-- Compatível com MySQL 5.7+ / MariaDB 10.4+
-- ============================================================

CREATE DATABASE IF NOT EXISTS lac_centro_medico
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE lac_centro_medico;

-- ============================================================
-- TABELA: usuarios
-- Armazena pacientes e administradores do sistema
-- Regra de negócio: o login pode ser feito por e-mail OU CPF.
-- A primeira senha de um usuário cadastrado pelo admin é o CPF.
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100) NOT NULL,
    email           VARCHAR(100) UNIQUE NULL,
    cpf             VARCHAR(14)  UNIQUE NULL,
    senha           VARCHAR(255) NOT NULL, -- Em produção, usar password_hash()
    telefone        VARCHAR(20)  NULL,
    data_nascimento DATE         NULL,
    endereco        VARCHAR(255) NULL,
    tipo            ENUM('comum', 'admin') DEFAULT 'comum',
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABELA: medicos
-- Cadastro dos profissionais de saúde da clínica
-- ============================================================
CREATE TABLE IF NOT EXISTS medicos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(100) NOT NULL,
    crm           VARCHAR(30)  NOT NULL UNIQUE,
    especialidade VARCHAR(100) NOT NULL,
    email         VARCHAR(100) UNIQUE NULL,
    telefone      VARCHAR(20)  NULL,
    criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABELA: consultas
-- Agendamentos realizados pelos pacientes
-- ============================================================
CREATE TABLE IF NOT EXISTS consultas (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT NOT NULL,
    especialidade VARCHAR(100) NOT NULL,
    modalidade    VARCHAR(50)  NOT NULL,
    data_hora     DATETIME     NOT NULL,
    status        VARCHAR(50)  DEFAULT 'Agendada',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ============================================================
-- TABELA: tokens_recuperacao
-- Armazena tokens de recuperação de senha ("esqueci minha senha")
-- Cada token é válido por 30 minutos e só pode ser usado uma vez.
-- ============================================================
CREATE TABLE IF NOT EXISTS tokens_recuperacao (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token      VARCHAR(64) NOT NULL,
    expira_em  DATETIME    NOT NULL,
    usado      TINYINT(1)  DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ============================================================
-- USUÁRIOS DE TESTE
-- Senha padrão para todos: 123456
-- (Lembre-se: em produção usar password_hash())
-- ============================================================

-- Administrador (acesso ao Painel Admin)
INSERT INTO usuarios (nome, email, cpf, senha, tipo) VALUES
('Administrador', 'testeadm@gmail.com', '00000000000', '123456', 'admin');

-- Usuário comum (paciente de teste)
INSERT INTO usuarios (nome, email, cpf, senha, tipo) VALUES
('João Carlos Zanetti', 'joao@email.com', '11111111111', '123456', 'comum');