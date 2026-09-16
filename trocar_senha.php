<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }

$conn = new mysqli('localhost', 'root', '', 'lac_centro_medico');
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

$id = (int)$_SESSION['usuario_id'];
$msg = ""; $tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atual = $_POST['senha_atual'] ?? '';
    $nova  = $_POST['nova_senha'] ?? '';
    $conf  = $_POST['confirmar_senha'] ?? '';

    $row = $conn->query("SELECT senha FROM usuarios WHERE id = $id")->fetch_assoc();

    if (!$row || $row['senha'] !== $atual) {
        $msg = "Senha atual incorreta."; $tipo = "erro";
    } elseif (strlen($nova) < 6) {
        $msg = "A nova senha deve ter pelo menos 6 caracteres."; $tipo = "erro";
    } elseif ($nova !== $conf) {
        $msg = "As senhas não coincidem."; $tipo = "erro";
    } else {
        $novaEsc = $conn->real_escape_string($nova);
        if ($conn->query("UPDATE usuarios SET senha = '$novaEsc' WHERE id = $id")) {
            $msg = "Senha alterada com sucesso!"; $tipo = "sucesso";
        } else {
            $msg = "Erro ao alterar senha: " . $conn->error; $tipo = "erro";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Transição suave da barra de força */
        #barra-forca {
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1),
                        background-color 0.4s ease;
        }
        /* Animação de brilho quando a senha é forte */
        @keyframes brilho {
            0%, 100% { box-shadow: 0 0 0 rgba(16,185,129,0); }
            50%      { box-shadow: 0 0 12px rgba(16,185,129,0.55); }
        }
        .barra-forte {
            animation: brilho 1.8s ease-in-out infinite;
        }
        /* Pulso do texto do nível */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-2px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.25s ease-out; }
    </style>
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <img src="logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
            <a href="perfil.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Perfil
            </a>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 py-8">
        <section class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-xl space-y-6">
            <div class="text-center space-y-2">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] text-xl">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h1 class="text-2xl font-serif text-[#3E352E]">Trocar Senha</h1>
                <p class="text-sm text-stone-500">Confirme sua senha atual para definir uma nova</p>
            </div>

            <?php if ($msg): ?>
                <div class="<?php echo $tipo==='sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Senha Atual</label>
                    <input type="password" name="senha_atual" required
                           class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Nova Senha</label>
                    <input type="password" name="nova_senha" id="nova_senha" required minlength="6"
                           class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">

                    <!-- Medidor de Força -->
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-stone-500">Força da senha</span>
                            <span id="nivel-texto" class="text-xs font-bold text-stone-400 transition-colors duration-300">
                                Digite a senha...
                            </span>
                        </div>

                        <div class="h-2 bg-stone-100 rounded-full overflow-hidden">
                            <div id="barra-forca" class="h-full w-0 bg-stone-300 rounded-full"></div>
                        </div>

                        <!-- Dicas dinâmicas -->
                        <ul class="text-[11px] text-stone-500 space-y-1 pt-1" id="dicas">
                            <li data-regra="len"    class="flex items-center gap-1.5"><i class="fa-regular fa-circle text-stone-300"></i> Pelo menos 8 caracteres</li>
                            <li data-regra="maius"  class="flex items-center gap-1.5"><i class="fa-regular fa-circle text-stone-300"></i> Uma letra maiúscula</li>
                            <li data-regra="minus"  class="flex items-center gap-1.5"><i class="fa-regular fa-circle text-stone-300"></i> Uma letra minúscula</li>
                            <li data-regra="num"    class="flex items-center gap-1.5"><i class="fa-regular fa-circle text-stone-300"></i> Um número</li>
                            <li data-regra="simbol" class="flex items-center gap-1.5"><i class="fa-regular fa-circle text-stone-300"></i> Um símbolo (!@#$%)</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase mb-1.5">Confirmar Nova Senha</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="6"
                           class="w-full border border-stone-300 px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-[#8C6D36] bg-[#FAF9F6]">
                    <p id="msg-match" class="text-xs mt-1.5 hidden"></p>
                </div>

                <button type="submit" id="btn-salvar"
                        class="w-full bg-[#3E352E] hover:bg-[#5A4A3A] text-white py-3.5 rounded-xl font-medium transition shadow-sm">
                    Salvar Nova Senha
                </button>
            </form>
        </section>
    </main>

    <script>
        const inputNova = document.getElementById('nova_senha');
        const inputConf = document.getElementById('confirmar_senha');
        const barra     = document.getElementById('barra-forca');
        const nivelTxt  = document.getElementById('nivel-texto');
        const dicas     = document.getElementById('dicas');

        function avaliarSenha(s) {
            const regras = {
                len:    s.length >= 8,
                maius:  /[A-Z]/.test(s),
                minus:  /[a-z]/.test(s),
                num:    /[0-9]/.test(s),
                simbol: /[^A-Za-z0-9]/.test(s),
            };
            const pontos = Object.values(regras).filter(Boolean).length;
            return { regras, pontos };
        }

        function atualizarDicas(regras) {
            dicas.querySelectorAll('li').forEach(li => {
                const ok = regras[li.dataset.regra];
                const icone = li.querySelector('i');
                if (ok) {
                    icone.className = 'fa-solid fa-circle-check text-emerald-500';
                    li.classList.remove('text-stone-500');
                    li.classList.add('text-emerald-700');
                } else {
                    icone.className = 'fa-regular fa-circle text-stone-300';
                    li.classList.remove('text-emerald-700');
                    li.classList.add('text-stone-500');
                }
            });
        }

        function aplicarNivel(pontos, tamanho) {
            // Níveis: 0 vazio | 1-2 fraca | 3-4 média | 5 forte
            const niveis = {
                0: { w: '0%',   cor: 'bg-stone-300',  txt: 'Digite a senha...', cls: 'text-stone-400' },
                1: { w: '25%',  cor: 'bg-red-500',    txt: 'Muito fraca',      cls: 'text-red-600' },
                2: { w: '45%',  cor: 'bg-orange-500', txt: 'Fraca',            cls: 'text-orange-600' },
                3: { w: '65%',  cor: 'bg-amber-500',  txt: 'Média',            cls: 'text-amber-600' },
                4: { w: '85%',  cor: 'bg-lime-500',   txt: 'Boa',              cls: 'text-lime-600' },
                5: { w: '100%', cor: 'bg-emerald-500',txt: 'Forte',            cls: 'text-emerald-600' },
            };
            const cfg = tamanho === 0 ? niveis[0] : niveis[pontos] || niveis[1];

            barra.style.width = cfg.w;
            barra.className = 'h-full rounded-full ' + cfg.cor + (pontos === 5 ? ' barra-forte' : '');
            nivelTxt.textContent = cfg.txt;
            nivelTxt.className = 'text-xs font-bold transition-colors duration-300 fade-in ' + cfg.cls;
        }

        inputNova.addEventListener('input', () => {
            const s = inputNova.value;
            const { regras, pontos } = avaliarSenha(s);
            atualizarDicas(regras);
            aplicarNivel(pontos, s.length);
            verificarMatch();
        });

        function verificarMatch() {
            const msg = document.getElementById('msg-match');
            const a = inputNova.value, b = inputConf.value;
            if (b.length === 0) {
                msg.classList.add('hidden');
                return;
            }
            msg.classList.remove('hidden');
            if (a === b) {
                msg.textContent = '✓ As senhas coincidem';
                msg.className = 'text-xs mt-1.5 text-emerald-600 font-medium';
            } else {
                msg.textContent = '✗ As senhas não coincidem';
                msg.className = 'text-xs mt-1.5 text-red-600 font-medium';
            }
        }
        inputConf.addEventListener('input', verificarMatch);
    </script>
</body>
</html>