<?php
require_once __DIR__ . '/../bootstrap.php';
// Painel reutilizável: o médico escreve receita, atestado ou declaração e envia ao paciente.

use App\CBOs;

function painelDocumentos($pacienteId, $consultaId, $raiz = '') {
    // Pega especialidade do médico logado para popular a lista de CBOs
    $espMedico = '';
    if (isset($GLOBALS['conn']) && function_exists('medicoAtual')) {
        $mm = medicoAtual($GLOBALS['conn']);
        if ($mm) $espMedico = $mm['especialidade'] ?? '';
    }
    $listaCBO = CBOs::paraEspecialidade($espMedico);
    $cboPadrao = CBOs::padrao($espMedico);
    $validades = CBOs::validades();

    $input = 'w-full border border-stone-300 px-3 py-2 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]';
    $label = 'block text-xs font-bold text-stone-600 uppercase mb-1';
    $abaOn  = 'px-3 py-2 rounded-xl text-xs sm:text-sm font-medium bg-[#3E352E] text-white whitespace-nowrap';
    $abaOff = 'px-3 py-2 rounded-xl text-xs sm:text-sm font-medium bg-[#F9F4EC] text-[#5A4A3A] border border-[#E6D5B8] whitespace-nowrap';
    ?>
<div id="pd" class="space-y-4">
    <div class="flex flex-wrap gap-2">
        <button type="button" id="pd-aba-receita"    onclick="pdAba('receita')"    class="<?php echo $abaOn; ?>">Receita</button>
        <button type="button" id="pd-aba-atestado"   onclick="pdAba('atestado')"   class="<?php echo $abaOff; ?>">Atestado</button>
        <button type="button" id="pd-aba-declaracao" onclick="pdAba('declaracao')" class="<?php echo $abaOff; ?>">Declaração</button>
    </div>

    <!-- ABA: RECEITA -->
    <form id="pd-form-receita" class="space-y-3" onsubmit="return pdEnviar(event, 'receita')">
        <div>
            <label class="<?php echo $label; ?>">Medicamentos e posologia</label>
            <textarea name="conteudo" rows="6" required class="<?php echo $input; ?>" placeholder="Ex.: Dipirona 500 mg — 1 comprimido a cada 6 horas, por 3 dias, se dor ou febre."></textarea>
        </div>

        <!-- Validade + CBO (receita) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="<?php echo $label; ?>">Validade da receita</label>
                <select name="validade_dias" class="<?php echo $input; ?>">
                    <?php foreach ($validades as $d => $lbl): ?>
                        <option value="<?php echo (int)$d; ?>" <?php echo $d === 30 ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lbl); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="<?php echo $label; ?>">CBO (ocupação)</label>
                <select name="cbo" class="<?php echo $input; ?>">
                    <?php foreach ($listaCBO as $cod => $desc): ?>
                        <option value="<?php echo htmlspecialchars($cod); ?>" <?php echo $cod === $cboPadrao ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cod . ' — ' . $desc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="w-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-3 rounded-xl text-sm font-medium transition shadow-sm">
            <i class="fa-solid fa-paper-plane mr-1"></i> Enviar receita ao paciente
        </button>
    </form>

    <!-- ABA: ATESTADO -->
    <form id="pd-form-atestado" class="space-y-3 hidden" onsubmit="return pdEnviar(event, 'atestado')">
        <div>
            <label class="<?php echo $label; ?>">Dias de afastamento <span class="normal-case font-normal text-stone-400">(0 = atestado de comparecimento)</span></label>
            <input type="number" name="dias_afastamento" min="0" max="365" value="1" required class="<?php echo $input; ?>">
        </div>
        <label class="flex items-start gap-2 text-sm text-stone-700">
            <input type="checkbox" name="incluir_cid" value="1" id="pd-at-cid-check" class="mt-1" onchange="document.getElementById('pd-at-cid').classList.toggle('hidden', !this.checked)">
            <span>Incluir CID-10 <span class="text-xs text-stone-400">(somente com autorização do paciente)</span></span>
        </label>
        <input name="cid10" id="pd-at-cid" placeholder="Ex.: J00" class="<?php echo $input; ?> hidden">
        <div>
            <label class="<?php echo $label; ?>">Observações <span class="normal-case font-normal text-stone-400">(opcional)</span></label>
            <textarea name="conteudo" rows="3" class="<?php echo $input; ?>"></textarea>
        </div>
        <button type="submit" class="w-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-3 rounded-xl text-sm font-medium transition shadow-sm">
            <i class="fa-solid fa-paper-plane mr-1"></i> Enviar atestado ao paciente
        </button>
    </form>

    <!-- ABA: DECLARAÇÃO -->
    <form id="pd-form-declaracao" class="space-y-3 hidden" onsubmit="return pdEnviar(event, 'declaracao')">
        <div>
            <label class="<?php echo $label; ?>">Finalidade da declaração *</label>
            <select name="finalidade" required class="<?php echo $input; ?>">
                <option value="">Selecione o motivo...</option>
                <option value="comparecimento à consulta médica">Comparecimento à consulta médica</option>
                <option value="acompanhamento de paciente">Acompanhamento de paciente</option>
                <option value="aptidão física para atividades esportivas">Aptidão física para atividades esportivas</option>
                <option value="aptidão para o trabalho">Aptidão para o trabalho</option>
                <option value="dispensa de atividade física escolar">Dispensa de atividade física escolar</option>
                <option value="autorização para procedimento">Autorização para procedimento</option>
                <option value="uso contínuo de medicamento">Uso contínuo de medicamento</option>
            </select>
        </div>

        <!-- Validade + CBO (declaração) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="<?php echo $label; ?>">Validade da declaração</label>
                <select name="validade_dias" class="<?php echo $input; ?>">
                    <?php foreach ($validades as $d => $lbl): ?>
                        <option value="<?php echo (int)$d; ?>" <?php echo $d === 30 ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lbl); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="<?php echo $label; ?>">CBO (ocupação)</label>
                <select name="cbo" class="<?php echo $input; ?>">
                    <?php foreach ($listaCBO as $cod => $desc): ?>
                        <option value="<?php echo htmlspecialchars($cod); ?>" <?php echo $cod === $cboPadrao ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cod . ' — ' . $desc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <label class="flex items-start gap-2 text-sm text-stone-700">
            <input type="checkbox" name="incluir_cid" value="1" id="pd-dc-cid-check" class="mt-1" onchange="document.getElementById('pd-dc-cid').classList.toggle('hidden', !this.checked)">
            <span>Incluir CID-10 <span class="text-xs text-stone-400">(somente com autorização do paciente)</span></span>
        </label>
        <input name="cid10" id="pd-dc-cid" placeholder="Ex.: I10" class="<?php echo $input; ?> hidden">

        <div>
            <label class="<?php echo $label; ?>">Observações <span class="normal-case font-normal text-stone-400">(opcional)</span></label>
            <textarea name="conteudo" rows="3" class="<?php echo $input; ?>" placeholder="Ex.: O paciente esteve sob meus cuidados das 14h às 15h do dia 15/10/2026."></textarea>
        </div>

        <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
            <i class="fa-solid fa-circle-info mt-0.5"></i>
            <span>O texto será gerado automaticamente com a finalidade escolhida, observações e CID (se autorizado).</span>
        </div>

        <button type="submit" class="w-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-3 rounded-xl text-sm font-medium transition shadow-sm">
            <i class="fa-solid fa-paper-plane mr-1"></i> Enviar declaração ao paciente
        </button>
    </form>

    <p id="pd-msg" class="text-sm hidden rounded-xl p-3 border"></p>

    <div>
        <h4 class="text-xs font-bold text-stone-500 uppercase mb-2">Já enviados a este paciente</h4>
        <ul id="pd-lista" class="space-y-2 text-sm"><li class="text-stone-400">Carregando...</li></ul>
    </div>
