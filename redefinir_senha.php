<?php
$conn = new mysqli('localhost', 'root', '', 'lac_centro_medico');
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$msg = ""; $tipo = ""; $valido = false;

if ($token) {
    $stmt = $conn->prepare("SELECT * FROM tokens_recuperacao 
                            WHERE token = ? AND usado = 0 AND expira_em > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 1) {
        $valido = true;
        $row = $res->fetch_assoc();
    } else {
        $msg = "Token inválido ou expirado. Solicite um novo link.";
        $tipo = "erro";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valido) {
    $nova = $_POST['nova_senha'];
    $conf = $_POST['confirmar_senha'];

    if ($nova !== $conf) {
        $msg = "As senhas não coincidem."; $tipo = "erro";
    } elseif (strlen($nova) < 6) {
        $msg = "A senha deve ter pelo menos 6 caracteres."; $tipo = "erro";
    } else {
        $upd = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $upd->bind_param("si", $nova, $row['usuario_id']);
        $upd->execute();

        $mark = $conn->prepare("UPDATE tokens_recuperacao SET usado = 1 WHERE id = ?");
        $mark->bind_param("i", $row['id']);
        $mark->execute();

        $msg = "Senha alterada com sucesso! <a href='login.php' class='underline font-semibold'>Ir para o login</a>";
        $tipo = "sucesso";
        $valido = false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] h-screen flex items-center justify-center font-sans antialiased">
    <div class="bg-white p-8 rounded-3xl border border-[#E6D5B8] shadow-xl max-w-md w-full space-y-6 mx-4">
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-serif text-[#3E352E]">Nova Senha</h1>
            <p class="text-sm text-stone-500">Defina sua nova senha de acesso</p>
        </div>

        <?php if($msg): ?>
            <div class="<?php echo $tipo==='sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if($valido): ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div>
                <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Nova Senha</label>
                <input type="password" name="nova_senha" required minlength="6"
                       class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Confirmar Nova Senha</label>
                <input type="password" name="confirmar_senha" required minlength="6"
                       class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
            </div>
            <button type="submit" class="w-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white py-3.5 rounded-xl font-medium transition">
                Salvar nova senha
            </button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>