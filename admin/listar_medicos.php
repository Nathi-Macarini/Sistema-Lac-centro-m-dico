<?php
session_start();
require __DIR__ . '/../conexao.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Exclusão do médico (tabela medicos)
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];

    // Se já tem atendimento registrado no prontuário, não pode apagar (histórico clínico)
    $st = $conn->prepare("SELECT COUNT(*) AS n FROM atendimentos WHERE medico_id = ?");
    $st->bind_param("i", $id);
    $st->execute();
    $temAtendimentos = (int)$st->get_result()->fetch_assoc()['n'] > 0;

    if ($temAtendimentos) {
        header("Location: listar_medicos.php?bloqueado=1");
        exit();
    }

    // Libera as consultas e a ficha vinculadas a ele e remove o cadastro
    $u = $conn->prepare("UPDATE consultas SET medico_id = NULL WHERE medico_id = ?");
    $u->bind_param("i", $id); $u->execute();
    $u = $conn->prepare("UPDATE prontuarios SET atualizado_por = NULL WHERE atualizado_por = ?");
    $u->bind_param("i", $id); $u->execute();
    $d = $conn->prepare("DELETE FROM medicos WHERE id = ?");
    $d->bind_param("i", $id); $d->execute();

    header("Location: listar_medicos.php?removido=1");
    exit();
}

// Médicos cadastrados (tabela medicos)
$st = $conn->prepare("SELECT id, nome, crm, especialidade, email, telefone FROM medicos ORDER BY nome ASC");
$st->execute();
$medicos = $st->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médicos Cadastrados - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

<header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <img src="../logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
            <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">Área do Administrador</span>
        </div>
        <a href="dashboard.php" class="text-xs text-stone-600 hover:text-[#8C6D36] font-medium transition flex items-center">
            <i class="fa-solid fa-arrow-left mr-1"></i> Voltar ao Painel
        </a>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-serif text-[#3E352E] mb-1">Médicos Cadastrados</h1>
            <p class="text-stone-600 text-sm">Profissionais de saúde cadastrados no sistema (<?php echo count($medicos); ?>).</p>
        </div>
        <a href="cadastrar_medico.php" class="bg-[#3E352E] text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A4A3A] transition shadow-sm inline-flex items-center">
            <i class="fa-solid fa-user-plus mr-2"></i> Novo Médico
        </a>
    </div>

    <?php if (isset($_GET['removido'])): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
            <i class="fa-solid fa-circle-check mr-1"></i> Médico excluído com sucesso.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['bloqueado'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
            <i class="fa-solid fa-circle-exclamation mr-1"></i> Este médico já tem atendimentos registrados em prontuários e não pode ser excluído.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['editado'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
            <i class="fa-solid fa-circle-check mr-1"></i> Dados do médico atualizados.
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-[#E6D5B8]/40 shadow-sm overflow-hidden">
        <?php if (!empty($medicos)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-stone-200 text-stone-500 text-xs uppercase tracking-wider bg-[#FAF9F6]">
                            <th class="py-3 px-4 font-semibold">ID</th>
                            <th class="py-3 px-4 font-semibold">Nome</th>
                            <th class="py-3 px-4 font-semibold">CRM</th>
                            <th class="py-3 px-4 font-semibold">Especialidade</th>
                            <th class="py-3 px-4 font-semibold">E-mail</th>
                            <th class="py-3 px-4 font-semibold">Telefone</th>
                            <th class="py-3 px-4 font-semibold text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-sm">
                        <?php foreach ($medicos as $m): ?>
                            <tr class="hover:bg-[#FAF9F6]/60 transition">
                                <td class="py-4 px-4 font-mono text-xs text-stone-500">#<?php echo (int)$m['id']; ?></td>
                                <td class="py-4 px-4 font-medium text-stone-800"><?php echo h($m['nome']); ?></td>
                                <td class="py-4 px-4 text-stone-600"><?php echo h($m['crm'] ?: '—'); ?></td>
                                <td class="py-4 px-4 text-stone-600"><?php echo h($m['especialidade'] ?: '—'); ?></td>
                                <td class="py-4 px-4 text-stone-600"><?php echo h($m['email'] ?: '—'); ?></td>
                                <td class="py-4 px-4 text-stone-600"><?php echo h($m['telefone'] ?: '—'); ?></td>
                                <td class="py-4 px-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="editar_medico.php?id=<?php echo (int)$m['id']; ?>"
                                       class="inline-flex items-center px-3 py-1.5 bg-stone-100 text-stone-700 rounded-lg text-xs font-medium hover:bg-stone-200 transition">
                                        <i class="fa-solid fa-pen-to-square mr-1 text-[#8C6D36]"></i> Editar
                                    </a>
                                    <a href="listar_medicos.php?excluir=<?php echo (int)$m['id']; ?>"
                                       onclick="return confirm('Tem certeza que deseja excluir este médico?');"
                                       class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 rounded-lg text-xs font-medium hover:bg-red-100 transition">
                                        <i class="fa-solid fa-trash mr-1"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fa-solid fa-user-doctor text-3xl text-stone-300 mb-3"></i>
                <p class="text-stone-500 font-medium">Nenhum médico cadastrado no sistema até o momento.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>