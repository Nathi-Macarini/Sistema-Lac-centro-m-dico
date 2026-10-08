<?php
require_once __DIR__ . '/bootstrap.php';

use App\ActivityLogger;

// Logar ANTES de destruir a sessão
if (!empty($_SESSION['usuario_id'])) {
    ActivityLogger::log(
        action: 'logout',
        description: 'Usuário saiu do sistema'
    );
}

session_destroy();
header('Location: login.php');
exit;