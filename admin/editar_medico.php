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
// 2. PEGA O ID (ANTES DE QUALQUER POST!)
// ============================================================
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar_medicos.php");
    exit;
}

// ============================================================
// 3. BUSCA O MÉDICO (também antes dos POSTs)
// ============================================================
$st = $conn->prepare("SELECT id, nome, crm, especialidade, email, telefone FROM medicos WHERE id = ?");
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
// AÇÃO: REDEFINIR SENHA (COM SENHA CUSTOMIZADA)
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
// 6. AÇÃO: SALVAR EDIÇÃO DOS DADOS DO MÉDICO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $nome          = trim($_POST['nome'] ?? '');
    $crm           = trim($_POST['crm'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');
    $novaSenha     = trim($_POST['senha'] ?? '');

    if ($nome === '' || $crm === '' || $especialidade === '' || $email === '') {
        $erro = "Preencha Nome, CRM, Especialidade e E-mail.";
    } elseif ($novaSenha !== '' && strlen($novaSenha) < 6) {
        $erro = "A nova senha deve ter pelo menos 6 caracteres.";
    } else {
        // Verifica duplicidade de CRM ou e-mail (exceto o próprio médico)
        $st = $conn->prepare("SELECT id FROM medicos WHERE (crm = ? OR email = ?) AND id <> ?");
        $st->bind_param("ssi", $crm, $email, $id);
        $st->execute();
        $st->store_result();

        if ($st->num_rows > 0) {
            $erro = "Outro médico já usa esse CRM ou e-mail.";
        } else {
            if ($novaSenha !== '') {
                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE medicos SET nome=?, crm=?, especialidade=?, email=?, telefone=?, senha=? WHERE id=?");
                $up->bind_param("ssssssi", $nome, $crm, $especialidade, $email, $telefone, $hash, $id);
            } else {
                $up = $conn->prepare("UPDATE medicos SET nome=?, crm=?, especialidade=?, email=?, telefone=? WHERE id=?");
                $up->bind_param("sssssi", $nome, $crm, $especialidade, $email, $telefone, $id);
            }

            if ($up->execute()) {
                header("Location: editar_medico.php?id={$id}&editado=1");
                exit;
            }
            $erro = "Erro ao salvar: " . $up->error;
        }
    }

    // Atualiza os dados na tela em caso de erro
    $m = [
        'id'            => $id,
        'nome'          => $nome,
        'crm'           => $crm,
        'especialidade' => $especialidade,
        'email'         => $email,
        'telefone'      => $telefone,
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

    <!-- Header (idêntico ao editar_usuario.php) -->
    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Área do Administrador
                </span>
            </div>
            <a href="listar_medicos.php" class="text-xs text-stone-600 hover:text-[#8C6D36] font-medium transition flex items-center">
                <i class="fa-solid fa-arrow-left mr-1"></i> Voltar à lista
            </a>
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="<?php echo $rotulo; ?>">CRM *</label>
                        <input type="text" name="crm" required value="<?php echo h($m['crm']); ?>" class="<?php echo $campo; ?>">
                    </div>
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
                </div>

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

                <div>
                    <label class="<?php echo $rotulo; ?>">Nova senha (deixe em branco para manter a atual)</label>
                    <input type="text" name="senha" minlength="6" class="<?php echo $campo; ?>" placeholder="Mínimo 6 caracteres">
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3.5 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <span>
                        <strong>CRM e e-mail</strong> são usados para login. Alterar pode exigir que o médico reaprenda as credenciais.
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