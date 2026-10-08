<?php
require __DIR__ . '/../conexao.php';
$medico = exigirMedico($conn);
verificarEsquema($conn);
require __DIR__ . '/layout.php';
require __DIR__ . '/painel_documentos.php';

$embed = !empty($_GET['embed']);

$pid = (int)($_GET['id'] ?? 0);
$consultaId = (int)($_GET['consulta'] ?? 0);

$st = $conn->prepare("SELECT id, nome, email, cpf, telefone, data_nascimento, endereco FROM usuarios WHERE id = ? AND tipo = 'comum'");
$st->bind_param('i', $pid);
$st->execute();
$p = $st->get_result()->fetch_assoc();
if (!$p) { header("Location: pacientes.php"); exit; }

$msg = ''; $tipoMsg = '';

// ---------- Salvar ficha base do prontuário ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'ficha') {
    $campos = ['sexo','tipo_sanguineo','alergias','doencas_cronicas','medicamentos_uso','cirurgias_previas','historico_familiar','habitos_vida','observacoes'];
    $v = [];
    foreach ($campos as $k) { $t = trim($_POST[$k] ?? ''); $v[$k] = $t === '' ? null : $t; }
    $mid = (int)$medico['id'];
    $st = $conn->prepare(
        "INSERT INTO prontuarios (paciente_id, sexo, tipo_sanguineo, alergias, doencas_cronicas, medicamentos_uso, cirurgias_previas, historico_familiar, habitos_vida, observacoes, atualizado_por)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE sexo=VALUES(sexo), tipo_sanguineo=VALUES(tipo_sanguineo), alergias=VALUES(alergias), doencas_cronicas=VALUES(doencas_cronicas),
            medicamentos_uso=VALUES(medicamentos_uso), cirurgias_previas=VALUES(cirurgias_previas), historico_familiar=VALUES(historico_familiar),
            habitos_vida=VALUES(habitos_vida), observacoes=VALUES(observacoes), atualizado_por=VALUES(atualizado_por)");
    $st->bind_param('isssssssssi', $pid, $v['sexo'], $v['tipo_sanguineo'], $v['alergias'], $v['doencas_cronicas'], $v['medicamentos_uso'],
                    $v['cirurgias_previas'], $v['historico_familiar'], $v['habitos_vida'], $v['observacoes'], $mid);
    if ($st->execute()) { $msg = "Ficha do prontuário atualizada."; $tipoMsg = 'sucesso'; }
    else { $msg = "Erro ao salvar a ficha: " . $conn->error; $tipoMsg = 'erro'; }
}

// ---------- Registrar nova evolução / atendimento ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'evolucao') {
    $txt = function ($k) { $t = trim($_POST[$k] ?? ''); return $t === '' ? null : $t; };
    $num = function ($k) { $t = str_replace(',', '.', trim($_POST[$k] ?? '')); return is_numeric($t) ? $t + 0 : null; };

    $queixa = $txt('queixa_principal'); $hda = $txt('historia_doenca'); $exame = $txt('exame_fisico');
    $pa = $txt('pressao_arterial'); $fc = $num('freq_cardiaca'); $temp = $num('temperatura'); $sat = $num('saturacao');
    $peso = $num('peso_kg'); $alt = $num('altura_cm'); $diag = $txt('diagnostico'); $cid = $txt('cid10');
    $conduta = $txt('conduta'); $presc = $txt('prescricao'); $exames = $txt('exames_solicitados'); $ret = $txt('retorno');
    $cons = (int)($_POST['consulta_id'] ?? 0) ?: null;
    $mid = (int)$medico['id'];
    if ($cons) {
        $chk = $conn->prepare("SELECT id FROM consultas WHERE id = ? AND usuario_id = ? AND medico_id = ?");
        $chk->bind_param('iii', $cons, $pid, $mid);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) $cons = null;
    }

    if ($queixa === null && $diag === null && $conduta === null) {
        $msg = "Preencha ao menos a queixa principal, o diagnóstico ou a conduta."; $tipoMsg = 'erro';
    } else {
        $st = $conn->prepare(
            "INSERT INTO atendimentos (paciente_id, medico_id, consulta_id, queixa_principal, historia_doenca, exame_fisico, pressao_arterial, freq_cardiaca,
                                       temperatura, saturacao, peso_kg, altura_cm, diagnostico, cid10, conduta, prescricao, exames_solicitados, retorno)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->bind_param('iiissssididissssss', $pid, $mid, $cons, $queixa, $hda, $exame, $pa, $fc, $temp, $sat, $peso, $alt, $diag, $cid, $conduta, $presc, $exames, $ret);
        if ($st->execute()) {
            $msg = "Evolução registrada no prontuário."; $tipoMsg = 'sucesso';
            if (!empty($_POST['finalizar_consulta']) && $cons) {
                $u = $conn->prepare("UPDATE consultas SET status='Realizada' WHERE id = ? AND medico_id = ?");
                $u->bind_param('ii', $cons, $mid);
                $u->execute();
            }
        } else { $msg = "Erro ao registrar a evolução: " . $conn->error; $tipoMsg = 'erro'; }
    }
}

