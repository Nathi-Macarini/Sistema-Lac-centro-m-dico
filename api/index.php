<?php
// api/index.php — Entry point único para o Vercel

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

// Se for vazio, vai para o login
if ($uri === '') {
    $uri = 'login.php';
}

// Sanitiza o caminho (evita path traversal)
$uri = str_replace(['..', "\0"], '', $uri);

// Caminho absoluto dentro do projeto
$caminho = __DIR__ . '/../' . $uri;

// Se for um arquivo PHP, executa
if (is_file($caminho) && pathinfo($caminho, PATHINFO_EXTENSION) === 'php') {
    chdir(dirname($caminho));
    require $caminho;
    exit;
}

// Se for um arquivo estático (css, js, imagem), serve
if (is_file($caminho)) {
    $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'json' => 'application/json',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    readfile($caminho);
    exit;
}

// 404
http_response_code(404);
echo "Página não encontrada: " . htmlspecialchars($uri);