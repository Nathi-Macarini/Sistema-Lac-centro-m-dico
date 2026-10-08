<?php
// Painel reutilizável: o médico escreve receita ou atestado e envia ao paciente.
// Usado dentro da sala de teleconsulta e na tela do prontuário.
//   $pacienteId  : paciente que vai receber
//   $consultaId  : consulta vinculada (0 se não houver)
//   $raiz        : caminho até a raiz do sistema ('' a partir da raiz, '../' a partir de /medico)
function painelDocumentos($pacienteId, $consultaId, $raiz = '') {
    $input = 'w-full border border-stone-300 px-3 py-2 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]';
    $label = 'block text-xs font-bold text-stone-600 uppercase mb-1';
    ?>
<div id="pd" class="space-y-4">
    <div class="flex gap-2">
        <button type="button" id="pd-aba-receita" onclick="pdAba('receita')" class="px-4 py-2 rounded-xl text-sm font-medium bg-[#3E352E] text-white">Receita</button>
        <button type="button" id="pd-aba-atestado" onclick="pdAba('atestado')" class="px-4 py-2 rounded-xl text-sm font-medium bg-[#F9F4EC] text-[#5A4A3A] border border-[#E6D5B8]">Atestado</button>
    </div>

    <form id="pd-form-receita" class="space-y-3" onsubmit="return pdEnviar(event, 'receita')">
        <div>
            <label class="<?php echo $label; ?>">Medicamentos e posologia</label>
            <textarea name="conteudo" rows="6" required class="<?php echo $input; ?>" placeholder="Ex.: Dipirona 500 mg — 1 comprimido a cada 6 horas, por 3 dias, se dor ou febre."></textarea>
        </div>
        <button type="submit" class="w-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-3 rounded-xl text-sm font-medium transition shadow-sm">
            <i class="fa-solid fa-paper-plane mr-1"></i> Enviar receita ao paciente
        </button>
    </form>

    <form id="pd-form-atestado" class="space-y-3 hidden" onsubmit="return pdEnviar(event, 'atestado')">
        <div>
            <label class="<?php echo $label; ?>">Dias de afastamento <span class="normal-case font-normal text-stone-400">(0 = atestado de comparecimento)</span></label>
            <input type="number" name="dias_afastamento" min="0" max="365" value="1" required class="<?php echo $input; ?>">
        </div>
        <label class="flex items-start gap-2 text-sm text-stone-700">
            <input type="checkbox" name="incluir_cid" value="1" id="pd-incluir-cid" class="mt-1" onchange="document.getElementById('pd-cid').classList.toggle('hidden', !this.checked)">
            <span>Incluir CID-10 <span class="text-xs text-stone-400">(somente com autorização do paciente)</span></span>
        </label>
        <input name="cid10" id="pd-cid" placeholder="Ex.: J00" class="<?php echo $input; ?> hidden">
        <div>
            <label class="<?php echo $label; ?>">Observações <span class="normal-case font-normal text-stone-400">(opcional)</span></label>
            <textarea name="conteudo" rows="3" class="<?php echo $input; ?>"></textarea>
        </div>
        <button type="submit" class="w-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-3 rounded-xl text-sm font-medium transition shadow-sm">
            <i class="fa-solid fa-paper-plane mr-1"></i> Enviar atestado ao paciente
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

    window.pdAba = function (qual) {
        const rec = qual === 'receita';
        $('pd-form-receita').classList.toggle('hidden', !rec);
        $('pd-form-atestado').classList.toggle('hidden', rec);
        $('pd-aba-receita').className  = 'px-4 py-2 rounded-xl text-sm font-medium ' + (rec ? 'bg-[#3E352E] text-white' : 'bg-[#F9F4EC] text-[#5A4A3A] border border-[#E6D5B8]');
        $('pd-aba-atestado').className = 'px-4 py-2 rounded-xl text-sm font-medium ' + (!rec ? 'bg-[#3E352E] text-white' : 'bg-[#F9F4EC] text-[#5A4A3A] border border-[#E6D5B8]');
    };

    function mensagem(txt, ok) {
        const m = $('pd-msg');
        m.textContent = txt;
        m.className = 'text-sm rounded-xl p-3 border ' + (ok ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200');
        if (ok) setTimeout(() => m.classList.add('hidden'), 6000);
    }

    window.pdEnviar = async function (ev, tipo) {
        ev.preventDefault();
        const form = ev.target;
        const fd = new FormData(form);
        fd.append('acao', 'emitir'); fd.append('tipo', tipo);
        fd.append('paciente_id', PID); fd.append('consulta_id', CID);
        const btn = form.querySelector('button[type=submit]'); btn.disabled = true;
        try {
            const r = await fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' });
            const j = await r.json();
            if (j.ok) {
                mensagem((tipo === 'receita' ? 'Receita' : 'Atestado') + ' enviado ao paciente.', true);
                form.reset();
                $('pd-cid').classList.add('hidden');
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
                const li = document.createElement('li'); li.className = 'text-stone-400'; li.textContent = 'Nenhum documento enviado ainda.'; ul.appendChild(li); return;
            }
            j.documentos.forEach(d => {
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between gap-2 border border-stone-200 rounded-xl px-3 py-2 bg-[#FAF9F6]';
                const t = document.createElement('div');
                const b = document.createElement('strong'); b.textContent = d.tipo === 'receita' ? 'Receita' : 'Atestado';
                const s = document.createElement('div'); s.className = 'text-xs text-stone-500'; s.textContent = d.data + ' · ' + d.resumo;
                t.appendChild(b); t.appendChild(s);
                const a = document.createElement('a');
                a.href = RAIZ + 'documento.php?id=' + d.id; a.target = '_blank';
                a.className = 'text-xs font-medium border border-stone-300 px-2.5 py-1.5 rounded-lg hover:bg-white'; a.textContent = 'Abrir';
                li.appendChild(t); li.appendChild(a); ul.appendChild(li);
            });
        } catch (e) { /* tenta de novo ao enviar outro documento */ }
    }
    carregar();
})();
</script>
<?php } ?>