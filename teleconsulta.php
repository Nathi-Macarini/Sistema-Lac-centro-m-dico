<?php
require __DIR__ . '/conexao.php';
require __DIR__ . '/medico/painel_documentos.php';
verificarEsquema($conn);

if (!isset($_SESSION['usuario_id']) && !souMedico()) { header("Location: login.php"); exit; }

$cid    = (int)($_GET['consulta'] ?? 0);
$voltar = souMedico() ? 'medico/dashboard.php' : 'index.php';
$acesso = acessoConsulta($conn, $cid);

if (!$acesso) {
    http_response_code(403);
    $erro = "Sala não encontrada ou você não participa desta consulta.";
} else {
    $c = $acesso['consulta'];
    $papel = $acesso['papel'];
    if ($papel === 'medico' && $c['status'] === 'Agendada') {
        $up = $conn->prepare("UPDATE consultas SET status = 'Em andamento' WHERE id = ? AND medico_id = ? AND status = 'Agendada'");
        $mid = (int)$c['medico_id']; $cidUp = (int)$c['id'];
        $up->bind_param('ii', $cidUp, $mid);
        $up->execute();
        $c['status'] = 'Em andamento';
    }
    $ehPA = ($c['tipo'] ?? '') === 'pronto_atendimento';
    $encerrada = !in_array($c['status'], ['Agendada', 'Em andamento'], true);

    $alergias = '';
    if ($papel === 'medico') {
        $pid = (int)$c['usuario_id'];
        $q = $conn->prepare("SELECT alergias FROM prontuarios WHERE paciente_id = ?");
        $q->bind_param('i', $pid);
        $q->execute();
        $alergias = $q->get_result()->fetch_assoc()['alergias'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teleconsulta - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">
<header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <img src="logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
        <a href="<?php echo $voltar; ?>" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6">
<?php if (!$acesso): ?>
    <div class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-xl text-center max-w-lg mx-auto space-y-3">
        <i class="fa-solid fa-lock text-3xl text-[#8C6D36]"></i>
        <p class="text-stone-700"><?php echo h($erro); ?></p>
        <a href="<?php echo $voltar; ?>" class="inline-block bg-[#3E352E] text-white px-5 py-2.5 rounded-xl text-sm">Voltar ao início</a>
    </div>
<?php elseif ($encerrada): ?>
    <div class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-xl text-center max-w-lg mx-auto space-y-3">
        <i class="fa-regular fa-circle-check text-3xl text-[#8C6D36]"></i>
        <p class="text-stone-700">Esta consulta está com status <strong><?php echo h($c['status']); ?></strong> e a sala foi encerrada.</p>
        <a href="<?php echo $voltar; ?>" class="inline-block bg-[#3E352E] text-white px-5 py-2.5 rounded-xl text-sm">Voltar ao início</a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 <?php echo $papel === 'medico' ? 'lg:grid-cols-5' : 'lg:grid-cols-3'; ?> gap-6">
        <!-- Vídeo -->
        <section class="<?php echo $papel === 'medico' ? 'lg:col-span-3' : 'lg:col-span-2'; ?> space-y-4">
            <div class="bg-stone-900 rounded-3xl overflow-hidden relative aspect-video shadow-xl border border-[#C5A059]/30">
                <video id="video-remoto" autoplay playsinline class="w-full h-full object-cover"></video>
                <div id="aguardando" class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-stone-300 text-sm text-center px-6">
                    <i class="fa-solid fa-user-clock text-3xl text-[#C5A059]"></i>
                    <span id="texto-status">Conectando...</span>
                </div>
                <video id="video-local" autoplay playsinline muted
                       class="absolute bottom-3 right-3 w-32 sm:w-44 aspect-video object-cover rounded-xl border-2 border-white/70 bg-stone-800 shadow-lg"></video>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <button id="btn-mic" onclick="alternarMic()" class="w-12 h-12 rounded-full bg-white border border-stone-300 text-stone-700 hover:bg-stone-50 transition" title="Microfone">
                    <i class="fa-solid fa-microphone"></i>
                </button>
                <button id="btn-cam" onclick="alternarCam()" class="w-12 h-12 rounded-full bg-white border border-stone-300 text-stone-700 hover:bg-stone-50 transition" title="Câmera">
                    <i class="fa-solid fa-video"></i>
                </button>
                <button onclick="sair(false)" class="px-5 h-12 rounded-full bg-white border border-stone-300 text-stone-700 text-sm font-medium hover:bg-stone-50 transition">
                    <i class="fa-solid fa-door-open mr-1"></i> Sair da sala
                </button>
                <?php if ($papel === 'medico'): ?>
                <button onclick="sair(true)" class="px-5 h-12 rounded-full bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition shadow-sm">
                    <i class="fa-solid fa-phone-slash mr-1"></i> Finalizar consulta
                </button>
                <?php endif; ?>
            </div>
            <p class="text-xs text-stone-500 text-center"><i class="fa-solid fa-lock text-[#C5A059]"></i> Áudio e vídeo trafegam diretamente entre você e o outro participante.</p>
        </section>

        <!-- Painel lateral -->
        <aside class="space-y-4">
            <?php if ($papel === 'medico'): ?>
                <div class="bg-white p-5 rounded-3xl border border-[#E6D5B8]/40 shadow-sm space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full uppercase tracking-wider">Paciente</span>
                        <?php if ($ehPA): ?><span class="text-xs font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2.5 py-1 rounded-full">Pronto atendimento</span><?php endif; ?>
                    </div>
                    <h2 class="text-2xl font-serif text-[#3E352E]"><?php echo h($c['paciente_nome']); ?></h2>
                    <p class="text-sm text-stone-600"><i class="fa-regular fa-calendar text-[#8C6D36]"></i> <?php echo h(textoIdade($c['paciente_nasc'])); ?> · <?php echo h($c['especialidade']); ?></p>
                    <div class="text-sm <?php echo $alergias ? 'bg-red-50 border-red-200 text-red-800' : 'bg-stone-50 border-stone-200 text-stone-600'; ?> border rounded-xl p-3">
                        <strong>Alergias:</strong> <?php echo $alergias ? h($alergias) : 'não registradas'; ?>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-3xl border border-[#E6D5B8]/40 shadow-sm space-y-3">
                    <div class="flex gap-2">
                        <button type="button" id="aba-pront" onclick="abaPainel('pront')" class="flex-1 px-3 py-2 rounded-xl text-sm font-medium bg-[#3E352E] text-white"><i class="fa-solid fa-notes-medical mr-1"></i> Prontuário</button>
                        <button type="button" id="aba-docs" onclick="abaPainel('docs')" class="flex-1 px-3 py-2 rounded-xl text-sm font-medium bg-[#F9F4EC] text-[#5A4A3A] border border-[#E6D5B8]"><i class="fa-solid fa-file-medical mr-1"></i> Receita / Atestado</button>
                    </div>
                    <div id="painel-pront" class="space-y-2">
                        <iframe src="medico/prontuario.php?id=<?php echo (int)$c['usuario_id']; ?>&consulta=<?php echo (int)$c['id']; ?>&embed=1#nova-evolucao"
                                class="w-full h-[68vh] rounded-xl border border-stone-200 bg-white" title="Prontuário do paciente"></iframe>
                        <a href="medico/prontuario.php?id=<?php echo (int)$c['usuario_id']; ?>&consulta=<?php echo (int)$c['id']; ?>#nova-evolucao" target="_blank" class="text-xs text-stone-500 hover:text-[#8C6D36] underline">Abrir o prontuário em outra aba</a>
                    </div>
                    <div id="painel-docs" class="hidden">
                        <?php painelDocumentos((int)$c['usuario_id'], (int)$c['id'], ''); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white p-6 rounded-3xl border border-[#E6D5B8]/40 shadow-sm space-y-3">
                    <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full uppercase tracking-wider">Seu médico</span>
                    <h2 id="nome-medico" class="text-2xl font-serif text-[#3E352E]"><?php echo h($c['medico_nome'] ?? 'Aguardando um médico da especialidade'); ?></h2>
                    <p class="text-sm text-stone-600"><?php echo h($c['especialidade']); ?></p>
                    <p class="text-sm text-stone-600"><i class="fa-regular fa-clock text-[#8C6D36]"></i> <?php echo date('d/m/Y H:i', strtotime($c['data_hora'])); ?></p>
                    <p class="text-xs text-stone-500 bg-[#F9F4EC] border border-[#E6D5B8] rounded-xl p-3">Fique em um local tranquilo, com boa iluminação e internet estável. Quando o médico entrar na sala, a chamada começa automaticamente.</p>
                </div>
                <div id="caixa-docs" class="hidden bg-white p-6 rounded-3xl border border-[#E6D5B8]/40 shadow-sm space-y-3">
                    <h3 class="text-lg font-serif text-[#3E352E]"><i class="fa-solid fa-file-medical text-[#8C6D36] mr-1"></i> Documentos enviados pelo médico</h3>
                    <ul id="lista-docs" class="space-y-2 text-sm"></ul>
                </div>
                <div id="aviso-encerrada" class="hidden bg-emerald-50 border border-emerald-200 text-emerald-800 p-5 rounded-3xl text-sm space-y-2">
                    <p><i class="fa-regular fa-circle-check mr-1"></i> <strong>Consulta encerrada pelo médico.</strong> Suas receitas e atestados ficam guardados no histórico.</p>
                    <a href="historico.php" class="inline-block bg-[#3E352E] text-white px-4 py-2 rounded-xl">Ir para o histórico</a>
                </div>
                <div id="toast-doc" class="hidden fixed bottom-6 right-6 z-50 bg-[#3E352E] text-white px-5 py-3 rounded-xl shadow-xl text-sm">
                    <i class="fa-solid fa-file-medical text-[#C5A059] mr-2"></i> O médico enviou um documento para você.
                </div>
            <?php endif; ?>
        </aside>
    </div>

<script>
const CONSULTA = <?php echo (int)$c['id']; ?>;
const PAPEL    = <?php echo json_encode($papel); ?>;
const VOLTAR   = <?php echo json_encode($voltar); ?>;
const EH_PA    = <?php echo json_encode($ehPA); ?>;
const API      = 'api/sinal.php';
const ICE      = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }, { urls: 'stun:stun1.l.google.com:19302' }] };

let pc = null, localStream = null, ultimoId = 0, ativo = true;
let remotoPronto = false, filaCandidates = [];

const el = id => document.getElementById(id);
const dormir = ms => new Promise(r => setTimeout(r, ms));
function status(txt, mostrar = true) {
    el('texto-status').textContent = txt;
    el('aguardando').style.display = mostrar ? 'flex' : 'none';
}

async function chamar(acao, dados) {
    const opts = dados ? { method: 'POST', body: new URLSearchParams(dados), credentials: 'same-origin' } : { credentials: 'same-origin' };
    const url = API + '?acao=' + acao + '&consulta=' + CONSULTA + (dados ? '' : '&desde=' + ultimoId);
    const r = await fetch(url, opts);
    return r.json();
}
const enviar = (tipo, payload) => chamar('enviar', { tipo, payload: payload || '' });

function criarPeer() {
    if (pc) { pc.onicecandidate = pc.ontrack = pc.onconnectionstatechange = null; pc.close(); }
    pc = new RTCPeerConnection(ICE);
    remotoPronto = false; filaCandidates = [];
    if (localStream) localStream.getTracks().forEach(t => pc.addTrack(t, localStream));
    pc.ontrack = e => { el('video-remoto').srcObject = e.streams[0]; status('', false); };
    pc.onicecandidate = e => { if (e.candidate) enviar('candidate', JSON.stringify(e.candidate)); };
    pc.onconnectionstatechange = () => {
        if (pc.connectionState === 'connected') status('', false);
        if (pc.connectionState === 'failed') status('Falha na conexão. Saia e entre novamente na sala.');
        if (pc.connectionState === 'disconnected') status('Conexão instável... tentando reconectar.');
    };
}

async function liberarCandidates() {
    remotoPronto = true;
    for (const c of filaCandidates) { try { await pc.addIceCandidate(c); } catch (e) {} }
    filaCandidates = [];
}

async function processar(m) {
    if (m.tipo === 'entrou') {
        criarPeer();
        el('video-remoto').srcObject = null;
        if (PAPEL === 'medico') {
            status('Paciente entrou. Iniciando a chamada...');
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            await enviar('offer', JSON.stringify(pc.localDescription));
        } else {
            status('Médico na sala. Conectando...');
        }
    } else if (m.tipo === 'offer' && PAPEL === 'paciente') {
        if (!pc) criarPeer();
        await pc.setRemoteDescription(JSON.parse(m.payload));
        await liberarCandidates();
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        await enviar('answer', JSON.stringify(pc.localDescription));
    } else if (m.tipo === 'answer' && PAPEL === 'medico') {
        await pc.setRemoteDescription(JSON.parse(m.payload));
        await liberarCandidates();
    } else if (m.tipo === 'candidate') {
        const cand = JSON.parse(m.payload);
        if (pc && remotoPronto) { try { await pc.addIceCandidate(cand); } catch (e) {} }
        else filaCandidates.push(cand);
    } else if (m.tipo === 'saiu') {
        el('video-remoto').srcObject = null;
        status(PAPEL === 'medico' ? 'O paciente saiu da sala.' : 'O médico saiu da sala. Aguarde, ele pode voltar.');
    }
}

async function loopSinal() {
    while (ativo) {
        try {
            const r = await chamar('receber');
            for (const m of (r.mensagens || [])) { ultimoId = Math.max(ultimoId, +m.id); await processar(m); }
        } catch (e) { /* tenta de novo no próximo ciclo */ }
        await dormir(1000);
    }
}

async function iniciar() {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    } catch (e) {
        try { localStream = await navigator.mediaDevices.getUserMedia({ audio: true }); status('Câmera indisponível: você entrou só com áudio.'); }
        catch (e2) { status('Não foi possível acessar câmera/microfone. Libere a permissão no navegador (é necessário HTTPS ou localhost).'); return; }
    }
    el('video-local').srcObject = localStream;
    status(PAPEL === 'medico'
        ? 'Aguardando o paciente entrar na sala...'
        : (EH_PA ? 'Você está na fila do pronto atendimento. Assim que um médico entrar, a chamada começa automaticamente.' : 'Aguardando o médico entrar na sala...'));
    criarPeer();
    await chamar('entrar', {});
    loopSinal();
    if (PAPEL === 'paciente') acompanhar();
}

let totalDocs = 0;
async function acompanhar() {
    while (ativo) {
        try {
            const r = await fetch('api/documentos.php?acao=listar&consulta=' + CONSULTA, { credentials: 'same-origin' });
            if (r.ok) {
                const j = await r.json();
                if (j.medico_nome) el('nome-medico').textContent = j.medico_nome;
                renderDocs(j.documentos || []);
                if (j.status === 'Realizada' || j.status === 'Cancelada') {
                    ativo = false;
                    el('video-remoto').srcObject = null;
                    status(j.status === 'Realizada' ? 'Consulta encerrada pelo médico.' : 'Esta solicitação foi cancelada.');
                    if (j.status === 'Realizada') el('aviso-encerrada').classList.remove('hidden');
                    if (localStream) localStream.getTracks().forEach(t => t.stop());
                    if (pc) pc.close();
                    return;
                }
            }
        } catch (e) { /* tenta de novo no próximo ciclo */ }
        await dormir(4000);
    }
}
let docsCarregados = false;
function renderDocs(docs) {
    const novo = docsCarregados && docs.length > totalDocs;
    docsCarregados = true;
    if (!docs.length) return;
    el('caixa-docs').classList.remove('hidden');
    if (novo) {
        el('toast-doc').classList.remove('hidden');
        setTimeout(() => el('toast-doc').classList.add('hidden'), 6000);
    }
    totalDocs = docs.length;
    const ul = el('lista-docs'); ul.innerHTML = '';
    docs.forEach(d => {
        const li = document.createElement('li');
        li.className = 'flex items-center justify-between gap-2 border border-stone-200 rounded-xl px-3 py-2 bg-[#FAF9F6]';
        const t = document.createElement('div');
        const b = document.createElement('strong'); b.textContent = d.tipo === 'receita' ? 'Receita' : 'Atestado';
        const s2 = document.createElement('div'); s2.className = 'text-xs text-stone-500'; s2.textContent = d.data;
        t.appendChild(b); t.appendChild(s2);
        const a = document.createElement('a');
        a.href = 'documento.php?id=' + d.id; a.target = '_blank';
        a.className = 'text-xs font-medium border border-stone-300 px-2.5 py-1.5 rounded-lg hover:bg-white'; a.textContent = 'Abrir / imprimir';
        li.appendChild(t); li.appendChild(a); ul.appendChild(li);
    });
}

function abaPainel(qual) {
    const pront = qual === 'pront';
    el('painel-pront').classList.toggle('hidden', !pront);
    el('painel-docs').classList.toggle('hidden', pront);
    el('aba-pront').className = 'flex-1 px-3 py-2 rounded-xl text-sm font-medium ' + (pront ? 'bg-[#3E352E] text-white' : 'bg-[#F9F4EC] text-[#5A4A3A] border border-[#E6D5B8]');
    el('aba-docs').className  = 'flex-1 px-3 py-2 rounded-xl text-sm font-medium ' + (!pront ? 'bg-[#3E352E] text-white' : 'bg-[#F9F4EC] text-[#5A4A3A] border border-[#E6D5B8]');
}

function alternarMic() {
    if (!localStream) return;
    const t = localStream.getAudioTracks()[0]; if (!t) return;
    t.enabled = !t.enabled;
    el('btn-mic').classList.toggle('bg-red-100', !t.enabled);
    el('btn-mic').innerHTML = t.enabled ? '<i class="fa-solid fa-microphone"></i>' : '<i class="fa-solid fa-microphone-slash text-red-600"></i>';
}
function alternarCam() {
    if (!localStream) return;
    const t = localStream.getVideoTracks()[0]; if (!t) return;
    t.enabled = !t.enabled;
    el('btn-cam').classList.toggle('bg-red-100', !t.enabled);
    el('btn-cam').innerHTML = t.enabled ? '<i class="fa-solid fa-video"></i>' : '<i class="fa-solid fa-video-slash text-red-600"></i>';
}

async function sair(finalizar) {
    if (finalizar && !confirm('Finalizar a consulta? Ela será marcada como Realizada e a sala será encerrada.\n\nAntes de confirmar, veja se já registrou o prontuário e enviou a receita/atestado, se for o caso.')) return;
    ativo = false;
    try {
        if (finalizar) await chamar('finalizar', {});
        else await enviar('saiu');
    } catch (e) {}
    if (localStream) localStream.getTracks().forEach(t => t.stop());
    if (pc) pc.close();
    window.location.href = VOLTAR;
}

window.addEventListener('pagehide', () => {
    if (!ativo) return;
    const fd = new FormData(); fd.append('tipo', 'saiu'); fd.append('payload', '');
    navigator.sendBeacon(API + '?acao=enviar&consulta=' + CONSULTA, fd);
});

iniciar();
</script>
<?php endif; ?>
</main>
</body>
</html>