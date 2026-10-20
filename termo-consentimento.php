<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/termo_texto.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termo de Consentimento - LAC Centro Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <img src="logo_lac.png" alt="LAC Centro Médico" class="h-10 w-auto object-contain">
            <button type="button" onclick="voltarInteligente()"
                    class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </button>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">
        <article class="bg-white p-8 md:p-12 rounded-3xl border border-[#E6D5B8]/40 shadow-sm">
            <header class="border-b border-[#E6D5B8] pb-6 mb-6">
                <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full uppercase tracking-wider">
                    <i class="fa-solid fa-file-contract mr-1"></i> Documento Oficial
                </span>
                <h1 class="text-3xl font-serif text-[#3E352E] mt-3">
                    Termo de Consentimento para Teleconsulta
                </h1>
                <p class="text-sm text-stone-500 mt-2">
                    Leia com atenção antes de aceitar. Versão <?php echo TERMO_VERSAO; ?>.
                </p>
            </header>

            <div class="prose prose-stone max-w-none
                        [&_h2]:text-lg [&_h2]:font-serif [&_h2]:text-[#3E352E] [&_h2]:mt-6 [&_h2]:mb-2
                        [&_p]:text-sm [&_p]:leading-relaxed [&_p]:text-stone-700 [&_p]:my-3
                        [&_ul]:text-sm [&_ul]:text-stone-700 [&_ul]:pl-5 [&_ul]:my-3 [&_ul]:list-disc
                        [&_li]:my-1 [&_strong]:text-[#3E352E]">
                <?php echo termoTextoTeleconsulta(false); ?>
            </div>

            <div class="mt-8 pt-6 border-t border-[#E6D5B8] flex justify-center">
                <button type="button" onclick="voltarInteligente()"
                        class="inline-flex items-center gap-2 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-3 rounded-xl font-medium transition shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i> Voltar
                </button>
            </div>
        </article>
    </main>

    <?php include __DIR__ . '/includes/rodape.php'; ?>

    <script>
    function voltarInteligente() {
        // 1) Foi aberto numa aba nova (target="_blank")? Fecha a aba.
        if (window.opener && !window.opener.closed) {
            window.close();
            setTimeout(() => {
                if (!window.closed) {
                    window.location.href = document.referrer || 'index.php';
                }
            }, 200);
            return;
        }

        // 2) Tem histórico anterior na mesma aba? Volta.
        if (window.history.length > 1) {
            window.history.back();
            return;
        }

        // 3) Tem referrer? Vai para ele.
        if (document.referrer) {
            window.location.href = document.referrer;
            return;
        }

        // 4) Fallback final: volta para a home
        window.location.href = 'index.php';
    }
    </script>
</body>
</html>