<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

use App\ActivityLogger;

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$uid = (int)$_SESSION['usuario_id'];
$msg = ''; $msgTipo = '';

// ============================================================
// AÇÃO: REGISTRAR INCIDENTE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'registrar') {
    $titulo    = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $gravidade = $_POST['gravidade'] ?? 'media';
    $tipo      = trim($_POST['tipo_incidente'] ?? '');
    $cats      = trim($_POST['categorias_dados'] ?? '');
    $afetados  = (int)($_POST['titulares_afetados'] ?? 0);
    $dataInc   = trim($_POST['data_incidente'] ?? '');
    $dataDet   = trim($_POST['data_deteccao'] ?? '');

    if ($titulo === '' || $descricao === '') {
        $msg = "Preencha título e descrição.";
        $msgTipo = 'erro';
    } elseif (!in_array($gravidade, ['baixa','media','alta','critica'], true)) {
        $msg = "Gravidade inválida.";
        $msgTipo = 'erro';
    } else {
        $protocolo = 'INC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $dataIncEsc = $dataInc !== '' ? $dataInc : null;
        $dataDetEsc = $dataDet !== '' ? $dataDet : null;

        $st = $conn->prepare("
            INSERT INTO lgpd_incidentes
                (protocolo, titulo, descricao, gravidade, tipo_incidente,
                 categorias_dados, titulares_afetados, data_incidente, data_deteccao, registrado_por)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");
        $st->bind_param(
            'ssssssissi',
            $protocolo, $titulo, $descricao, $gravidade, $tipo,
            $cats, $afetados, $dataIncEsc, $dataDetEsc, $uid
        );

        if ($st->execute()) {
            $msg = "Incidente registrado com protocolo: <strong>{$protocolo}</strong>";
            $msgTipo = 'sucesso';

            ActivityLogger::log(
                action: 'lgpd_incidente_registrado',
                entity: 'lgpd_incidente',
                entityId: (int)$conn->insert_id,
                description: "Admin {$nomeAdmin} registrou incidente: {$protocolo} ({$gravidade})",
                metadata: ['protocolo' => $protocolo, 'gravidade' => $gravidade],
                tags: ['lgpd', 'incidente']
            );
        } else {
            $msg = "Erro: " . $conn->error;
            $msgTipo = 'erro';
        }
    }
}

// ============================================================
// AÇÃO: ATUALIZAR STATUS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
    $id = (int)($_POST['id'] ?? 0);
    $novoStatus = $_POST['status'] ?? '';
    $comAnpd     = isset($_POST['comunicado_anpd']) ? 1 : 0;
    $comTit      = isset($_POST['comunicado_titulares']) ? 1 : 0;
    $medContidas = trim($_POST['medidas_contidas'] ?? '');
    $causa       = trim($_POST['causa_raiz'] ?? '');
    $medCorr     = trim($_POST['medidas_corretivas'] ?? '');

    if (!in_array($novoStatus, ['aberto','contido','comunicado','encerrado'], true)) {
        $msg = "Status inválido.";
        $msgTipo = 'erro';
    } else {
        $dataAnpd = $comAnpd ? date('Y-m-d') : null;
        $dataTit  = $comTit ? date('Y-m-d') : null;

        $st = $conn->prepare("
            UPDATE lgpd_incidentes
            SET status = ?, comunicado_anpd = ?, comunicado_anpd_em = ?,
                comunicado_titulares = ?, comunicado_tit_em = ?,
                medidas_contidas = ?, causa_raiz = ?, medidas_corretivas = ?
            WHERE id = ?
        ");
        $st->bind_param(
            'sisissssi',
            $novoStatus, $comAnpd, $dataAnpd, $comTit, $dataTit,
            $medContidas, $causa, $medCorr, $id
        );
        $st->execute();

        ActivityLogger::log(
            action: 'lgpd_incidente_atualizado',
            entity: 'lgpd_incidente',
            entityId: $id,
            description: "Admin {$nomeAdmin} atualizou incidente #{$id} para: {$novoStatus}",
            tags: ['lgpd', 'incidente']
        );

        $msg = "Incidente atualizado.";
        $msgTipo = 'sucesso';
    }
}

