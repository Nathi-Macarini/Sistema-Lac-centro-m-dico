-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 08/10/2026 às 22:59
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `lac_centro_medico`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity` varchar(100) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `user_role`, `action`, `entity`, `entity_id`, `tags`, `description`, `ip_address`, `user_agent`, `metadata`, `created_at`) VALUES
(1, 1, 'testeadm', 'admin', 'test', 'teste', 1, NULL, 'Teste de log com usuário real logado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"foo\":\"bar\",\"senha\":\"***\"}', '2026-10-08 18:07:59'),
(2, 1, 'testeadm', 'admin', 'test', 'teste', 1, NULL, 'Teste de log com usuário real logado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"foo\":\"bar\",\"senha\":\"***\"}', '2026-10-08 18:07:59'),
(3, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:14:58'),
(4, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:15:39'),
(5, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:18:24'),
(6, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:20:09'),
(7, 2, 'testecomum', 'comum', 'login', NULL, NULL, NULL, 'Usuário testecomum fez login (comum)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:20:12'),
(8, 2, 'testecomum', 'comum', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:20:13'),
(9, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:20:16'),
(10, 2, 'testecomum', 'comum', 'login', NULL, NULL, NULL, 'Usuário testecomum fez login (comum)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:25:47'),
(11, 2, 'testecomum', 'comum', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:25:54'),
(12, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 18:25:57'),
(13, NULL, NULL, NULL, 'teste_tag', NULL, NULL, 'lac_teleatendimento,iniciado', 'Teste de filtro por tag', NULL, NULL, NULL, '2026-10-08 18:56:30'),
(14, 3, 'Maria Silva Santos', 'comum', 'consulta_agendada', 'consulta', 1, 'lac_teleatendimento,agendado', 'Paciente Maria Silva Santos agendou Cardiologia (Vídeo)', '127.0.0.1', NULL, NULL, '2026-10-05 19:27:29'),
(15, 9, 'Dr. Roberto Cardoso', 'medico', 'atendimento_iniciado', 'consulta', 1, 'lac_teleatendimento,iniciado,em_andamento', 'Dr(a). Dr. Roberto Cardoso iniciou atendimento', '127.0.0.1', NULL, NULL, '2026-10-05 19:27:29'),
(16, 9, 'Dr. Roberto Cardoso', 'medico', 'atendimento_finalizado', 'consulta', 1, 'lac_teleatendimento,finalizado', 'Dr(a). Dr. Roberto Cardoso finalizou atendimento', '127.0.0.1', NULL, NULL, '2026-10-05 19:27:29'),
(17, 4, 'João Pedro Oliveira', 'comum', 'lac_atende_iniciado', 'consulta', 13, 'lac_atende,iniciado,em_andamento', 'Paciente João Pedro Oliveira entrou na fila do LAC Atende', '127.0.0.1', NULL, NULL, '2026-10-07 19:27:29'),
(18, 4, 'João Pedro Oliveira', 'comum', 'consulta_cancelada', 'consulta', 10, 'lac_teleatendimento,cancelado', 'Paciente João Pedro Oliveira cancelou consulta', '127.0.0.1', NULL, NULL, '2026-10-03 19:27:29'),
(19, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Administrador testeadm fez login', '127.0.0.1', NULL, NULL, '2026-10-08 17:27:29'),
(20, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:32:18'),
(21, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:32:43'),
(22, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:32:46'),
(23, NULL, NULL, NULL, 'login_failed', NULL, NULL, NULL, 'Tentativa de login falhou para: 123***', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:33:28'),
(24, 1, 'Dr. Teste Médico', 'medico', 'login', NULL, NULL, NULL, 'Médico Dr. Teste Médico fez login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:33:43'),
(25, 1, 'Dr. Teste Médico', 'medico', 'login_failed', NULL, NULL, NULL, 'Tentativa de login falhou para: 123***', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:35:36'),
(26, 1, 'Dr. Teste Médico', 'medico', 'login', NULL, NULL, NULL, 'Médico Dr. Teste Médico fez login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:35:50'),
(27, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:37:51'),
(28, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:49:34'),
(29, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 19:49:46'),
(30, 1, 'testeadm', 'admin', 'senha_redefinida', 'medico', 16, 'medico,senha', 'Admin redefiniu a senha do médico Dra. Sofia Nogueira (89012/PR)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"medico_id\":16,\"medico_nome\":\"Dra. Sofia Nogueira\",\"medico_crm\":\"89012\\/PR\"}', '2026-10-08 20:13:15'),
(31, 1, 'testeadm', 'admin', 'senha_redefinida', 'medico', 16, 'medico,senha', 'Admin redefiniu a senha do médico Dra. Sofia Nogueira (89012/PR)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"medico_id\":16,\"medico_nome\":\"Dra. Sofia Nogueira\",\"medico_crm\":\"89012\\/PR\"}', '2026-10-08 20:13:24'),
(32, 1, 'testeadm', 'admin', 'senha_redefinida', 'medico', 16, 'medico,senha', 'Admin redefiniu a senha do médico Dra. Sofia Nogueira (89012/PR)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"medico_id\":16,\"medico_nome\":\"Dra. Sofia Nogueira\",\"medico_crm\":\"89012\\/PR\"}', '2026-10-08 20:13:37'),
(33, 1, 'testeadm', 'admin', 'senha_redefinida', 'medico', 16, 'medico,senha', 'Admin redefiniu a senha do médico Dra. Sofia Nogueira (89012/PR)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"medico_id\":16,\"medico_nome\":\"Dra. Sofia Nogueira\",\"medico_crm\":\"89012\\/PR\"}', '2026-10-08 20:14:28'),
(34, 1, 'testeadm', 'admin', 'senha_redefinida', 'medico', 16, 'medico,senha', 'Admin redefiniu a senha do médico Dra. Sofia Nogueira (89012/PR)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"medico_id\":16,\"medico_nome\":\"Dra. Sofia Nogueira\",\"medico_crm\":\"89012\\/PR\"}', '2026-10-08 20:14:41'),
(35, 1, 'testeadm', 'admin', 'senha_redefinida', 'medico', 11, 'medico,senha', 'Admin redefiniu a senha do médico Dr. Marcelo Tavares (34567/PR)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"medico_id\":11,\"medico_nome\":\"Dr. Marcelo Tavares\",\"medico_crm\":\"34567\\/PR\"}', '2026-10-08 20:16:49'),
(36, 1, 'testeadm', 'admin', 'senha_redefinida', 'medico', 16, 'medico,senha', 'Admin redefiniu a senha do médico Dra. Sofia Nogueira (89012/PR)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"medico_id\":16,\"medico_nome\":\"Dra. Sofia Nogueira\",\"medico_crm\":\"89012\\/PR\"}', '2026-10-08 20:22:15'),
(37, 16, 'Dra. Sofia Nogueira', 'medico', 'login', NULL, NULL, NULL, 'Médico Dra. Sofia Nogueira fez login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:23:04'),
(38, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:31:43'),
(39, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:31:45'),
(40, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:32:47'),
(41, 2, 'testecomum', 'comum', 'login', NULL, NULL, NULL, 'Usuário testecomum fez login (comum)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:32:51'),
(42, 2, 'testecomum', 'comum', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:32:52'),
(43, NULL, NULL, NULL, 'login_failed', NULL, NULL, NULL, 'Tentativa de login falhou para: bor***', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:33:11'),
(44, 2, 'testecomum', 'comum', 'login', NULL, NULL, NULL, 'Usuário testecomum fez login (comum)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:33:14'),
(45, 2, 'testecomum', 'comum', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:33:16'),
(46, 2, 'testecomum', 'comum', 'login', NULL, NULL, NULL, 'Usuário testecomum fez login (comum)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:33:28'),
(47, 2, 'testecomum', 'comum', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:33:33'),
(48, 3, 'Maria Silva Santos', 'comum', 'login', NULL, NULL, NULL, 'Usuário Maria Silva Santos fez login (comum)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:33:46'),
(49, 3, 'Maria Silva Santos', 'comum', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:34:49'),
(50, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:34:51'),
(51, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:34:54'),
(52, 2, 'testecomum', 'comum', 'login', NULL, NULL, NULL, 'Usuário testecomum fez login (comum)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:35:40'),
(53, 2, 'testecomum', 'comum', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:35:42'),
(54, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:35:45'),
(55, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:35:48'),
(56, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:36:11'),
(57, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:37:00'),
(58, 17, 'testemedico', 'medico', 'login', NULL, NULL, NULL, 'Médico testemedico fez login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:37:09'),
(59, 17, 'testemedico', 'medico', 'login', NULL, NULL, NULL, 'Médico testemedico fez login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:37:31'),
(60, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:37:35'),
(61, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:45:50'),
(62, 2, 'testecomum', 'comum', 'login', NULL, NULL, NULL, 'Usuário testecomum fez login (comum)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:45:52'),
(63, 2, 'testecomum', 'comum', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:46:05'),
(64, 17, 'testemedico', 'medico', 'login', NULL, NULL, NULL, 'Médico testemedico fez login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:46:08'),
(65, 3, 'Maria Silva Santos', 'comum', 'login', NULL, NULL, NULL, 'Usuário Maria Silva Santos fez login (comum)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:46:37'),
(66, 3, 'Maria Silva Santos', 'comum', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:47:05'),
(67, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:47:33'),
(68, 1, 'testeadm', 'admin', 'senha_redefinida', 'medico', 9, 'medico,senha', 'Admin redefiniu a senha do médico Dr. Roberto Cardoso (12345/PR)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"medico_id\":9,\"medico_nome\":\"Dr. Roberto Cardoso\",\"medico_crm\":\"12345\\/PR\"}', '2026-10-08 20:47:48'),
(69, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:47:54'),
(70, 9, 'Dr. Roberto Cardoso', 'medico', 'login', NULL, NULL, NULL, 'Médico Dr. Roberto Cardoso fez login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:47:58'),
(71, 9, 'Dr. Roberto Cardoso', 'medico', 'atendimento_iniciado', 'consulta', 9, 'lac_teleatendimento,iniciado,em_andamento', 'LAC Teleatendimento: Dr(a). Dr. Roberto Cardoso iniciou atendimento com Maria Silva Santos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"consulta_id\":9,\"medico_id\":9,\"medico_nome\":\"Dr. Roberto Cardoso\",\"paciente_id\":3,\"paciente_nome\":\"Maria Silva Santos\",\"especialidade\":\"Cardiologia\",\"modalidade\":\"Vídeo\",\"tipo_consulta\":\"agendada\",\"iniciado_em\":\"2026-10-08 17:48:20\"}', '2026-10-08 20:48:20'),
(72, 1, 'testeadm', 'admin', 'login', NULL, NULL, NULL, 'Usuário testeadm fez login (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:52:50'),
(73, 1, 'testeadm', 'admin', 'logout', NULL, NULL, NULL, 'Usuário saiu do sistema', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:53:13'),
(74, 9, 'Dr. Roberto Cardoso', 'medico', 'login', NULL, NULL, NULL, 'Médico Dr. Roberto Cardoso fez login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', NULL, '2026-10-08 20:53:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimentos`
--