</div>

<script>
(function () {
    const RAIZ = <?php echo json_encode($raiz); ?>;
    const PID  = <?php echo (int)$pacienteId; ?>;
    const CID  = <?php echo (int)$consultaId; ?>;
    const API  = RAIZ + 'api/documentos.php';
    const $ = id => document.getElementById(id);

    const ROTULOS = {
        receita:    'Receita',
        atestado:   'Atestado',
        declaracao: 'Declaração'
    };

    window.pdAba = function (qual) {
        const forms = ['receita', 'atestado', 'declaracao'];
        forms.forEach(f => {
            const on = f === qual;
            $('pd-form-' + f).classList.toggle('hidden', !on);
            $('pd-aba-' + f).className = 'px-3 py-2 rounded-xl text-xs sm:text-sm font-medium whitespace-nowrap ' +
                (on ? 'bg-[#3E352E] text-white'
                    : 'bg-[#F9F4EC] text-[#5A4A3A] border border-[#E6D5B8]');
        });
    };

    function mensagem(txt, ok) {
        const m = $('pd-msg');
        m.textContent = txt;
        m.className = 'text-sm rounded-xl p-3 border ' + (ok
            ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
            : 'bg-red-50 text-red-700 border-red-200');
        m.classList.remove('hidden');
        if (ok) setTimeout(() => m.classList.add('hidden'), 6000);
    }

    window.pdEnviar = async function (ev, tipo) {
        ev.preventDefault();
        const form = ev.target;
        const fd = new FormData(form);

        if (tipo !== 'atestado') fd.delete('dias_afastamento');

        if (tipo === 'declaracao') {
            const finalidade = (fd.get('finalidade') || '').toString().trim();
            const obs = (fd.get('conteudo') || '').toString().trim();
            const incluirCid = !!fd.get('incluir_cid');
            const cid = incluirCid ? (fd.get('cid10') || '').toString().trim() : '';

            let texto = `Declaro, para os devidos fins, que o(a) paciente esteve sob meus cuidados profissionais para ${finalidade}.`;
            if (obs) texto += `\n\n${obs}`;
            if (cid) texto += `\n\nCID-10: ${cid}`;

            fd.set('conteudo', texto);
            fd.delete('finalidade');
            fd.delete('incluir_cid');
        }

        fd.append('acao', 'emitir');
        fd.append('tipo', tipo);
        fd.append('paciente_id', PID);
        fd.append('consulta_id', CID);

        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;

        try {
            const r = await fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' });
            const j = await r.json();
            if (j.ok) {
                mensagem(ROTULOS[tipo] + ' enviado(a) ao paciente.', true);
                form.reset();
                ['pd-at-cid', 'pd-dc-cid'].forEach(id => {
                    const el = $(id); if (el) el.classList.add('hidden');
                });
                carregar();
            } else {
                mensagem(j.erro || 'Não foi possível enviar.', false);
            }
        } catch (e) {
            mensagem('Falha de conexão. Tente novamente.', false);
        }
        btn.disabled = false;
        return false;
    };

    async function carregar() {
        try {
            const r = await fetch(API + '?acao=listar&paciente=' + PID, { credentials: 'same-origin' });
            const j = await r.json();
            const ul = $('pd-lista'); ul.innerHTML = '';
            if (!j.documentos || !j.documentos.length) {
                const li = document.createElement('li');
                li.className = 'text-stone-400';
                li.textContent = 'Nenhum documento enviado ainda.';
                ul.appendChild(li);
                return;
            }
            j.documentos.forEach(d => {
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between gap-2 border border-stone-200 rounded-xl px-3 py-2 bg-[#FAF9F6]';
                const t = document.createElement('div');
                const b = document.createElement('strong');
                b.textContent = ROTULOS[d.tipo] || d.tipo;
                const s = document.createElement('div');
                s.className = 'text-xs text-stone-500';
                s.textContent = d.data + ' · ' + d.resumo;
                t.appendChild(b); t.appendChild(s);
                const a = document.createElement('a');
                a.href = RAIZ + 'documento.php?id=' + d.id;
                a.target = '_blank';
                a.className = 'text-xs font-medium border border-stone-300 px-2.5 py-1.5 rounded-lg hover:bg-white';
                a.textContent = 'Abrir';
                li.appendChild(t); li.appendChild(a); ul.appendChild(li);
            });
        } catch (e) {}
    }
    carregar();
})();
</script>
<?php } ?>