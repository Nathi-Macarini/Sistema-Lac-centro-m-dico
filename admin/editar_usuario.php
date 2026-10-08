<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

// ============================================================
// 1. AUTENTICAÇÃO
// ============================================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ============================================================
// 2. PEGA O ID (ANTES DE QUALQUER POST)
// ============================================================
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar_usuarios.php");
    exit;
}

// ============================================================
// 3. BUSCA O USUÁRIO
// ============================================================
$st = $conn->prepare("SELECT id, nome, email, cpf, telefone, data_nascimento, endereco, tipo FROM usuarios WHERE id = ?");
$st->bind_param("i", $id);
$st->execute();
$u = $st->get_result()->fetch_assoc();

if (!$u) {
    header("Location: listar_usuarios.php");
    exit;
}

// ============================================================
// 4. VARIÁVEIS DE ESTADO
// ============================================================
$erro = '';
$avisoRedefinicao = '';
$avisoPlano = '';
$avisoDependente = '';

// Controle de admin único
$qAdmins = $conn->query("SELECT COUNT(*) AS n FROM usuarios WHERE tipo = 'admin'");
$totalAdmins = (int)$qAdmins->fetch_assoc()['n'];
$ehUltimoAdmin = ($u['tipo'] === 'admin' && $totalAdmins <= 1);

// ============================================================
// 5. AÇÃO: REDEFINIR SENHA (HASH BCRYPT)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'redefinir_senha') {
    $cpfLimpo = preg_replace('/\D/', '', (string)$u['cpf']);

    if ($cpfLimpo === '') {
        $erro = "Este usuário não tem CPF cadastrado. Não é possível redefinir a senha para o CPF.";
    } else {
        $senhaHash = password_hash($cpfLimpo, PASSWORD_DEFAULT);

        $up = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        if (!$up) {
            $erro = "Erro interno: " . $conn->error;
        } else {
            $up->bind_param("si", $senhaHash, $id);

            if ($up->execute()) {
                if ($up->affected_rows > 0) {
                    $avisoRedefinicao = "Senha redefinida com sucesso. A nova senha é o CPF do usuário (somente números). Oriente-o a trocar no primeiro acesso.";

                    // Log de auditoria
                    if (class_exists('App\\ActivityLogger')) {
                        \App\ActivityLogger::log(
                            action: 'senha_redefinida',
                            entity: 'usuario',
                            entityId: $id,
                            description: "Admin redefiniu a senha do usuário {$u['nome']}",
                            metadata: [
                                'usuario_id'   => $id,
                                'usuario_nome' => $u['nome'],
                            ],
                            tags: ['usuario', 'senha']
                        );
                    }
                } else {
                    $erro = "Nada foi alterado. Verifique se o usuário existe.";
                }
            } else {
                $erro = "Erro ao redefinir senha: " . $up->error;
            }
        }
    }
}

