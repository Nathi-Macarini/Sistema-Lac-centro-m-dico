<?php
/**
 * Componente reutilizável: modal de teste de áudio e vídeo.
 * 
 * Uso: <?php include __DIR__ . '/../includes/teste_av.php'; ?>
 * 
 * Depois, no HTML, chame: abrirTesteAV()
 * Para fechar: fecharTesteAV()
 * 
 * Opcional: definir `window.aoConfirmarTesteAV = function() { ... }` 
 * para executar algo quando o usuário clicar em "Confirmar".
 */
?>
<!-- Modal: Teste de Áudio e Vídeo -->
<div id="modal-teste-av" class="fixed inset-0 bg-black/70 z-[100] hidden flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[95vh] overflow-y-auto p-6 space-y-5 shadow-2xl border border-[#E6D5B8]">
        <div class="flex justify-between items-center border-b border-stone-100 pb-4">
            <div>
                <h3 class="text-xl font-serif text-[#3E352E]">Teste de Áudio e Vídeo</h3>
                <p class="text-xs text-stone-500 mt-0.5">Confira seus equipamentos antes de entrar na sala.</p>
            </div>
            <button onclick="fecharTesteAV()" class="text-stone-400 hover:text-stone-600">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <!-- Preview da câmera -->
        <div class="bg-stone-900 rounded-2xl aspect-video relative flex items-center justify-center overflow-hidden shadow-inner">
            <video id="tav-video" autoplay playsinline muted class="w-full h-full object-cover"></video>
            <div id="tav-video-placeholder" class="absolute text-stone-400 text-sm flex flex-col items-center gap-2 text-center px-6">
                <i class="fa-solid fa-video-slash text-2xl text-[#8C6D36]"></i>
                <span>Carregando câmera...</span>
            </div>

            <!-- Selo de resolução -->
            <div id="tav-badge-res" class="hidden absolute top-3 left-3 bg-black/60 text-white text-[10px] font-medium px-2 py-1 rounded-lg backdrop-blur-sm">
                —
            </div>
        </div>

        <!-- Controles de teste -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <!-- Microfone -->
            <div class="bg-[#FAF9F6] border border-stone-200 rounded-xl p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-bold text-stone-600 uppercase tracking-wider">
                        <i class="fa-solid fa-microphone text-[#8C6D36] mr-1"></i> Microfone
                    </div>
                    <span id="tav-status-mic" class="text-[10px] font-bold text-stone-500">Aguardando...</span>
                </div>
                <div class="h-2.5 bg-stone-100 rounded-full overflow-hidden">
                    <div id="tav-barra-mic" class="h-full bg-[#8C6D36] transition-all duration-100" style="width:0%"></div>
                </div>
                <p class="text-[10px] text-stone-500">Fale algo para testar o nível de captação.</p>
            </div>

            <!-- Caixa de som -->
            <div class="bg-[#FAF9F6] border border-stone-200 rounded-xl p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-bold text-stone-600 uppercase tracking-wider">
                        <i class="fa-solid fa-volume-high text-[#8C6D36] mr-1"></i> Caixa de som
                    </div>
                    <button type="button" onclick="testarAltoFalante()"
                            class="text-[10px] font-bold text-[#8C6D36] hover:text-[#5A4A3A] transition">
                        <i class="fa-solid fa-play mr-1"></i> Tocar som
                    </button>
                </div>
                <p class="text-[10px] text-stone-500" id="tav-status-speaker">Clique em "Tocar som" e verifique se você escutou.</p>
            </div>
        </div>

        <!-- Seletores de dispositivos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">
                    <i class="fa-solid fa-camera text-[#8C6D36] mr-1"></i> Câmera
                </label>
                <select id="tav-sel-camera" class="w-full border border-stone-300 px-3 py-2 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    <option>Padrão do sistema</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">
                    <i class="fa-solid fa-microphone text-[#8C6D36] mr-1"></i> Microfone
                </label>
                <select id="tav-sel-mic" class="w-full border border-stone-300 px-3 py-2 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    <option>Padrão do sistema</option>
                </select>
            </div>
        </div>

        <!-- Aviso -->
        <div class="bg-[#F9F4EC] border border-[#E6D5B8] p-3 rounded-xl text-xs text-[#5A4A3A] flex items-start gap-2">
            <i class="fa-solid fa-circle-info mt-0.5"></i>
            <span>
                <strong>Dica:</strong> fique em local tranquilo, com boa iluminação e internet estável.
                O vídeo e o áudio vão direto entre você e o médico — sem gravação.
            </span>
        </div>

        <!-- Botões -->
        <div class="flex justify-between gap-3 pt-2 border-t border-stone-100">
            <button type="button" onclick="fecharTesteAV()"
                    class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50 transition">
                Cancelar
            </button>
            <button type="button" id="tav-btn-confirmar" onclick="confirmarTesteAV()"
                    class="px-6 py-2.5 bg-[#3E352E] text-white rounded-xl text-sm font-medium hover:bg-[#5A4A3A] transition shadow-sm">
                <i class="fa-solid fa-check mr-1"></i> Tudo pronto
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    let tavStream = null;
    let tavAudioCtx = null;
    let tavMedidor = null;

    // ============================================================
    // Abrir / Fechar
    // ============================================================
    window.abrirTesteAV = async function () {
        document.getElementById('modal-teste-av').classList.remove('hidden');
        await iniciarTesteAV();
    };

    window.fecharTesteAV = function () {
        document.getElementById('modal-teste-av').classList.add('hidden');
        pararTesteAV();
    };

    window.confirmarTesteAV = function () {
        // Callback customizado (se definido)
        if (typeof window.aoConfirmarTesteAV === 'function') {
            window.aoConfirmarTesteAV();
        }
        fecharTesteAV();
    };

    // ============================================================
    // Iniciar teste
    // ============================================================
    async function iniciarTesteAV() {
        const video       = document.getElementById('tav-video');
        const placeholder = document.getElementById('tav-video-placeholder');
        const badgeRes    = document.getElementById('tav-badge-res');

        try {
            tavStream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: { echoCancellation: true, noiseSuppression: true }
            });
        } catch (e) {
            try {
                tavStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                placeholder.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-amber-500 text-2xl"></i><span>Câmera indisponível — só áudio.</span>';
            } catch (e2) {
                placeholder.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-amber-500 text-2xl"></i><span>Permissão de câmera/microfone negada.<br><small class="text-stone-500">Libere no navegador e recarregue.</small></span>';
                document.getElementById('tav-status-mic').textContent = 'Sem acesso';
                return;
            }
        }

        // -------- Preview da câmera --------
        if (tavStream.getVideoTracks().length) {
            video.srcObject = tavStream;
            placeholder.style.display = 'none';

            // Resolução
            const track = tavStream.getVideoTracks()[0];
            const settings = track.getSettings();
            if (settings.width && settings.height) {
                badgeRes.textContent = settings.width + '×' + settings.height;
                badgeRes.classList.remove('hidden');
            }
        }

        // -------- Medidor de mic --------
        if (tavStream.getAudioTracks().length) {
            iniciarMedidorMic(tavStream);
        }

        // -------- Lista de dispositivos --------
        await listarDispositivos();
    }

    // ============================================================
    // Medidor de microfone
    // ============================================================
    function iniciarMedidorMic(stream) {
        if (!stream.getAudioTracks().length) return;
        try {
            tavAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const analisador = tavAudioCtx.createAnalyser();
            analisador.fftSize = 256;
            tavAudioCtx.createMediaStreamSource(stream).connect(analisador);

            const dados = new Uint8Array(analisador.frequencyBinCount);
            const barra = document.getElementById('tav-barra-mic');
            const status = document.getElementById('tav-status-mic');

            status.textContent = 'Fale algo para testar';
            status.className = 'text-[10px] font-bold text-stone-500';

            tavMedidor = setInterval(() => {
                analisador.getByteFrequencyData(dados);
                const media = dados.reduce((a, b) => a + b, 0) / dados.length;
                const pct = Math.min(100, Math.round(media * 2));
                barra.style.width = pct + '%';

                if (pct > 8) {
                    status.textContent = 'Captando áudio ✓';
                    status.className = 'text-[10px] font-bold text-emerald-600';
                } else {
                    status.textContent = 'Fale algo para testar';
                    status.className = 'text-[10px] font-bold text-stone-500';
                }
            }, 100);
        } catch (e) {}
    }

    // ============================================================
    // Testar caixa de som (bip)
    // ============================================================
    window.testarAltoFalante = function () {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.connect(gain);
            gain.connect(ctx.destination);

            // Melodia curta (3 notas)
            osc.type = 'sine';
            osc.frequency.setValueAtTime(523.25, ctx.currentTime);       // C5
            osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.2); // E5
            osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.4); // G5

            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.7);

            osc.start();
            osc.stop(ctx.currentTime + 0.7);

            document.getElementById('tav-status-speaker').textContent = '🔊 Você escutou o som?';
            document.getElementById('tav-status-speaker').className = 'text-[10px] text-emerald-600 font-bold';

            setTimeout(() => {
                document.getElementById('tav-status-speaker').textContent = 'Clique novamente para testar outra vez.';
                document.getElementById('tav-status-speaker').className = 'text-[10px] text-stone-500';
            }, 2500);
        } catch (e) {
            document.getElementById('tav-status-speaker').textContent = 'Navegador bloqueou o áudio.';
        }
    };

    // ============================================================
    // Listar dispositivos
    // ============================================================
    async function listarDispositivos() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const selCam = document.getElementById('tav-sel-camera');
            const selMic = document.getElementById('tav-sel-mic');

            selCam.innerHTML = '';
            selMic.innerHTML = '';

            const cams = devices.filter(d => d.kind === 'videoinput');
            const mics = devices.filter(d => d.kind === 'audioinput');

            if (cams.length === 0) {
                selCam.innerHTML = '<option>Nenhuma câmera</option>';
            } else {
                cams.forEach((d, i) => {
                    const opt = document.createElement('option');
                    opt.value = d.deviceId;
                    opt.textContent = d.label || 'Câmera ' + (i + 1);
                    selCam.appendChild(opt);
                });
            }

            if (mics.length === 0) {
                selMic.innerHTML = '<option>Nenhum microfone</option>';
            } else {
                mics.forEach((d, i) => {
                    const opt = document.createElement('option');
                    opt.value = d.deviceId;
                    opt.textContent = d.label || 'Microfone ' + (i + 1);
                    selMic.appendChild(opt);
                });
            }
        } catch (e) {}
    }

    // ============================================================
    // Trocar dispositivo
    // ============================================================
    document.getElementById('tav-sel-camera').addEventListener('change', reiniciarStream);
    document.getElementById('tav-sel-mic').addEventListener('change', reiniciarStream);

    async function reiniciarStream() {
        pararTesteAV();
        const video = document.getElementById('tav-video');
        video.srcObject = null;

        const camId = document.getElementById('tav-sel-camera').value;
        const micId = document.getElementById('tav-sel-mic').value;

        const constraints = {
            video: camId ? { deviceId: { exact: camId } } : true,
            audio: micId ? { deviceId: { exact: micId } } : true
        };

        try {
            tavStream = await navigator.mediaDevices.getUserMedia(constraints);
            if (tavStream.getVideoTracks().length) {
                video.srcObject = tavStream;
                document.getElementById('tav-video-placeholder').style.display = 'none';
            }
            if (tavStream.getAudioTracks().length) {
                iniciarMedidorMic(tavStream);
            }
        } catch (e) {
            console.warn('[tav] Falha ao trocar dispositivo:', e);
        }
    }

    // ============================================================
    // Parar tudo
    // ============================================================
    function pararTesteAV() {
        if (tavMedidor) { clearInterval(tavMedidor); tavMedidor = null; }
        if (tavAudioCtx) { try { tavAudioCtx.close(); } catch (e) {} tavAudioCtx = null; }
        if (tavStream) {
            tavStream.getTracks().forEach(t => t.stop());
            tavStream = null;
        }
        const barra = document.getElementById('tav-barra-mic');
        if (barra) barra.style.width = '0%';
    }
})();
</script>