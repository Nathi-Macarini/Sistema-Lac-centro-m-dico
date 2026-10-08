<?php
session_start();
require __DIR__ . '/../conexao.php';

// Só o administrador acessa
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ---------- Exclusão do usuário (tabela usuarios) ----------
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];

    // Bloqueia se tiver qualquer vínculo clínico:
    //  - consultas agendadas/realizadas
    //  - prontuário
    //  - atendimentos
    //  - documentos (receitas / atestados)
    $temVinculo = false;

    $q = $conn->prepare("SELECT COUNT(*) AS n FROM consultas WHERE usuario_id = ?");
    $q->bind_param("i", $id); $q->execute();
    if ((int)$q->get_result()->fetch_assoc()['n'] > 0) $temVinculo = true;

    if (!$temVinculo) {
        $q = $conn->prepare("SELECT COUNT(*) AS n FROM prontuarios WHERE paciente_id = ?");
        $q->bind_param("i", $id); $q->execute();
        if ((int)$q->get_result()->fetch_assoc()['n'] > 0) $temVinculo = true;
    }
    if (!$temVinculo) {
        $q = $conn->prepare("SELECT COUNT(*) AS n FROM atendimentos WHERE paciente_id = ?");
        $q->bind_param("i", $id); $q->execute();
        if ((int)$q->get_result()->fetch_assoc()['n'] > 0) $temVinculo = true;
    }
    if (!$temVinculo) {
        $q = $conn->prepare("SELECT COUNT(*) AS n FROM documentos WHERE paciente_id = ?");
        $q->bind_param("i", $id); $q->execute();
        if ((int)$q->get_result()->fetch_assoc()['n'] > 0) $temVinculo = true;
    }

    if ($temVinculo) {
        header("Location: listar_usuarios.php?bloqueado=1");
        exit();
    }

    // Sem vínculo: pode excluir. Limpa tokens de recuperação antes.
    $d = $conn->prepare("DELETE FROM tokens_recuperacao WHERE usuario_id = ?");
    $d->bind_param("i", $id); $d->execute();

    $d = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $d->bind_param("i", $id); $d->execute();

    header("Location: listar_usuarios.php?removido=1");
    exit();
}

// ---------- Lista de usuários ----------
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
            <div class="flex items-center space-x-3">
                <img src="../logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">Área do Administrador</span>
            </div>
            <a href="dashboard.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-serif text-[#3E352E]">Usuários Cadastrados</h1>
                <p class="text-stone-600 text-sm">Pacientes e administradores do sistema.</p>
            </div>
            <a href="cadastrar_usuario.php" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-3 rounded-xl text-sm font-medium transition">
                <i class="fa-solid fa-user-plus mr-1"></i> Novo Usuário
            </a>
        </div>

        <?php if (isset($_GET['removido'])): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
                <i class="fa-solid fa-circle-check mr-1"></i> Usuário excluído com sucesso.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['bloqueado'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> Este usuário já possui histórico clínico (consultas, prontuário, atendimentos ou documentos) e não pode ser excluído. Se precisar, edite os dados dele.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['editado'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
                <i class="fa-solid fa-circle-check mr-1"></i> Dados do usuário atualizados.
            </div>
        <?php endif; ?>

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
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php if ($res && $res->num_rows > 0): ?>
                            <?php while($u = $res->fetch_assoc()): ?>
                                <tr class="hover:bg-[#FAF9F6]">
                                    <td class="px-4 py-3 font-mono text-xs text-stone-500">#<?php echo (int)$u['id']; ?></td>
                                    <td class="px-4 py-3 font-medium text-stone-800"><?php echo h($u['nome']); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo h($u['email'] ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo $u['cpf'] ? h(formatarCpf($u['cpf'])) : '—'; ?></td>
                                    <td class="px-4 py-3 text-stone-600"><?php echo h($u['telefone'] ?? '—'); ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($u['tipo'] === 'admin'): ?>
                                            <span class="text-xs font-bold bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full">Admin</span>
                                        <?php else: ?>
                                            <span class="text-xs font-bold bg-stone-100 text-stone-700 px-2.5 py-1 rounded-full">Comum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                        <a href="editar_usuario.php?id=<?php echo (int)$u['id']; ?>"
                                           class="inline-flex items-center px-3 py-1.5 bg-stone-100 text-stone-700 rounded-lg text-xs font-medium hover:bg-stone-200 transition">
                                            <i class="fa-solid fa-pen-to-square mr-1 text-[#8C6D36]"></i> Editar
                                        </a>
                                        <a href="listar_usuarios.php?excluir=<?php echo (int)$u['id']; ?>"
                                           onclick="return confirm('Tem certeza que deseja excluir este usuário?');"
                                           class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 rounded-lg text-xs font-medium hover:bg-red-100 transition">
                                            <i class="fa-solid fa-trash mr-1"></i> Excluir
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="px-4 py-8 text-center text-stone-500">Nenhum usuário cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>