// ---------- Dados para a tela ----------
$st = $conn->prepare("SELECT * FROM prontuarios WHERE paciente_id = ?");
$st->bind_param('i', $pid);
$st->execute();
$f = $st->get_result()->fetch_assoc() ?: [];
$ficha = fn($k) => $f[$k] ?? '';

$st = $conn->prepare(
    "SELECT a.*, m.nome AS medico_nome, m.crm, m.especialidade
     FROM atendimentos a JOIN medicos m ON m.id = a.medico_id
     WHERE a.paciente_id = ? ORDER BY a.criado_em DESC");
$st->bind_param('i', $pid);
$st->execute();
$evolucoes = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$st = $conn->prepare(
    "SELECT c.*, m.nome AS medico_nome FROM consultas c LEFT JOIN medicos m ON m.id = c.medico_id
     WHERE c.usuario_id = ? ORDER BY c.data_hora DESC LIMIT 15");
$st->bind_param('i', $pid);
$st->execute();
$consultas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$consultaAtual = null;
if ($consultaId) {
    $st = $conn->prepare("SELECT * FROM consultas WHERE id = ? AND usuario_id = ? AND medico_id = ?");
    $mid = (int)$medico['id'];
    $st->bind_param('iii', $consultaId, $pid, $mid);
    $st->execute();
    $consultaAtual = $st->get_result()->fetch_assoc();
}

$input = 'w-full border border-stone-300 px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]';
$label = 'block text-xs font-bold text-stone-600 uppercase mb-1.5';

cabecalho('Prontuário - ' . $p['nome'], 'pacientes', $embed);
?>
<?php if (!$embed): ?>
<a href="pacientes.php" class="text-sm text-stone-600 hover:text-[#8C6D36]"><i class="fa-solid fa-arrow-left"></i> Voltar para pacientes</a>
<?php endif; ?>

<?php if ($msg): ?>
    <div class="<?php echo $tipoMsg==='sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border"><?php echo h($msg); ?></div>
<?php endif; ?>

<section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
        <div>
            <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full uppercase tracking-wider">Prontuário nº <?php echo str_pad($pid, 6, '0', STR_PAD_LEFT); ?></span>
            <h1 class="text-3xl font-serif text-[#3E352E] mt-3"><?php echo h($p['nome']); ?></h1>
            <p class="text-stone-600 mt-1"><?php echo h(textoIdade($p['data_nascimento'])); ?>
                <?php if ($p['data_nascimento']): ?> · nascimento <?php echo date('d/m/Y', strtotime($p['data_nascimento'])); ?><?php endif; ?>
                <?php if ($ficha('sexo')): ?> · <?php echo h($ficha('sexo')); ?><?php endif; ?></p>
        </div>
        <?php if ($ficha('alergias')): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 text-sm max-w-sm"><strong><i class="fa-solid fa-triangle-exclamation"></i> Alergias:</strong> <?php echo nl2br(h($ficha('alergias'))); ?></div>
        <?php endif; ?>
    </div>
    <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 text-sm">
        <div><dt class="text-xs font-bold text-stone-500 uppercase">CPF</dt><dd><?php echo $p['cpf'] ? h(formatarCpf($p['cpf'])) : '—'; ?></dd></div>
        <div><dt class="text-xs font-bold text-stone-500 uppercase">Telefone</dt><dd><?php echo h($p['telefone'] ?: '—'); ?></dd></div>
        <div><dt class="text-xs font-bold text-stone-500 uppercase">E-mail</dt><dd class="break-all"><?php echo h($p['email'] ?: '—'); ?></dd></div>
        <div><dt class="text-xs font-bold text-stone-500 uppercase">Tipo sanguíneo</dt><dd><?php echo h($ficha('tipo_sanguineo') ?: '—'); ?></dd></div>
        <div class="col-span-2 md:col-span-4"><dt class="text-xs font-bold text-stone-500 uppercase">Endereço</dt><dd><?php echo h($p['endereco'] ?: '—'); ?></dd></div>
    </dl>
</section>

<div class="grid grid-cols-1 <?php echo $embed ? '' : 'xl:grid-cols-5'; ?> gap-8">
    <div class="<?php echo $embed ? '' : 'xl:col-span-3'; ?> space-y-8">

        <section id="nova-evolucao" class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h3 class="text-xl font-serif text-[#3E352E]"><i class="fa-solid fa-pen-to-square text-[#8C6D36] mr-1"></i> Registrar evolução / atendimento</h3>
            <?php if ($consultaAtual): ?>
                <p class="text-sm bg-[#F9F4EC] border border-[#E6D5B8] rounded-xl p-3 text-stone-700">Vinculado à consulta de <?php echo date('d/m/Y H:i', strtotime($consultaAtual['data_hora'])); ?> (<?php echo h($consultaAtual['modalidade']); ?>).</p>
            <?php endif; ?>
            <form method="POST" action="prontuario.php?id=<?php echo $pid; ?><?php echo $consultaAtual ? '&consulta=' . (int)$consultaAtual['id'] : ''; ?><?php echo $embed ? '&embed=1' : ''; ?>#nova-evolucao" class="space-y-4">
                <input type="hidden" name="acao" value="evolucao">
                <input type="hidden" name="consulta_id" value="<?php echo $consultaAtual ? (int)$consultaAtual['id'] : 0; ?>">

                <div><label class="<?php echo $label; ?>">Queixa principal</label><textarea name="queixa_principal" rows="2" class="<?php echo $input; ?>"></textarea></div>
                <div><label class="<?php echo $label; ?>">História da doença atual</label><textarea name="historia_doenca" rows="3" class="<?php echo $input; ?>"></textarea></div>

                <div>
                    <span class="<?php echo $label; ?>">Sinais vitais e medidas</span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <input name="pressao_arterial" placeholder="PA (120/80)" class="<?php echo $input; ?>">
                        <input name="freq_cardiaca" placeholder="FC (bpm)" class="<?php echo $input; ?>">
                        <input name="temperatura" placeholder="Temp. (°C)" class="<?php echo $input; ?>">
                        <input name="saturacao" placeholder="SpO₂ (%)" class="<?php echo $input; ?>">
                        <input name="peso_kg" placeholder="Peso (kg)" class="<?php echo $input; ?>">
                        <input name="altura_cm" placeholder="Altura (cm)" class="<?php echo $input; ?>">
                    </div>
                </div>

                <div><label class="<?php echo $label; ?>">Exame físico <span class="normal-case font-normal text-stone-400">(na teleconsulta: o que foi observado por vídeo)</span></label><textarea name="exame_fisico" rows="3" class="<?php echo $input; ?>"></textarea></div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2"><label class="<?php echo $label; ?>">Diagnóstico / hipótese diagnóstica</label><input name="diagnostico" class="<?php echo $input; ?>"></div>
                    <div><label class="<?php echo $label; ?>">CID-10</label><input name="cid10" placeholder="J00" class="<?php echo $input; ?>"></div>
                </div>
                <div><label class="<?php echo $label; ?>">Conduta / plano terapêutico</label><textarea name="conduta" rows="3" class="<?php echo $input; ?>"></textarea></div>
                <div><label class="<?php echo $label; ?>">Prescrição</label><textarea name="prescricao" rows="3" class="<?php echo $input; ?>" placeholder="Medicamento, dose, via, frequência e duração"></textarea></div>
                <div><label class="<?php echo $label; ?>">Exames solicitados</label><textarea name="exames_solicitados" rows="2" class="<?php echo $input; ?>"></textarea></div>
                <div><label class="<?php echo $label; ?>">Retorno</label><input name="retorno" placeholder="Ex.: em 15 dias, se persistirem os sintomas" class="<?php echo $input; ?>"></div>

                <?php if (!$embed && $consultaAtual && $consultaAtual['status'] !== 'Realizada'): ?>
                    <label class="flex items-center gap-2 text-sm text-stone-700"><input type="checkbox" name="finalizar_consulta" value="1" checked> Marcar a consulta como <strong>Realizada</strong> ao salvar</label>
                <?php endif; ?>
                <div class="flex justify-end"><button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-3 rounded-xl text-sm font-medium transition shadow-sm"><i class="fa-solid fa-floppy-disk mr-1"></i> Salvar evolução</button></div>
            </form>
        </section>

        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h3 class="text-xl font-serif text-[#3E352E]"><i class="fa-solid fa-clock-rotate-left text-[#8C6D36] mr-1"></i> Histórico de atendimentos</h3>
            <?php if (!$evolucoes): ?><p class="text-sm text-stone-500 py-4 text-center">Nenhum atendimento registrado ainda.</p><?php endif; ?>
            <?php foreach ($evolucoes as $e):
                $campo = function ($rot, $val) { return $val ? '<div><dt class="text-xs font-bold text-stone-500 uppercase">'.$rot.'</dt><dd class="text-sm text-stone-800">'.nl2br(h($val)).'</dd></div>' : ''; };
                $vitais = array_filter([
                    $e['pressao_arterial'] ? 'PA ' . $e['pressao_arterial'] : null,
                    $e['freq_cardiaca'] ? 'FC ' . $e['freq_cardiaca'] . ' bpm' : null,
                    $e['temperatura'] !== null ? 'Temp ' . $e['temperatura'] . ' °C' : null,
                    $e['saturacao'] ? 'SpO₂ ' . $e['saturacao'] . '%' : null,
                    $e['peso_kg'] ? $e['peso_kg'] . ' kg' : null,
                    $e['altura_cm'] ? $e['altura_cm'] . ' cm' : null,
                ]);
            ?>
                <article class="border border-stone-200 rounded-2xl p-5 bg-[#FAF9F6] space-y-3">
                    <header class="flex flex-wrap justify-between gap-2 text-sm">
                        <strong class="text-[#3E352E]"><?php echo date('d/m/Y H:i', strtotime($e['criado_em'])); ?></strong>
                        <span class="text-stone-500"><?php echo h($e['medico_nome']); ?> · <?php echo h($e['crm']); ?> · <?php echo h($e['especialidade']); ?></span>
                    </header>
                    <?php if ($vitais): ?><p class="text-xs text-stone-600 bg-white border border-stone-200 rounded-lg px-3 py-2"><?php echo h(implode('  ·  ', $vitais)); ?></p><?php endif; ?>
                    <dl class="space-y-2">
                        <?php echo $campo('Queixa principal', $e['queixa_principal']); ?>
                        <?php echo $campo('História da doença atual', $e['historia_doenca']); ?>
                        <?php echo $campo('Exame físico', $e['exame_fisico']); ?>
                        <?php echo $campo('Diagnóstico' . ($e['cid10'] ? ' (CID-10 ' . $e['cid10'] . ')' : ''), $e['diagnostico']); ?>
                        <?php echo $campo('Conduta', $e['conduta']); ?>
                        <?php echo $campo('Prescrição', $e['prescricao']); ?>
                        <?php echo $campo('Exames solicitados', $e['exames_solicitados']); ?>
                        <?php echo $campo('Retorno', $e['retorno']); ?>
                    </dl>
                </article>
            <?php endforeach; ?>
        </section>
    </div>

    <div class="<?php echo $embed ? '' : 'xl:col-span-2'; ?> space-y-8">
        <?php if (!$embed): ?>
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h3 class="text-xl font-serif text-[#3E352E]"><i class="fa-solid fa-file-medical text-[#8C6D36] mr-1"></i> Receita e atestado</h3>
            <?php painelDocumentos($pid, $consultaAtual ? (int)$consultaAtual['id'] : 0, '../'); ?>
        </section>
        <?php endif; ?>

        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h3 class="text-xl font-serif text-[#3E352E]"><i class="fa-solid fa-file-medical text-[#8C6D36] mr-1"></i> Ficha do paciente</h3>
            <form method="POST" action="prontuario.php?id=<?php echo $pid; ?><?php echo $consultaAtual ? '&consulta=' . (int)$consultaAtual['id'] : ''; ?><?php echo $embed ? '&embed=1' : ''; ?>" class="space-y-4">
                <input type="hidden" name="acao" value="ficha">
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="<?php echo $label; ?>">Sexo</label>
                        <select name="sexo" class="<?php echo $input; ?>">
                            <?php foreach (['', 'Feminino', 'Masculino', 'Outro'] as $o): ?><option value="<?php echo $o; ?>" <?php echo $ficha('sexo') === $o ? 'selected' : ''; ?>><?php echo $o ?: 'Não informado'; ?></option><?php endforeach; ?>
                        </select></div>
                    <div><label class="<?php echo $label; ?>">Tipo sanguíneo</label>
                        <select name="tipo_sanguineo" class="<?php echo $input; ?>">
                            <?php foreach (['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $o): ?><option value="<?php echo $o; ?>" <?php echo $ficha('tipo_sanguineo') === $o ? 'selected' : ''; ?>><?php echo $o ?: '—'; ?></option><?php endforeach; ?>
                        </select></div>
                </div>
                <div><label class="<?php echo $label; ?>">Alergias</label><textarea name="alergias" rows="2" class="<?php echo $input; ?>"><?php echo h($ficha('alergias')); ?></textarea></div>
                <div><label class="<?php echo $label; ?>">Doenças crônicas / comorbidades</label><textarea name="doencas_cronicas" rows="2" class="<?php echo $input; ?>"><?php echo h($ficha('doencas_cronicas')); ?></textarea></div>
                <div><label class="<?php echo $label; ?>">Medicamentos em uso</label><textarea name="medicamentos_uso" rows="2" class="<?php echo $input; ?>"><?php echo h($ficha('medicamentos_uso')); ?></textarea></div>
                <div><label class="<?php echo $label; ?>">Cirurgias e internações prévias</label><textarea name="cirurgias_previas" rows="2" class="<?php echo $input; ?>"><?php echo h($ficha('cirurgias_previas')); ?></textarea></div>
                <div><label class="<?php echo $label; ?>">Histórico familiar</label><textarea name="historico_familiar" rows="2" class="<?php echo $input; ?>"><?php echo h($ficha('historico_familiar')); ?></textarea></div>
                <div><label class="<?php echo $label; ?>">Hábitos de vida</label><textarea name="habitos_vida" rows="2" class="<?php echo $input; ?>" placeholder="Tabagismo, álcool, atividade física, sono..."><?php echo h($ficha('habitos_vida')); ?></textarea></div>
                <div><label class="<?php echo $label; ?>">Observações gerais</label><textarea name="observacoes" rows="2" class="<?php echo $input; ?>"><?php echo h($ficha('observacoes')); ?></textarea></div>
                <div class="flex justify-end"><button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">Salvar ficha</button></div>
                <?php if (!empty($f['atualizado_em'])): ?><p class="text-xs text-stone-400">Última atualização: <?php echo date('d/m/Y H:i', strtotime($f['atualizado_em'])); ?></p><?php endif; ?>
            </form>
        </section>

        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h3 class="text-xl font-serif text-[#3E352E]"><i class="fa-regular fa-calendar-days text-[#8C6D36] mr-1"></i> Consultas</h3>
            <?php if (!$consultas): ?><p class="text-sm text-stone-500">Sem consultas.</p><?php endif; ?>
            <?php foreach ($consultas as $c): ?>
                <div class="border border-stone-200 rounded-xl p-3 text-sm bg-[#FAF9F6]">
                    <div class="flex justify-between"><strong><?php echo date('d/m/Y H:i', strtotime($c['data_hora'])); ?></strong><span class="text-xs text-stone-500"><?php echo h($c['status']); ?></span></div>
                    <div class="text-stone-600"><?php echo h($c['especialidade']); ?> · <?php echo h($c['modalidade']); ?><?php echo $c['medico_nome'] ? ' · ' . h($c['medico_nome']) : ''; ?></div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
</div>
<?php rodape(); ?>