// ============================================================
// 6. AÇÃO: SALVAR EDIÇÃO DOS DADOS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $cpf      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $nasc     = trim($_POST['data_nascimento'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $tipo     = $_POST['tipo'] ?? 'comum';
    if (!in_array($tipo, ['comum', 'admin'], true)) $tipo = 'comum';

    if ($nome === '') {
        $erro = "O nome é obrigatório.";
    } elseif ($email === '' && $cpf === '') {
        $erro = "Informe pelo menos E-mail ou CPF.";
    } elseif ($ehUltimoAdmin && $tipo !== 'admin') {
        $erro = "Este é o único administrador. Não é possível rebaixá-lo para comum.";
    } else {
        // Verifica duplicidade
        $dup = false;
        if ($email !== '') {
            $q = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ?");
            $q->bind_param("si", $email, $id);
            $q->execute();
            if ($q->get_result()->fetch_assoc()) $dup = true;
        }
        if (!$dup && $cpf !== '') {
            $q = $conn->prepare("SELECT id FROM usuarios WHERE cpf = ? AND id <> ?");
            $q->bind_param("si", $cpf, $id);
            $q->execute();
            if ($q->get_result()->fetch_assoc()) $dup = true;
        }

        if ($dup) {
            $erro = "Já existe outro usuário com esse e-mail ou CPF.";
        } else {
            $emailEsc = $email !== '' ? $email : null;
            $cpfEsc   = $cpf !== ''   ? $cpf   : null;
            $nascEsc  = $nasc !== ''  ? $nasc  : null;

            $up = $conn->prepare("UPDATE usuarios
                SET nome = ?, email = ?, cpf = ?, telefone = ?, data_nascimento = ?, endereco = ?, tipo = ?
                WHERE id = ?");
            $up->bind_param("sssssssi", $nome, $emailEsc, $cpfEsc, $telefone, $nascEsc, $endereco, $tipo, $id);

            if ($up->execute()) {
                header("Location: listar_usuarios.php?editado=1");
                exit;
            }
            $erro = "Erro ao salvar: " . $up->error;
        }
    }

    // Atualiza dados na tela em caso de erro
    $u = [
        'id' => $id, 'nome' => $nome, 'email' => $email, 'cpf' => $cpf,
        'telefone' => $telefone, 'data_nascimento' => $nasc,
        'endereco' => $endereco, 'tipo' => $tipo,
    ];
    $qAdmins = $conn->query("SELECT COUNT(*) AS n FROM usuarios WHERE tipo = 'admin'");
    $totalAdmins = (int)$qAdmins->fetch_assoc()['n'];
    $ehUltimoAdmin = ($u['tipo'] === 'admin' && $totalAdmins <= 1);
}

// ============================================================
// 7. AÇÃO: ADICIONAR PLANO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'add_plano') {
    $operadora = trim($_POST['operadora'] ?? '');
    $numero    = trim($_POST['numero_carteirinha'] ?? '');
    $validade  = trim($_POST['validade'] ?? '');
    $acomod    = trim($_POST['acomodacao'] ?? '');
    $obs       = trim($_POST['observacoes'] ?? '');

    if ($operadora === '') {
        $erro = "Informe o nome da operadora do plano.";
    } else {
        $validadeEsc = $validade !== '' ? $validade : null;
        $numeroEsc   = $numero !== '' ? $numero : null;
        $acomodEsc   = $acomod !== '' ? $acomod : null;
        $obsEsc      = $obs !== '' ? $obs : null;

        $ins = $conn->prepare("INSERT INTO planos (usuario_id, operadora, numero_carteirinha, validade, acomodacao, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->bind_param("isssss", $id, $operadora, $numeroEsc, $validadeEsc, $acomodEsc, $obsEsc);
        if ($ins->execute()) {
            $avisoPlano = "Plano cadastrado com sucesso.";
        } else {
            $erro = "Erro ao cadastrar plano: " . $ins->error;
        }
    }
}

// ============================================================
// 8. AÇÃO: REMOVER PLANO
// ============================================================
if (isset($_GET['remover_plano'])) {
    $planoId = (int)$_GET['remover_plano'];
    $del = $conn->prepare("DELETE FROM planos WHERE id = ? AND usuario_id = ?");
    $del->bind_param("ii", $planoId, $id);
    $del->execute();
    header("Location: editar_usuario.php?id=$id&plano_removido=1");
    exit;
}
if (isset($_GET['plano_removido'])) $avisoPlano = "Plano removido.";

// ============================================================
// 9. AÇÃO: ADICIONAR DEPENDENTE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'add_dependente') {
    $nomeDep    = trim($_POST['dep_nome'] ?? '');
    $cpfDep     = preg_replace('/\D/', '', $_POST['dep_cpf'] ?? '');
    $nascDep    = trim($_POST['dep_nascimento'] ?? '');
    $parentesco = trim($_POST['dep_parentesco'] ?? '');

    if ($nomeDep === '' || $parentesco === '') {
        $erro = "Preencha o nome e o parentesco do dependente.";
    } else {
        $cpfDepEsc  = $cpfDep !== '' ? $cpfDep : null;
        $nascDepEsc = $nascDep !== '' ? $nascDep : null;
        $ins = $conn->prepare("INSERT INTO dependentes (titular_id, nome, cpf, data_nascimento, parentesco) VALUES (?, ?, ?, ?, ?)");
        $ins->bind_param("issss", $id, $nomeDep, $cpfDepEsc, $nascDepEsc, $parentesco);
        if ($ins->execute()) {
            $avisoDependente = "Dependente cadastrado com sucesso.";
        } else {
            $erro = "Erro ao cadastrar dependente: " . $ins->error;
        }
    }
}

