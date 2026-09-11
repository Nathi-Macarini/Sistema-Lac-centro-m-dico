<?php
session_start();
$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = new mysqli('localhost', 'root', '', 'lac_centro_medico');
    
    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }

    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = '$email' AND senha = '$senha'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nome_usuario'] = $user['nome'];
        $_SESSION['tipo_usuario'] = $user['tipo'];
        
        header("Location: index.php");
        exit;
    } else {
        $erro = "E-mail ou senha inválidos.";
    }
    $conn->close();
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
<body class="bg-[#FAF9F6] h-screen flex items-center justify-center font-sans antialiased">
    <div class="bg-white p-8 rounded-3xl border border-[#E6D5B8] shadow-xl max-w-md w-full space-y-6 mx-4">
        <div class="text-center space-y-3 flex flex-col items-center">
            <!-- Exibindo a logo_lac.png no topo da tela de login -->
            <img src="logo_lac.png" alt="LAC Centro Médico" class="h-14 w-auto object-contain mb-1">
            <h1 class="text-2xl font-serif text-[#3E352E]">Portal do Paciente</h1>
            <p class="text-sm text-stone-500">Entre com suas credenciais para continuar</p>
        </div>

        <?php if($erro): ?>
            <div class="bg-red-50 text-red-700 p-3.5 rounded-xl text-sm border border-red-200 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">E-mail</label>
                <input type="email" name="email" required class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]" placeholder="seu@email.com">
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Senha</label>
                <input type="password" name="senha" required class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]" placeholder="••••••••">
            </div>
            <button type="submit" class="w-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white py-3.5 rounded-xl font-medium transition shadow-sm">Acessar Sistema</button>
        </form>
        
        <div class="border-t border-stone-100 pt-4 text-center">
            <p class="text-xs text-stone-400">Dica de acesso: <span class="text-stone-600 font-medium">joao@email.com</span> / Senha: <span class="text-stone-600 font-medium">123456</span></p>
        </div>
    </div>
</body>
</html>