<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/conexao.php';

use App\LGPD;

$dpoEmail = LGPD::config($conn, 'dpo_email', 'atendimento@laccentromedico.com.br');
$versao   = LGPD::config($conn, 'politica_versao', 'v1.0');

$tipoUsuario = $_SESSION['tipo_usuario'] ?? null;
$nomeUsuario = $_SESSION['nome_usuario'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Cookies - LAC Centro Médico</title>
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
                <a href="<?php echo $nomeUsuario ? 'index.php' : 'login.php'; ?>" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36]">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar</span>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">
        <article class="bg-white p-8 md:p-12 rounded-3xl border border-[#E6D5B8]/40 shadow-sm">

            <header class="border-b border-[#E6D5B8] pb-6 mb-8">
                <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full uppercase tracking-wider">
                    <i class="fa-solid fa-cookie-bite mr-1"></i> Documento Oficial
                </span>
                <h1 class="text-3xl font-serif text-[#3E352E] mt-3">
                    Política de Cookies
                </h1>
                <p class="text-sm text-stone-500 mt-2">
                    Versão <?php echo htmlspecialchars($versao); ?> · Última atualização: <?php echo date('d/m/Y'); ?>
                </p>
            </header>

            <div class="prose prose-stone max-w-none
                        [&_h2]:text-xl [&_h2]:font-serif [&_h2]:text-[#3E352E] [&_h2]:mt-8 [&_h2]:mb-3 [&_h2]:pb-2 [&_h2]:border-b [&_h2]:border-[#E6D5B8]
                        [&_p]:text-sm [&_p]:leading-relaxed [&_p]:text-stone-700 [&_p]:my-3
                        [&_ul]:text-sm [&_ul]:text-stone-700 [&_ul]:pl-5 [&_ul]:my-3 [&_ul]:list-disc
                        [&_li]:my-1 [&_strong]:text-[#3E352E] [&_a]:text-[#8C6D36] [&_a]:underline">

                <h2>1. O que são cookies</h2>
                <p>
                    Cookies são pequenos arquivos de texto armazenados no seu navegador quando você visita um site.
                    Eles permitem que o site se "lembre" de você entre visitas, garantindo funcionalidades como login,
                    preferências e segurança.
                </p>

                <h2>2. Quais cookies usamos</h2>

                <h3 class="text-base font-bold text-[#3E352E] mt-5 mb-2">2.1. Cookies essenciais</h3>
                <p>São indispensáveis para o funcionamento do site:</p>
                <ul>
                    <li>
                        <strong>PHPSESSID</strong> — identifica sua sessão enquanto você navega. Sem ele, você não
                        consegue permanecer logado.
                    </li>
                    <li>
                        <strong>lac_cookies_consent</strong> — armazena sua escolha sobre os cookies.
                    </li>
                </ul>
                <p>
                    <strong>Base legal:</strong> execução de contrato (art. 7º, V) e legítimo interesse (art. 7º, IX).
                    <br>
                    <strong>Duração:</strong> sessão (PHPSESSID) e 12 meses (lac_cookies_consent).
                </p>

                <h3 class="text-base font-bold text-[#3E352E] mt-5 mb-2">2.2. Cookies analíticos</h3>
                <p>
                    Seriam usados para medir audiência e entender como o site é utilizado.
                    <strong class="text-amber-700">Atualmente não utilizamos nenhum cookie analítico.</strong>
                </p>

                <h3 class="text-base font-bold text-[#3E352E] mt-5 mb-2">2.3. Cookies de marketing</h3>
                <p>
                    Seriam usados para exibir anúncios personalizados.
                    <strong class="text-amber-700">Atualmente não utilizamos nenhum cookie de marketing.</strong>
                </p>

                <h2>3. Como você controla os cookies</h2>
                <p>Você pode:</p>
                <ul>
                    <li><strong>Aceitar todos</strong> os cookies (essenciais + opcionais);</li>
                    <li><strong>Aceitar apenas os essenciais</strong> (recomendado);</li>
                    <li><strong>Personalizar</strong> cada categoria;</li>
                    <li><strong>Revogar</strong> sua escolha a qualquer momento, clicando no ícone de cookie
                        <i class="fa-solid fa-cookie-bite"></i> no canto inferior direito.</li>
                </ul>
                <p>
                    Você também pode gerenciar cookies diretamente nas configurações do seu navegador. Note que
                    bloquear os cookies essenciais pode impedir o login no site.
                </p>

                <h2>4. Base legal e consentimento</h2>
                <p>
                    Nos termos da LGPD (Lei 13.709/2018), os cookies essenciais são tratados com base em
                    <em>execução de contrato</em> e <em>legítimo interesse</em>. Cookies opcionais dependeriam de
                    <em>consentimento</em> (art. 7º, I) — mas como não usamos opcionais, não há coleta para essas
                    finalidades.
                </p>

                <h2>5. Registro de consentimento</h2>
                <p>
                    Toda vez que você escolhe uma preferência, registramos (para fins de auditoria):
                    data/hora, endereço IP, user-agent, versão da política e as categorias aceitas.
                    Esse registro é mantido por <strong>6 meses</strong> e pode ser consultado via DPO.
                </p>

                <h2>6. Contato</h2>
                <p>
                    Dúvidas sobre cookies? Fale com o Encarregado de Proteção de Dados:
                    <a href="mailto:<?php echo htmlspecialchars($dpoEmail); ?>"><?php echo htmlspecialchars($dpoEmail); ?></a>.
                </p>

                <div class="mt-10 pt-6 border-t border-[#E6D5B8] text-xs text-stone-500">
                    <strong>LAC Centro Médico</strong> · CNPJ 55.807.708/0001-40 · CNES 8133670<br>
                    Av. Maringá, 2729 — Atuba, Pinhais/PR — CEP 83324-442
                </div>
            </div>

            <div class="mt-8 flex justify-center gap-3">
                <button onclick="abrirBannerCookies()"
                        class="inline-flex items-center gap-2 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-3 rounded-xl font-medium transition shadow-sm">
                    <i class="fa-solid fa-cookie-bite"></i> Gerenciar minhas preferências
                </button>
            </div>
        </article>
    </main>

    <?php include __DIR__ . '/includes/rodape.php'; ?>
</body>
</html>