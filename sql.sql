-- ============================================================
-- LAC CENTRO MÉDICO — Banco de dados completo
-- Compatível com MySQL 5.7+ / MariaDB 10.4+
-- ============================================================

DROP DATABASE IF EXISTS lac_centro_medico;

CREATE DATABASE lac_centro_medico
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE lac_centro_medico;

-- ============================================================
-- usuarios: pacientes e administradores
-- Login por e-mail OU CPF. Em produção, usar password_hash().
-- ============================================================
CREATE TABLE usuarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100) NOT NULL,
    email           VARCHAR(100) UNIQUE NULL,
    cpf             VARCHAR(14)  UNIQUE NULL,
    senha           VARCHAR(255) NOT NULL,
    telefone        VARCHAR(20)  NULL,
    data_nascimento DATE         NULL,
    endereco        VARCHAR(255) NULL,
    tipo            ENUM('comum', 'admin') DEFAULT 'comum',
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- medicos: profissionais cadastrados pelo admin
-- Login do médico: e-mail ou CRM; senha = CRM (completo ou só números)
-- ============================================================
CREATE TABLE medicos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(100) NOT NULL,
    crm           VARCHAR(30)  NOT NULL UNIQUE,
    especialidade VARCHAR(100) NOT NULL,
    email         VARCHAR(100) UNIQUE NULL,
    telefone      VARCHAR(20)  NULL,
    criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- consultas: agendamentos feitos pelos pacientes
-- medico_id fica NULL até um médico da especialidade assumir
-- ============================================================
CREATE TABLE consultas (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT NOT NULL,
    medico_id     INT NULL,
    especialidade VARCHAR(100) NOT NULL,
    modalidade    VARCHAR(50)  NOT NULL,
    data_hora     DATETIME     NOT NULL,
    status        VARCHAR(50)  DEFAULT 'Agendada',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (medico_id)  REFERENCES medicos(id)
);

-- ============================================================
-- tokens_recuperacao: "esqueci minha senha" (30 min, uso único)
-- ============================================================
CREATE TABLE tokens_recuperacao (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token      VARCHAR(64) NOT NULL,
    expira_em  DATETIME    NOT NULL,
    usado      TINYINT(1)  DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ============================================================
-- prontuarios: ficha base do paciente (1 por paciente)
-- ============================================================
CREATE TABLE prontuarios (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id        INT NOT NULL UNIQUE,
    sexo               VARCHAR(20)  NULL,
    tipo_sanguineo     VARCHAR(5)   NULL,
    alergias           TEXT NULL,
    doencas_cronicas   TEXT NULL,
    medicamentos_uso   TEXT NULL,
    cirurgias_previas  TEXT NULL,
    historico_familiar TEXT NULL,
    habitos_vida       TEXT NULL,
    observacoes        TEXT NULL,
    atualizado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    atualizado_por     INT NULL,
    FOREIGN KEY (paciente_id)    REFERENCES usuarios(id),
    FOREIGN KEY (atualizado_por) REFERENCES medicos(id)
);

-- ============================================================
-- atendimentos: evoluções do prontuário (histórico clínico)
-- ============================================================
CREATE TABLE atendimentos (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id        INT NOT NULL,
    medico_id          INT NOT NULL,
    consulta_id        INT NULL,
    queixa_principal   TEXT NULL,
    historia_doenca    TEXT NULL,
    exame_fisico       TEXT NULL,
    pressao_arterial   VARCHAR(10) NULL,
    freq_cardiaca      SMALLINT NULL,
    temperatura        DECIMAL(4,1) NULL,
    saturacao          TINYINT NULL,
    peso_kg            DECIMAL(5,2) NULL,
    altura_cm          SMALLINT NULL,
    diagnostico        TEXT NULL,
    cid10              VARCHAR(10) NULL,
    conduta            TEXT NULL,
    prescricao         TEXT NULL,
    exames_solicitados TEXT NULL,
    retorno            VARCHAR(100) NULL,
    criado_em          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id),
    FOREIGN KEY (medico_id)   REFERENCES medicos(id),
    FOREIGN KEY (consulta_id) REFERENCES consultas(id)
);

-- ============================================================
-- sinalizacao: mensagens de conexão da teleconsulta (WebRTC)
-- ============================================================
CREATE TABLE sinalizacao (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    consulta_id INT NOT NULL,
    papel       VARCHAR(10) NOT NULL,   -- 'medico' ou 'paciente'
    tipo        VARCHAR(20) NOT NULL,
    payload     MEDIUMTEXT NULL,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sinal (consulta_id, id),
    FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE
);

-- ============================================================
-- DADOS DE TESTE (senha de usuários: 123456)
-- ============================================================
INSERT INTO usuarios (nome, email, cpf, senha, data_nascimento, tipo) VALUES
('testeadm',   'testeadm@gmail.com',   '00000000000', '123456', NULL,         'admin'),
('testecomum', 'testecomum@gmail.com', '11111111111', '123456', '1995-03-10', 'comum');

-- Médico de teste: login testemedico@gmail.com, senha 123456 (número do CRM)
INSERT INTO medicos (nome, crm, especialidade, email) VALUES
('Dr. Teste Médico', 'CRM/PR 123456', 'Clínica Geral', 'testemedico@gmail.com');