CREATE TABLE `atendimentos` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `consulta_id` int(11) DEFAULT NULL,
  `queixa_principal` text DEFAULT NULL,
  `historia_doenca` text DEFAULT NULL,
  `exame_fisico` text DEFAULT NULL,
  `pressao_arterial` varchar(10) DEFAULT NULL,
  `freq_cardiaca` smallint(6) DEFAULT NULL,
  `temperatura` decimal(4,1) DEFAULT NULL,
  `saturacao` tinyint(4) DEFAULT NULL,
  `peso_kg` decimal(5,2) DEFAULT NULL,
  `altura_cm` smallint(6) DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `cid10` varchar(10) DEFAULT NULL,
  `conduta` text DEFAULT NULL,
  `prescricao` text DEFAULT NULL,
  `exames_solicitados` text DEFAULT NULL,
  `retorno` varchar(100) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `atendimentos`
--

INSERT INTO `atendimentos` (`id`, `paciente_id`, `medico_id`, `consulta_id`, `queixa_principal`, `historia_doenca`, `exame_fisico`, `pressao_arterial`, `freq_cardiaca`, `temperatura`, `saturacao`, `peso_kg`, `altura_cm`, `diagnostico`, `cid10`, `conduta`, `prescricao`, `exames_solicitados`, `retorno`, `criado_em`) VALUES
(1, 3, 15, 22, 'Dor no peito ao esforço', 'Paciente relata dor precordial há 3 semanas, desencadeada por esforço físico, sem irradiação.', 'Ausculta pulmonar limpa. Ritmo cardíaco regular, 2T, sem sopros.', '130/85', 78, 36.5, 98, 72.50, 168, 'Angina estável - investigar', 'I20.8', 'Solicitado eletrocardiograma e teste ergométrico. Retorno com exames.', 'AAS 100mg 1x/dia. Anlodipino 5mg 1x/dia.', 'ECG de repouso, Teste ergométrico, Perfil lipídico', '30 dias', '2026-10-08 19:26:19'),
(2, 4, 10, 18, 'Manchas na pele', 'Paciente apresenta manchas eritematosas em braços há 2 meses.', 'Lesões maculopapulares em face extensora de antebraços bilateralmente.', '120/80', 72, 36.2, 99, 58.00, 165, 'Dermatite de contato', 'L23.9', 'Orientado evitar contato com agentes irritantes. Prescrito corticoide tópico.', 'Hidrocortisona creme 1% 2x/dia por 10 dias', NULL, '15 dias', '2026-10-08 19:26:19');

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultas`
--

CREATE TABLE `consultas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `medico_id` int(11) DEFAULT NULL,
  `especialidade` varchar(100) NOT NULL,
  `modalidade` varchar(50) NOT NULL,
  `tipo` varchar(30) NOT NULL DEFAULT 'agendada',
  `data_hora` datetime NOT NULL,
  `status` varchar(50) DEFAULT 'Agendada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `consultas`
