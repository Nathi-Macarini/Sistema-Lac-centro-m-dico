<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'lac_centro_medico');
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

$msg = ""; $tipo = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $conn->real_escape_string($_POST['login']);
    $sql = "SELECT * FROM usuarios WHERE email = '$login' OR cpf = '$login'";
    $res = $conn->query($sql);

    if ($res->num_rows == 1) {
        $user = $res->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $stmt = $conn->prepare("INSERT INTO tokens_recuperacao (usuario_id, token, expira_em) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $token, $expira);
        $stmt->execute();

        // Em produção: enviar por e-mail. Aqui mostramos na tela:
        $msg = "Link de recuperação gerado (válido por 30 min):<br>
                <a class='text-[#8C6D36] underline font-semibold' 
                   href='redefinir_senha.php?token=$token'>Redefinir minha senha</a>";
        $tipo = "sucesso";
    } else {
        $msg = "Nenhum usuário encontrado com esse e-mail ou CPF.";
        $tipo = "erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] h-screen flex items-center justify-center font-sans antialiased">
    <div class="bg-white p-8 rounded-3xl border border-[#E6D5B8] shadow-xl max-w-md w-full space-y-6 mx-4">
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-serif text-[#3E352E]">Recuperar Senha</h1>
            <p class="text-sm text-stone-500">Informe seu e-mail ou CPF cadastrado</p>
        </div>

        <?php if($msg): ?>
            <div class="<?php echo $tipo==='sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">E-mail ou CPF</label>
                <input type="text" name="login" required
                       class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]"
                       placeholder="seu@email.com ou 000.000.000-00">
            </div>
            <button type="submit" class="w-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white py-3.5 rounded-xl font-medium transition">
                Enviar link de recuperação
            </button>
        </form>

        <div class="text-center">
            <a href="login.php" class="text-xs text-[#8C6D36] hover:underline font-medium">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao login
            </a>
        </div>
    </div>
</body>
</html>