// ============================================================
// 10. AÇÃO: REMOVER DEPENDENTE
// ============================================================
if (isset($_GET['remover_dependente'])) {
    $depId = (int)$_GET['remover_dependente'];
    $del = $conn->prepare("DELETE FROM dependentes WHERE id = ? AND titular_id = ?");
    $del->bind_param("ii", $depId, $id);
    $del->execute();
    header("Location: editar_usuario.php?id=$id&dep_removido=1");
    exit;
}
if (isset($_GET['dep_removido'])) $avisoDependente = "Dependente removido.";

// ============================================================
// 11. BUSCA PLANOS E DEPENDENTES PARA A TELA
// ============================================================
$planosStmt = $conn->prepare("SELECT * FROM planos WHERE usuario_id = ? ORDER BY criado_em DESC");
$planosStmt->bind_param("i", $id);
$planosStmt->execute();
$planos = $planosStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$depsStmt = $conn->prepare("SELECT * FROM dependentes WHERE titular_id = ? ORDER BY nome ASC");
$depsStmt->bind_param("i", $id);
$depsStmt->execute();
$dependentes = $depsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ============================================================
// 12. CLASSES DE ESTILO
// ============================================================
$campo  = 'w-full px-4 py-3 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]';
$rotulo = 'block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Admin LAC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Área do Administrador
                </span>
            </div>
            <a href="listar_usuarios.php" class="text-xs text-stone-600 hover:text-[#8C6D36] font-medium transition flex items-center">
                <i class="fa-solid fa-arrow-left mr-1"></i> Voltar à lista
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8 space-y-6">

        <!-- ============================================================ -->
        <!-- BLOCO 1: DADOS BÁSICOS                                        -->
        <!-- ============================================================ -->
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-stone-200 pb-4">
                <div>
                    <h1 class="text-2xl font-serif text-[#3E352E]">Editar Usuário</h1>
                    <p class="text-xs text-stone-500 mt-0.5">#<?php echo (int)$u['id']; ?> · ajuste os dados cadastrais</p>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                    <i class="fa-solid fa-circle-exclamation mr-1"></i> <?php echo h($erro); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="salvar">

                <div>
                    <label class="<?php echo $rotulo; ?>">Nome completo</label>
                    <input type="text" name="nome" required value="<?php echo h($u['nome']); ?>" class="<?php echo $campo; ?>">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="<?php echo $rotulo; ?>">E-mail (login)</label>
                        <input type="email" name="email" value="<?php echo h($u['email'] ?? ''); ?>" class="<?php echo $campo; ?>">
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">CPF</label>
                        <input type="text" name="cpf" maxlength="14" value="<?php echo h($u['cpf'] ?? ''); ?>" class="<?php echo $campo; ?>" placeholder="000.000.000-00">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="<?php echo $rotulo; ?>">Telefone</label>
                        <input type="text" name="telefone" value="<?php echo h($u['telefone'] ?? ''); ?>" class="<?php echo $campo; ?>">
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" value="<?php echo h($u['data_nascimento'] ?? ''); ?>" class="<?php echo $campo; ?>">
                    </div>
                </div>

                <div>
                    <label class="<?php echo $rotulo; ?>">Endereço completo</label>
                    <input type="text" name="endereco" value="<?php echo h($u['endereco'] ?? ''); ?>" class="<?php echo $campo; ?>">
                </div>

                <div>
                    <label class="<?php echo $rotulo; ?>">Tipo de usuário</label>
                    <?php if ($ehUltimoAdmin): ?>
                        <input type="hidden" name="tipo" value="admin">
                        <input type="text" value="Administrador (único — não pode ser rebaixado)" disabled class="<?php echo $campo; ?> opacity-70">
                    <?php else: ?>
                        <select name="tipo" class="<?php echo $campo; ?>">
                            <option value="comum" <?php echo $u['tipo'] === 'comum' ? 'selected' : ''; ?>>Comum (paciente)</option>
                            <option value="admin" <?php echo $u['tipo'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3.5 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <span><strong>E-mail e CPF</strong> são usados para login. Alterar pode exigir que o usuário reaprenda as credenciais.</span>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="listar_usuarios.php" class="px-5 py-3 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</a>
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-3 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Salvar alterações
                    </button>
                </div>
            </form>
        </section>

        <!-- ============================================================ -->
        <!-- BLOCO 2: REDEFINIR SENHA                                      -->
        <!-- ============================================================ -->
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <div>
                <h2 class="text-lg font-serif text-[#3E352E]">
                    <i class="fa-solid fa-key text-[#8C6D36] mr-1"></i> Redefinir senha do usuário
                </h2>
                <p class="text-sm text-stone-500 mt-1">
                    Volta a senha do usuário para o <strong>CPF dele (somente números)</strong>. A senha é armazenada de forma criptografada.
                </p>
            </div>

            <?php if ($avisoRedefinicao !== ''): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                    <span><?php echo h($avisoRedefinicao); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($u['cpf']): ?>
                <div class="text-xs text-stone-500 bg-[#FAF9F6] border border-stone-200 rounded-xl p-3">
                    Nova senha: <strong class="font-mono"><?php echo h(preg_replace('/\D/', '', $u['cpf'])); ?></strong>
                </div>
                <form method="POST" onsubmit="return confirm('Redefinir a senha deste usuário para o CPF dele?');">
                    <input type="hidden" name="acao" value="redefinir_senha">
                    <button type="submit" class="bg-[#8C6D36] hover:bg-[#755a2c] text-white px-5 py-3 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-key mr-1"></i> Redefinir senha para o CPF
                    </button>
                </form>
            <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Este usuário não tem CPF cadastrado.
                </div>
            <?php endif; ?>
        </section>

        <!-- ============================================================ -->
        <!-- BLOCO 3: PLANO DE SAÚDE                                       -->
        <!-- ============================================================ -->
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-stone-200 pb-4">
                <div>
                    <h2 class="text-lg font-serif text-[#3E352E]"><i class="fa-solid fa-id-card text-[#8C6D36] mr-1"></i> Plano de saúde</h2>
                    <p class="text-xs text-stone-500 mt-0.5">Cadastre o plano do paciente para que ele veja em "Plano".</p>
                </div>
            </div>

            <?php if ($avisoPlano !== ''): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
                    <i class="fa-solid fa-circle-check mr-1"></i> <?php echo h($avisoPlano); ?>
                </div>
            <?php endif; ?>

            <?php if ($planos): ?>
                <ul class="space-y-2">
                    <?php foreach ($planos as $p): ?>
                        <li class="flex items-center justify-between gap-3 border border-stone-200 rounded-xl px-4 py-3 bg-[#FAF9F6]">
                            <div class="min-w-0">
                                <div class="font-medium text-stone-800"><?php echo h($p['operadora']); ?></div>
                                <div class="text-xs text-stone-500">
                                    <?php echo h($p['numero_carteirinha'] ?: 'sem número'); ?>
                                    <?php if ($p['validade']): ?> · válido até <?php echo date('d/m/Y', strtotime($p['validade'])); ?><?php endif; ?>
                                    <?php if ($p['acomodacao']): ?> · <?php echo h($p['acomodacao']); ?><?php endif; ?>
                                </div>
                            </div>
                            <a href="editar_usuario.php?id=<?php echo $id; ?>&remover_plano=<?php echo (int)$p['id']; ?>"
                               onclick="return confirm('Remover este plano?');"
                               class="text-xs font-medium bg-red-50 text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-100 transition whitespace-nowrap">
                                <i class="fa-solid fa-trash mr-1"></i> Remover
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-sm text-stone-500">Nenhum plano cadastrado ainda.</p>
            <?php endif; ?>

            <form method="POST" class="space-y-3 pt-4 border-t border-stone-100">
                <input type="hidden" name="acao" value="add_plano">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="<?php echo $rotulo; ?>">Operadora *</label>
                        <input type="text" name="operadora" required class="<?php echo $campo; ?>" placeholder="Ex.: Unimed, Bradesco Saúde">
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">Número da carteirinha</label>
                        <input type="text" name="numero_carteirinha" class="<?php echo $campo; ?>">
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">Validade</label>
                        <input type="date" name="validade" class="<?php echo $campo; ?>">
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">Acomodação</label>
                        <select name="acomodacao" class="<?php echo $campo; ?>">
                            <option value="">—</option>
                            <option value="Enfermaria">Enfermaria</option>
                            <option value="Apartamento">Apartamento</option>
                            <option value="Não se aplica">Não se aplica</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="<?php echo $rotulo; ?>">Observações</label>
                    <textarea name="observacoes" rows="2" class="<?php echo $campo; ?>"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-plus mr-1"></i> Adicionar plano
                    </button>
                </div>
            </form>
        </section>

        <!-- ============================================================ -->
        <!-- BLOCO 4: DEPENDENTES                                          -->
        <!-- ============================================================ -->
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-stone-200 pb-4">
                <div>
                    <h2 class="text-lg font-serif text-[#3E352E]"><i class="fa-solid fa-users text-[#8C6D36] mr-1"></i> Dependentes</h2>
                    <p class="text-xs text-stone-500 mt-0.5">Familiares vinculados ao titular. Eles não têm login próprio.</p>
                </div>
            </div>

            <?php if ($avisoDependente !== ''): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
                    <i class="fa-solid fa-circle-check mr-1"></i> <?php echo h($avisoDependente); ?>
                </div>
            <?php endif; ?>

            <?php if ($dependentes): ?>
                <ul class="space-y-2">
                    <?php foreach ($dependentes as $d): ?>
                        <li class="flex items-center justify-between gap-3 border border-stone-200 rounded-xl px-4 py-3 bg-[#FAF9F6]">
                            <div class="min-w-0">
                                <div class="font-medium text-stone-800"><?php echo h($d['nome']); ?>
                                    <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2 py-0.5 rounded-full ml-1"><?php echo h($d['parentesco']); ?></span>
                                </div>
                                <div class="text-xs text-stone-500">
                                    <?php echo $d['data_nascimento'] ? date('d/m/Y', strtotime($d['data_nascimento'])) : 'sem nascimento'; ?>
                                    <?php if ($d['cpf']): ?> · CPF <?php echo h(formatarCpf($d['cpf'])); ?><?php endif; ?>
                                </div>
                            </div>
                            <a href="editar_usuario.php?id=<?php echo $id; ?>&remover_dependente=<?php echo (int)$d['id']; ?>"
                               onclick="return confirm('Remover este dependente?');"
                               class="text-xs font-medium bg-red-50 text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-100 transition whitespace-nowrap">
                                <i class="fa-solid fa-trash mr-1"></i> Remover
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-sm text-stone-500">Nenhum dependente cadastrado ainda.</p>
            <?php endif; ?>

            <form method="POST" class="space-y-3 pt-4 border-t border-stone-100">
                <input type="hidden" name="acao" value="add_dependente">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="<?php echo $rotulo; ?>">Nome completo *</label>
                        <input type="text" name="dep_nome" required class="<?php echo $campo; ?>">
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">Parentesco *</label>
                        <select name="dep_parentesco" required class="<?php echo $campo; ?>">
                            <option value="Filho(a)">Filho(a)</option>
                            <option value="Cônjuge">Cônjuge</option>
                            <option value="Pai">Pai</option>
                            <option value="Mãe">Mãe</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">Data de nascimento</label>
                        <input type="date" name="dep_nascimento" class="<?php echo $campo; ?>">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="<?php echo $rotulo; ?>">CPF</label>
                        <input type="text" name="dep_cpf" maxlength="14" class="<?php echo $campo; ?>" placeholder="000.000.000-00">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-plus mr-1"></i> Adicionar dependente
                    </button>
                </div>
            </form>
        </section>

    </main>
</body>
</html>