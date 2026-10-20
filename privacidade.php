<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/conexao.php';

use App\LGPD;

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
verificarEsquema($conn);

$uid         = (int)$_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['tipo_usuario'] ?? 'comum';

// Configurações do DPO
$dpoNome  = LGPD::config($conn, 'dpo_nome', '');
$dpoEmail = LGPD::config($conn, 'dpo_email', '');
$dpoTel   = LGPD::config($conn, 'dpo_telefone', '');

// Solicitações do usuário
$solicitacoes = LGPD::listarSolicitacoes($conn, $uid);

// Termos aceitos
$st = $conn->prepare("
    SELECT termo_tipo, termo_versao, aceito_em
    FROM termos_aceitos
    WHERE usuario_id = ?
    ORDER BY aceito_em DESC
    LIMIT 20
");
$st->bind_param('i', $uid);
$st->execute();
$termos = $st->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacidade e Meus Dados - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased">

<header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <a href="index.php"><img src="logo_lac.png" alt="LAC" class="h-10 w-auto object-contain"></a>
        <nav class="hidden md:flex space-x-8 font-medium text-stone-600">
            <a href="index.php" class="hover:text-[#8C6D36] transition">Início</a>
            <a href="historico.php" class="hover:text-[#8C6D36] transition">Histórico</a>
            <a href="plano.php" class="hover:text-[#8C6D36] transition">Plano</a>
            <a href="dependentes.php" class="hover:text-[#8C6D36] transition">Dependentes</a>
            <a href="privacidade.php" class="text-[#8C6D36] border-b-2 border-[#8C6D36] pb-1 font-semibold">Privacidade</a>
        </nav>
        <div class="flex items-center space-x-4">
            <a href="perfil.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition hidden sm:inline"><?php echo h($_SESSION['nome_usuario']); ?></a>
            <a href="logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair"><i class="fa-solid fa-right-from-bracket text-lg"></i></a>
        </div>
    </div>
</header>

<main class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    <!-- Cabeçalho -->
    <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] text-2xl shrink-0">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h1 class="text-2xl font-serif text-[#3E352E]">Privacidade e Meus Dados</h1>
                <p class="text-sm text-stone-600 mt-1">
                    Aqui você pode exercer seus direitos previstos na Lei Geral de Proteção de Dados (LGPD — Lei 13.709/2018).
                    Todos os pedidos são registrados com protocolo e respondidos em até <strong>15 dias</strong>.
                </p>
            </div>
        </div>
    </section>

    <!-- Aviso -->
    <div class="bg-blue-50 border border-blue-200 text-blue-900 p-4 rounded-2xl text-sm flex items-start gap-3">
        <i class="fa-solid fa-circle-info mt-0.5"></i>
        <div>
            <strong>Seus direitos como titular de dados:</strong> confirmação, acesso, correção, anonimização, portabilidade,
            eliminação, informação sobre compartilhamento e revogação do consentimento.
            <a href="politica-privacidade.php" class="underline font-medium">Ver política completa</a>.
        </div>
    </div>

    <!-- Ações principais -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <!-- Exportar dados -->
        <div class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] shrink-0">
                    <i class="fa-solid fa-download text-lg"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-serif text-lg text-[#3E352E]">Baixar meus dados</h3>
                    <p class="text-sm text-stone-500 mt-1">
                        Receba um arquivo JSON com todos os seus dados: cadastro, consultas, prontuário, documentos e histórico.
                    </p>
                    <button onclick="baixarDados()"
                            class="mt-4 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-download mr-1"></i> Baixar meus dados
                    </button>
                </div>
            </div>
        </div>

        <!-- Revogar consentimento -->
        <div class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-700 shrink-0">
                    <i class="fa-solid fa-ban text-lg"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-serif text-lg text-[#3E352E]">Revogar consentimento</h3>
                    <p class="text-sm text-stone-500 mt-1">
                        Você pode revogar o consentimento a qualquer momento. Isso impede novas teleconsultas.
                    </p>
                    <button onclick="abrirSolicitacao('revogacao_consentimento')"
                            class="mt-4 bg-white border border-amber-300 hover:bg-amber-50 text-amber-800 px-5 py-2.5 rounded-xl text-sm font-medium transition">
                        <i class="fa-solid fa-ban mr-1"></i> Revogar consentimento
                    </button>
                </div>
            </div>
        </div>

        <!-- Exclusão -->
        <div class="bg-white p-6 rounded-2xl border border-red-200 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-center text-red-700 shrink-0">
                    <i class="fa-solid fa-trash text-lg"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-serif text-lg text-[#3E352E]">Excluir minha conta</h3>
                    <p class="text-sm text-stone-500 mt-1">
                        <strong>Atenção:</strong> dados clínicos são retidos por 20 anos (exigência do CFM).
                        Será criada uma solicitação para análise do Encarregado (DPO).
                    </p>
                    <button onclick="abrirSolicitacao('exclusao')"
                            class="mt-4 bg-red-50 border border-red-300 hover:bg-red-100 text-red-700 px-5 py-2.5 rounded-xl text-sm font-medium transition">
                        <i class="fa-solid fa-trash mr-1"></i> Solicitar exclusão
                    </button>
                </div>
            </div>
        </div>

        <!-- Outras solicitações -->
        <div class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] shrink-0">
                    <i class="fa-solid fa-comment-dots text-lg"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-serif text-lg text-[#3E352E]">Outras solicitações</h3>
                    <p class="text-sm text-stone-500 mt-1">
                        Correção, informação sobre compartilhamento, anonimização e outras.
                    </p>
                    <button onclick="abrirSolicitacao('')"
                            class="mt-4 bg-white border border-stone-300 hover:bg-stone-50 text-stone-700 px-5 py-2.5 rounded-xl text-sm font-medium transition">
                        <i class="fa-solid fa-plus mr-1"></i> Nova solicitação
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- DPO -->
    <section class="bg-[#F9F4EC] border border-[#E6D5B8] p-6 rounded-2xl">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-white border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] shrink-0">
                <i class="fa-solid fa-user-shield text-lg"></i>
            </div>
            <div>
                <h2 class="font-serif text-lg text-[#3E352E]">Encarregado de Proteção de Dados (DPO)</h2>
                <p class="text-sm text-stone-600 mt-1">
                    Para dúvidas, reclamações ou informações sobre o tratamento dos seus dados, entre em contato:
                </p>
                <div class="mt-3 space-y-1 text-sm">
                    <?php if ($dpoNome): ?>
                        <div><strong>Nome:</strong> <?php echo h($dpoNome); ?></div>
                    <?php endif; ?>
                    <?php if ($dpoEmail): ?>
                        <div>
                            <strong>E-mail:</strong>
                            <a href="mailto:<?php echo h($dpoEmail); ?>" class="text-[#8C6D36] underline"><?php echo h($dpoEmail); ?></a>
                        </div>
                    <?php else: ?>
                        <div class="text-stone-500">Contato do DPO ainda não configurado. Entre em contato pelo e-mail <a href="mailto:atendimento@laccentromedico.com.br" class="underline">atendimento@laccentromedico.com.br</a>.</div>
                    <?php endif; ?>
                    <?php if ($dpoTel): ?>
                        <div><strong>Telefone:</strong> <?php echo h($dpoTel); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Histórico de termos aceitos -->
    <?php if ($termos): ?>
    <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
        <h2 class="text-lg font-serif text-[#3E352E] mb-4">
            <i class="fa-solid fa-file-contract text-[#8C6D36] mr-1"></i> Termos aceitos
        </h2>
        <ul class="space-y-2">
            <?php foreach ($termos as $t): ?>
                <li class="flex items-center justify-between gap-3 text-sm border border-stone-200 rounded-xl px-4 py-2.5 bg-[#FAF9F6]">
                    <div>
                        <strong class="text-stone-800">
                            <?php
                                echo match($t['termo_tipo']) {
                                    'teleconsulta_pa'         => 'Termo de Teleconsulta (Pronto Atendimento)',
                                    'teleconsulta_agendada'   => 'Termo de Teleconsulta (Consulta Agendada)',
                                    default                   => 'Termo: ' . $t['termo_tipo'],
                                };
                            ?>
                        </strong>
                        <span class="text-xs text-stone-500 ml-2">versão <?php echo h($t['termo_versao']); ?></span>
                    </div>
                    <span class="text-xs text-stone-500"><?php echo date('d/m/Y H:i', strtotime($t['aceito_em'])); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Solicitações anteriores -->
    <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
        <h2 class="text-lg font-serif text-[#3E352E] mb-4">
            <i class="fa-solid fa-list-check text-[#8C6D36] mr-1"></i> Minhas solicitações
        </h2>

        <?php if (!$solicitacoes): ?>
            <p class="text-sm text-stone-500 py-4">Você ainda não fez nenhuma solicitação.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($solicitacoes as $s):
                    $statusCores = [
                        'pendente'    => 'bg-amber-50 text-amber-800 border-amber-200',
                        'em_analise'  => 'bg-blue-50 text-blue-800 border-blue-200',
                        'aprovada'    => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        'rejeitada'   => 'bg-red-50 text-red-700 border-red-200',
                        'concluida'   => 'bg-stone-100 text-stone-700 border-stone-200',
                    ];
                    $cls = $statusCores[$s['status']] ?? 'bg-stone-100 text-stone-700';
                ?>
                    <li class="border border-stone-200 rounded-xl p-4 bg-[#FAF9F6] space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-xs bg-white border border-stone-300 px-2 py-1 rounded">
                                    <?php echo h($s['protocolo']); ?>
                                </span>
                                <span class="text-xs font-semibold border <?php echo $cls; ?> px-2.5 py-1 rounded-full">
                                    <?php echo h(str_replace('_', ' ', ucfirst($s['status']))); ?>
                                </span>
                            </div>
                            <span class="text-xs text-stone-500">
                                <?php echo date('d/m/Y H:i', strtotime($s['criado_em'])); ?>
                            </span>
                        </div>
                        <div class="text-sm">
                            <strong><?php echo h(LGPD::TIPOS[$s['tipo']] ?? $s['tipo']); ?></strong>
                            <?php if (!empty($s['descricao'])): ?>
                                <p class="text-stone-600 mt-1"><?php echo nl2br(h($s['descricao'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($s['respondido_em']): ?>
                            <div class="text-xs bg-white border border-stone-200 rounded-lg p-3">
                                <strong>Resposta:</strong>
                                <?php if (!empty($s['resposta'])): ?>
                                    <p class="mt-1"><?php echo nl2br(h($s['resposta'])); ?></p>
                                <?php else: ?>
                                    <span class="text-stone-500">—</span>
                                <?php endif; ?>
                                <?php if (!empty($s['motivo_rejeicao'])): ?>
                                    <p class="mt-1 text-red-600"><strong>Motivo:</strong> <?php echo nl2br(h($s['motivo_rejeicao'])); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<!-- Modal: Nova solicitação -->
<div id="modal-lgpd" class="fixed inset-0 bg-black/70 z-[120] hidden flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-[#E6D5B8]">
        <div class="flex justify-between items-center border-b border-stone-100 pb-4">
            <h3 class="text-xl font-serif text-[#3E352E]">Nova solicitação LGPD</h3>
            <button onclick="fecharModal()" class="text-stone-400 hover:text-stone-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>

        <form id="form-lgpd" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Tipo de solicitação *</label>
                <select name="tipo" id="lgpd-tipo" required class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm bg-[#FAF9F6]">
                    <option value="">Selecione...</option>
                    <?php foreach (LGPD::TIPOS as $k => $v): ?>
                        <option value="<?php echo h($k); ?>"><?php echo h($v); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Descrição (opcional)</label>
                <textarea name="descricao" rows="4" class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm bg-[#FAF9F6]" placeholder="Detalhe sua solicitação..."></textarea>
            </div>

            <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3 rounded-xl text-xs text-[#5A4A3A]">
                <strong>Prazo:</strong> sua solicitação será respondida em até 15 dias corridos.
            </div>

            <p id="lgpd-msg" class="text-sm hidden rounded-xl p-3 border"></p>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="fecharModal()" class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</button>
                <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Enviar solicitação
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirSolicitacao(tipo) {
    document.getElementById('modal-lgpd').classList.remove('hidden');
    if (tipo) document.getElementById('lgpd-tipo').value = tipo;
    document.getElementById('lgpd-msg').classList.add('hidden');
}

function fecharModal() {
    document.getElementById('modal-lgpd').classList.add('hidden');
}

async function baixarDados() {
    const btn = event.target;
    const texto = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Gerando...';

    try {
        const r = await fetch('api/lgpd.php?acao=exportar', { credentials: 'same-origin' });
        if (!r.ok) throw new Error('Erro ' + r.status);
        const blob = await r.blob();
        const url  = window.URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'meus-dados-' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.json';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        setTimeout(() => location.reload(), 1500);
    } catch (e) {
        alert('Falha ao baixar: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = texto;
    }
}

document.getElementById('form-lgpd').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const msg = document.getElementById('lgpd-msg');

    try {
        const r = await fetch('api/lgpd.php?acao=solicitar', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        });
        const j = await r.json();

        if (j.ok) {
            msg.textContent = 'Solicitação registrada! Protocolo: ' + j.protocolo + '. Prazo: ' + j.prazo + '.';
            msg.className = 'text-sm rounded-xl p-3 border bg-emerald-50 text-emerald-800 border-emerald-200';
            setTimeout(() => location.reload(), 2500);
        } else {
            msg.textContent = j.erro || 'Erro ao enviar.';
            msg.className = 'text-sm rounded-xl p-3 border bg-red-50 text-red-700 border-red-200';
        }
    } catch (err) {
        msg.textContent = 'Falha de conexão.';
        msg.className = 'text-sm rounded-xl p-3 border bg-red-50 text-red-700 border-red-200';
    }
});
</script>

<?php include __DIR__ . '/includes/rodape.php'; ?>
</body>
</html>