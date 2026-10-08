<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ---------- Exclusão do médico ----------
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];

    // Bloqueia se já tem atendimento registrado em prontuário
    $st = $conn->prepare("SELECT COUNT(*) AS n FROM atendimentos WHERE medico_id = ?");
    $st->bind_param("i", $id);
    $st->execute();
    $temAtendimentos = (int)$st->get_result()->fetch_assoc()['n'] > 0;

    if ($temAtendimentos) {
        header("Location: listar_medicos.php?bloqueado=1");
        exit();
    }

    // Libera consultas e prontuários vinculados, depois remove
    $u = $conn->prepare("UPDATE consultas SET medico_id = NULL WHERE medico_id = ?");
    $u->bind_param("i", $id); $u->execute();

    $u = $conn->prepare("UPDATE prontuarios SET atualizado_por = NULL WHERE atualizado_por = ?");
    $u->bind_param("i", $id); $u->execute();

    $d = $conn->prepare("DELETE FROM medicos WHERE id = ?");
    $d->bind_param("i", $id); $d->execute();

    header("Location: listar_medicos.php?removido=1");
    exit();
}

// ---------- Lista de médicos ----------
$st = $conn->prepare("SELECT id, nome, crm, especialidade, email, telefone FROM medicos ORDER BY nome ASC");
$st->execute();
$medicos = $st->get_result()->fetch_all(MYSQLI_ASSOC);
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
            <div class="flex items-center space-x-3">
                <img src="../logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Painel Administrativo
                </span>
            </div>
            <a href="dashboard.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">

        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-serif text-[#3E352E]">Médicos Cadastrados</h1>
                <p class="text-stone-600 text-sm">Profissionais de saúde cadastrados no sistema.</p>
            </div>
            <a href="cadastrar_medico.php" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-3 rounded-xl text-sm font-medium transition">
                <i class="fa-solid fa-user-doctor mr-1"></i> Novo Médico
            </a>
        </div>

        <?php if (isset($_GET['removido'])): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
                <i class="fa-solid fa-circle-check mr-1"></i> Médico excluído com sucesso.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['bloqueado'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> Este médico já possui atendimentos registrados em prontuários e não pode ser excluído.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['editado'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
                <i class="fa-solid fa-circle-check mr-1"></i> Dados do médico atualizados.
            </div>
        <?php endif; ?>

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
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if (!empty($medicos)): ?>
                            <?php foreach ($medicos as $m): ?>
                                <tr class="hover:bg-[#FAF9F6]">
                                    <td class="px-4 py-3 font-mono text-xs text-stone-500">#<?php echo (int)$m['id']; ?></td>
                                    <td class="px-4 py-3 font-medium text-stone-800"><?php echo h($m['nome']); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo h($m['crm'] ?: '—'); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo h($m['especialidade'] ?: '—'); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo h($m['email'] ?: '—'); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo h($m['telefone'] ?: '—'); ?></td>
                                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
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
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-stone-500">
                                    Nenhum médico cadastrado no sistema até o momento.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>