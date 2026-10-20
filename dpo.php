<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/conexao.php';

use App\LGPD;

$dpoNome  = LGPD::config($conn, 'dpo_nome', '');
$dpoEmail = LGPD::config($conn, 'dpo_email', 'atendimento@laccentromedico.com.br');
$dpoTel   = LGPD::config($conn, 'dpo_telefone', '(41) 3500-8072');

$nomeUsuario = $_SESSION['nome_usuario'] ?? null;
$tipoUsuario = $_SESSION['tipo_usuario'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encarregado de Proteção de Dados (DPO) - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="index.php" class="flex items-center space-x-3">
                <img src="logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
            </a>
            <div class="flex items-center gap-4">
                <?php if ($nomeUsuario): ?>
                    <a href="index.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36]">
                        <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-medium text-[#8C6D36] hover:underline">
                        <i class="fa-solid fa-right-to-bracket"></i> Entrar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8 space-y-6">

        <section class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-2xl bg-[#F9F4EC] border border-[#E6D5B8] flex items-center justify-center text-[#8C6D36] text-2xl shrink-0">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-serif text-[#3E352E]">Encarregado de Proteção de Dados</h1>
                    <p class="text-sm text-stone-600 mt-2">
                        Nos termos do art. 41 da Lei Geral de Proteção de Dados (LGPD — Lei 13.709/2018),
                        a LAC Centro Médico nomeou um Encarregado pelo Tratamento de Dados Pessoais (DPO),
                        responsável por orientar e fiscalizar o cumprimento das normas de proteção de dados.
                    </p>
                </div>
            </div>
        </section>

        <!-- Cartão do DPO -->
        <section class="bg-gradient-to-br from-[#3E352E] to-[#5A4A3A] text-white p-8 rounded-3xl shadow-xl border border-[#C5A059]/30">
            <div class="space-y-4">
                <div>
                    <span class="text-xs font-bold text-[#F4E8D1] bg-white/10 border border-white/20 px-3 py-1 rounded-full uppercase tracking-wider">
                        <i class="fa-solid fa-user-shield text-[#C5A059] mr-1"></i> Encarregado (DPO)
                    </span>
                    <?php if ($dpoNome): ?>
                        <h2 class="text-2xl font-serif text-white mt-3"><?php echo htmlspecialchars($dpoNome); ?></h2>
                    <?php else: ?>
                        <h2 class="text-2xl font-serif text-white mt-3">Canal de Privacidade</h2>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-white/10">
                    <a href="mailto:<?php echo htmlspecialchars($dpoEmail); ?>?subject=Contato%20DPO%20-%20LAC%20Centro%20M%C3%A9dico"
                       class="flex items-center gap-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-4 transition group">
                        <div class="w-10 h-10 rounded-xl bg-[#C5A059]/20 border border-[#C5A059]/30 flex items-center justify-center text-[#F4E8D1]">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold text-stone-300 uppercase tracking-wider">E-mail</div>
                            <div class="text-sm text-white break-all group-hover:text-[#F4E8D1] transition"><?php echo htmlspecialchars($dpoEmail); ?></div>
                        </div>
                    </a>

                    <?php if ($dpoTel): ?>
                    <a href="tel:<?php echo preg_replace('/\D/', '', $dpoTel); ?>"
                       class="flex items-center gap-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-4 transition group">
                        <div class="w-10 h-10 rounded-xl bg-[#C5A059]/20 border border-[#C5A059]/30 flex items-center justify-center text-[#F4E8D1]">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold text-stone-300 uppercase tracking-wider">Telefone</div>
                            <div class="text-sm text-white"><?php echo htmlspecialchars($dpoTel); ?></div>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- O que o DPO faz -->
        <section class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-sm">
            <h2 class="text-xl font-serif text-[#3E352E] mb-4">
                <i class="fa-solid fa-circle-question text-[#8C6D36] mr-1"></i>
                Quando procurar o DPO
            </h2>
            <ul class="space-y-3 text-sm text-stone-700">
                <li class="flex items-start gap-3">
                    <i class="fa-solid fa-check text-emerald-600 mt-0.5 shrink-0"></i>
                    <span>Dúvidas sobre como seus dados pessoais são tratados;</span>
                </li>
                <li class="flex items-start gap-3">
                    <i class="fa-solid fa-check text-emerald-600 mt-0.5 shrink-0"></i>
                    <span>Solicitar <strong>acesso</strong>, <strong>correção</strong>, <strong>portabilidade</strong> ou <strong>exclusão</strong> dos seus dados;</span>
                </li>
                <li class="flex items-start gap-3">
                    <i class="fa-solid fa-check text-emerald-600 mt-0.5 shrink-0"></i>
                    <span>Revogar consentimentos concedidos;</span>
                </li>
                <li class="flex items-start gap-3">
                    <i class="fa-solid fa-check text-emerald-600 mt-0.5 shrink-0"></i>
                    <span>Informar sobre compartilhamento de dados com terceiros;</span>
                </li>
                <li class="flex items-start gap-3">
                    <i class="fa-solid fa-check text-emerald-600 mt-0.5 shrink-0"></i>
                    <span>Reportar incidentes ou suspeitas de vazamento;</span>
                </li>
                <li class="flex items-start gap-3">
                    <i class="fa-solid fa-check text-emerald-600 mt-0.5 shrink-0"></i>
                    <span>Reclamar sobre o tratamento dos seus dados.</span>
                </li>
            </ul>
        </section>

        <!-- Como registrar -->
        <section class="bg-white p-8 rounded-3xl border border-[#E6D5B8]/40 shadow-sm space-y-4">
            <h2 class="text-xl font-serif text-[#3E352E]">
                <i class="fa-solid fa-paper-plane text-[#8C6D36] mr-1"></i>
                Como registrar sua solicitação
            </h2>

            <div class="space-y-3 text-sm">
                <div class="bg-[#F9F4EC] border border-[#E6D5B8] rounded-xl p-4">
                    <div class="font-bold text-[#3E352E] mb-1">
                        <i class="fa-solid fa-user-check text-[#8C6D36] mr-1"></i> Se você tem conta no portal
                    </div>
                    <p class="text-stone-600">
                        Acesse a área <strong>Privacidade</strong> do seu painel para registrar a solicitação
                        com protocolo automático e acompanhar a resposta.
                    </p>
                    <?php if ($nomeUsuario): ?>
                        <a href="privacidade.php"
                           class="inline-flex items-center gap-2 mt-3 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-2.5 rounded-xl text-xs font-medium transition">
                            <i class="fa-solid fa-shield-halved"></i> Ir para Privacidade
                        </a>
                    <?php else: ?>
                        <a href="login.php"
                           class="inline-flex items-center gap-2 mt-3 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-2.5 rounded-xl text-xs font-medium transition">
                            <i class="fa-solid fa-right-to-bracket"></i> Fazer login
                        </a>
                    <?php endif; ?>
                </div>

                <div class="bg-[#F9F4EC] border border-[#E6D5B8] rounded-xl p-4">
                    <div class="font-bold text-[#3E352E] mb-1">
                        <i class="fa-solid fa-envelope text-[#8C6D36] mr-1"></i> Se você não tem conta
                    </div>
                    <p class="text-stone-600">
                        Envie um e-mail para
                        <a href="mailto:<?php echo htmlspecialchars($dpoEmail); ?>" class="text-[#8C6D36] underline font-medium">
                            <?php echo htmlspecialchars($dpoEmail); ?>
                        </a>
                        informando seu nome completo e o pedido. Responderemos em até <strong>15 dias corridos</strong>.
                    </p>
                </div>
            </div>
        </section>

        <!-- Links úteis -->
        <section class="bg-white p-6 rounded-3xl border border-[#E6D5B8]/40 shadow-sm">
            <h2 class="text-sm font-bold text-stone-600 uppercase tracking-wider mb-3">Documentos relacionados</h2>
            <div class="flex flex-wrap gap-2">
                <a href="politica-privacidade.php" class="inline-flex items-center gap-2 bg-[#F9F4EC] hover:bg-[#E6D5B8]/50 border border-[#E6D5B8] text-[#5A4A3A] px-4 py-2 rounded-xl text-xs font-medium transition">
                    <i class="fa-solid fa-shield-halved"></i> Política de Privacidade
                </a>
                <a href="cookies.php" class="inline-flex items-center gap-2 bg-[#F9F4EC] hover:bg-[#E6D5B8]/50 border border-[#E6D5B8] text-[#5A4A3A] px-4 py-2 rounded-xl text-xs font-medium transition">
                    <i class="fa-solid fa-cookie-bite"></i> Política de Cookies
                </a>
                <a href="termo-consentimento.php" class="inline-flex items-center gap-2 bg-[#F9F4EC] hover:bg-[#E6D5B8]/50 border border-[#E6D5B8] text-[#5A4A3A] px-4 py-2 rounded-xl text-xs font-medium transition">
                    <i class="fa-solid fa-file-contract"></i> Termo de Consentimento
                </a>
            </div>
        </section>

    </main>

    <?php include __DIR__ . '/includes/rodape.php'; ?>
</body>
</html>