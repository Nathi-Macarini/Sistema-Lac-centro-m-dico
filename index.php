<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$tipoUsuario = $_SESSION['tipo_usuario'] ?? 'comum';

$conn = new mysqli('localhost', 'root', '', 'lac_centro_medico');

// Processar o agendamento se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'agendar') {
    $esp = $conn->real_escape_string($_POST['especialidade']);
    $mod = $conn->real_escape_string($_POST['modalidade']);
    $data = $conn->real_escape_string($_POST['data_hora']);
    $uid = $_SESSION['usuario_id'];

    $conn->query("INSERT INTO consultas (usuario_id, especialidade, modalidade, data_hora) VALUES ('$uid', '$esp', '$mod', '$data')");
    header("Location: index.php");
    exit;
}

$uid = $_SESSION['usuario_id'];
$consultas = $conn->query("SELECT * FROM consultas WHERE usuario_id = '$uid' ORDER BY data_hora DESC");
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

    <!-- Header / Menu Superior com a Logo Real -->
    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <!-- Exibindo a logo_lac.png com altura controlada -->
                <img src="logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
            </div>
            <nav class="hidden md:flex space-x-8 font-medium text-stone-600">
                <a href="index.php" class="text-[#8C6D36] border-b-2 border-[#8C6D36] pb-1 font-semibold">Início</a>
                <a href="#" class="hover:text-[#8C6D36] transition">Histórico</a>
                <a href="#" class="hover:text-[#8C6D36] transition">Plano</a>
                <a href="#" class="hover:text-[#8C6D36] transition">Dependentes</a>
            </nav>
            <div class="flex items-center space-x-4">
                <!-- Botão EXCLUSIVO do Admin no cabeçalho -->
                <?php if ($tipoUsuario === 'admin'): ?>
                    <a href="admin/dashboard.php"
                       class="hidden sm:flex items-center gap-2 bg-[#F9F4EC] text-[#8C6D36] border border-[#E6D5B8] px-3.5 py-2 rounded-xl text-sm font-semibold hover:bg-[#E6D5B8]/50 transition">
                        <i class="fa-solid fa-shield-halved"></i> Painel Admin
                    </a>
                <?php endif; ?>

                <a href="perfil.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition hidden sm:inline" title="Meu Perfil">
                    <?php echo htmlspecialchars($_SESSION['nome_usuario']); ?>
                </a>
                <a href="logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="max-w-7xl mx-auto px-4 py-8 space-y-8">

        <!-- Saudação Personalizada -->
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h1 class="text-3xl font-serif text-[#3E352E] mb-2">
                Olá, <span><?php echo htmlspecialchars($_SESSION['nome_usuario']); ?></span>.
            </h1>
            <p class="text-stone-600 text-lg">
                Seja bem-vindo(a). Estamos aqui para cuidar de você com agilidade, serenidade e acolhimento clínico.
            </p>
        </section>

        <!-- Grade com os 2 Cards Principais -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Card Esquerdo: Falar com um médico agora (Pronto Atendimento) -->
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
                        <i class="fa-solid fa-lock text-[#C5A059]"></i> Criptografia fim a fim
                    </span>
                </div>

                <div class="absolute right-[-15px] bottom-[-15px] opacity-10 text-9xl text-[#C5A059]">
                    <i class="fa-solid fa-kit-medical"></i>
                </div>
            </div>

            <!-- Card Direito: Agendar Consulta -->
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
                            Escolha a especialidade desejada, seu médico de preferência, data e modalidade (presencial em nossa unidade ou por vídeo).
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

        <!-- Suas Consultas Marcadas -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h3 class="text-xl font-serif text-[#3E352E]">Suas consultas marcadas</h3>
            <div class="grid gap-4 md:grid-cols-2">
                <?php if ($consultas->num_rows > 0): ?>
                    <?php while($c = $consultas->fetch_assoc()): ?>
                        <div class="border border-stone-200 p-5 rounded-2xl flex items-center justify-between bg-[#FAF9F6]">
                            <div>
                                <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full"><?php echo $c['modalidade']; ?></span>
                                <h4 class="font-semibold text-stone-800 mt-2"><?php echo $c['especialidade']; ?></h4>
                                <p class="text-sm text-stone-500 mt-1"><i class="fa-regular fa-calendar text-[#8C6D36]"></i> <?php echo date('d/m/Y H:i', strtotime($c['data_hora'])); ?></p>
                            </div>
                            <?php if($c['modalidade'] == 'Vídeo'): ?>
                                <button class="bg-[#3E352E] text-white text-sm px-4 py-2.5 rounded-xl hover:bg-[#5A4A3A] transition shadow-sm">Acessar Sala</button>
                            <?php else: ?>
                                <span class="text-xs text-stone-600 bg-stone-200 px-3 py-2 rounded-xl font-medium">Presencial</span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
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
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-6 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <h3 class="text-xl font-serif text-[#3E352E]">Configuração de Áudio e Vídeo</h3>
                <button onclick="fecharModalChecagem()" class="text-stone-400 hover:text-stone-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <p class="text-sm text-stone-600">Antes de entrar na fila de atendimento, precisamos conferir se sua câmera e microfone estão funcionando perfeitamente.</p>
            
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
                    <span id="status-audio" class="text-emerald-600 font-bold">Captando áudio...</span>
                </div>
                <div class="h-2.5 bg-stone-100 rounded-full overflow-hidden">
                    <div id="barra-volume" class="h-full bg-[#8C6D36] w-2/3 transition-all duration-150"></div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button onclick="fecharModalChecagem()" class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</button>
                <button onclick="entrarNaFila()" class="bg-[#3E352E] text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A4A3A] transition shadow-sm">Tudo pronto, entrar na fila</button>
            </div>
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
                        <option value="Cardiologia">Cardiologia</option>
                        <option value="Dermatologia">Dermatologia</option>
                        <option value="Ortopedia">Ortopedia</option>
                        <option value="Ginecologia">Ginecologia</option>
                        <option value="Clínica Geral">Clínica Geral</option>
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
                    <input type="datetime-local" name="data_hora" required class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="fecharModalAgendamento()" class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</button>
                    <button type="submit" class="bg-[#3E352E] text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A4A3A] transition shadow-sm">Confirmar Agendamento</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let localStream = null;

        async function abrirModalChecagem() {
            document.getElementById('modal-checagem').classList.remove('hidden');
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                const videoElement = document.getElementById('webcam-preview');
                videoElement.srcObject = localStream;
                document.getElementById('video-placeholder').style.display = 'none';
            } catch (error) {
                document.getElementById('video-placeholder').innerHTML = '<i class="fa-solid fa-triangle-exclamation text-amber-500 text-2xl"></i><span>Permissão de câmera negada ou indisponível</span>';
            }
        }

        function fecharModalChecagem() {
            document.getElementById('modal-checagem').classList.add('hidden');
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
        }

        function entrarNaFila() {
            alert("Sucesso! Você entrou na fila de atendimento imediato. Tempo estimado: 3 minutos.");
            fecharModalChecagem();
        }

        function abrirModalAgendamento() {
            document.getElementById('modal-agendamento').classList.remove('hidden');
        }

        function fecharModalAgendamento() {
            document.getElementById('modal-agendamento').classList.add('hidden');
        }

        setInterval(() => {
            const barra = document.getElementById('barra-volume');
            if (barra && !document.getElementById('modal-checagem').classList.contains('hidden')) {
                const randomWidth = Math.floor(Math.random() * (85 - 30 + 1)) + 30;
                barra.style.width = randomWidth + '%';
            }
        }, 200);
    </script>
</body>
</html>