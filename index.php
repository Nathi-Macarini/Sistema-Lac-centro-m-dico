<?php
require __DIR__ . '/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
verificarEsquema($conn);

$uid         = (int)$_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['tipo_usuario'] ?? 'comum';
$especialidadesAgenda = ['Cardiologia', 'Dermatologia', 'Ortopedia', 'Ginecologia', 'Pediatria', 'Clínica Geral'];
$aviso = ''; $avisoTipo = '';

// ---------- Agendar consulta ----------
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
    } else {
        $dataSql = $dt->format('Y-m-d H:i:00');
        $st = $conn->prepare("INSERT INTO consultas (usuario_id, especialidade, modalidade, tipo, data_hora, status) VALUES (?, ?, ?, 'agendada', ?, 'Agendada')");
        $st->bind_param('isss', $uid, $esp, $mod, $dataSql);
        if ($st->execute()) {
            header("Location: index.php?ok=agendada");
            exit;
        }
        $aviso = "Não foi possível salvar o agendamento: " . $conn->error;
        $avisoTipo = 'erro';
    }
}

// ---------- Pronto atendimento: entra na fila e vai para a sala ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'pronto_atendimento') {
    $esp = trim($_POST['especialidade'] ?? '');
    if (!in_array($esp, PA_ESPECIALIDADES, true)) {
        $aviso = "Escolha uma especialidade válida para o pronto atendimento.";
        $avisoTipo = 'erro';
    } else {
        $limite = date('Y-m-d H:i:s', time() - PA_VALIDADE_HORAS * 3600);
        $st = $conn->prepare("SELECT id FROM consultas WHERE usuario_id = ? AND tipo = 'pronto_atendimento' AND status IN ('Agendada','Em andamento') AND data_hora >= ? ORDER BY id DESC LIMIT 1");
        $st->bind_param('is', $uid, $limite);
        $st->execute();
        $ja = $st->get_result()->fetch_assoc();
        if ($ja) {
            header("Location: teleconsulta.php?consulta=" . (int)$ja['id']);
            exit;
        }
        $agora = date('Y-m-d H:i:s');
        $st = $conn->prepare("INSERT INTO consultas (usuario_id, especialidade, modalidade, tipo, data_hora, status) VALUES (?, ?, 'Vídeo', 'pronto_atendimento', ?, 'Agendada')");
        $st->bind_param('iss', $uid, $esp, $agora);
        if ($st->execute()) {
            header("Location: teleconsulta.php?consulta=" . (int)$conn->insert_id);
            exit;
        }
        $aviso = "Não foi possível entrar na fila: " . $conn->error;
        $avisoTipo = 'erro';
    }
}

// ---------- Cancelar ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'cancelar') {
    $cid = (int)($_POST['consulta_id'] ?? 0);
    $st = $conn->prepare("UPDATE consultas SET status = 'Cancelada' WHERE id = ? AND usuario_id = ? AND status = 'Agendada'");
    $st->bind_param('ii', $cid, $uid);
    $st->execute();
    header("Location: index.php?ok=cancelada");
    exit;
}

if (($_GET['ok'] ?? '') === 'agendada') { $aviso = "Consulta agendada! Ela já aparece para os médicos da especialidade e no seu histórico."; $avisoTipo = 'sucesso'; }
if (($_GET['ok'] ?? '') === 'cancelada') { $aviso = "Solicitação cancelada."; $avisoTipo = 'sucesso'; }

