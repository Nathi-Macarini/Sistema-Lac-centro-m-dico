<?php
require_once __DIR__ . '/bootstrap.php';

use App\ActivityLogger;

require __DIR__ . '/conexao.php';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($login !== '' && $senha !== '') {

        // 1) Paciente ou administrador (tabela usuarios) — por e-mail ou CPF
        $cpfBusca = preg_replace('/\D/', '', $login);
        $stmt = $conn->prepare("SELECT id, nome, senha, tipo FROM usuarios WHERE email = ? OR (cpf IS NOT NULL AND cpf = ?)");
        $stmt->bind_param("ss", $login, $cpfBusca);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();

        if ($usuario && conferirSenha($senha, $usuario['senha'])) {
    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION['usuario_id']   = $usuario['id'];
    $_SESSION['nome_usuario'] = $usuario['nome'];
    $_SESSION['tipo_usuario'] = $usuario['tipo'];

    // 👇 ADICIONE ESTAS LINHAS
    ActivityLogger::log(
        action: 'login',
        description: "Usuário {$usuario['nome']} fez login ({$usuario['tipo']})"
    );

    if ($usuario['tipo'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

        // 2) Médico (tabela medicos) — por e-mail ou CRM
        $stmt = $conn->prepare("SELECT id, nome, senha FROM medicos WHERE email = ? OR crm = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $medico = $stmt->get_result()->fetch_assoc();

        if ($medico && conferirSenha($senha, $medico['senha'])) {
    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION['medico_id']    = $medico['id'];
    $_SESSION['nome_usuario'] = $medico['nome'];
    $_SESSION['tipo_usuario'] = 'medico';

    // 👇 ADICIONE ESTAS LINHAS
    ActivityLogger::log(
        action: 'login',
        description: "Médico {$medico['nome']} fez login"
    );

    header("Location: medico/dashboard.php");
    exit();
}
// Registra tentativa falha (útil contra ataques de força bruta)
ActivityLogger::log(
    action: 'login_failed',
    description: "Tentativa de login falhou para: " . substr($login, 0, 3) . "***"
);
        $erro = "E-mail ou senha inválidos.";
    } else {
        $erro = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen flex items-center justify-center p-4">

<div class="max-w-md w-full bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
    <div class="text-center space-y-2">
        <img src="logo_lac.png" alt="LAC Centro Médico" class="h-12 mx-auto object-contain">
        <h1 class="text-2xl font-serif text-[#3E352E]">Acesse sua conta</h1>
        <p class="text-xs text-stone-500">Entre com suas credenciais para continuar</p>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
            <i class="fa-solid fa-circle-exclamation mr-1"></i> <?php echo h($erro); ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" class="space-y-4">
        <div>
            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">E-mail, CPF ou CRM</label>
            <input type="text" name="email" required 
                   class="w-full px-4 py-3 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]">
        </div>

        <div>
            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Senha</label>
            <input type="password" name="senha" required 
                   class="w-full px-4 py-3 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]">
        </div>

        <button type="submit" 
                class="w-full bg-[#3E352E] text-white py-3 rounded-xl font-medium hover:bg-[#5A4A3A] transition shadow-sm text-sm">
            Entrar
        </button>
    </form>

    <div class="text-center">
        <a href="esqueci_senha.php" class="text-xs text-[#8C6D36] hover:underline font-medium">
            Esqueci minha senha
        </a>
    </div>
</div>

</body>
</html>