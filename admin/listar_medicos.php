<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
$conn = new mysqli('localhost', 'root', '', 'lac_centro_medico');
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

$res = $conn->query("SELECT * FROM medicos ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médicos - Admin LAC</title>
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
            <h1 class="text-2xl font-serif text-[#3E352E]">Médicos Cadastrados</h1>
            <a href="cadastrar_medico.php" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-3 rounded-xl text-sm font-medium transition">
                <i class="fa-solid fa-user-doctor mr-1"></i> Novo Médico
            </a>
        </div>

        <section class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F9F4EC] text-[#5A4A3A] text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nome</th>
                            <th class="px-4 py-3 text-left">CRM</th>
                            <th class="px-4 py-3 text-left">Especialidade</th>
                            <th class="px-4 py-3 text-left">E-mail</th>
                            <th class="px-4 py-3 text-left">Telefone</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if ($res && $res->num_rows > 0): ?>
                            <?php while($m = $res->fetch_assoc()): ?>
                                <tr class="hover:bg-[#FAF9F6]">
                                    <td class="px-4 py-3 text-stone-500">#<?php echo $m['id']; ?></td>
                                    <td class="px-4 py-3 font-medium text-stone-800"><?php echo htmlspecialchars($m['nome']); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo htmlspecialchars($m['crm']); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo htmlspecialchars($m['especialidade']); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo htmlspecialchars($m['email'] ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo htmlspecialchars($m['telefone'] ?? '—'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="px-4 py-8 text-center text-stone-500">Nenhum médico cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>