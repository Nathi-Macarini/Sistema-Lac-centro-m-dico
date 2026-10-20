<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/termo_texto.php';
require_once __DIR__ . '/includes/teste_av.php';

use App\ActivityLogger;
use App\TiposAtendimento;
use App\StatusConsulta;

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
verificarEsquema($conn);

$uid         = (int)$_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['tipo_usuario'] ?? 'comum';
$especialidadesAgenda = ['Cardiologia', 'Dermatologia', 'Ortopedia', 'Ginecologia', 'Pediatria', 'Clínica Geral'];
$aviso = ''; $avisoTipo = '';

// ✅ Flag: o usuário já aceitou o termo alguma vez?
$jaAceitouTermo = usuarioAceitouTeleconsulta($conn, $uid);

// ============================================================
// AÇÃO: AGENDAR CONSULTA
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'agendar') {
    $esp  = trim($_POST['especialidade'] ?? '');
    $mod  = trim($_POST['modalidade'] ?? '');
    $data = trim($_POST['data_hora'] ?? '');
    $dt   = DateTime::createFromFormat('Y-m-d\TH:i', $data);

    if (!in_array($esp, $especialidadesAgenda, true) || !in_array($mod, ['Vídeo', 'Presencial'], true) || !$dt) {
        $aviso = "Dados do agendamento inválidos. Confira a especialidade, a modalidade e a data.";
        $avisoTipo = 'erro';
    } elseif ($dt->getTimestamp() < time() - 300) {
        $aviso = "Escolha uma data e um horário que ainda não passaram.";
        $avisoTipo = 'erro';
    } elseif ((int)$dt->format('w') === 0) {
        $aviso = "Não atendemos aos domingos.";
        $avisoTipo = 'erro';
    } else {
        $dataSql = $dt->format('Y-m-d H:i:00');

        $statusCancel = StatusConsulta::CANCELAR;
        $statusRemarc = StatusConsulta::REMARCAR;

        $q = $conn->prepare("SELECT COUNT(*) AS n FROM medicos WHERE especialidade = ? AND ativo = 1");
        $q->bind_param('s', $esp);
        $q->execute();
        $totalMedicos = (int)$q->get_result()->fetch_assoc()['n'];

        if ($totalMedicos === 0) {
            $aviso = "Nenhum médico ativo em {$esp}. Escolha outra especialidade.";
            $avisoTipo = 'erro';
        } else {
            $q = $conn->prepare("
                SELECT COUNT(DISTINCT medico_id) AS n FROM consultas
                WHERE especialidade = ? AND data_hora = ?
                  AND medico_id IS NOT NULL
                  AND status NOT IN (?, ?)
            ");
            $q->bind_param('ssss', $esp, $dataSql, $statusCancel, $statusRemarc);
            $q->execute();
            $ocupados = (int)$q->get_result()->fetch_assoc()['n'];

            $q = $conn->prepare("
                SELECT id FROM consultas
                WHERE usuario_id = ? AND data_hora = ?
                  AND status NOT IN (?, ?)
                LIMIT 1
            ");
            $q->bind_param('isss', $uid, $dataSql, $statusCancel, $statusRemarc);
            $q->execute();
            $euJaTenho = (bool)$q->get_result()->fetch_assoc();

            if ($euJaTenho) {
                $aviso = "Você já tem uma consulta marcada nesse horário.";
                $avisoTipo = 'erro';
            } elseif ($ocupados >= $totalMedicos) {
                $aviso = "Este horário acabou de ser preenchido. Escolha outro.";
                $avisoTipo = 'erro';
            } else {
                $statusIni = StatusConsulta::AGENDADA;
                $st = $conn->prepare("INSERT INTO consultas (usuario_id, especialidade, modalidade, tipo, data_hora, status) VALUES (?, ?, ?, 'agendada', ?, ?)");
                $st->bind_param('issss', $uid, $esp, $mod, $dataSql, $statusIni);

                if ($st->execute()) {
                    $consultaId = (int) $conn->insert_id;

                    ActivityLogger::log(
                        action: 'consulta_agendada',
                        entity: 'consulta',
                        entityId: $consultaId,
                        description: "Paciente {$_SESSION['nome_usuario']} agendou {$esp} ({$mod}) para " . date('d/m/Y H:i', strtotime($dataSql)) . " — status: " . StatusConsulta::AGENDADA,
                        metadata: [
                            'consulta_id'   => $consultaId,
                            'especialidade' => $esp,
                            'modalidade'    => $mod,
                            'data_hora'     => $dataSql,
                            'tipo'          => 'agendada',
                            'status_novo'   => StatusConsulta::AGENDADA,
                        ],
                        tags: [
                            TiposAtendimento::LAC_TELEATENDIMENTO,
                            TiposAtendimento::AGENDADO,
                            StatusConsulta::tag(StatusConsulta::AGENDADA),
                        ]
                    );

                    header("Location: index.php?ok=agendada");
                    exit;
                }
                $aviso = "Não foi possível salvar o agendamento: " . $conn->error;
                $avisoTipo = 'erro';
            }
        }
    }
}

// ============================================================
// AÇÃO: PRONTO ATENDIMENTO (entrar na fila)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'pronto_atendimento') {
    $esp = trim($_POST['especialidade'] ?? '');

    // ✅ Aceita se: (a) já aceitou antes OU (b) marcou o checkbox agora
    $jaAceitouAgora = ($_POST['aceite_termo'] ?? '') === '1';
    $termoOk = $jaAceitouTermo || $jaAceitouAgora;

    if (!$termoOk) {
        $aviso = "Você precisa ler e aceitar o Termo de Consentimento para entrar na fila.";
        $avisoTipo = 'erro';
    } elseif (!in_array($esp, PA_ESPECIALIDADES, true)) {
        $aviso = "Escolha uma especialidade válida para o pronto atendimento.";
        $avisoTipo = 'erro';
    } else {
        $limite = date('Y-m-d H:i:s', time() - PA_VALIDADE_HORAS * 3600);

        $st = $conn->prepare(
            "SELECT id FROM consultas
             WHERE usuario_id = ? AND tipo = 'pronto_atendimento'
             AND status IN (" . StatusConsulta::sqlIn(StatusConsulta::ATIVAS) . ")
             AND data_hora >= ? ORDER BY id DESC LIMIT 1"
        );
        $st->bind_param('is', $uid, $limite);
        $st->execute();
        $ja = $st->get_result()->fetch_assoc();

        if ($ja) {
            header("Location: teleconsulta.php?consulta=" . (int)$ja['id']);
            exit;
        }

        $agora     = date('Y-m-d H:i:s');
        $statusIni = StatusConsulta::AGENDADA;

        $st = $conn->prepare("INSERT INTO consultas (usuario_id, especialidade, modalidade, tipo, data_hora, status) VALUES (?, ?, 'Vídeo', 'pronto_atendimento', ?, ?)");
        $st->bind_param('isss', $uid, $esp, $agora, $statusIni);

        if ($st->execute()) {
            $consultaId = (int) $conn->insert_id;

            // ✅ Registrar termo (a função já evita duplicar)
            if ($jaAceitouAgora) {
                registrarTermo($conn, $uid, 'teleconsulta_pa', $consultaId);
            }

            ActivityLogger::log(
                action: 'lac_atende_iniciado',
                entity: 'consulta',
                entityId: $consultaId,
                description: "Paciente {$_SESSION['nome_usuario']} entrou na fila do LAC Atende ({$esp}) — status: " . StatusConsulta::AGENDADA,
                metadata: [
                    'consulta_id'   => $consultaId,
                    'especialidade' => $esp,
                    'tipo'          => 'pronto_atendimento',
                    'data_hora'     => $agora,
                    'status_novo'   => StatusConsulta::AGENDADA,
                ],
                tags: [
                    TiposAtendimento::LAC_ATENDE,
                    TiposAtendimento::INICIADO,
                    StatusConsulta::tag(StatusConsulta::AGENDADA),
                ]
            );

            header("Location: teleconsulta.php?consulta=" . $consultaId);
            exit;
        }
        $aviso = "Não foi possível entrar na fila: " . $conn->error;
        $avisoTipo = 'erro';
    }
}

// ============================================================
// AÇÃO: CANCELAR CONSULTA
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'cancelar') {
    $cid        = (int)($_POST['consulta_id'] ?? 0);
    $statusCanc = StatusConsulta::CANCELAR;
    $statusPerm = StatusConsulta::AGENDADA;

    $st = $conn->prepare("UPDATE consultas SET status = ? WHERE id = ? AND usuario_id = ? AND status = ?");
    $st->bind_param('siis', $statusCanc, $cid, $uid, $statusPerm);
    $st->execute();

    if ($st->affected_rows > 0) {
        ActivityLogger::log(
            action: 'consulta_cancelada',
            entity: 'consulta',
            entityId: $cid,
            description: "Paciente {$_SESSION['nome_usuario']} cancelou a consulta #{$cid} — status: " . StatusConsulta::label(StatusConsulta::CANCELAR),
            metadata: [
                'consulta_id' => $cid,
                'status_novo' => StatusConsulta::CANCELAR,
            ],
            tags: [
                TiposAtendimento::LAC_TELEATENDIMENTO,
                TiposAtendimento::CANCELADO,
                StatusConsulta::tag(StatusConsulta::CANCELAR),
            ]
        );
    }

    header("Location: index.php?ok=cancelada");
    exit;
}

