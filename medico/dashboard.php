<?php
require __DIR__ . '/../conexao.php';
$medico = exigirMedico($conn);
verificarEsquema($conn);
require __DIR__ . '/layout.php';

$mid = (int)$medico['id'];
$esp = $medico['especialidade'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'assumir') {
    $cid = (int)($_POST['consulta_id'] ?? 0);
    $st = $conn->prepare("UPDATE consultas SET medico_id = ? WHERE id = ? AND medico_id IS NULL AND especialidade = ?");
    $st->bind_param('iis', $mid, $cid, $esp);
    $st->execute();
    header("Location: dashboard.php?assumida=" . ($st->affected_rows > 0 ? '1' : '0'));
    exit;
}

$inicioHoje = date('Y-m-d 00:00:00');
$limitePA   = date('Y-m-d H:i:s', time() - PA_VALIDADE_HORAS * 3600);

$base = "SELECT c.*, u.nome AS paciente_nome, u.data_nascimento
         FROM consultas c JOIN usuarios u ON u.id = c.usuario_id
         WHERE (c.medico_id = ? OR (c.medico_id IS NULL AND c.especialidade = ?))";

// Fila do pronto atendimento
$st = $conn->prepare($base . " AND c.tipo = 'pronto_atendimento' AND c.status IN ('Agendada','Em andamento') AND c.data_hora >= ? ORDER BY c.data_hora ASC");
$st->bind_param('iss', $mid, $esp, $limitePA);
$st->execute();
$fila = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// Consultas agendadas (hoje em diante)
$st = $conn->prepare($base . " AND c.tipo = 'agendada' AND c.status IN ('Agendada','Em andamento') AND c.data_hora >= ? ORDER BY c.data_hora ASC");
$st->bind_param('iss', $mid, $esp, $inicioHoje);
$st->execute();
$proximas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// Anteriores
$st = $conn->prepare($base . " AND NOT (c.status IN ('Agendada','Em andamento')
                                        AND ((c.tipo = 'agendada' AND c.data_hora >= ?) OR (c.tipo = 'pronto_atendimento' AND c.data_hora >= ?)))
                         ORDER BY c.data_hora DESC LIMIT 10");
$st->bind_param('isss', $mid, $esp, $inicioHoje, $limitePA);
$st->execute();
$anteriores = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$st = $conn->prepare("SELECT COUNT(DISTINCT usuario_id) AS n FROM consultas WHERE medico_id = ? OR (medico_id IS NULL AND especialidade = ?)");
$st->bind_param('is', $mid, $esp);
$st->execute();
$totalPacientes = (int)$st->get_result()->fetch_assoc()['n'];

$hoje = 0;
foreach ($proximas as $p) {
    if (date('Y-m-d', strtotime($p['data_hora'])) === date('Y-m-d')) $hoje++;
}
$aguardandoPA = 0;
foreach ($fila as $f) if ($f['medico_id'] === null) $aguardandoPA++;