// ---------- Consultas marcadas / em andamento ----------
$inicioHoje = date('Y-m-d 00:00:00');
$limitePA   = date('Y-m-d H:i:s', time() - PA_VALIDADE_HORAS * 3600);
$st = $conn->prepare(
    "SELECT c.*, m.nome AS medico_nome
     FROM consultas c LEFT JOIN medicos m ON m.id = c.medico_id
     WHERE c.usuario_id = ? AND c.status IN ('Agendada','Em andamento')
       AND ((c.tipo = 'agendada' AND c.data_hora >= ?) OR (c.tipo = 'pronto_atendimento' AND c.data_hora >= ?))
     ORDER BY (c.tipo = 'pronto_atendimento') DESC, c.data_hora ASC");
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
    <style>
        .border-gold { border-color: #E6D5B8; }
    </style>
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
                    <button onclick="abrirModalChecagem()" class="bg-white text-[#3E352E] hover:bg-[#F4E8D1] font-medium px-6 py-3.5 rounded-xl shadow-lg transition flex items-center gap-2">
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
                                    <span class="text-xs text-stone-500 bg-stone-100 px-2.5 py-1 rounded-full"><?php echo h($c['status']); ?></span>
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
                                <?php if ($c['status'] === 'Agendada'): ?>
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
            <a href="https://wa.me/5541999999999" target="_blank" class="bg-[#008069] hover:bg-[#006e5a] text-white font-medium px-5 py-3 rounded-xl transition flex items-center gap-2 whitespace-nowrap shadow-sm">
                <i class="fa-brands fa-whatsapp text-lg"></i> Falar pelo WhatsApp
            </a>
        </section>
    </main>

    <!-- Modal de Verificação de Áudio/Vídeo -->
    <div id="modal-checagem" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <h3 class="text-xl font-serif text-[#3E352E]">Configuração de Áudio e Vídeo</h3>
                <button onclick="fecharModalChecagem()" class="text-stone-400 hover:text-stone-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <p class="text-sm text-stone-600">Antes de entrar na fila, confira se sua câmera e microfone estão funcionando.</p>

            <div class="bg-stone-900 rounded-2xl aspect-video relative flex items-center justify-center overflow-hidden shadow-inner">
                <video id="webcam-preview" autoplay playsinline muted class="w-full h-full object-cover"></video>
                <div id="video-placeholder" class="absolute text-stone-400 text-sm flex flex-col items-center gap-2">
                    <i class="fa-solid fa-video-slash text-2xl text-[#8C6D36]"></i>
                    <span>Carregando câmera...</span>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between text-xs text-stone-600 font-medium">
                    <span>Teste de Microfone / Volume</span>
                    <span id="status-audio" class="text-stone-500 font-bold">Aguardando...</span>
                </div>
                <div class="h-2.5 bg-stone-100 rounded-full overflow-hidden">
                    <div id="barra-volume" class="h-full bg-[#8C6D36] transition-all duration-100" style="width:0%"></div>
                </div>
            </div>

            <form id="form-pa" method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="pronto_atendimento">
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1">Com quem você quer falar?</label>
                    <select name="especialidade" class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                        <?php foreach (PA_ESPECIALIDADES as $e): ?><option value="<?php echo h($e); ?>"><?php echo h($e); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" onclick="fecharModalChecagem()" class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</button>
                    <button type="submit" onclick="liberarCamera()" class="bg-[#3E352E] text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A4A3A] transition shadow-sm">Tudo pronto, entrar na fila</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Agendamento -->
    <div id="modal-agendamento" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-6 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <h3 class="text-xl font-serif text-[#3E352E]">Novo Agendamento</h3>
                <button onclick="fecharModalAgendamento()" class="text-stone-400 hover:text-stone-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="agendar">
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1">Especialidade</label>
                    <select name="especialidade" class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                        <?php foreach ($especialidadesAgenda as $e): ?><option value="<?php echo h($e); ?>"><?php echo h($e); ?></option><?php endforeach; ?>
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
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1">Data e Horário</label>
                    <input type="datetime-local" name="data_hora" id="campo-data" required class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="fecharModalAgendamento()" class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</button>
                    <button type="submit" class="bg-[#3E352E] text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A4A3A] transition shadow-sm">Confirmar Agendamento</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let localStream = null, audioCtx = null, medidor = null;

        async function abrirModalChecagem() {
            document.getElementById('modal-checagem').classList.remove('hidden');
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                document.getElementById('webcam-preview').srcObject = localStream;
                document.getElementById('video-placeholder').style.display = 'none';
                iniciarMedidor(localStream);
            } catch (error) {
                document.getElementById('video-placeholder').innerHTML = '<i class="fa-solid fa-triangle-exclamation text-amber-500 text-2xl"></i><span>Permissão de câmera/microfone negada ou indisponível</span>';
                document.getElementById('status-audio').textContent = 'Sem acesso ao microfone';
            }
        }

        function iniciarMedidor(stream) {
            if (!stream.getAudioTracks().length) return;
            try {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const analisador = audioCtx.createAnalyser();
                analisador.fftSize = 256;
                audioCtx.createMediaStreamSource(stream).connect(analisador);
                const dados = new Uint8Array(analisador.frequencyBinCount);
                document.getElementById('status-audio').textContent = 'Fale algo para testar';
                medidor = setInterval(() => {
                    analisador.getByteFrequencyData(dados);
                    const media = dados.reduce((a, b) => a + b, 0) / dados.length;
                    const pct = Math.min(100, Math.round(media * 2));
                    document.getElementById('barra-volume').style.width = pct + '%';
                    if (pct > 8) {
                        const s = document.getElementById('status-audio');
                        s.textContent = 'Captando áudio'; s.className = 'text-emerald-600 font-bold';
                    }
                }, 100);
            } catch (e) { /* sem medidor, mas a câmera segue funcionando */ }
        }

        function liberarCamera() {
            if (medidor) clearInterval(medidor);
            if (audioCtx) { try { audioCtx.close(); } catch (e) {} }
            if (localStream) localStream.getTracks().forEach(t => t.stop());
        }

        function fecharModalChecagem() {
            document.getElementById('modal-checagem').classList.add('hidden');
            liberarCamera();
            localStream = null;
        }

        function abrirModalAgendamento() {
            const d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            document.getElementById('campo-data').min = d.toISOString().slice(0, 16);
            document.getElementById('modal-agendamento').classList.remove('hidden');
        }

        function fecharModalAgendamento() {
            document.getElementById('modal-agendamento').classList.add('hidden');
        }
    </script>
</body>
</html>