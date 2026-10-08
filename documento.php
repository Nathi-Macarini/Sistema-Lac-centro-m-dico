<?php
require_once __DIR__ . '/bootstrap.php'; 
// Visualização / impressão de receita ou atestado.
// Acesso: o paciente dono do documento ou o médico que o emitiu.
require __DIR__ . '/conexao.php';

if (!isset($_SESSION['usuario_id']) && !souMedico()) { header("Location: login.php"); exit; }

$id = (int)($_GET['id'] ?? 0);
$st = $conn->prepare(
    "SELECT d.*, p.nome AS paciente_nome, p.cpf AS paciente_cpf, p.data_nascimento AS paciente_nasc,
            m.nome AS medico_nome, m.crm, m.especialidade
     FROM documentos d
     JOIN usuarios p ON p.id = d.paciente_id
     JOIN medicos  m ON m.id = d.medico_id
     WHERE d.id = ?");
$st->bind_param('i', $id);
$st->execute();
$d = $st->get_result()->fetch_assoc();

$permitido = false;
if ($d) {
    if (souMedico()) $permitido = (int)$_SESSION['medico_id'] === (int)$d['medico_id'];
    else             $permitido = (int)($_SESSION['usuario_id'] ?? 0) === (int)$d['paciente_id'];
}
$voltar = souMedico() ? 'medico/dashboard.php' : 'historico.php';

if (!$permitido) {
    http_response_code(403);
    ?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Documento</title>
    <script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-[#FAF9F6] min-h-screen flex items-center justify-center p-6">
    <div class="bg-white p-8 rounded-2xl border border-stone-200 text-center space-y-3">
        <p class="text-stone-700">Documento não encontrado ou você não tem acesso a ele.</p>
        <a href="<?php echo $voltar; ?>" class="inline-block bg-[#3E352E] text-white px-5 py-2.5 rounded-xl text-sm">Voltar</a>
    </div></body></html><?php
    exit;
}

$emissao  = date('d/m/Y', strtotime($d['criado_em']));
$emissaoH = date('d/m/Y H:i', strtotime($d['criado_em']));
$ehReceita = $d['tipo'] === 'receita';
$titulo = $ehReceita ? 'Receita Médica' : 'Atestado Médico';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($titulo); ?> - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .folha { box-shadow: none !important; border: none !important; margin: 0 !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen py-8 px-4">

<div class="no-print max-w-3xl mx-auto mb-4 flex items-center justify-between">
    <a href="<?php echo $voltar; ?>" class="text-sm text-stone-600 hover:text-[#8C6D36]">&larr; Voltar</a>
    <button onclick="window.print()" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm">
        Imprimir / Salvar em PDF
    </button>
</div>

<article class="folha max-w-3xl mx-auto bg-white border border-[#E6D5B8] rounded-2xl shadow-sm p-10 space-y-8">
    <header class="flex items-center justify-between border-b border-[#E6D5B8] pb-5">
        <img src="logo_lac.png" alt="LAC Centro Médico" class="h-12 w-auto object-contain">
        <div class="text-right">
            <h1 class="text-2xl font-serif text-[#3E352E] uppercase tracking-wide"><?php echo h($titulo); ?></h1>
            <p class="text-xs text-stone-500">Emitido em <?php echo h($emissao); ?></p>
        </div>
    </header>

    <section class="text-sm space-y-1">
        <p><strong>Paciente:</strong> <?php echo h($d['paciente_nome']); ?></p>
        <?php if ($d['paciente_cpf']): ?><p><strong>CPF:</strong> <?php echo h(formatarCpf($d['paciente_cpf'])); ?></p><?php endif; ?>
        <?php if ($d['paciente_nasc']): ?><p><strong>Data de nascimento:</strong> <?php echo h(date('d/m/Y', strtotime($d['paciente_nasc']))); ?></p><?php endif; ?>
    </section>

    <section class="min-h-[220px] text-[15px] leading-relaxed">
        <?php if ($ehReceita): ?>
            <h2 class="text-xs font-bold text-[#8C6D36] uppercase tracking-wider mb-3">Prescrição</h2>
            <div class="whitespace-pre-line"><?php echo h($d['conteudo']); ?></div>
        <?php else: ?>
            <p>
                Atesto, para os devidos fins, que <strong><?php echo h($d['paciente_nome']); ?></strong>
                foi atendido(a) por teleconsulta em <?php echo h($emissao); ?>
                <?php if ((int)$d['dias_afastamento'] > 0): ?>
                    e necessita de <strong><?php echo (int)$d['dias_afastamento']; ?> dia(s)</strong> de afastamento de suas atividades,
                    a partir desta data.
                <?php else: ?>
                    (atestado de comparecimento).
                <?php endif; ?>
            </p>
            <?php if ($d['cid10']): ?><p class="mt-4"><strong>CID-10:</strong> <?php echo h($d['cid10']); ?></p><?php endif; ?>
            <?php if ($d['conteudo']): ?><p class="mt-4 whitespace-pre-line"><strong>Observações:</strong> <?php echo h($d['conteudo']); ?></p><?php endif; ?>
        <?php endif; ?>
    </section>

    <footer class="pt-10 text-center space-y-1">
        <div class="mx-auto w-72 border-t border-stone-400"></div>
        <p class="font-semibold text-[#3E352E]"><?php echo h($d['medico_nome']); ?></p>
        <p class="text-sm text-stone-600"><?php echo h($d['crm']); ?> · <?php echo h($d['especialidade']); ?></p>
        <p class="text-[11px] text-stone-400 pt-4">Emitido eletronicamente em <?php echo h($emissaoH); ?> · LAC Centro Médico · Código de verificação: <?php echo h($d['codigo']); ?></p>
    </footer>
</article>
</body>
</html>