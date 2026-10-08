<?php
session_start();
require __DIR__ . '/../conexao.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$st = $conn->prepare("SELECT id, nome, crm, especialidade, email, telefone FROM medicos WHERE id = ?");
$st->bind_param("i", $id);
$st->execute();
$m = $st->get_result()->fetch_assoc();
if (!$m) { header("Location: listar_medicos.php"); exit; }

$erro = '';
$especialidades = ['Cardiologia', 'Dermatologia', 'Ortopedia', 'Ginecologia', 'Clínica Geral'];
if (!in_array($m['especialidade'], $especialidades, true)) $especialidades[] = $m['especialidade'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                header("Location: listar_medicos.php?editado=1");
                exit;
            }
            $erro = "Erro ao salvar: " . $up->error;
        }
    }
    $m = ['id' => $id, 'nome' => $nome, 'crm' => $crm, 'especialidade' => $especialidade, 'email' => $email, 'telefone' => $telefone];
    if (!in_array($m['especialidade'], $especialidades, true)) $especialidades[] = $m['especialidade'];
}

$campo = 'w-full px-4 py-3 rounded-xl border border-stone-300 focus:outline-none focus:border-[#8C6D36] text-sm bg-[#FAF9F6]';
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
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen p-6 flex items-center justify-center">
<div class="max-w-xl w-full bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
    <div class="flex items-center justify-between border-b border-stone-200 pb-4">
        <h1 class="text-2xl font-serif text-[#3E352E]">Editar Médico</h1>
        <a href="listar_medicos.php" class="text-xs text-stone-500 hover:text-[#8C6D36] font-medium transition flex items-center">
            <i class="fa-solid fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><i class="fa-solid fa-circle-exclamation mr-1"></i> <?php echo h($erro); ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label class="<?php echo $rotulo; ?>">Nome completo</label>
            <input type="text" name="nome" required value="<?php echo h($m['nome']); ?>" class="<?php echo $campo; ?>">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="<?php echo $rotulo; ?>">CRM</label>
                <input type="text" name="crm" required value="<?php echo h($m['crm']); ?>" class="<?php echo $campo; ?>">
            </div>
            <div>
                <label class="<?php echo $rotulo; ?>">Especialidade</label>
                <select name="especialidade" required class="<?php echo $campo; ?>">
                    <?php foreach ($especialidades as $e): ?>
                        <option value="<?php echo h($e); ?>" <?php echo $m['especialidade'] === $e ? 'selected' : ''; ?>><?php echo h($e); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="<?php echo $rotulo; ?>">E-mail (login)</label>
                <input type="email" name="email" required value="<?php echo h($m['email']); ?>" class="<?php echo $campo; ?>">
            </div>
            <div>
                <label class="<?php echo $rotulo; ?>">Telefone</label>
                <input type="text" name="telefone" value="<?php echo h($m['telefone']); ?>" class="<?php echo $campo; ?>">
            </div>
        </div>
        <div>
            <label class="<?php echo $rotulo; ?>">Nova senha (deixe em branco para manter a atual)</label>
            <input type="text" name="senha" minlength="6" class="<?php echo $campo; ?>">
        </div>
        <button type="submit" class="w-full bg-[#3E352E] text-white py-3 rounded-xl font-medium hover:bg-[#5A4A3A] transition shadow-sm text-sm">Salvar alterações</button>
    </form>
</div>
</body>
</html>