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

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
// ============================================================
// 2. PEGA O ID (ANTES DE QUALQUER POST)
// ============================================================
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar_medicos.php");
    exit;
}

// ============================================================
// 3. BUSCA O MÉDICO
// ============================================================
$st = $conn->prepare("SELECT id, nome, crm, rqe, especialidade, tipo_atendimento, ativo, email, telefone FROM medicos WHERE id = ?");
$st->bind_param("i", $id);
$st->execute();
$m = $st->get_result()->fetch_assoc();

if (!$m) {
    header("Location: listar_medicos.php");
    exit;
}

// ============================================================
// 4. VARIÁVEIS DE ESTADO
// ============================================================
$erro = '';
$avisoRedefinicao = '';
$avisoSucesso = '';

$especialidades = ['Cardiologia', 'Dermatologia', 'Ortopedia', 'Ginecologia', 'Pediatria', 'Clínica Geral'];
if (!in_array($m['especialidade'], $especialidades, true)) {
    $especialidades[] = $m['especialidade'];
}

// ============================================================
// 5. AÇÃO: REDEFINIR SENHA
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'redefinir_senha') {
    $novaSenha = trim($_POST['nova_senha'] ?? '');
    $confirmar = trim($_POST['confirmar_senha'] ?? '');

    if ($novaSenha === '' || $confirmar === '') {
        $erro = "Preencha os dois campos de senha.";
    } elseif (strlen($novaSenha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($novaSenha !== $confirmar) {
        $erro = "As senhas não coincidem. Digite novamente.";
    } else {
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);

        $up = $conn->prepare("UPDATE medicos SET senha = ? WHERE id = ?");
        if (!$up) {
            $erro = "Erro interno ao preparar consulta: " . $conn->error;
        } else {
            $up->bind_param("si", $hash, $id);

            if ($up->execute()) {
                if ($up->affected_rows > 0) {
                    $avisoRedefinicao = "Senha do médico redefinida com sucesso. Informe a ele a nova senha.";

                    if (class_exists('App\\ActivityLogger')) {
                        \App\ActivityLogger::log(
                            action: 'senha_redefinida',
                            entity: 'medico',
                            entityId: $id,
                            description: "Admin redefiniu a senha do médico {$m['nome']} ({$m['crm']})",
                            metadata: [
                                'medico_id'   => $id,
                                'medico_nome' => $m['nome'],
                                'medico_crm'  => $m['crm'],
                            ],
                            tags: ['medico', 'senha']
                        );
                    }
                } else {
                    $erro = "Nada foi alterado. Verifique se o médico existe ou se a senha digitada é igual à anterior.";
                }
            } else {
                $erro = "Erro ao executar atualização: " . $up->error;
            }
        }
    }
}

// ============================================================
// 6. AÇÃO: SALVAR EDIÇÃO DOS DADOS DO MÉDICO (sem senha)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $nome            = trim($_POST['nome'] ?? '');
    $crm             = trim($_POST['crm'] ?? '');
    $rqe             = trim($_POST['rqe'] ?? '');
    $especialidade   = trim($_POST['especialidade'] ?? '');
    $tipoAtendimento = $_POST['tipo_atendimento'] ?? 'particular';
    $email           = trim($_POST['email'] ?? '');
    $telefone        = trim($_POST['telefone'] ?? '');
    $ativo           = !empty($_POST['ativo']) ? 1 : 0;

    // Valida tipo de atendimento
    if (!in_array($tipoAtendimento, ['particular', 'sus', 'ambos'], true)) {
        $tipoAtendimento = 'particular';
    }

    if ($nome === '' || $crm === '' || $especialidade === '' || $email === '') {
        $erro = "Preencha Nome, CRM, Especialidade e E-mail.";
    } else {
        // Verifica duplicidade de CRM ou e-mail (exceto o próprio médico)
        $st = $conn->prepare("SELECT id FROM medicos WHERE (crm = ? OR email = ?) AND id <> ?");
        $st->bind_param("ssi", $crm, $email, $id);
        $st->execute();
        $st->store_result();

        if ($st->num_rows > 0) {
            $erro = "Outro médico já usa esse CRM ou e-mail.";
        } else {
            $rqeEsc = $rqe !== '' ? $rqe : null;

            $up = $conn->prepare("UPDATE medicos SET nome=?, crm=?, rqe=?, especialidade=?, tipo_atendimento=?, ativo=?, email=?, telefone=? WHERE id=?");
$up->bind_param("sssssissi", $nome, $crm, $rqeEsc, $especialidade, $tipoAtendimento, $ativo, $email, $telefone, $id);

            if ($up->execute()) {
                header("Location: editar_medico.php?id={$id}&editado=1");
                exit;
            }
            $erro = "Erro ao salvar: " . $up->error;
        }
    }

    // Atualiza os dados na tela em caso de erro
    $m = [
        'id'               => $id,
        'nome'             => $nome,
        'crm'              => $crm,
        'rqe'              => $rqe,
        'especialidade'    => $especialidade,
        'tipo_atendimento' => $tipoAtendimento,
        'ativo'            => $ativo,
        'email'            => $email,
        'telefone'         => $telefone,
    ];
    if (!in_array($m['especialidade'], $especialidades, true)) {
        $especialidades[] = $m['especialidade'];
    }
}

// ============================================================
// 7. MENSAGENS DE SUCESSO (via GET)
// ============================================================
if (isset($_GET['editado'])) {
    $avisoSucesso = "Dados do médico atualizados com sucesso.";
}

