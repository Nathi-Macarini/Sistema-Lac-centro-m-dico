<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

// Só o administrador cadastra médicos
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$erro = '';
$sucesso = isset($_GET['sucesso']);
$especialidades = ['Cardiologia', 'Dermatologia', 'Ortopedia', 'Ginecologia', 'Clínica Geral'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome          = trim($_POST['nome'] ?? '');
    $crm           = trim($_POST['crm'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');
    $senha         = trim($_POST['senha'] ?? '');

    if ($nome === '' || $crm === '' || $especialidade === '' || $email === '' || $senha === '') {
        $erro = "Preencha Nome, CRM, Especialidade, E-mail e Senha.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha temporária deve ter pelo menos 6 caracteres.";
    } else {
        $st = $conn->prepare("SELECT id FROM medicos WHERE crm = ? OR email = ?");
        $st->bind_param("ss", $crm, $email);
        $st->execute();
        $st->store_result();

        if ($st->num_rows > 0) {
            $erro = "Já existe um médico com esse CRM ou e-mail.";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO medicos (nome, crm, especialidade, email, telefone, senha) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("ssssss", $nome, $crm, $especialidade, $email, $telefone, $hash);

            if ($ins->execute()) {
                header("Location: cadastrar_medico.php?sucesso=1");
                exit();
            }
            $erro = "Erro ao salvar no banco de dados: " . $ins->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Médico - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <!-- Header Admin (igual ao cadastrar_usuario.php) -->
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

    <main class="max-w-3xl mx-auto px-4 py-8">
        <section class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-xl space-y-6">
            <div>
                <h1 class="text-2xl font-serif text-[#3E352E]">Cadastrar Novo Médico</h1>
                <p class="text-sm text-stone-500 mt-1">O médico poderá entrar no sistema com o e-mail e a senha cadastrados aqui.</p>
            </div>

            <?php if ($sucesso): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-3.5 rounded-xl text-sm flex items-start gap-2">
                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                    <span>Médico cadastrado! Ele já pode entrar pela tela de login. <a href="listar_medicos.php" class="underline font-medium">Ver médicos cadastrados</a></span>
                </div>
            <?php endif; ?>

            <?php if ($erro !== ''): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 p-3.5 rounded-xl text-sm flex items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <span><?php echo h($erro); ?></span>
                </div>
            <?php endif; ?>

            <form action="cadastrar_medico.php" method="POST" class="space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Nome Completo *</label>
                        <input type="text" name="nome" required value="<?php echo h($_POST['nome'] ?? ''); ?>"
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">CRM *</label>
                        <input type="text" name="crm" required placeholder="Ex: 12345/PR" value="<?php echo h($_POST['crm'] ?? ''); ?>"
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Especialidade *</label>
                        <select name="especialidade" required
                                class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                            <?php foreach ($especialidades as $e): ?>
                                <option value="<?php echo h($e); ?>" <?php echo (($_POST['especialidade'] ?? '') === $e) ? 'selected' : ''; ?>><?php echo h($e); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">E-mail (login) *</label>
                        <input type="email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>"
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]"
                               placeholder="medico@email.com">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Telefone / Celular</label>
                        <input type="text" name="telefone" placeholder="(41) 99999-9999" value="<?php echo h($_POST['telefone'] ?? ''); ?>"
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Senha Temporária * (mínimo 6 caracteres)</label>
                        <input type="text" name="senha" required minlength="6"
                               class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]"
                               placeholder="O médico pode alterar depois">
                    </div>
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3.5 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <span><strong>Importante:</strong> CRM e e-mail não podem repetir. A senha será criptografada antes de ser salva.</span>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="dashboard.php" class="px-5 py-3 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</a>
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-3 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-user-doctor mr-1"></i> Cadastrar Médico
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>