function cartaoConsulta($c, $ativa) {
    $video = $c['modalidade'] === 'Vídeo';
    $pa = $c['tipo'] === 'pronto_atendimento';
    $semMedico = $c['medico_id'] === null;
    ?>
    <div class="border <?php echo $semMedico && $ativa ? 'border-amber-300 bg-amber-50/40' : 'border-stone-200 bg-[#FAF9F6]'; ?> p-5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full">
                    <i class="fa-solid <?php echo $video ? 'fa-video' : 'fa-hospital'; ?> mr-1"></i><?php echo h($c['modalidade']); ?>
                </span>
                <?php if ($pa): ?><span class="text-xs font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2.5 py-1 rounded-full">Pronto atendimento</span><?php endif; ?>
                <span class="text-xs text-stone-500 bg-stone-100 px-2.5 py-1 rounded-full"><?php echo h($c['status']); ?></span>
                <?php if ($semMedico && $ativa): ?>
                    <span class="text-xs font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2.5 py-1 rounded-full">Aguardando médico</span>
                <?php endif; ?>
            </div>
            <h4 class="font-semibold text-stone-800"><?php echo h($c['paciente_nome']); ?> <span class="text-sm font-normal text-stone-500">· <?php echo h(textoIdade($c['data_nascimento'])); ?></span></h4>
            <p class="text-sm text-stone-500"><i class="fa-regular fa-calendar text-[#8C6D36]"></i> <?php echo date('d/m/Y H:i', strtotime($c['data_hora'])); ?> · <?php echo h($c['especialidade']); ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if ($semMedico && $ativa && !$video): ?>
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="acao" value="assumir">
                    <input type="hidden" name="consulta_id" value="<?php echo (int)$c['id']; ?>">
                    <button type="submit" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-hand mr-1"></i> Assumir consulta
                    </button>
                </form>
            <?php endif; ?>
            <a href="prontuario.php?id=<?php echo (int)$c['usuario_id']; ?>&consulta=<?php echo (int)$c['id']; ?>"
               class="px-4 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-white transition">
                <i class="fa-solid fa-notes-medical mr-1"></i> Prontuário
            </a>
            <?php if ($video && $ativa): ?>
                <a href="../teleconsulta.php?consulta=<?php echo (int)$c['id']; ?>"
                   class="bg-[#3E352E] text-white text-sm px-4 py-2.5 rounded-xl hover:bg-[#5A4A3A] transition shadow-sm">
                    <i class="fa-solid fa-video mr-1"></i> <?php echo $semMedico ? 'Assumir e entrar na sala' : 'Entrar na sala'; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php }

function renderAgenda($medico, $fila, $proximas, $anteriores, $hoje, $aguardandoPA, $totalPacientes) { ?>
<div id="agenda" data-total="<?php echo count($proximas); ?>" data-pa="<?php echo (int)$aguardandoPA; ?>" class="space-y-8">
    <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
        <h1 class="text-3xl font-serif text-[#3E352E] mb-2">Olá, <?php echo h($medico['nome']); ?>.</h1>
        <p class="text-stone-600"><?php echo h($medico['especialidade']); ?> · <?php echo h($medico['crm']); ?></p>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-6">
            <div class="bg-[#F9F4EC] border border-[#E6D5B8] rounded-2xl p-4"><div class="text-3xl font-serif text-[#3E352E]"><?php echo $hoje; ?></div><div class="text-xs uppercase font-bold text-[#8C6D36] tracking-wider">Consultas hoje</div></div>
            <div class="bg-[#F9F4EC] border border-[#E6D5B8] rounded-2xl p-4"><div class="text-3xl font-serif text-[#3E352E]"><?php echo count($proximas); ?></div><div class="text-xs uppercase font-bold text-[#8C6D36] tracking-wider">Próximas consultas</div></div>
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4"><div class="text-3xl font-serif text-amber-800"><?php echo $aguardandoPA; ?></div><div class="text-xs uppercase font-bold text-amber-700 tracking-wider">Na fila do pronto atendimento</div></div>
            <a href="pacientes.php" class="bg-[#F9F4EC] border border-[#E6D5B8] rounded-2xl p-4 hover:bg-[#E6D5B8]/40 transition block"><div class="text-3xl font-serif text-[#3E352E]"><?php echo $totalPacientes; ?></div><div class="text-xs uppercase font-bold text-[#8C6D36] tracking-wider">Pacientes na agenda</div></a>
        </div>
    </section>

    <section class="bg-[#3E352E] p-6 rounded-2xl border border-[#C5A059]/30 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-serif text-white"><i class="fa-solid fa-truck-medical text-[#C5A059] mr-2"></i>Pronto atendimento — fila de vídeo</h3>
            <span class="text-xs text-stone-300">Atualiza sozinho a cada 5 segundos</span>
        </div>
        <?php if ($fila): ?>
            <div class="grid gap-3">
            <?php foreach ($fila as $c):
                $min = max(0, (int)floor((time() - strtotime($c['data_hora'])) / 60));
                $meu = $c['medico_id'] !== null;
            ?>
                <div class="bg-white/95 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold text-stone-800"><?php echo h($c['paciente_nome']); ?> <span class="text-sm font-normal text-stone-500">· <?php echo h(textoIdade($c['data_nascimento'])); ?></span></div>
                        <div class="text-sm text-stone-500"><?php echo h($c['especialidade']); ?> · <?php echo $meu ? 'em atendimento com você' : 'aguardando há ' . ($min < 1 ? 'menos de 1 min' : $min . ' min'); ?></div>
                    </div>
                    <a href="../teleconsulta.php?consulta=<?php echo (int)$c['id']; ?>" class="bg-[#8C6D36] hover:bg-[#755a2c] text-white text-sm font-medium px-5 py-2.5 rounded-xl transition shadow-sm text-center">
                        <i class="fa-solid fa-video mr-1"></i> <?php echo $meu ? 'Voltar à sala' : 'Atender agora'; ?>
                    </a>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-stone-300 py-2">Nenhum paciente aguardando no momento. Quando alguém pedir atendimento em <?php echo h($medico['especialidade']); ?>, ele aparece aqui na hora.</p>
        <?php endif; ?>
    </section>

    <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
        <h3 class="text-xl font-serif text-[#3E352E]">Próximas consultas</h3>
        <?php if ($proximas): ?>
            <div class="grid gap-4"><?php foreach ($proximas as $c) cartaoConsulta($c, true); ?></div>
        <?php else: ?>
            <p class="text-sm text-stone-500 py-4 text-center">Nenhuma consulta de <?php echo h($medico['especialidade']); ?> agendada no momento.</p>
        <?php endif; ?>
    </section>

    <?php if ($anteriores): ?>
    <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
        <h3 class="text-xl font-serif text-[#3E352E]">Consultas anteriores</h3>
        <div class="grid gap-4"><?php foreach ($anteriores as $c) cartaoConsulta($c, false); ?></div>
    </section>
    <?php endif; ?>
</div>
<?php }

