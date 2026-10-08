<?php
header('Content-Type: application/json');

echo json_encode([
    'DB_HOST' => getenv('DB_HOST') ?: '(vazio)',
    'DB_PORT' => getenv('DB_PORT') ?: '(vazio)',
    'DB_USER' => getenv('DB_USER') ?: '(vazio)',
    'DB_NAME' => getenv('DB_NAME') ?: '(vazio)',
    'DB_PASS_definida' => getenv('DB_PASS') ? 'SIM' : 'NÃO',
    'todas_env' => array_filter($_ENV + $_SERVER, fn($k) => str_starts_with($k, 'DB_'), ARRAY_FILTER_USE_KEY),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);