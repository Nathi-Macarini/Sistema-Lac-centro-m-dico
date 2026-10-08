<?php
require_once __DIR__ . '/bootstrap.php'; 
// Histórico do paciente: todas as consultas agendadas e atendimentos de pronto atendimento,
// mais as receitas e atestados enviados pelos médicos.
require __DIR__ . '/conexao.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
verificarEsquema($conn);

$uid         = (int)$_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['tipo_usuario'] ?? 'comum';
$filtro      = $_GET['tipo'] ?? 'todos';
if (!in_array($filtro, ['todos', 'agendada', 'pronto_atendimento'], true)) $filtro = 'todos';

$sql = "SELECT c.*, m.nome AS medico_nome FROM consultas c LEFT JOIN medicos m ON m.id = c.medico_id WHERE c.usuario_id = ?";
if ($filtro !== 'todos') $sql .= " AND c.tipo = ?";
$sql .= " ORDER BY c.data_hora DESC, c.id DESC";
$st = $conn->prepare($sql);
if ($filtro !== 'todos') $st->bind_param('is', $uid, $filtro); else $st->bind_param('i', $uid);
$st->execute();
$consultas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// Documentos do paciente, agrupados por consulta
$st = $conn->prepare("SELECT d.id, d.tipo, d.consulta_id, d.criado_em, d.dias_afastamento, m.nome AS medico_nome
                      FROM documentos d JOIN medicos m ON m.id = d.medico_id
                      WHERE d.paciente_id = ? ORDER BY d.id DESC");
$st->bind_param('i', $uid);
$st->execute();
$todosDocs = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$docsPorConsulta = [];
foreach ($todosDocs as $d) $docsPorConsulta[(int)$d['consulta_id']][] = $d;

$inicioHoje = strtotime(date('Y-m-d 00:00:00'));
$limitePA   = time() - PA_VALIDADE_HORAS * 3600;
function statusExibido($c, $inicioHoje, $limitePA) {
    if (in_array($c['status'], ['Agendada', 'Em andamento'], true)) {
        $t = strtotime($c['data_hora']);
        $expirou = $c['tipo'] === 'pronto_atendimento' ? $t < $limitePA : $t < $inicioHoje;
        if ($expirou) return ['Não realizada', 'bg-stone-100 text-stone-500', false];
        return [$c['status'], 'bg-amber-100 text-amber-800 border border-amber-300', true];
    }
    if ($c['status'] === 'Realizada') return ['Realizada', 'bg-emerald-100 text-emerald-800', false];
    return [$c['status'], 'bg-stone-100 text-stone-500', false];
}

function abaFiltro($valor, $rotulo, $atual) {
    $cls = $valor === $atual ? 'bg-[#3E352E] text-white' : 'bg-[#F9F4EC] text-[#5A4A3A] border border-[#E6D5B8] hover:bg-[#E6D5B8]/50';
    return '<a href="historico.php?tipo=' . $valor . '" class="px-4 py-2 rounded-xl text-sm font-medium transition ' . $cls . '">' . $rotulo . '</a>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased">

<header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <a href="index.php"><img src="logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain"></a>
        <nav class="hidden md:flex space-x-8 font-medium text-stone-600">
    <a href="index.php" class="hover:text-[#8C6D36] transition">Início</a>
    <a href="historico.php" class="text-[#8C6D36] border-b-2 border-[#8C6D36] pb-1 font-semibold">Histórico</a>
    <a href="plano.php" class="hover:text-[#8C6D36] transition">Plano</a>
    <a href="dependentes.php" class="hover:text-[#8C6D36] transition">Dependentes</a>
</nav>
        <div class="flex items-center space-x-4">
            <?php if ($tipoUsuario === 'admin'): ?>
                <a href="admin/dashboard.php" class="hidden sm:flex items-center gap-2 bg-[#F9F4EC] text-[#8C6D36] border border-[#E6D5B8] px-3.5 py-2 rounded-xl text-sm font-semibold hover:bg-[#E6D5B8]/50 transition">
                    <i class="fa-solid fa-shield-halved"></i> Painel Admin
                </a>
            <?php endif; ?>
            <a href="perfil.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition hidden sm:inline"><?php echo h($_SESSION['nome_usuario']); ?></a>
            <a href="logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair"><i class="fa-solid fa-right-from-bracket text-lg"></i></a>
        </div>
    </div>
</header>

<main class="max-w-5xl mx-auto px-4 py-8 space-y-8">

    <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-serif text-[#3E352E]">Seu histórico</h1>
                <p class="text-stone-600 text-sm">Consultas agendadas, atendimentos de pronto atendimento e os documentos enviados pelos médicos.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php echo abaFiltro('todos', 'Todos', $filtro); ?>
                <?php echo abaFiltro('agendada', 'Consultas agendadas', $filtro); ?>
                <?php echo abaFiltro('pronto_atendimento', 'Pronto atendimento', $filtro); ?>
            </div>
        </div>

        <?php if (!$consultas): ?>
            <p class="text-sm text-stone-500 py-8 text-center">Nada por aqui ainda. Agende uma consulta ou fale com um médico agora na <a href="index.php" class="text-[#8C6D36] underline">página inicial</a>.</p>
        <?php endif; ?>

        <div class="space-y-4">
        <?php foreach ($consultas as $c):
            [$rotuloStatus, $classeStatus, $ativa] = statusExibido($c, $inicioHoje, $limitePA);
            $pa    = $c['tipo'] === 'pronto_atendimento';
            $video = $c['modalidade'] === 'Vídeo';
            $docs  = $docsPorConsulta[(int)$c['id']] ?? [];
        ?>
            <article class="border border-stone-200 rounded-2xl p-5 bg-[#FAF9F6] space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full">
                                <i class="fa-solid <?php echo $video ? 'fa-video' : 'fa-hospital'; ?> mr-1"></i><?php echo h($c['modalidade']); ?>
                            </span>
                            <?php if ($pa): ?><span class="text-xs font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2.5 py-1 rounded-full">Pronto atendimento</span><?php endif; ?>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?php echo $classeStatus; ?>"><?php echo h($rotuloStatus); ?></span>
                        </div>
                        <h4 class="font-semibold text-stone-800"><?php echo h($c['especialidade']); ?></h4>
                        <p class="text-sm text-stone-500"><i class="fa-regular fa-calendar text-[#8C6D36]"></i> <?php echo date('d/m/Y H:i', strtotime($c['data_hora'])); ?>
                            · <?php echo $c['medico_nome'] ? h($c['medico_nome']) : 'sem médico atribuído'; ?></p>
                    </div>
                    <?php if ($ativa && $video): ?>
                        <a href="teleconsulta.php?consulta=<?php echo (int)$c['id']; ?>" class="self-start bg-[#3E352E] text-white text-sm px-4 py-2.5 rounded-xl hover:bg-[#5A4A3A] transition shadow-sm whitespace-nowrap">
                            <i class="fa-solid fa-video mr-1"></i> Acessar Sala
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($docs): ?>
                    <div class="border-t border-stone-200 pt-3 flex flex-wrap gap-2">
                        <?php foreach ($docs as $d): ?>
                            <a href="documento.php?id=<?php echo (int)$d['id']; ?>" target="_blank"
                               class="inline-flex items-center gap-2 text-xs font-medium bg-white border border-[#E6D5B8] text-[#5A4A3A] px-3 py-2 rounded-lg hover:bg-[#F9F4EC] transition">
                                <i class="fa-solid <?php echo $d['tipo'] === 'receita' ? 'fa-prescription-bottle-medical' : 'fa-file-medical'; ?> text-[#8C6D36]"></i>
                                <?php echo $d['tipo'] === 'receita' ? 'Receita' : 'Atestado'; ?> · <?php echo date('d/m/Y', strtotime($d['criado_em'])); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        </div>
    </section>

    <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
        <h2 class="text-xl font-serif text-[#3E352E]"><i class="fa-solid fa-file-medical text-[#8C6D36] mr-1"></i> Receitas e atestados</h2>
        <?php if (!$todosDocs): ?>
            <p class="text-sm text-stone-500">Quando um médico enviar uma receita ou um atestado, ele aparece aqui para você ver e imprimir.</p>
        <?php else: ?>
            <ul class="divide-y divide-stone-100">
                <?php foreach ($todosDocs as $d): ?>
                    <li class="py-3 flex items-center justify-between gap-3 text-sm">
                        <div>
                            <strong class="text-stone-800"><?php echo $d['tipo'] === 'receita' ? 'Receita' : 'Atestado'; ?></strong>
                            <?php if ($d['tipo'] === 'atestado'): ?>
                                <span class="text-stone-500">· <?php echo (int)$d['dias_afastamento'] > 0 ? (int)$d['dias_afastamento'] . ' dia(s) de afastamento' : 'comparecimento'; ?></span>
                            <?php endif; ?>
                            <div class="text-xs text-stone-500"><?php echo h($d['medico_nome']); ?> · <?php echo date('d/m/Y H:i', strtotime($d['criado_em'])); ?></div>
                        </div>
                        <a href="documento.php?id=<?php echo (int)$d['id']; ?>" target="_blank" class="text-xs font-medium border border-stone-300 text-stone-700 px-3 py-2 rounded-lg hover:bg-[#F9F4EC] whitespace-nowrap">
                            Abrir / imprimir
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
</body>
</html>