<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

use App\ActivityLogger;
use App\LGPD;

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$uidAdmin  = (int)$_SESSION['usuario_id'];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.php"); exit; }

// Busca a solicitação
$st = $conn->prepare("
    SELECT s.*, 
           u.nome AS usuario_nome, u.email AS usuario_email, u.cpf_ultimos4,
           a.nome AS respondido_nome
    FROM lgpd_solicitacoes s
    JOIN usuarios u ON u.id = s.usuario_id
    LEFT JOIN usuarios a ON a.id = s.respondido_por
    WHERE s.id = ?
");
$st->bind_param('i', $id);
$st->execute();
$s = $st->get_result()->fetch_assoc();

if (!$s) { header("Location: index.php"); exit; }

$msg = ''; $msgTipo = '';

// ============================================================
// AÇÃO: RESPONDER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'responder') {
    $novoStatus  = $_POST['status'] ?? '';
    $resposta    = trim($_POST['resposta'] ?? '');
    $motivo      = trim($_POST['motivo_rejeicao'] ?? '');

    $permitidos = ['em_analise', 'aprovada', 'rejeitada', 'concluida'];

    if (!in_array($novoStatus, $permitidos, true)) {
        $msg = "Status inválido.";
        $msgTipo = 'erro';
    } elseif ($novoStatus === 'rejeitada' && $motivo === '') {
        $msg = "Informe o motivo da rejeição.";
        $msgTipo = 'erro';
    } else {
        // Se aprovada e é exclusão → anonimiza
        $anonimizar = false;
        if ($novoStatus === 'aprovada' && $s['tipo'] === 'exclusao') {
            $anonimizar = true;
        }

        $st = $conn->prepare("
            UPDATE lgpd_solicitacoes
            SET status = ?, resposta = ?, motivo_rejeicao = ?, respondido_por = ?, respondido_em = NOW()
            WHERE id = ?
        ");
        $respostaEsc = $resposta !== '' ? $resposta : null;
        $motivoEsc   = $motivo !== '' ? $motivo : null;
        $st->bind_param('sssii', $novoStatus, $respostaEsc, $motivoEsc, $uidAdmin, $id);

        if ($st->execute()) {
            // Executa ação prática se aprovada
            if ($anonimizar) {
                try {
                    LGPD::anonimizarUsuario($conn, (int)$s['usuario_id']);
                    // Marca como concluída
                    $conn->query("UPDATE lgpd_solicitacoes SET status = 'concluida' WHERE id = $id");
                    $novoStatus = 'concluida';
                } catch (\Throwable $e) {
                    $msg = "Aviso: solicitação aprovada, mas a anonimização falhou: " . $e->getMessage();
                    $msgTipo = 'erro';
                }
            }

            if ($msg === '') {
                $msg = "Solicitação atualizada para: " . str_replace('_', ' ', $novoStatus);
                $msgTipo = 'sucesso';
            }

            ActivityLogger::log(
                action: 'lgpd_solicitacao_respondida',
                entity: 'lgpd_solicitacao',
                entityId: $id,
                description: "Admin {$nomeAdmin} respondeu a solicitação {$s['protocolo']} — status: {$novoStatus}",
                metadata: [
                    'protocolo'    => $s['protocolo'],
                    'status_novo'  => $novoStatus,
                    'usuario_id'   => (int)$s['usuario_id'],
                ],
                tags: ['lgpd', 'resposta']
            );

            // Recarrega
            header("Location: ver.php?id={$id}&ok=1");
            exit;
        } else {
            $msg = "Erro ao salvar: " . $conn->error;
            $msgTipo = 'erro';
        }
    }
}

// ============================================================
// HISTÓRICO DE TERMOS E ACESSOS (contexto)
// ============================================================
$st = $conn->prepare("SELECT termo_tipo, termo_versao, aceito_em FROM termos_aceitos WHERE usuario_id = ? ORDER BY aceito_em DESC LIMIT 5");
$st->bind_param('i', $s['usuario_id']);
$st->execute();
$termos = $st->get_result()->fetch_all(MYSQLI_ASSOC);

function statusBadge(string $status): string {
    $map = [
        'pendente'   => 'bg-amber-50 text-amber-800 border-amber-200',
        'em_analise' => 'bg-blue-50 text-blue-800 border-blue-200',
        'aprovada'   => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'rejeitada'  => 'bg-red-50 text-red-700 border-red-200',
        'concluida'  => 'bg-stone-100 text-stone-700 border-stone-200',
    ];
    $cls = $map[$status] ?? 'bg-stone-100 text-stone-700';
    $lbl = str_replace('_', ' ', ucfirst($status));
    return "<span class=\"inline-block text-xs font-semibold border $cls px-2.5 py-1 rounded-full\">" . htmlspecialchars($lbl) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação <?php echo htmlspecialchars($s['protocolo']); ?> - LGPD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../../logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Painel Administrativo
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar para as Solicitações LGPD</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <?php if (isset($_GET['ok'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
                <i class="fa-solid fa-circle-check mt-0.5"></i>
                <span>Solicitação atualizada com sucesso.</span>
            </div>
        <?php endif; ?>

        <?php if ($msg): ?>
            <div class="<?php echo $msgTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid <?php echo $msgTipo === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <!-- Cabeçalho da solicitação -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="font-mono text-sm bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1.5 rounded-lg">
                        <?php echo htmlspecialchars($s['protocolo']); ?>
                    </span>
                    <?php echo statusBadge($s['status']); ?>
                    <h1 class="text-2xl font-serif text-[#3E352E] mt-3">
                        <?php echo htmlspecialchars(LGPD::TIPOS[$s['tipo']] ?? $s['tipo']); ?>
                    </h1>
                    <p class="text-sm text-stone-500 mt-1">
                        Solicitada em <?php echo date('d/m/Y H:i', strtotime($s['criado_em'])); ?>
                        <?php if (!empty($s['prazo_resposta'])): ?>
                            · Prazo: <strong><?php echo date('d/m/Y', strtotime($s['prazo_resposta'])); ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- Dados do solicitante -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h2 class="text-lg font-serif text-[#3E352E] mb-4">
                <i class="fa-solid fa-user text-[#8C6D36] mr-1"></i> Titular
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-bold text-stone-500 uppercase">Nome</dt>
                    <dd class="text-stone-800"><?php echo htmlspecialchars($s['usuario_nome']); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-stone-500 uppercase">E-mail</dt>
                    <dd class="text-stone-800"><?php echo htmlspecialchars($s['usuario_email']); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-stone-500 uppercase">CPF</dt>
                    <dd class="text-stone-800 font-mono text-xs">
                        <?php echo !empty($s['cpf_ultimos4']) ? '***.***.***-' . htmlspecialchars($s['cpf_ultimos4']) : '—'; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-stone-500 uppercase">IP da solicitação</dt>
                    <dd class="text-stone-800 font-mono text-xs"><?php echo htmlspecialchars($s['ip_solicitante'] ?? '—'); ?></dd>
                </div>
            </dl>
        </section>

        <!-- Descrição -->
        <?php if (!empty($s['descricao'])): ?>
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h2 class="text-lg font-serif text-[#3E352E] mb-3">
                <i class="fa-solid fa-comment-dots text-[#8C6D36] mr-1"></i> Descrição do pedido
            </h2>
            <div class="text-sm text-stone-700 whitespace-pre-line bg-[#FAF9F6] border border-stone-200 rounded-xl p-4">
                <?php echo nl2br(htmlspecialchars($s['descricao'])); ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Termos aceitos -->
        <?php if ($termos): ?>
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h2 class="text-lg font-serif text-[#3E352E] mb-3">
                <i class="fa-solid fa-file-contract text-[#8C6D36] mr-1"></i> Consentimentos registrados
            </h2>
            <ul class="space-y-1.5 text-xs">
                <?php foreach ($termos as $t): ?>
                    <li class="flex items-center justify-between gap-2 border border-stone-200 rounded-lg px-3 py-2 bg-[#FAF9F6]">
                        <span><?php echo htmlspecialchars($t['termo_tipo']); ?> (<?php echo htmlspecialchars($t['termo_versao']); ?>)</span>
                        <span class="text-stone-500"><?php echo date('d/m/Y H:i', strtotime($t['aceito_em'])); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <!-- Resposta anterior -->
        <?php if (!empty($s['respondido_em'])): ?>
        <section class="bg-blue-50 border border-blue-200 p-6 rounded-2xl">
            <h2 class="text-lg font-serif text-blue-900 mb-3">
                <i class="fa-solid fa-reply text-blue-700 mr-1"></i> Resposta registrada
            </h2>
            <div class="text-sm text-blue-900 space-y-2">
                <div>
                    <strong>Respondido por:</strong> <?php echo htmlspecialchars($s['respondido_nome'] ?? 'Admin'); ?>
                    em <?php echo date('d/m/Y H:i', strtotime($s['respondido_em'])); ?>
                </div>
                <?php if (!empty($s['resposta'])): ?>
                    <div class="bg-white border border-blue-200 rounded-xl p-3">
                        <strong>Resposta:</strong>
                        <p class="mt-1 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($s['resposta'])); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($s['motivo_rejeicao'])): ?>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-red-700">
                        <strong>Motivo da rejeição:</strong>
                        <p class="mt-1 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($s['motivo_rejeicao'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Formulário de resposta -->
        <?php if (!in_array($s['status'], ['concluida'], true)): ?>
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h2 class="text-lg font-serif text-[#3E352E] mb-4">
                <i class="fa-solid fa-reply text-[#8C6D36] mr-1"></i> Responder
            </h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="responder">

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Novo status *</label>
                    <select name="status" id="sel-status" required class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                        <option value="em_analise" <?php echo $s['status'] === 'em_analise' ? 'selected' : ''; ?>>Em análise</option>
                        <option value="aprovada" <?php echo $s['status'] === 'aprovada' ? 'selected' : ''; ?>>Aprovar</option>
                        <option value="rejeitada" <?php echo $s['status'] === 'rejeitada' ? 'selected' : ''; ?>>Rejeitar</option>
                        <option value="concluida" <?php echo $s['status'] === 'concluida' ? 'selected' : ''; ?>>Concluir</option>
                    </select>
                </div>

                <div id="bloco-motivo" class="hidden">
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Motivo da rejeição *</label>
                    <textarea name="motivo_rejeicao" rows="3" class="w-full border border-stone-300 px-3 py-2.5 rounded-xl text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                              placeholder="Ex.: dados clínicos devem ser retidos por 20 anos conforme CFM"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Resposta ao titular (opcional)</label>
                    <textarea name="resposta" rows="4" class="w-full border border-stone-300 px-3 py-2.5 rounded-xl text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                              placeholder="Explique a decisão ao titular..."></textarea>
                </div>

                <?php if ($s['tipo'] === 'exclusao'): ?>
                <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                    <span>
                        <strong>Atenção:</strong> se você aprovar, o sistema fará a <strong>anonimização</strong> do usuário
                        (nome, CPF, e-mail e dados pessoais serão removidos; dados clínicos são mantidos conforme CFM).
                    </span>
                </div>
                <?php endif; ?>

                <div class="flex justify-end gap-3 pt-2 border-t border-stone-100">
                    <a href="index.php" class="px-5 py-2.5 rounded-xl text-sm font-medium text-stone-700 border border-stone-300 hover:bg-stone-50">
                        Voltar
                    </a>
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-paper-plane mr-1"></i> Salvar resposta
                    </button>
                </div>
            </form>
        </section>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sel = document.getElementById('sel-status');
            const bloco = document.getElementById('bloco-motivo');
            if (!sel || !bloco) return;

            function atualizar() {
                if (sel.value === 'rejeitada') {
                    bloco.classList.remove('hidden');
                } else {
                    bloco.classList.add('hidden');
                }
            }
            sel.addEventListener('change', atualizar);
            atualizar();
        });
        </script>
        <?php endif; ?>

    </main>

    <?php include __DIR__ . '/../../includes/rodape.php'; ?>
</body>
</html>