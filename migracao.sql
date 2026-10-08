-- ============================================================
-- MIGRAÇÃO — rode no phpMyAdmin (aba SQL) com o banco
-- lac_centro_medico SELECIONADO. Não apaga nenhum dado.
-- Pode rodar mais de uma vez sem problema.
-- ============================================================
USE lac_centro_medico;

-- 1) medicos.senha (o login do médico e o cadastro do admin usam essa coluna)
SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE medicos ADD COLUMN senha VARCHAR(255) NULL', 'SELECT 1')
          FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medicos' AND COLUMN_NAME = 'senha');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- 2) consultas.tipo ('agendada' ou 'pronto_atendimento')
SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE consultas ADD COLUMN tipo VARCHAR(30) NOT NULL DEFAULT ''agendada'' AFTER modalidade', 'SELECT 1')
          FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consultas' AND COLUMN_NAME = 'tipo');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- 3) consultas.medico_id (caso o banco venha da versão antiga do projeto)
SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE consultas ADD COLUMN medico_id INT NULL AFTER usuario_id', 'SELECT 1')
          FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consultas' AND COLUMN_NAME = 'medico_id');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- 4) tabela de receitas e atestados
CREATE TABLE IF NOT EXISTS documentos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id      INT NOT NULL,
    medico_id        INT NOT NULL,
    consulta_id      INT NULL,
    tipo             VARCHAR(20) NOT NULL,
    conteudo         TEXT NULL,
    dias_afastamento SMALLINT NULL,
    cid10            VARCHAR(10) NULL,
    codigo           VARCHAR(16) NOT NULL UNIQUE,
    criado_em        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_doc_paciente (paciente_id, criado_em),
    INDEX idx_doc_consulta (consulta_id),
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id),
    FOREIGN KEY (medico_id)   REFERENCES medicos(id),
    FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE SET NULL
);

-- 5) médicos de teste (um por especialidade). Só entram se o e-mail ainda não existir.
INSERT INTO medicos (nome, crm, especialidade, email, senha)
SELECT * FROM (
  SELECT 'Dra. Teste Cardio' AS nome, 'CRM/PR 123457' AS crm, 'Cardiologia' AS especialidade, 'testecardio@gmail.com' AS email, '123456' AS senha
  UNION ALL SELECT 'Dra. Teste Pediatra','CRM/PR 123458','Pediatria','testepediatra@gmail.com','123456'
  UNION ALL SELECT 'Dr. Teste Derma','CRM/PR 123459','Dermatologia','testederma@gmail.com','123456'
  UNION ALL SELECT 'Dr. Teste Orto','CRM/PR 123460','Ortopedia','testeorto@gmail.com','123456'
  UNION ALL SELECT 'Dra. Teste Gineco','CRM/PR 123461','Ginecologia','testegineco@gmail.com','123456'
) novos
WHERE NOT EXISTS (SELECT 1 FROM medicos m WHERE m.email = novos.email OR m.crm = novos.crm);

-- 6) médicos já existentes sem senha: usa 123456 só para você conseguir testar (troque depois)
UPDATE medicos SET senha = '123456' WHERE senha IS NULL OR senha = '';
