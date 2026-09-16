<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
$conn = new mysqli('localhost', 'root', '', 'lac_centro_medico');
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

$res = $conn->query("SELECT id, nome, email, cpf, telefone, tipo, criado_em FROM usuarios ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Admin LAC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <img src="../logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
            <a href="dashboard.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-2xl font-serif text-[#3E352E]">Usuários Cadastrados</h1>
            <a href="cadastrar_usuario.php" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-3 rounded-xl text-sm font-medium transition">
                <i class="fa-solid fa-user-plus mr-1"></i> Novo Usuário
            </a>
        </div>

        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nome</th>
                            <th class="px-4 py-3 text-left">E-mail</th>
                            <th class="px-4 py-3 text-left">CPF</th>
                            <th class="px-4 py-3 text-left">Telefone</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if ($res && $res->num_rows > 0): ?>
                            <?php while($u = $res->fetch_assoc()): ?>
                                <tr class="hover:bg-[#FAF9F6]">
                                    <td class="px-4 py-3 text-stone-500">#<?php echo $u['id']; ?></td>
                                    <td class="px-4 py-3 font-medium text-stone-800"><?php echo htmlspecialchars($u['nome']); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo htmlspecialchars($u['email'] ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo htmlspecialchars($u['cpf'] ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo htmlspecialchars($u['telefone'] ?? '—'); ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($u['tipo'] === 'admin'): ?>
                                            <span class="text-xs font-bold bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full">Admin</span>
                                        <?php else: ?>
                                            <span class="text-xs font-bold bg-stone-100 text-stone-700 px-2.5 py-1 rounded-full">Comum</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="px-4 py-8 text-center text-stone-500">Nenhum usuário cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>