--

INSERT INTO `consultas` (`id`, `usuario_id`, `medico_id`, `especialidade`, `modalidade`, `tipo`, `data_hora`, `status`) VALUES
(9, 3, 9, 'Cardiologia', 'Vídeo', 'agendada', '2026-10-09 16:26:19', 'Em andamento'),
(10, 4, 10, 'Dermatologia', 'Presencial', 'agendada', '2026-10-10 16:26:19', 'Agendada'),
(11, 5, 11, 'Ortopedia', 'Vídeo', 'agendada', '2026-10-11 16:26:19', 'Agendada'),
(12, 6, 12, 'Ginecologia', 'Presencial', 'agendada', '2026-10-12 16:26:19', 'Agendada'),
(13, 7, 13, 'Pediatria', 'Vídeo', 'agendada', '2026-10-13 16:26:19', 'Agendada'),
(14, 8, 14, 'Clínica Geral', 'Presencial', 'agendada', '2026-10-14 16:26:19', 'Agendada'),
(15, 9, 15, 'Cardiologia', 'Vídeo', 'agendada', '2026-10-15 16:26:19', 'Agendada'),
(16, 10, 16, 'Clínica Geral', 'Presencial', 'agendada', '2026-10-16 16:26:19', 'Agendada'),
(17, 3, 9, 'Cardiologia', 'Vídeo', 'agendada', '2026-09-23 16:26:19', 'Realizada'),
(18, 4, 10, 'Dermatologia', 'Presencial', 'agendada', '2026-09-18 16:26:19', 'Realizada'),
(19, 5, 11, 'Ortopedia', 'Vídeo', 'agendada', '2026-09-08 16:26:19', 'Realizada'),
(20, 6, 12, 'Ginecologia', 'Presencial', 'agendada', '2026-08-24 16:26:19', 'Realizada'),
(21, 7, 13, 'Pediatria', 'Vídeo', 'agendada', '2026-08-09 16:26:19', 'Realizada'),
(22, 3, 15, 'Cardiologia', 'Vídeo', 'agendada', '2026-07-10 16:26:19', 'Realizada'),
(23, 4, 14, 'Clínica Geral', 'Presencial', 'agendada', '2026-06-10 16:26:19', 'Realizada'),
(24, 8, 16, 'Clínica Geral', 'Vídeo', 'agendada', '2026-09-28 16:26:19', 'Realizada'),
(25, 5, 10, 'Dermatologia', 'Vídeo', 'agendada', '2026-10-03 16:26:19', 'Cancelada'),
(26, 7, 11, 'Ortopedia', 'Presencial', 'agendada', '2026-09-30 16:26:19', 'Cancelada'),
(27, 3, 14, 'Clínica Geral', 'Vídeo', 'pronto_atendimento', '2026-10-06 16:26:19', 'Realizada'),
(28, 8, 16, 'Clínica Geral', 'Vídeo', 'pronto_atendimento', '2026-10-07 16:26:19', 'Realizada');