if (($_GET['ok'] ?? '') === 'agendada') { $aviso = "Consulta agendada! Ela já aparece para os médicos da especialidade e no seu histórico."; $avisoTipo = 'sucesso'; }
if (($_GET['ok'] ?? '') === 'cancelada') { $aviso = "Solicitação cancelada."; $avisoTipo = 'sucesso'; }

// ============================================================
// CONSULTAS MARCADAS / EM ANDAMENTO
// ============================================================
$inicioHoje = date('Y-m-d 00:00:00');
$limitePA   = date('Y-m-d H:i:s', time() - PA_VALIDADE_HORAS * 3600);

$st = $conn->prepare(
    "SELECT c.*, m.nome AS medico_nome
     FROM consultas c LEFT JOIN medicos m ON m.id = c.medico_id
     WHERE c.usuario_id = ? AND c.status IN (" . StatusConsulta::sqlIn(StatusConsulta::ATIVAS) . ")
       AND ((c.tipo = 'agendada' AND c.data_hora >= ?) OR (c.tipo = 'pronto_atendimento' AND c.data_hora >= ?))
     ORDER BY (c.tipo = 'pronto_atendimento') DESC, c.data_hora ASC"
);
$st->bind_param('iss', $uid, $inicioHoje, $limitePA);
$st->execute();
$consultas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAC Centro Médico - Portal do Paciente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
            </div>
            <nav class="hidden md:flex space-x-8 font-medium text-stone-600">
                <a href="index.php" class="text-[#8C6D36] border-b-2 border-[#8C6D36] pb-1 font-semibold">Início</a>
                <a href="historico.php" class="hover:text-[#8C6D36] transition">Histórico</a>
                <a href="plano.php" class="hover:text-[#8C6D36] transition">Plano</a>
                <a href="dependentes.php" class="hover:text-[#8C6D36] transition">Dependentes</a>
                <a href="privacidade.php" class="hover:text-[#8C6D36] transition">Privacidade</a>
            </nav>
            <div class="flex items-center space-x-4">
                <?php if ($tipoUsuario === 'admin'): ?>
                    <a href="admin/dashboard.php"
                       class="hidden sm:flex items-center gap-2 bg-[#F9F4EC] text-[#8C6D36] border border-[#E6D5B8] px-3.5 py-2 rounded-xl text-sm font-semibold hover:bg-[#E6D5B8]/50 transition">
                        <i class="fa-solid fa-shield-halved"></i> Painel Admin
                    </a>
                <?php endif; ?>
                <a href="perfil.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition hidden sm:inline" title="Meu Perfil">
                    <?php echo h($_SESSION['nome_usuario']); ?>
                </a>
                <a href="logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 space-y-8">

        <?php if ($aviso): ?>
            <div class="<?php echo $avisoTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border">
                <?php echo h($aviso); ?>
            </div>
        <?php endif; ?>

        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h1 class="text-3xl font-serif text-[#3E352E] mb-2">
                Olá, <span><?php echo h($_SESSION['nome_usuario']); ?></span>.
            </h1>
            <p class="text-stone-600 text-lg">
                Seja bem-vindo(a). Estamos aqui para cuidar de você com agilidade, serenidade e acolhimento clínico.
            </p>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Pronto Atendimento -->
            <div class="bg-[#3E352E] text-white p-8 rounded-3xl shadow-xl relative overflow-hidden flex flex-col justify-between border border-[#C5A059]/30">
                <div class="space-y-6 relative z-10">
                    <div class="flex justify-between items-start">
                        <span class="bg-[#C5A059]/30 text-[#F4E8D1] text-xs font-semibold px-3.5 py-1.5 rounded-full uppercase tracking-wider border border-[#C5A059]/40">
                            Pronto Atendimento 24h/7d
                        </span>
                        <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-[#F4E8D1] border border-white/10">
                            <i class="fa-solid fa-video text-lg"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <h2 class="text-3xl md:text-4xl font-serif text-white">Falar com um médico agora</h2>
                        <p class="text-stone-300 text-sm leading-relaxed">
                            Atendimento de urgência imediato sem sair de casa. Médicos de plantão em Clínica Geral e Pediatria, prontos para orientar e prescrever.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-xs font-medium text-stone-200 pt-2">
                        <span class="flex items-center gap-1.5 bg-black/30 px-3 py-2 rounded-xl border border-white/5">
                            <i class="fa-regular fa-clock text-[#C5A059]"></i> Tempo de espera: ~3 min
                        </span>
                        <span class="flex items-center gap-1.5 bg-black/30 px-3 py-2 rounded-xl border border-white/5">
                            <i class="fa-regular fa-face-smile text-[#C5A059]"></i> Pediatria disponível
                        </span>
                    </div>
                </div>
                <div class="pt-8 relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <button onclick="abrirFluxoPA()" class="bg-white text-[#3E352E] hover:bg-[#F4E8D1] font-medium px-6 py-3.5 rounded-xl shadow-lg transition flex items-center gap-2">
                        <i class="fa-solid fa-video text-[#8C6D36]"></i> Entrar na fila de teleconsulta
                    </button>
                    <span class="text-xs text-stone-400 flex items-center gap-1">
                        <i class="fa-solid fa-lock text-[#C5A059]"></i> Vídeo direto entre você e o médico
                    </span>
                </div>
            </div>

            <!-- Agendar Consulta -->
            <div class="bg-white p-8 rounded-3xl shadow-xl border border-[#E6D5B8]/40 flex flex-col justify-between relative overflow-hidden">
                <div class="space-y-6 relative z-10">
                    <div class="flex justify-between items-start">
                        <span class="bg-[#F9F4EC] text-[#8C6D36] text-xs font-semibold px-3.5 py-1.5 rounded-full uppercase tracking-wider border border-[#E6D5B8]">
                            <i class="fa-solid fa-stethoscope mr-1"></i> Consultório & Especialidades
                        </span>
                        <div class="w-12 h-12 rounded-2xl bg-[#F9F4EC] flex items-center justify-center text-[#8C6D36] border border-[#E6D5B8]">
                            <i class="fa-regular fa-calendar-days text-lg"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <h2 class="text-3xl md:text-4xl font-serif text-[#3E352E]">Agendar consulta</h2>
                        <p class="text-stone-600 text-sm leading-relaxed">
                            Escolha a especialidade, data e modalidade (presencial ou por vídeo). O agendamento aparece para os médicos da especialidade.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <span class="bg-[#F9F4EC] text-[#5A4A3A] px-3.5 py-2 rounded-xl text-xs font-medium border border-[#E6D5B8]">Cardiologia</span>
                        <span class="bg-[#F9F4EC] text-[#5A4A3A] px-3.5 py-2 rounded-xl text-xs font-medium border border-[#E6D5B8]">Dermatologia</span>
                        <span class="bg-[#F9F4EC] text-[#5A4A3A] px-3.5 py-2 rounded-xl text-xs font-medium border border-[#E6D5B8]">Ortopedia</span>
                        <span class="bg-[#F9F4EC] text-[#5A4A3A] px-3.5 py-2 rounded-xl text-xs font-medium border border-[#E6D5B8]">Ginecologia</span>
                        <span class="bg-[#F9F4EC] text-[#5A4A3A] px-3.5 py-2 rounded-xl text-xs font-medium border border-[#E6D5B8]">+28 áreas</span>
                    </div>
                </div>
                <div class="pt-8 relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <button onclick="abrirModalAgendamento()" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white font-medium px-6 py-3.5 rounded-xl shadow-lg transition flex items-center gap-2">
                        <i class="fa-regular fa-calendar-days text-[#C5A059]"></i> Ver horários disponíveis
                    </button>
                    <span class="text-xs text-stone-500 flex items-center gap-1 font-medium">
                        <i class="fa-regular fa-circle-check text-[#8C6D36]"></i> Confirmação instantânea
                    </span>
                </div>
            </div>

        </section>

        <!-- Suas Consultas -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-serif text-[#3E352E]">Suas consultas marcadas</h3>
                <a href="historico.php" class="text-sm font-medium text-[#8C6D36] hover:underline">Ver histórico completo <i class="fa-solid fa-arrow-right text-xs"></i></a>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <?php if ($consultas): ?>
                    <?php foreach ($consultas as $c):
                        $pa    = $c['tipo'] === 'pronto_atendimento';
                        $video = $c['modalidade'] === 'Vídeo';
                    ?>
                        <div class="border <?php echo $pa ? 'border-[#C5A059]' : 'border-stone-200'; ?> p-5 rounded-2xl flex items-center justify-between gap-3 bg-[#FAF9F6]">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full"><?php echo h($c['modalidade']); ?></span>
                                    <?php if ($pa): ?><span class="text-xs font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2.5 py-1 rounded-full">Pronto atendimento</span><?php endif; ?>
                                    <span class="text-xs font-semibold border <?php echo StatusConsulta::badgeClasses($c['status']); ?> px-2.5 py-1 rounded-full">
                                        <?php echo h(StatusConsulta::label($c['status'])); ?>
                                    </span>
                                </div>
                                <h4 class="font-semibold text-stone-800 mt-2"><?php echo h($c['especialidade']); ?></h4>
                                <p class="text-sm text-stone-500 mt-1"><i class="fa-regular fa-calendar text-[#8C6D36]"></i> <?php echo date('d/m/Y H:i', strtotime($c['data_hora'])); ?></p>
                                <p class="text-xs text-stone-500 mt-1"><?php echo $c['medico_nome'] ? 'Médico: ' . h($c['medico_nome']) : 'Aguardando um médico de ' . h($c['especialidade']); ?></p>
                            </div>
                            <div class="flex flex-col items-end gap-2 shrink-0">
                                <?php if ($video): ?>
                                    <a href="teleconsulta.php?consulta=<?php echo (int)$c['id']; ?>" class="bg-[#3E352E] text-white text-sm px-4 py-2.5 rounded-xl hover:bg-[#5A4A3A] transition shadow-sm">
                                        <i class="fa-solid fa-video mr-1"></i> Acessar Sala
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-stone-600 bg-stone-200 px-3 py-2 rounded-xl font-medium">Presencial</span>
                                <?php endif; ?>
                                <?php if ($c['status'] === StatusConsulta::AGENDADA): ?>
                                    <form method="POST" onsubmit="return confirm('Cancelar esta solicitação?');">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <input type="hidden" name="consulta_id" value="<?php echo (int)$c['id']; ?>">
                                        <button type="submit" class="text-xs text-stone-500 hover:text-red-600 underline">Cancelar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-sm text-stone-500 col-span-2 py-4 text-center">Nenhuma consulta marcada no momento. Utilize o card de agendamento acima.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Suporte WhatsApp -->
        <section class="bg-[#F9F4EC] border border-[#E6D5B8] p-6 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="bg-emerald-100 text-emerald-800 p-3.5 rounded-2xl text-xl">
                    <i class="fa-brands fa-whatsapp"></i>
                </div>
                <div>
                    <div class="text-xs font-bold text-[#8C6D36] uppercase tracking-wider">Suporte e Concierge Clínico • Resposta média em 2 min</div>
                    <h4 class="font-serif text-lg text-[#3E352E]">Precisa de ajuda para marcar ou tirar dúvidas?</h4>
                    <p class="text-sm text-stone-600">Nossa equipe de recepção está online para orientar sobre preparo de exames, pedidos médicos e reagendamentos.</p>
                </div>
            </div>
            <a href="https://wa.me/5541987268262" target="_blank" class="bg-[#008069] hover:bg-[#006e5a] text-white font-medium px-5 py-3 rounded-xl transition flex items-center gap-2 whitespace-nowrap shadow-sm">
                <i class="fa-brands fa-whatsapp text-lg"></i> Falar pelo WhatsApp
            </a>
        </section>
    </main>

    <!-- ============================================================ -->
    <!-- MODAL: PA (especialidade + termo)                            -->
    <!-- ============================================================ -->
    <div id="modal-pa" class="fixed inset-0 bg-black/70 z-[110] hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-lg w-full max-h-[95vh] overflow-y-auto p-6 space-y-5 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <div>
                    <h3 class="text-xl font-serif text-[#3E352E]">Falar com um médico agora</h3>
                    <p class="text-xs text-stone-500 mt-0.5">
                        <?php echo $jaAceitouTermo ? 'Escolha a especialidade para entrar na fila.' : 'Escolha a especialidade e aceite o termo.'; ?>
                    </p>
                </div>
                <button onclick="fecharModalPA()" class="text-stone-400 hover:text-stone-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <form id="form-pa" method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="pronto_atendimento">

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1">Com quem você quer falar?</label>
                    <select name="especialidade" class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                        <?php foreach (PA_ESPECIALIDADES as $e): ?>
                            <option value="<?php echo h($e); ?>"><?php echo h($e); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Termo (só aparece se o usuário NUNCA aceitou) -->
                <?php if (!$jaAceitouTermo): ?>
                <div id="bloco-termo" class="space-y-3 pt-3 border-t border-stone-100">
                    <div class="bg-[#FAF9F6] border border-stone-200 rounded-xl p-4 max-h-48 overflow-y-auto text-xs text-stone-600 leading-relaxed">
                        <h3 class="text-xs font-bold text-[#8C6D36] uppercase tracking-wider mb-2">
                            <i class="fa-solid fa-file-contract mr-1"></i> Termo de Consentimento
                        </h3>
                        <?php echo termoTextoTeleconsulta(true); ?>
                    </div>

                    <label class="flex items-start gap-2 text-sm text-stone-700 cursor-pointer select-none">
                        <input type="checkbox" name="aceite_termo" value="1" id="aceite-termo"
                               class="mt-1 w-4 h-4 accent-[#8C6D36] cursor-pointer">
                        <span>
                            Li e aceito o
                            <a href="termo-consentimento.php" target="_blank" class="text-[#8C6D36] underline font-medium">Termo de Consentimento</a>
                            na íntegra.
                        </span>
                    </label>
                </div>
                <?php else: ?>
                <!-- Já aceitou antes — não mostra o termo -->
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-3 rounded-xl text-xs flex items-start gap-2">
                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                    <span>Você já aceitou o Termo de Consentimento. Não é necessário aceitar novamente.</span>
                </div>
                <?php endif; ?>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="fecharModalPA()" class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</button>
                    <button type="submit" id="btn-pa-confirmar"
                            class="bg-[#3E352E] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-video mr-1"></i> Entrar na fila
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL: AGENDAMENTO                                            -->
    <!-- ============================================================ -->
    <div id="modal-agendamento" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 space-y-6 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <h3 class="text-xl font-serif text-[#3E352E]">Novo Agendamento</h3>
                <button onclick="fecharModalAgendamento()" class="text-stone-400 hover:text-stone-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <form method="POST" class="space-y-5" id="form-agendamento">
                <input type="hidden" name="acao" value="agendar">
                <input type="hidden" name="data_hora" id="campo-data-hora-final" required>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1">Especialidade</label>
                    <select name="especialidade" id="sel-especialidade" required
                            class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                        <?php foreach ($especialidadesAgenda as $e): ?>
                            <option value="<?php echo h($e); ?>"><?php echo h($e); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1">Modalidade</label>
                    <select name="modalidade" class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                        <option value="Vídeo">Teleconsulta por Vídeo</option>
                        <option value="Presencial">Presencial (Unidade)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1">Data</label>
                    <input type="date" id="campo-data" required
                           class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                </div>

                <div id="bloco-horarios">
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-2">
                        Horários disponíveis <span class="normal-case font-normal text-stone-400" id="info-slots"></span>
                    </label>
                    <div id="grade-horarios" class="grid grid-cols-4 sm:grid-cols-5 gap-2 min-h-[80px]">
                        <p class="col-span-full text-sm text-stone-400 py-4 text-center">
                            Selecione uma data para ver os horários.
                        </p>
                    </div>
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <span>Os horários mostrados consideram a disponibilidade dos médicos ativos da especialidade escolhida.</span>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-stone-100">
                    <button type="button" onclick="fecharModalAgendamento()" class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</button>
                    <button type="submit" id="btn-confirmar-agendamento" disabled
                            class="bg-[#3E352E] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm opacity-50 cursor-not-allowed">
                        Confirmar Agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // ============================================================
    // Flag do PHP → JavaScript
    // ============================================================
    const JA_ACEITOU_TERMO = <?php echo $jaAceitouTermo ? 'true' : 'false'; ?>;

    // ============================================================
    // FLUXO PA: teste AV → (termo, se ainda não aceitou) → fila
    // ============================================================
    function abrirFluxoPA() {
        abrirTesteAV();

        window.aoConfirmarTesteAV = function () {
            if (JA_ACEITOU_TERMO) {
                // Já aceitou → abre o modal sem termo, ou envia direto
                abrirModalPA();
            } else {
                abrirModalPA();
            }
        };
    }

    function abrirModalPA() {
        const modal = document.getElementById('modal-pa');
        if (!modal) return;
        modal.classList.remove('hidden');

        const chk = document.getElementById('aceite-termo');
        const btn = document.getElementById('btn-pa-confirmar');

        if (JA_ACEITOU_TERMO || !chk) {
            // Não tem checkbox (já aceitou) → botão já habilitado
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return;
        }

        // Primeira vez → trava até aceitar
        chk.checked = false;
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');

            chk.onchange = function () {
                btn.disabled = !this.checked;
                btn.classList.toggle('opacity-50', !this.checked);
                btn.classList.toggle('cursor-not-allowed', !this.checked);
            };
        }
    }

    function fecharModalPA() {
        document.getElementById('modal-pa').classList.add('hidden');
    }

    // ============================================================
    // AGENDAMENTO — grade de horários
    // ============================================================
    const selEspecialidade = document.getElementById('sel-especialidade');
    const campoData        = document.getElementById('campo-data');
    const gradeHorarios    = document.getElementById('grade-horarios');
    const infoSlots        = document.getElementById('info-slots');
    const campoHoraFinal   = document.getElementById('campo-data-hora-final');
    const btnConfirmar     = document.getElementById('btn-confirmar-agendamento');

    let slotSelecionado = null;

    function abrirModalAgendamento() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        const hoje = d.toISOString().slice(0, 10);

        campoData.min = hoje;
        if (!campoData.value) campoData.value = hoje;

        slotSelecionado = null;
        campoHoraFinal.value = '';
        btnConfirmar.disabled = true;
        btnConfirmar.classList.add('opacity-50', 'cursor-not-allowed');

        document.getElementById('modal-agendamento').classList.remove('hidden');
        carregarHorarios();
    }

    function fecharModalAgendamento() {
        document.getElementById('modal-agendamento').classList.add('hidden');
    }

    async function carregarHorarios() {
        const esp = selEspecialidade ? selEspecialidade.value : '';
        const data = campoData ? campoData.value : '';

        if (!esp || !data) {
            gradeHorarios.innerHTML = '<p class="col-span-full text-sm text-stone-400 py-4 text-center">Selecione uma especialidade e uma data.</p>';
            return;
        }

        gradeHorarios.innerHTML = '<p class="col-span-full text-sm text-stone-400 py-4 text-center"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Carregando horários...</p>';
        infoSlots.textContent = '';

        try {
            const url = 'api/horarios_disponiveis.php?especialidade=' + encodeURIComponent(esp) +
                        '&data=' + encodeURIComponent(data) + '&_=' + Date.now();
            const r = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
            const j = await r.json();

            if (!j.ok) {
                gradeHorarios.innerHTML = '<p class="col-span-full text-sm text-red-600 py-4 text-center">' + (j.erro || 'Erro ao carregar.') + '</p>';
                return;
            }

            if (j.motivo === 'domingo') {
                gradeHorarios.innerHTML = '<p class="col-span-full text-sm text-stone-500 py-4 text-center"><i class="fa-solid fa-calendar-xmark mr-1"></i> Não atendemos aos domingos.</p>';
                return;
            }

            if (j.motivo === 'data_passada') {
                gradeHorarios.innerHTML = '<p class="col-span-full text-sm text-stone-500 py-4 text-center"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Escolha uma data a partir de hoje.</p>';
                return;
            }

            if (j.motivo === 'sem_medico') {
                gradeHorarios.innerHTML = '<p class="col-span-full text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl py-4 text-center px-4">Nenhum médico ativo em ' + esp + '. Escolha outra especialidade.</p>';
                return;
            }

            if (!j.slots || j.slots.length === 0) {
                gradeHorarios.innerHTML = '<p class="col-span-full text-sm text-stone-500 py-4 text-center">Nenhum horário disponível nesta data.</p>';
                return;
            }

            infoSlots.textContent = '· ' + j.slots.filter(s => s.disponivel).length + ' livres';

            gradeHorarios.innerHTML = '';
            j.slots.forEach(s => {
                const btn = document.createElement('button');
                btn.type = 'button';

                const baseCls = 'px-2 py-2.5 rounded-xl text-sm font-semibold transition border text-center';

                if (!s.disponivel) {
                    btn.className = baseCls + ' bg-stone-100 text-stone-400 border-stone-200 cursor-not-allowed line-through';
                    btn.title = s.motivo === 'voce_ja_tem'
                        ? 'Você já tem consulta nesse horário'
                        : 'Todos os médicos ocupados';
                    btn.disabled = true;
                } else {
                    btn.className = baseCls + ' bg-white text-[#3E352E] border-stone-300 hover:border-[#8C6D36] hover:bg-[#F9F4EC]';
                    btn.textContent = s.hora;
                    btn.addEventListener('click', () => selecionarSlot(s.hora, data, btn));
                }

                if (!btn.textContent) btn.textContent = s.hora;
                gradeHorarios.appendChild(btn);
            });

        } catch (e) {
            gradeHorarios.innerHTML = '<p class="col-span-full text-sm text-red-600 py-4 text-center">Falha ao carregar horários. Tente novamente.</p>';
            console.error('[carregarHorarios]', e);
        }
    }

    function selecionarSlot(hora, data, btn) {
        gradeHorarios.querySelectorAll('button').forEach(b => {
            if (b.classList.contains('bg-[#3E352E]')) {
                b.classList.remove('bg-[#3E352E]', 'text-white', 'border-[#3E352E]');
                b.classList.add('bg-white', 'text-[#3E352E]', 'border-stone-300');
            }
        });

        btn.classList.remove('bg-white', 'text-[#3E352E]', 'border-stone-300');
        btn.classList.add('bg-[#3E352E]', 'text-white', 'border-[#3E352E]');

        slotSelecionado = hora;
        campoHoraFinal.value = data + 'T' + hora;

        btnConfirmar.disabled = false;
        btnConfirmar.classList.remove('opacity-50', 'cursor-not-allowed');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (selEspecialidade) selEspecialidade.addEventListener('change', () => {
            slotSelecionado = null;
            campoHoraFinal.value = '';
            btnConfirmar.disabled = true;
            btnConfirmar.classList.add('opacity-50', 'cursor-not-allowed');
            carregarHorarios();
        });

        if (campoData) campoData.addEventListener('change', () => {
            slotSelecionado = null;
            campoHoraFinal.value = '';
            btnConfirmar.disabled = true;
            btnConfirmar.classList.add('opacity-50', 'cursor-not-allowed');
            carregarHorarios();
        });

        const formAg = document.getElementById('form-agendamento');
        if (formAg) {
            formAg.addEventListener('submit', (e) => {
                if (!campoHoraFinal.value) {
                    e.preventDefault();
                    alert('Escolha um horário disponível antes de confirmar.');
                }
            });
        }
    });
    </script>

    <?php include __DIR__ . '/includes/rodape.php'; ?>
</body>
</html>