// ============================================================
// 8. CLASSES DE ESTILO REUTILIZÁVEIS
// ============================================================
$campo  = 'w-full px-4 py-3 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]';
$rotulo = 'block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Médico - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <!-- Header -->
    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Painel Administrativo
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar ao Painel</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8 space-y-6">

        <!-- ============================================================ -->
        <!-- BLOCO 1: DADOS BÁSICOS DO MÉDICO                              -->
        <!-- ============================================================ -->
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-stone-200 pb-4">
                <div>
                    <h1 class="text-2xl font-serif text-[#3E352E]">Editar Médico</h1>
                    <p class="text-xs text-stone-500 mt-0.5">#<?php echo (int)$m['id']; ?> · ajuste os dados do profissional</p>
                </div>
            </div>

            <?php if ($avisoSucesso !== ''): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                    <span><?php echo h($avisoSucesso); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($erro !== ''): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <span><?php echo h($erro); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="salvar">

                <div>
                    <label class="<?php echo $rotulo; ?>">Nome completo *</label>
                    <input type="text" name="nome" required value="<?php echo h($m['nome']); ?>" class="<?php echo $campo; ?>">
                </div>

                <!-- Linha: CRM + RQE -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="<?php echo $rotulo; ?>">CRM *</label>
                        <input type="text" name="crm" required value="<?php echo h($m['crm']); ?>" class="<?php echo $campo; ?>">
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">RQE</label>
                        <input type="text" name="rqe" value="<?php echo h($m['rqe'] ?? ''); ?>" class="<?php echo $campo; ?>" placeholder="Ex: 98765">
                    </div>
                </div>

                <!-- Linha: Especialidade + Tipo de Atendimento -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="<?php echo $rotulo; ?>">Especialidade *</label>
                        <select name="especialidade" required class="<?php echo $campo; ?>">
                            <?php foreach ($especialidades as $e): ?>
                                <option value="<?php echo h($e); ?>" <?php echo $m['especialidade'] === $e ? 'selected' : ''; ?>>
                                    <?php echo h($e); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">Tipo de Atendimento *</label>
                        <select name="tipo_atendimento" required class="<?php echo $campo; ?>">
                            <option value="particular" <?php echo ($m['tipo_atendimento'] ?? 'particular') === 'particular' ? 'selected' : ''; ?>>Particular</option>
                            <option value="sus" <?php echo ($m['tipo_atendimento'] ?? '') === 'sus' ? 'selected' : ''; ?>>SUS</option>
                            <option value="ambos" <?php echo ($m['tipo_atendimento'] ?? '') === 'ambos' ? 'selected' : ''; ?>>Particular + SUS</option>
                        </select>
                    </div>
                </div>

                                <!-- Toggle: Médico ativo/inativo -->
                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-4 rounded-xl flex items-start gap-3">
                    <input type="checkbox" name="ativo" id="ativo" value="1"
                           <?php echo (($m['ativo'] ?? 1) ? 'checked' : ''); ?>
                           class="mt-1 w-5 h-5 accent-[#8C6D36] cursor-pointer">
                    <label for="ativo" class="cursor-pointer flex-1">
                        <span class="block text-sm font-bold text-[#3E352E]">Médico ativo no sistema</span>
                        <span class="block text-xs text-stone-500 mt-0.5">
                            Médicos inativos <strong>não conseguem fazer login</strong> nem aparecem nas listagens de teleconsulta.
                            Desative em vez de excluir, para preservar o histórico de atendimentos.
                        </span>
                    </label>
                </div>
                
                <!-- Linha: E-mail + Telefone -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="<?php echo $rotulo; ?>">E-mail (login) *</label>
                        <input type="email" name="email" required value="<?php echo h($m['email']); ?>" class="<?php echo $campo; ?>">
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">Telefone</label>
                        <input type="text" name="telefone" value="<?php echo h($m['telefone']); ?>" class="<?php echo $campo; ?>">
                    </div>
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3.5 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <span>
                        <strong>CRM e e-mail</strong> são usados para login. Alterar pode exigir que o médico reaprenda as credenciais.
                        Para trocar a senha do médico, use o bloco <strong>"Redefinir senha"</strong> abaixo.
                    </span>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="listar_medicos.php" class="px-5 py-3 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">
                        Cancelar
                    </a>
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
                    <i class="fa-solid fa-key text-[#8C6D36] mr-1"></i> Redefinir senha do médico
                </h2>
                <p class="text-sm text-stone-500 mt-1">
                    Entre em contato com o médico, pergunte qual senha ele deseja e digite abaixo. A senha será criptografada antes de ser salva.
                </p>
            </div>

            <?php if ($avisoRedefinicao !== ''): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                    <span><?php echo h($avisoRedefinicao); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="redefinir_senha">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="<?php echo $rotulo; ?>">Senha desejada *</label>
                        <input type="text" name="nova_senha" required minlength="6"
                               class="<?php echo $campo; ?>"
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    <div>
                        <label class="<?php echo $rotulo; ?>">Confirmar senha *</label>
                        <input type="text" name="confirmar_senha" required minlength="6"
                               class="<?php echo $campo; ?>"
                               placeholder="Repita a senha">
                    </div>
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3.5 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <span>
                        <strong>Dica de segurança:</strong> oriente o médico a trocar a senha no primeiro acesso. Evite senhas óbvias como <em>123456</em> ou a data de nascimento.
                    </span>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-[#8C6D36] hover:bg-[#755a2c] text-white px-5 py-3 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-key mr-1"></i> Redefinir senha
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>