-- --------------------------------------------------------

--
-- Estrutura para tabela `dependentes`
--

CREATE TABLE `dependentes` (
  `id` int(11) NOT NULL,
  `titular_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `parentesco` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `dependentes`
--

INSERT INTO `dependentes` (`id`, `titular_id`, `nome`, `cpf`, `data_nascimento`, `parentesco`) VALUES
(1, 3, 'Lucas Silva Santos', '11122233344', '2015-06-10', 'Filho'),
(2, 3, 'Beatriz Silva Santos', '22233344455', '2018-09-22', 'Filha'),
(3, 4, 'Carla Oliveira', '33344455566', '1980-04-15', 'Cônjuge'),
(4, 6, 'Pedro Henrique Lima', '44455566677', '2010-11-30', 'Filho'),
(5, 7, 'Marcos Costa', '55566677788', '1988-02-08', 'Cônjuge'),
(6, 9, 'Isabela Gomes Rocha', '66677788899', '2012-07-19', 'Filha'),
(7, 10, 'Larissa Barbosa Neto', '77788899900', '2020-03-05', 'Filha');

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos`
--

CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `consulta_id` int(11) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL,
  `conteudo` text DEFAULT NULL,
  `dias_afastamento` smallint(6) DEFAULT NULL,
  `cid10` varchar(10) DEFAULT NULL,
  `codigo` varchar(16) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `lido_em` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `documentos`
--

INSERT INTO `documentos` (`id`, `paciente_id`, `medico_id`, `consulta_id`, `tipo`, `conteudo`, `dias_afastamento`, `cid10`, `codigo`, `criado_em`, `lido_em`) VALUES
(1, 3, 15, 22, 'receita', 'Uso contínuo:\n1) AAS 100mg - 1 comprimido pela manhã, após café.\n2) Anlodipino 5mg - 1 comprimido à noite.\n\nReavaliar em 30 dias.', NULL, 'I20.8', 'REC-000022', '2026-10-08 19:27:29', NULL),
(2, 7, 13, 21, 'atestado', 'Atesto, para os devidos fins, que o paciente esteve sob meus cuidados profissionais nesta data, necessitando de afastamento de suas atividades por 3 (três) dias.', 3, 'J18.9', 'ATE-000021', '2026-10-08 19:27:29', NULL),
(3, 4, 10, 18, 'receita', 'Prescrição:\n1) Hidrocortisona creme 1% - aplicar fina camada 2x/dia por 10 dias.\n2) Hidratante corporal - aplicar 2x/dia.', NULL, 'L23.9', 'REC-000018', '2026-10-08 19:27:29', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `medicos`
--

CREATE TABLE `medicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `crm` varchar(30) NOT NULL,
  `especialidade` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `senha` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `medicos`
--

INSERT INTO `medicos` (`id`, `nome`, `crm`, `especialidade`, `email`, `telefone`, `criado_em`, `senha`) VALUES
(9, 'Dr. Roberto Cardoso', '12345/PR', 'Cardiologia', 'roberto.cardoso@teste.lac', '(41) 99711-1111', '2026-10-08 19:22:37', '$2y$10$xyT7jzSSvWyl80eCQP7vnOm8rjBPDqpXzlT6WZOde2cmWyPUWWvyW'),
(10, 'Dra. Camila Ribeiro', '23456/PR', 'Dermatologia', 'camila.ribeiro@teste.lac', '(41) 99722-2222', '2026-10-08 19:22:37', '$2y$10$cP8H7m/ZCdb3wV.PMnwTo.ZIUPARKv8zu94RR1lwJrCyuKNQCuKjS'),
(11, 'Dr. Marcelo Tavares', '34567/PR', 'Ortopedia', 'marcelo.tavares@teste.lac', '(41) 99733-3333', '2026-10-08 19:22:37', '$2y$10$Q6Kdi/E5/GX5KRni4cHVVe9g4Ll/6KV0RUlTEw4MzZDxUpHEWy5xO'),
(12, 'Dra. Fernanda Alves', '45678/PR', 'Ginecologia', 'fernanda.alves@teste.lac', '(41) 99744-4444', '2026-10-08 19:22:37', '$2y$10$eImiTXuWVxfM37uY4JANjQ==.wXG1p0QvC9yzF3J1WtKqZ3y5C'),
(13, 'Dr. Paulo Henrique', '56789/PR', 'Pediatria', 'paulo.henrique@teste.lac', '(41) 99755-5555', '2026-10-08 19:22:37', '$2y$10$YWlXC9q73vjaOnfW0yjlQuyttZgqvh4KpoaCteiAi.A.lcHlBmKwi'),
(14, 'Dra. Luciana Prado', '67890/PR', 'Clínica Geral', 'luciana.prado@teste.lac', '(41) 99766-6666', '2026-10-08 19:22:37', '$2y$10$eImiTXuWVxfM37uY4JANjQ==.wXG1p0QvC9yzF3J1WtKqZ3y5C'),
(15, 'Dr. André Martins', '78901/PR', 'Cardiologia', 'andre.martins@teste.lac', '(41) 99777-7777', '2026-10-08 19:22:37', '$2y$10$PAQlqgvaFXeVfrvCI/RxjOJmxtCryz9Q/vVjwFoK/HIKnA1Om2qtS'),
(16, 'Dra. Sofia Nogueira', '89012/PR', 'Clínica Geral', 'sofia.nogueira@teste.lac', '(41) 99788-8888', '2026-10-08 19:22:37', '$2y$10$X5qyVxsfiYVrjBWpMyZSVOsza6LBVu9s7muN3shC/h9ON.w4fC/UO'),
(17, 'testemedico', '123456/PR', 'Clínica Geral', 'testemedico@gmail.com', '', '2026-10-08 20:36:58', '$2y$10$qR/e/7P8wKWzFgFloU1blOmkp8i7mqhLYu51CLmG8admPhQwUoAwW');

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `operadora` varchar(100) NOT NULL,
  `numero_carteirinha` varchar(50) DEFAULT NULL,
  `validade` date DEFAULT NULL,
  `acomodacao` varchar(30) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `planos`
--

INSERT INTO `planos` (`id`, `usuario_id`, `operadora`, `numero_carteirinha`, `validade`, `acomodacao`, `criado_em`) VALUES
(1, 3, 'Unimed Paraná', 'UNI-987654321', '2027-12-31', 'Apartamento', '2026-10-08 19:27:29'),
(2, 4, 'Bradesco Saúde', 'BRA-123456789', '2026-08-15', 'Enfermaria', '2026-10-08 19:27:29'),
(3, 5, 'Amil', 'AMI-456789123', '2027-03-22', 'Apartamento', '2026-10-08 19:27:29'),
(4, 6, 'SulAmérica', 'SUL-789123456', '2026-11-30', 'Enfermaria', '2026-10-08 19:27:29'),
(5, 7, 'Unimed Paraná', 'UNI-321654987', '2027-05-10', 'Apartamento', '2026-10-08 19:27:29'),
(6, 8, 'Bradesco Saúde', 'BRA-654987321', '2026-09-18', 'Enfermaria', '2026-10-08 19:27:29'),
(7, 9, 'Amil', 'AMI-159753456', '2027-01-25', 'Apartamento', '2026-10-08 19:27:29'),
(8, 10, 'SulAmérica', 'SUL-852963741', '2026-12-05', 'Enfermaria', '2026-10-08 19:27:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `prontuarios`
--

CREATE TABLE `prontuarios` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `sexo` varchar(20) DEFAULT NULL,
  `tipo_sanguineo` varchar(5) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `doencas_cronicas` text DEFAULT NULL,
  `medicamentos_uso` text DEFAULT NULL,
  `cirurgias_previas` text DEFAULT NULL,
  `historico_familiar` text DEFAULT NULL,
  `habitos_vida` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `prontuarios`
--

INSERT INTO `prontuarios` (`id`, `paciente_id`, `sexo`, `tipo_sanguineo`, `alergias`, `doencas_cronicas`, `medicamentos_uso`, `cirurgias_previas`, `historico_familiar`, `habitos_vida`, `observacoes`, `atualizado_em`, `atualizado_por`) VALUES
(1, 3, 'Feminino', 'O+', 'Dipirona, Penicilina', 'Hipertensão arterial', 'Losartana 50mg 2x/dia', 'Nenhuma', 'Mãe hipertensa, pai com histórico de AVC', 'Não fuma, não bebe. Pratica caminhada 3x/semana', 'Paciente colaborativa, boa adesão ao tratamento', '2026-10-08 19:27:29', NULL),
(2, 4, 'Masculino', 'A+', 'Nega alergias', 'Diabetes tipo 2', 'Metformina 850mg 2x/dia', 'Apendicectomia (2010)', 'Avô diabético', 'Ex-tabagista (parou há 5 anos). Bebe socialmente', 'Necessita acompanhamento nutricional', '2026-10-08 19:27:29', NULL),
(3, 5, 'Feminino', 'B+', 'Nega', 'Nenhuma', 'Nenhum', 'Nenhuma', 'Sem histórico relevante', 'Ativa, corre 4x/semana', 'Paciente saudável, realiza check-up anual', '2026-10-08 19:27:29', NULL),
(4, 6, 'Masculino', 'AB+', 'Látex', 'Asma', 'Salbutamol spray SOS', 'Nenhuma', 'Pai asmático', 'Não fuma, não bebe', 'Pratica natação 2x/semana', '2026-10-08 19:27:29', NULL),
(5, 7, 'Feminino', 'O-', 'Nega', 'Hipotireoidismo', 'Levotiroxina 75mcg 1x/dia', 'Cesariana (2019)', 'Mãe com tireoide', 'Não fuma, bebe socialmente', 'Acompanhamento endocrinológico regular', '2026-10-08 19:27:29', NULL),
(6, 8, 'Masculino', 'A-', 'Nega', 'Nenhuma', 'Nenhum', 'Nenhuma', 'Sem histórico', 'Ativo', 'Sem observações', '2026-10-08 19:27:29', NULL),
(7, 9, 'Feminino', 'O+', 'Nega', 'Enxaqueca crônica', 'Topiramato 50mg 2x/dia', 'Nenhuma', 'Mãe com enxaqueca', 'Não fuma', 'Boa resposta ao tratamento', '2026-10-08 19:27:29', NULL),
(8, 10, 'Masculino', 'B-', 'Nega', 'Nenhuma', 'Nenhum', 'Amigdalectomia (infância)', 'Sem histórico relevante', 'Não fuma, não bebe', 'Paciente jovem, saudável', '2026-10-08 19:27:29', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `sinalizacao`
--

CREATE TABLE `sinalizacao` (
  `id` int(11) NOT NULL,
  `consulta_id` int(11) NOT NULL,
  `papel` varchar(10) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `payload` mediumtext DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tokens_recuperacao`
--

CREATE TABLE `tokens_recuperacao` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tokens_recuperacao`
--

INSERT INTO `tokens_recuperacao` (`id`, `usuario_id`, `token`, `expira_em`, `usado`) VALUES
(1, 2, '544b428bf50cfce550ca39107e78dbf021e002e78971715a34adfde216dc8388', '2026-10-08 18:04:59', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `tipo` enum('comum','admin') DEFAULT 'comum',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `plano_saude` varchar(100) DEFAULT NULL,
  `plano_numero` varchar(50) DEFAULT NULL,
  `plano_validade` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `cpf`, `senha`, `telefone`, `data_nascimento`, `endereco`, `tipo`, `criado_em`, `plano_saude`, `plano_numero`, `plano_validade`) VALUES
(1, 'testeadm', 'testeadm@gmail.com', '00000000000', '$2y$10$EkkbgjxlpsdN57hlsoh5iuJeIG4lhDUmGVrYHBAYGD6B/iPNiLLQu', NULL, NULL, NULL, 'admin', '2026-10-08 14:26:08', NULL, NULL, NULL),
(2, 'testecomum', 'testecomum@gmail.com', '11111111111', '$2y$10$jl6oBkCubz.gOcWVvmoFp.HdhsZz4Pb0oG7FfbUTuxGMaGTffy3H2', NULL, '1995-03-10', NULL, 'comum', '2026-10-08 14:26:08', NULL, NULL, NULL),
(3, 'Maria Silva Santos', 'maria.silva@teste.lac', '12345678901', '$2y$10$dL8.OipgCnIV9QRLuXP/9.Ts1aYtFTHZ1RfBN8maCxLiDeaDDIRVq', '(41) 99811-2233', '1985-03-12', 'Rua das Acácias, 145 - Batel, Curitiba - PR', 'comum', '2026-10-08 19:22:37', NULL, NULL, NULL),
(4, 'João Pedro Oliveira', 'joao.oliveira@teste.lac', '23456789012', '$2y$10$yC47NQ89w.qb/N5u1SwH1uerm/XLCBFzqPj92OrOZxzcig.um6PNq', '(41) 99822-3344', '1978-07-25', 'Av. Sete de Setembro, 3200 - Centro, Curitiba - PR', 'comum', '2026-10-08 19:22:37', NULL, NULL, NULL),
(5, 'Ana Carolina Ferreira', 'ana.ferreira@teste.lac', '34567890123', '$2y$10$5oOhUtiz5PghEV2klsTn5uVHtRcNLq4/bOHY97Xz2GDG78VfnoqCW', '(41) 99833-4455', '1992-11-03', 'Rua Comendador Araújo, 88 - Batel, Curitiba - PR', 'comum', '2026-10-08 19:22:37', NULL, NULL, NULL),
(6, 'Carlos Eduardo Lima', 'carlos.lima@teste.lac', '45678901234', '$2y$10$B/lz8eImwZ0OtdCzkqSybO8L42X8P.NccmSBGJoJ1K2tHYSNP3xPm', '(41) 99844-5566', '1965-01-19', 'Rua Padre Anchieta, 1200 - Bigorrilho, Curitiba - PR', 'comum', '2026-10-08 19:22:37', NULL, NULL, NULL),
(7, 'Juliana Mendes Costa', 'juliana.costa@teste.lac', '56789012345', '$2y$10$/rWdlJcjpYdKlt3BOYVFWeZZe.xbQI3KZp3EoGtqbos4lwupCwaoK', '(41) 99855-6677', '1990-05-30', 'Al. Dr. Carlos de Carvalho, 417 - Batel, Curitiba - PR', 'comum', '2026-10-08 19:22:37', NULL, NULL, NULL),
(8, 'Ricardo Alves Souza', 'ricardo.souza@teste.lac', '67890123456', '$2y$10$nxR1alBbAO96eBRv4hbjwuasdxVDRR4awannV2dwKzfgXJsO54vh6', '(41) 99866-7788', '1982-09-14', 'Rua Itupava, 1500 - Alto da XV, Curitiba - PR', 'comum', '2026-10-08 19:22:37', NULL, NULL, NULL),
(9, 'Patrícia Gomes Rocha', 'patricia.rocha@teste.lac', '78901234567', '$2y$10$C8rLE4X9Du/uXof9KpHZNuIQIpuv76oLZWba/P9WOwILM7Mx3p/fS', '(41) 99877-8899', '1975-12-08', 'Av. República Argentina, 1250 - Água Verde, Curitiba - PR', 'comum', '2026-10-08 19:22:37', NULL, NULL, NULL),
(10, 'Fernando Barbosa Neto', 'fernando.neto@teste.lac', '89012345678', '$2y$10$gyciTbjZjI7alwo4FFutDOi9M8uuNtPtrdoCIHbfqWQM2XsMan2tS', '(41) 99888-9900', '1988-04-22', 'Rua Visconde de Nácar, 1400 - Centro, Curitiba - PR', 'comum', '2026-10-08 19:22:37', NULL, NULL, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity` (`entity`,`entity_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_tags` (`tags`);

--
-- Índices de tabela `atendimentos`
--
ALTER TABLE `atendimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `medico_id` (`medico_id`),
  ADD KEY `consulta_id` (`consulta_id`);

--
-- Índices de tabela `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `medico_id` (`medico_id`),
  ADD KEY `idx_status_data` (`status`,`data_hora`);

--
-- Índices de tabela `dependentes`
--
ALTER TABLE `dependentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `titular_id` (`titular_id`);

--
-- Índices de tabela `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_doc_paciente` (`paciente_id`,`criado_em`),
  ADD KEY `idx_doc_consulta` (`consulta_id`),
  ADD KEY `medico_id` (`medico_id`);

--
-- Índices de tabela `medicos`
--
ALTER TABLE `medicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `crm` (`crm`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `paciente_id` (`paciente_id`),
  ADD KEY `atualizado_por` (`atualizado_por`);

--
-- Índices de tabela `sinalizacao`
--
ALTER TABLE `sinalizacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sinal` (`consulta_id`,`id`);

--
-- Índices de tabela `tokens_recuperacao`
--
ALTER TABLE `tokens_recuperacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT de tabela `atendimentos`
--
ALTER TABLE `atendimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `dependentes`
--
ALTER TABLE `dependentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `medicos`
--
ALTER TABLE `medicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `sinalizacao`
--
ALTER TABLE `sinalizacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tokens_recuperacao`
--
ALTER TABLE `tokens_recuperacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `atendimentos`
--
ALTER TABLE `atendimentos`
  ADD CONSTRAINT `atendimentos_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `atendimentos_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`),
  ADD CONSTRAINT `atendimentos_ibfk_3` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`);

--
-- Restrições para tabelas `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `consultas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `consultas_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`);

--
-- Restrições para tabelas `dependentes`
--
ALTER TABLE `dependentes`
  ADD CONSTRAINT `dependentes_ibfk_1` FOREIGN KEY (`titular_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `documentos_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`),
  ADD CONSTRAINT `documentos_ibfk_3` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `planos`
--
ALTER TABLE `planos`
  ADD CONSTRAINT `planos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `prontuarios`
--
ALTER TABLE `prontuarios`
  ADD CONSTRAINT `prontuarios_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `prontuarios_ibfk_2` FOREIGN KEY (`atualizado_por`) REFERENCES `medicos` (`id`);

--
-- Restrições para tabelas `sinalizacao`
--
ALTER TABLE `sinalizacao`
  ADD CONSTRAINT `sinalizacao_ibfk_1` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tokens_recuperacao`
--
ALTER TABLE `tokens_recuperacao`
  ADD CONSTRAINT `tokens_recuperacao_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
