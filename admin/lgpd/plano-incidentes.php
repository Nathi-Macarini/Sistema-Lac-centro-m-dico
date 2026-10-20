<?php
require_once __DIR__ . '/../../bootstrap.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plano de Resposta a Incidentes - LGPD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#FAF9F6] text-[#2C2825] font-sans antialiased min-h-screen">

    <header class="bg-white border-b border-[#E6D5B8]/40 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="../../logo_lac.png" alt="LAC" class="h-10 w-auto object-contain">
                <span class="hidden sm:inline text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Painel Administrativo
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="incidentes.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar aos Incidentes</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <article class="bg-white p-8 md:p-12 rounded-3xl border border-[#E6D5B8]/40 shadow-sm">

            <header class="border-b border-[#E6D5B8] pb-6 mb-8">
                <span class="text-xs font-bold text-red-700 bg-red-50 border border-red-200 px-3 py-1 rounded-full uppercase tracking-wider">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Documento de Governança
                </span>
                <h1 class="text-3xl font-serif text-[#3E352E] mt-3">
                    Plano de Resposta a Incidentes de Segurança
                </h1>
                <p class="text-sm text-stone-500 mt-2">
                    Em conformidade com o art. 48 da LGPD e as diretrizes da ANPD.
                </p>
            </header>

            <div class="prose prose-stone max-w-none
                        [&_h2]:text-xl [&_h2]:font-serif [&_h2]:text-[#3E352E] [&_h2]:mt-8 [&_h2]:mb-3 [&_h2]:pb-2 [&_h2]:border-b [&_h2]:border-[#E6D5B8]
                        [&_h3]:text-base [&_h3]:font-bold [&_h3]:text-[#3E352E] [&_h3]:mt-5 [&_h3]:mb-2
                        [&_p]:text-sm [&_p]:leading-relaxed [&_p]:text-stone-700 [&_p]:my-3
                        [&_ul]:text-sm [&_ul]:text-stone-700 [&_ul]:pl-5 [&_ul]:my-3 [&_ul]:list-disc
                        [&_li]:my-1 [&_strong]:text-[#3E352E]">

                <h2>1. Objetivo</h2>
                <p>
                    Este Plano estabelece o processo de detecção, contenção, análise e comunicação de incidentes de
                    segurança envolvendo dados pessoais tratados pela <strong>LAC Centro Médico</strong>, conforme
                    exigido pelo art. 48 da LGPD.
                </p>

                <h2>2. Definição de incidente</h2>
                <p>
                    Considera-se incidente de segurança qualquer evento adverso, confirmado ou sob suspeita, relacionado
                    à violação de confidencialidade, integridade ou disponibilidade de dados pessoais, incluindo:
                </p>
                <ul>
                    <li>Acesso não autorizado a dados pessoais;</li>
                    <li>Vazamento de credenciais;</li>
                    <li>Perda ou roubo de dispositivos;</li>
                    <li>Exfiltração de dados por atacantes;</li>
                    <li>Erro humano no tratamento de dados;</li>
                    <li>Falhas de configuração que exponham dados.</li>
                </ul>

                <h2>3. Classificação de gravidade</h2>

                <h3>3.1. Baixa</h3>
                <ul>
                    <li>Incidente contido em ambiente interno, sem exposição externa;</li>
                    <li>Dados não sensíveis;</li>
                    <li>Poucos titulares afetados (&lt;10).</li>
                </ul>

                <h3>3.2. Média</h3>
                <ul>
                    <li>Exposição interna entre colaboradores não autorizados;</li>
                    <li>Dados pessoais comuns (nome, e-mail);</li>
                    <li>10 a 100 titulares afetados.</li>
                </ul>

                <h3>3.3. Alta</h3>
                <ul>
                    <li>Exposição externa limitada;</li>
                    <li>Dados pessoais sensíveis (CPF, dados de saúde);</li>
                    <li>100 a 1.000 titulares afetados.</li>
                </ul>

                <h3>3.4. Crítica</h3>
                <ul>
                    <li>Vazamento massivo ou público;</li>
                    <li>Dados sensíveis de saúde expostos;</li>
                    <li>Mais de 1.000 titulares afetados;</li>
                    <li>Risco iminente de dano relevante aos titulares.</li>
                </ul>

                <h2>4. Fluxo de resposta</h2>

                <h3>Fase 1 — Detecção (0 a 4 horas)</h3>
                <ul>
                    <li>Identificar a origem do incidente (alertas, logs, denúncias);</li>
                    <li>Registrar no sistema (<code>admin/lgpd/incidentes.php</code>) com protocolo único;</li>
                    <li>Notificar o DPO imediatamente.</li>
                </ul>

                <h3>Fase 2 — Contenção (4 a 24 horas)</h3>
                <ul>
                    <li>Bloquear acessos suspeitos;</li>
                    <li>Revogar credenciais comprometidas;</li>
                    <li>Isolar sistemas afetados;</li>
                    <li>Preservar evidências (não apagar logs).</li>
                </ul>

                <h3>Fase 3 — Análise (24 a 72 horas)</h3>
                <ul>
                    <li>Determinar causa raiz;</li>
                    <li>Identificar titulares e dados comprometidos;</li>
                    <li>Avaliar risco aos titulares;</li>
                    <li>Decidir sobre comunicação à ANPD e aos titulares.</li>
                </ul>

                <h3>Fase 4 — Comunicação (quando aplicável)</h3>
                <p>
                    A ANPD deve ser comunicada em <strong>prazo razoável</strong> quando o incidente puder acarretar
                    risco ou dano relevante aos titulares. A comunicação deve conter, no mínimo:
                </p>
                <ul>
                    <li>Descrição da natureza dos dados afetados;</li>
                    <li>Informações sobre os titulares envolvidos;</li>
                    <li>Indicação das medidas técnicas e de segurança utilizadas;</li>
                    <li>Riscos relacionados ao incidente;</li>
                    <li>Motivos da demora, se não foi imediata;</li>
                    <li>Medidas adotadas para reverter ou mitigar os efeitos.</li>
                </ul>
                <p>
                    Quando o risco for relevante, os <strong>titulares afetados</strong> também devem ser comunicados
                    diretamente.
                </p>

                <h3>Fase 5 — Remediação e encerramento (após 72 horas)</h3>
                <ul>
                    <li>Implementar medidas corretivas;</li>
                    <li>Revisar processos e controles;</li>
                    <li>Documentar lições aprendidas;</li>
                    <li>Encerrar o incidente no sistema.</li>
                </ul>

                <h2>5. Responsabilidades</h2>
                <ul>
                    <li><strong>DPO:</strong> coordena a resposta, decide sobre comunicações, é o ponto de contato com a ANPD;</li>
                    <li><strong>Equipe de TI:</strong> executa contenção técnica, preserva evidências, analisa causa raiz;</li>
                    <li><strong>Direção:</strong> aprova comunicações externas e medidas de maior impacto;</li>
                    <li><strong>Todos os colaboradores:</strong> devem reportar imediatamente qualquer suspeita de incidente.</li>
                </ul>

                <h2>6. Prazos de referência</h2>
                <div class="overflow-x-auto my-4">
                    <table class="w-full text-sm border border-stone-200">
                        <thead class="bg-[#F9F4EC]">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-stone-600 uppercase border-b">Etapa</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-stone-600 uppercase border-b">Prazo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr><td class="px-4 py-2">Detecção e registro</td><td class="px-4 py-2">Até 4 horas</td></tr>
                            <tr><td class="px-4 py-2">Notificação ao DPO</td><td class="px-4 py-2">Imediata</td></tr>
                            <tr><td class="px-4 py-2">Contenção</td><td class="px-4 py-2">Até 24 horas</td></tr>
                            <tr><td class="px-4 py-2">Análise e decisão</td><td class="px-4 py-2">Até 72 horas</td></tr>
                            <tr><td class="px-4 py-2">Comunicação ANPD / titulares</td><td class="px-4 py-2">Prazo razoável, preferencialmente até 72h após confirmação</td></tr>
                            <tr><td class="px-4 py-2">Encerramento e lições aprendidas</td><td class="px-4 py-2">Até 30 dias</td></tr>
                        </tbody>
                    </table>
                </div>

                <h2>7. Registro</h2>
                <p>
                    Todos os incidentes devem ser registrados em
                    <a href="incidentes.php">/admin/lgpd/incidentes.php</a>, com:
                </p>
                <ul>
                    <li>Protocolo único;</li>
                    <li>Descrição detalhada;</li>
                    <li>Gravidade;</li>
                    <li>Medidas de contenção, causa raiz e corretivas;</li>
                    <li>Comunicações realizadas (ANPD e titulares).</li>
                </ul>

                <h2>8. Revisão</h2>
                <p>
                    Este Plano deve ser revisado anualmente ou após qualquer incidente de gravidade alta ou crítica.
                </p>

                <div class="mt-10 pt-6 border-t border-[#E6D5B8] text-xs text-stone-500">
                    <strong>LAC Centro Médico</strong> · CNPJ 55.807.708/0001-40 · CNES 8133670<br>
                    Encarregado (DPO): consulte <a href="config.php">as configurações</a>.
                </div>
            </div>

            <div class="mt-8 flex justify-center">
                <a href="incidentes.php"
                   class="inline-flex items-center gap-2 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-3 rounded-xl font-medium transition shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation"></i> Ver incidentes registrados
                </a>
            </div>
        </article>
    </main>

    <?php include __DIR__ . '/../../includes/rodape.php'; ?>
</body>
</html>