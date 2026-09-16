<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }

$conn = new mysqli('localhost', 'root', '', 'lac_centro_medico');
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

$id = (int)$_SESSION['usuario_id'];
$u = $conn->query("SELECT * FROM usuarios WHERE id = $id")->fetch_assoc();
$tipoUsuario = $u['tipo'] ?? 'comum';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="index.php" class="flex items-center space-x-3">
                <img src="logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
            </a>
            <div class="flex items-center gap-3">
                <?php if ($tipoUsuario === 'admin'): ?>
                    <a href="admin/dashboard.php" class="hidden sm:flex items-center gap-2 bg-[#F9F4EC] text-[#8C6D36] border border-[#E6D5B8] px-3.5 py-2 rounded-xl text-sm font-semibold hover:bg-[#E6D5B8]/50 transition">
                        <i class="fa-solid fa-shield-halved"></i> Painel Admin
                    </a>
                <?php endif; ?>
                <a href="index.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8 space-y-6">
        <section class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center font-bold text-[#8C6D36] text-xl">
                    <?php echo strtoupper(substr($u['nome'], 0, 2)); ?>
                </div>
                <div>
                    <h1 class="text-2xl font-serif text-[#3E352E]"><?php echo htmlspecialchars($u['nome']); ?></h1>
                    <p class="text-sm text-stone-500"><?php echo htmlspecialchars($u['email'] ?? 'Sem e-mail cadastrado'); ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-stone-100">
                <div>
                    <p class="text-xs uppercase font-bold text-stone-500">CPF</p>
                    <p class="text-stone-800"><?php echo htmlspecialchars($u['cpf'] ?? '—'); ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase font-bold text-stone-500">Telefone</p>
                    <p class="text-stone-800"><?php echo htmlspecialchars($u['telefone'] ?? '—'); ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase font-bold text-stone-500">Data de Nascimento</p>
                    <p class="text-stone-800"><?php echo !empty($u['data_nascimento']) ? date('d/m/Y', strtotime($u['data_nascimento'])) : '—'; ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase font-bold text-stone-500">Endereço</p>
                    <p class="text-stone-800"><?php echo htmlspecialchars($u['endereco'] ?? '—'); ?></p>
                </div>
            </div>

            <div class="pt-4 border-t border-stone-100 flex flex-wrap gap-3">
                <a href="trocar_senha.php"
                   class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-3 rounded-xl text-sm font-medium transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-key"></i> Trocar Senha
                </a>
                <a href="logout.php"
                   class="border border-stone-300 text-stone-700 px-5 py-3 rounded-xl text-sm font-medium hover:bg-stone-50 transition flex items-center gap-2">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair
                </a>
            </div>
        </section>
    </main>
</body>
</html>