// ============================================================
// AÇÃO: MARCAR COMO FALSO ALARME (arquiva, mantém histórico)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'falso_alarme') {
    $id     = (int)($_POST['id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');

    if ($motivo === '') {
        $msg = "Informe o motivo (para auditoria).";
        $msgTipo = 'erro';
    } else {
        $st = $conn->prepare("
            UPDATE lgpd_incidentes
            SET status = 'falso_alarme',
                causa_raiz = CONCAT(COALESCE(causa_raiz, ''), '\n\n[FALSO ALARME em ', NOW(), '] ', ?)
            WHERE id = ?
        ");
        $st->bind_param('si', $motivo, $id);
        $st->execute();

        ActivityLogger::log(
            action: 'lgpd_incidente_falso_alarme',
            entity: 'lgpd_incidente',
            entityId: $id,
            description: "Admin {$nomeAdmin} marcou incidente #{$id} como falso alarme: {$motivo}",
            metadata: ['motivo' => $motivo],
            tags: ['lgpd', 'incidente', 'falso_alarme']
        );

        $msg = "Incidente marcado como falso alarme. O registro foi mantido no histórico.";
        $msgTipo = 'sucesso';
    }
}

// ============================================================
// AÇÃO: EXCLUIR PERMANENTEMENTE (com log de auditoria)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $id     = (int)($_POST['id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');

    if ($motivo === '') {
        $msg = "Informe o motivo da exclusão (obrigatório para auditoria).";
        $msgTipo = 'erro';
    } else {
        $st = $conn->prepare("SELECT protocolo, titulo, gravidade, status FROM lgpd_incidentes WHERE id = ?");
        $st->bind_param('i', $id);
        $st->execute();
        $inc = $st->get_result()->fetch_assoc();

        if (!$inc) {
            $msg = "Incidente não encontrado.";
            $msgTipo = 'erro';
        } else {
            $st = $conn->prepare("DELETE FROM lgpd_incidentes WHERE id = ?");
            $st->bind_param('i', $id);
            $st->execute();

            ActivityLogger::log(
                action: 'lgpd_incidente_excluido',
                entity: 'lgpd_incidente',
                entityId: $id,
                description: "Admin {$nomeAdmin} EXCLUIU permanentemente o incidente {$inc['protocolo']} ({$inc['titulo']}). Motivo: {$motivo}",
                metadata: [
                    'protocolo'         => $inc['protocolo'],
                    'titulo'            => $inc['titulo'],
                    'gravidade'         => $inc['gravidade'],
                    'status_anterior'   => $inc['status'],
                    'motivo_exclusao'   => $motivo,
                ],
                tags: ['lgpd', 'incidente', 'exclusao']
            );

            $msg = "Incidente excluído permanentemente. O evento ficou registrado no log de auditoria.";
            $msgTipo = 'sucesso';
        }
    }
}