if (isset($_GET['parcial'])) {
    renderAgenda($medico, $fila, $proximas, $anteriores, $hoje, $aguardandoPA, $totalPacientes);
    exit;
}

cabecalho('Minha agenda', 'agenda');

if (isset($_GET['assumida'])): ?>
    <div class="<?php echo $_GET['assumida'] === '1' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border">
        <?php echo $_GET['assumida'] === '1' ? 'Consulta assumida. Ela agora é sua.' : 'Não foi possível assumir: outro médico já assumiu esta consulta.'; ?>
    </div>
<?php endif;

renderAgenda($medico, $fila, $proximas, $anteriores, $hoje, $aguardandoPA, $totalPacientes);
?>

<div id="aviso-nova" class="hidden fixed bottom-6 right-6 z-50 bg-[#3E352E] text-white px-5 py-3 rounded-xl shadow-xl text-sm">
    <i class="fa-solid fa-bell text-[#C5A059] mr-2"></i> <span id="aviso-texto">Nova consulta agendada!</span>
</div>

<script>
(function () {
    const ag = document.getElementById('agenda');
    let total = parseInt(ag.dataset.total, 10);
    let pa = parseInt(ag.dataset.pa, 10);

    function bip() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination);
            o.frequency.value = 880; g.gain.value = 0.15;
            o.start(); setTimeout(() => { o.stop(); ctx.close(); }, 350);
        } catch (e) { /* navegador bloqueou o som: só o aviso visual aparece */ }
    }
    function avisar(texto) {
        document.getElementById('aviso-texto').textContent = texto;
        const a = document.getElementById('aviso-nova');
        a.classList.remove('hidden');
        setTimeout(() => a.classList.add('hidden'), 7000);
    }
    setInterval(async () => {
        if (document.hidden) return;
        try {
            const r = await fetch('dashboard.php?parcial=1', { credentials: 'same-origin' });
            if (!r.ok) return;
            const tmp = document.createElement('div');
            tmp.innerHTML = await r.text();
            const novo = tmp.querySelector('#agenda');
            if (!novo) return;
            const novoTotal = parseInt(novo.dataset.total, 10);
            const novoPa = parseInt(novo.dataset.pa, 10);
            document.getElementById('agenda').innerHTML = novo.innerHTML;
            if (novoPa > pa) { avisar('Novo paciente no pronto atendimento!'); bip(); }
            else if (novoTotal > total) avisar('Nova consulta agendada!');
            total = novoTotal; pa = novoPa;
        } catch (e) { /* tenta de novo no próximo ciclo */ }
    }, 5000);
})();
</script>
<?php rodape(); ?>