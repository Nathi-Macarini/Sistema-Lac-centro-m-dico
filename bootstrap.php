<?php
// ============================================================
// BOOTSTRAP - Carregado por TODOS os arquivos do sistema
// Responsável por: autoload, sessão, timezone e AUTENTICAÇÃO
// ============================================================

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

// ============================================================
// PROTEÇÃO GLOBAL DE AUTENTICAÇÃO
// ============================================================
// Detecta se o usuário está logado (paciente/admin OU médico)
$estaLogado = !empty($_SESSION['usuario_id']) || !empty($_SESSION['medico_id']);

if (!$estaLogado) {
    // Descobre o caminho do arquivo atual (relativo ao APP_ROOT)
    $arquivoAtual = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

    // Arquivos PÚBLICOS (não exigem login)
    $arquivosPublicos = [
        '/login.php',
        '/logout.php',           // logout precisa rodar sem login pra limpar sessão
        '/cadastro.php',         // se você tiver cadastro de paciente
        '/registro.php',
        '/esqueci_senha.php',
        '/redefinir_senha.php',
    ];

    // Também liberamos qualquer arquivo dentro de /api/ que seja público,
    // mas por padrão APIs exigem login (exceto se você quiser tratar separado)

    $caminhoRelativo = $arquivoAtual;

    // Se estiver dentro de uma subpasta (ex: /admin/dashboard.php),
    // consideramos o "nome do arquivo" como o último segmento
    $nomeArquivo = '/' . basename($arquivoAtual);

    // Exceções: arquivos públicos
    $ehPublico = in_array($nomeArquivo, $arquivosPublicos, true);

    // Exceção extra: se for uma chamada AJAX para /api/ que já valida por conta própria,
    // você pode liberar. Por segurança, deixamos BLOQUEADO por padrão.
    // Se precisar liberar, descomente a linha abaixo:
    // if (strpos($arquivoAtual, '/api/') !== false) { $ehPublico = true; }

    if (!$ehPublico) {
        // Se for uma chamada AJAX/API, retorna 401 em JSON
        $ehAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || strpos($arquivoAtual, '/api/') !== false;

        if ($ehAjax) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => 'não autenticado']);
            exit;
        }

        // Redirecionamento normal
        // Calcula o caminho até o login.php considerando a profundidade
        $profundidade = substr_count(trim(dirname($arquivoAtual), '/'), '/');
        $prefixo = str_repeat('../', $profundidade);
        // Se estiver na raiz, $profundidade = 0 → $prefixo = ''
        // Se estiver em /admin/, $profundidade = 1 → $prefixo = '../'
        // Se estiver em /admin/logs/, $profundidade = 2 → $prefixo = '../../'

        header("Location: {$prefixo}login.php");
        exit;
    }
}