// ============================================================
// LISTA
// ============================================================
$incidentes = $conn->query("
    SELECT i.*, u.nome AS registrado_nome
    FROM lgpd_incidentes i
    LEFT JOIN usuarios u ON u.id = i.registrado_por
    ORDER BY 
        CASE i.status 
            WHEN 'aberto' THEN 1 
            WHEN 'contido' THEN 2 
            WHEN 'comunicado' THEN 3 
            WHEN 'falso_alarme' THEN 5
            ELSE 4 
        END,
        i.criado_em DESC
")->fetch_all(MYSQLI_ASSOC);

function badgeGravidade(string $g): string {
    $map = [
        'baixa'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'media'   => 'bg-amber-50 text-amber-800 border-amber-200',
        'alta'    => 'bg-orange-50 text-orange-800 border-orange-200',
        'critica' => 'bg-red-50 text-red-700 border-red-200',
    ];
    $cls = $map[$g] ?? 'bg-stone-100 text-stone-700';
    return "<span class=\"inline-block text-xs font-bold border $cls px-2.5 py-1 rounded-full\">" . ucfirst($g) . "</span>";
}

function badgeStatusInc(string $s): string {
    $map = [
        'aberto'       => 'bg-red-50 text-red-700 border-red-200',
        'contido'      => 'bg-amber-50 text-amber-800 border-amber-200',
        'comunicado'   => 'bg-blue-50 text-blue-800 border-blue-200',
        'encerrado'    => 'bg-stone-100 text-stone-700 border-stone-200',
        'falso_alarme' => 'bg-purple-50 text-purple-700 border-purple-200',
    ];
    $cls = $map[$s] ?? 'bg-stone-100 text-stone-700';

    $labels = [
        'aberto'       => 'Aberto',
        'contido'      => 'Contido',
        'comunicado'   => 'Comunicado',
        'encerrado'    => 'Encerrado',
        'falso_alarme' => 'Falso alarme',
    ];
    $lbl = $labels[$s] ?? ucfirst($s);

    return "<span class=\"inline-block text-xs font-semibold border $cls px-2.5 py-1 rounded-full\">" . htmlspecialchars($lbl) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidentes de Segurança - LGPD</title>
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-serif text-[#3E352E] flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                    Incidentes de Segurança
                </h1>
                <p class="text-sm text-stone-500 mt-1">
                    Registro e tratamento de incidentes envolvendo dados pessoais (LGPD Art. 48).
                </p>
            </div>
            <div class="flex gap-2">
                <a href="plano-incidentes.php" class="inline-flex items-center gap-2 bg-white border border-stone-300 hover:bg-stone-50 text-stone-700 px-4 py-2.5 rounded-xl text-sm font-medium transition">
                    <i class="fa-solid fa-book"></i> Ver plano
                </a>
                <button onclick="document.getElementById('modal-incidente').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                    <i class="fa-solid fa-plus"></i> Registrar incidente
                </button>
            </div>
        </section>

        <?php if ($msg): ?>
            <div class="<?php echo $msgTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid <?php echo $msgTipo === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                <span><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>

        <section class="space-y-3">
            <?php if (!$incidentes): ?>
                <div class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm text-center">
                    <i class="fa-solid fa-shield-halved text-4xl text-emerald-500 mb-3 block"></i>
                    <p class="text-stone-700 font-medium">Nenhum incidente registrado.</p>
                    <p class="text-sm text-stone-500 mt-1">Continue mantendo boas práticas de segurança.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($incidentes as $inc):
                $ehFalso = ($inc['status'] === 'falso_alarme');
                $ehEncerrado = ($inc['status'] === 'encerrado');
            ?>
                <div class="bg-white p-6 rounded-2xl border <?php 
                    echo $inc['status'] === 'aberto' ? 'border-red-300 shadow-md' : (
                        $ehFalso ? 'border-purple-200 opacity-80' : 'border-[#E6D5B8]/40'
                    ); 
                ?> shadow-sm">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="font-mono text-xs bg-[#F9F4EC] border border-[#E6D5B8] px-2 py-1 rounded"><?php echo htmlspecialchars($inc['protocolo']); ?></span>
                                <?php echo badgeGravidade($inc['gravidade']); ?>
                                <?php echo badgeStatusInc($inc['status']); ?>
                            </div>
                            <h3 class="font-serif text-lg text-[#3E352E]"><?php echo htmlspecialchars($inc['titulo']); ?></h3>
                            <p class="text-sm text-stone-600 mt-2 whitespace-pre-line"><?php echo htmlspecialchars($inc['descricao']); ?></p>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 text-xs">
                                <?php if ($inc['tipo_incidente']): ?>
                                    <div><div class="font-bold text-stone-500 uppercase">Tipo</div><div class="text-stone-700"><?php echo htmlspecialchars($inc['tipo_incidente']); ?></div></div>
                                <?php endif; ?>
                                <?php if ($inc['titulares_afetados']): ?>
                                    <div><div class="font-bold text-stone-500 uppercase">Titulares</div><div class="text-stone-700"><?php echo (int)$inc['titulares_afetados']; ?></div></div>
                                <?php endif; ?>
                                <?php if ($inc['data_incidente']): ?>
                                    <div><div class="font-bold text-stone-500 uppercase">Incidente</div><div class="text-stone-700"><?php echo date('d/m/Y H:i', strtotime($inc['data_incidente'])); ?></div></div>
                                <?php endif; ?>
                                <?php if ($inc['data_deteccao']): ?>
                                    <div><div class="font-bold text-stone-500 uppercase">Detecção</div><div class="text-stone-700"><?php echo date('d/m/Y H:i', strtotime($inc['data_deteccao'])); ?></div></div>
                                <?php endif; ?>
                            </div>

                            <?php if ($inc['comunicado_anpd'] || $inc['comunicado_titulares']): ?>
                                <div class="flex gap-2 mt-3 text-xs">
                                    <?php if ($inc['comunicado_anpd']): ?>
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full">
                                            <i class="fa-solid fa-check"></i> ANPD em <?php echo date('d/m/Y', strtotime($inc['comunicado_anpd_em'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($inc['comunicado_titulares']): ?>
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full">
                                            <i class="fa-solid fa-check"></i> Titulares em <?php echo date('d/m/Y', strtotime($inc['comunicado_tit_em'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="text-xs text-stone-500 mt-3">
                                Registrado por <?php echo htmlspecialchars($inc['registrado_nome'] ?? 'Admin'); ?>
                                em <?php echo date('d/m/Y H:i', strtotime($inc['criado_em'])); ?>
                            </div>
                        </div>

                        <!-- Botões de ação -->
                        <div class="flex flex-col gap-2 shrink-0">
                            <?php if (!$ehEncerrado && !$ehFalso): ?>
                                <button onclick='abrirModalAtualizar(<?php echo json_encode($inc); ?>)'
                                        class="inline-flex items-center gap-2 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-2.5 rounded-xl text-xs font-medium transition shadow-sm whitespace-nowrap">
                                    <i class="fa-solid fa-pen"></i> Atualizar
                                </button>
                            <?php endif; ?>

                            <?php if (!$ehFalso): ?>
                                <button onclick='abrirModalFalsoAlarme(<?php echo (int)$inc["id"]; ?>, <?php echo json_encode($inc["protocolo"]); ?>)'
                                        class="inline-flex items-center gap-2 bg-stone-100 hover:bg-stone-200 text-stone-700 px-4 py-2.5 rounded-xl text-xs font-medium transition whitespace-nowrap">
                                    <i class="fa-solid fa-bell-slash text-[#8C6D36]"></i> Falso alarme
                                </button>
                            <?php endif; ?>

                            <button onclick='abrirModalExcluir(<?php echo (int)$inc["id"]; ?>, <?php echo json_encode($inc["protocolo"]); ?>)'
                                    class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-4 py-2.5 rounded-xl text-xs font-medium transition whitespace-nowrap">
                                <i class="fa-solid fa-trash"></i> Excluir
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

    </main>

    <!-- Modal: Registrar incidente -->
    <div id="modal-incidente" class="fixed inset-0 bg-black/70 z-[120] hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[95vh] overflow-y-auto p-6 space-y-5 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <h3 class="text-xl font-serif text-[#3E352E]">
                    <i class="fa-solid fa-triangle-exclamation text-red-600 mr-1"></i>
                    Registrar incidente
                </h3>
                <button onclick="document.getElementById('modal-incidente').classList.add('hidden')" class="text-stone-400 hover:text-stone-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="registrar">

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Título *</label>
                    <input type="text" name="titulo" required
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                           placeholder="Ex.: Acesso não autorizado ao banco de dados">
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Descrição *</label>
                    <textarea name="descricao" rows="4" required
                              class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                              placeholder="Descreva o incidente, como foi detectado e quais dados podem ter sido comprometidos"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Gravidade *</label>
                        <select name="gravidade" required class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                            <option value="baixa">Baixa</option>
                            <option value="media" selected>Média</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Tipo</label>
                        <input type="text" name="tipo_incidente" placeholder="Ex.: Acesso indevido"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Titulares afetados</label>
                        <input type="number" name="titulares_afetados" min="0" value="0"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Categorias de dados comprometidos</label>
                    <input type="text" name="categorias_dados" placeholder="Ex.: Nome, CPF, dados de saúde"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Data do incidente</label>
                        <input type="datetime-local" name="data_incidente"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Data da detecção</label>
                        <input type="datetime-local" name="data_deteccao"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                    </div>
                </div>

                <div class="bg-red-50 border border-red-200 p-3 rounded-xl text-xs text-red-800 flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <span>
                        Se o incidente puder acarretar risco ou dano relevante aos titulares, a ANPD deve ser comunicada
                        em prazo razoável (art. 48 LGPD). Consulte o plano de resposta.
                    </span>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-stone-100">
                    <button type="button" onclick="document.getElementById('modal-incidente').classList.add('hidden')"
                            class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Registrar incidente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Atualizar incidente -->
    <div id="modal-atualizar" class="fixed inset-0 bg-black/70 z-[120] hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[95vh] overflow-y-auto p-6 space-y-5 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <h3 class="text-xl font-serif text-[#3E352E]">Atualizar incidente</h3>
                <button onclick="document.getElementById('modal-atualizar').classList.add('hidden')" class="text-stone-400 hover:text-stone-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" id="upd-id">

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Status *</label>
                    <select name="status" id="upd-status" required class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]">
                        <option value="aberto">Aberto</option>
                        <option value="contido">Contido</option>
                        <option value="comunicado">Comunicado</option>
                        <option value="encerrado">Encerrado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Medidas de contenção adotadas</label>
                    <textarea name="medidas_contidas" id="upd-medidas" rows="2"
                              class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]"
                              placeholder="Ex.: revogação de acessos, reset de senhas, bloqueio de IPs..."></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Causa raiz</label>
                    <textarea name="causa_raiz" id="upd-causa" rows="2"
                              class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]"
                              placeholder="Ex.: falha em validação de permissões"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Medidas corretivas / preventivas</label>
                    <textarea name="medidas_corretivas" id="upd-corretivas" rows="2"
                              class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6]"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-start gap-2 text-sm text-stone-700 cursor-pointer">
                        <input type="checkbox" name="comunicado_anpd" id="upd-anpd" class="mt-1 w-4 h-4 accent-[#8C6D36]">
                        <span>Comunicado à ANPD</span>
                    </label>
                    <label class="flex items-start gap-2 text-sm text-stone-700 cursor-pointer">
                        <input type="checkbox" name="comunicado_titulares" id="upd-tit" class="mt-1 w-4 h-4 accent-[#8C6D36]">
                        <span>Comunicado aos titulares</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-stone-100">
                    <button type="button" onclick="document.getElementById('modal-atualizar').classList.add('hidden')"
                            class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Marcar como falso alarme -->
    <div id="modal-falso-alarme" class="fixed inset-0 bg-black/70 z-[120] hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <h3 class="text-xl font-serif text-[#3E352E]">
                    <i class="fa-solid fa-bell-slash text-[#8C6D36] mr-1"></i>
                    Marcar como falso alarme
                </h3>
                <button onclick="document.getElementById('modal-falso-alarme').classList.add('hidden')" class="text-stone-400 hover:text-stone-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="bg-purple-50 border border-purple-200 text-purple-800 p-3 rounded-xl text-xs flex items-start gap-2">
                <i class="fa-solid fa-circle-info mt-0.5"></i>
                <span>
                    O incidente será <strong>arquivado</strong> (não aparece mais entre os ativos),
                    mas <strong>continua no histórico</strong> para fins de auditoria.
                </span>
            </div>

            <p class="text-sm text-stone-600">
                Protocolo: <span class="font-mono font-bold" id="fa-protocolo">—</span>
            </p>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="falso_alarme">
                <input type="hidden" name="id" id="fa-id">

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Motivo (obrigatório) *</label>
                    <textarea name="motivo" rows="3" required
                              class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                              placeholder="Ex.: Alerta de teste do sistema de monitoramento. Nenhum dado foi comprometido."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-stone-100">
                    <button type="button" onclick="document.getElementById('modal-falso-alarme').classList.add('hidden')"
                            class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-[#8C6D36] hover:bg-[#755a2c] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-bell-slash mr-1"></i> Arquivar como falso alarme
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Excluir permanentemente -->
    <div id="modal-excluir" class="fixed inset-0 bg-black/70 z-[120] hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <h3 class="text-xl font-serif text-[#3E352E]">
                    <i class="fa-solid fa-trash text-red-600 mr-1"></i>
                    Excluir incidente
                </h3>
                <button onclick="document.getElementById('modal-excluir').classList.add('hidden')" class="text-stone-400 hover:text-stone-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded-xl text-xs flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <div>
                    <strong>Atenção — ação irreversível.</strong>
                    <p class="mt-1">
                        O incidente será <strong>apagado permanentemente</strong> do banco. Ficará apenas um registro no
                        <strong>log de auditoria</strong> (imutável), com seu usuário, data, motivo e os dados do incidente.
                    </p>
                </div>
            </div>

            <p class="text-sm text-stone-600">
                Protocolo: <span class="font-mono font-bold" id="ex-protocolo">—</span>
            </p>

            <form method="POST" class="space-y-4" onsubmit="return confirm('Confirmar exclusão PERMANENTE do incidente? Esta ação não pode ser desfeita.');">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" id="ex-id">

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Motivo da exclusão (obrigatório) *</label>
                    <textarea name="motivo" rows="3" required
                              class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                              placeholder="Ex.: Registro duplicado do mesmo incidente. Já acompanhado no protocolo INC-20261015-XXX."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-stone-100">
                    <button type="button" onclick="document.getElementById('modal-excluir').classList.add('hidden')"
                            class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-trash mr-1"></i> Excluir permanentemente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModalAtualizar(inc) {
        document.getElementById('upd-id').value          = inc.id;
        document.getElementById('upd-status').value      = inc.status;
        document.getElementById('upd-medidas').value     = inc.medidas_contidas || '';
        document.getElementById('upd-causa').value       = inc.causa_raiz || '';
        document.getElementById('upd-corretivas').value  = inc.medidas_corretivas || '';
        document.getElementById('upd-anpd').checked      = inc.comunicado_anpd == 1;
        document.getElementById('upd-tit').checked       = inc.comunicado_titulares == 1;
        document.getElementById('modal-atualizar').classList.remove('hidden');
    }

    function abrirModalFalsoAlarme(id, protocolo) {
        document.getElementById('fa-id').value              = id;
        document.getElementById('fa-protocolo').textContent = protocolo;
        document.getElementById('modal-falso-alarme').classList.remove('hidden');
    }

    function abrirModalExcluir(id, protocolo) {
        document.getElementById('ex-id').value              = id;
        document.getElementById('ex-protocolo').textContent = protocolo;
        document.getElementById('modal-excluir').classList.remove('hidden');
    }
    </script>

</body>
</html>