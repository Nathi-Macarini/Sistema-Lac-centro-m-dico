<?php
/**
 * Texto padrão do Termo de Consentimento para Teleconsulta.
 * Versão controlada pela constante TERMO_VERSAO.
 */

const TERMO_VERSAO = 'v1.0';

/**
 * Registra o aceite do termo para um usuário.
 * 
 * Importante: só grava se o usuário NUNCA aceitou o termo de teleconsulta
 * (qualquer modalidade) na versão atual. Assim, o usuário aceita 1x só.
 */
function registrarTermo($conn, int $usuarioId, string $tipo, ?int $consultaId = null): bool {
    // Se o usuário já aceitou o termo de teleconsulta (PA ou agendada), não grava de novo
    if (usuarioAceitouTeleconsulta($conn, $usuarioId)) {
        return true;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    $ua     = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $versao = TERMO_VERSAO;

    $st = $conn->prepare("
        INSERT INTO termos_aceitos
            (usuario_id, termo_tipo, termo_versao, consulta_id, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $st->bind_param('ississ', $usuarioId, $tipo, $versao, $consultaId, $ip, $ua);
    return $st->execute();
}

/**
 * Verifica se o usuário já aceitou o termo (versão atual) para uma consulta.
 * (mantida para compatibilidade)
 */
function termoAceito($conn, int $usuarioId, string $tipo, ?int $consultaId = null): bool {
    $versao = TERMO_VERSAO;
    if ($consultaId) {
        $st = $conn->prepare("
            SELECT id FROM termos_aceitos
            WHERE usuario_id = ? AND termo_tipo = ? AND termo_versao = ? AND consulta_id = ?
            LIMIT 1
        ");
        $st->bind_param('issi', $usuarioId, $tipo, $versao, $consultaId);
    } else {
        $st = $conn->prepare("
            SELECT id FROM termos_aceitos
            WHERE usuario_id = ? AND termo_tipo = ? AND termo_versao = ?
            LIMIT 1
        ");
        $st->bind_param('iss', $usuarioId, $tipo, $versao);
    }
    $st->execute();
    return (bool)$st->get_result()->fetch_assoc();
}

/**
 * Verifica se o usuário já aceitou o termo de teleconsulta (qualquer modalidade:
 * pronto atendimento OU consulta agendada), na versão atual.
 * 
 * Regra: basta aceitar UMA VEZ. Depois disso, todas as consultas seguintes
 * não pedem mais o termo.
 */
function usuarioAceitouTeleconsulta($conn, int $usuarioId): bool {
    $versao = TERMO_VERSAO;
    $st = $conn->prepare("
        SELECT id
        FROM termos_aceitos
        WHERE usuario_id = ?
          AND termo_tipo IN ('teleconsulta_pa', 'teleconsulta_agendada')
          AND termo_versao = ?
        LIMIT 1
    ");
    $st->bind_param('is', $usuarioId, $versao);
    $st->execute();
    return (bool)$st->get_result()->fetch_assoc();
}

/**
 * Retorna o texto do termo em HTML.
 * @param bool $resumido  Se true, mostra versão curta (para modal).
 */
function termoTextoTeleconsulta(bool $resumido = false): string {
    if ($resumido) {
        return <<<HTML
<p><strong>1. Consentimento.</strong> Declaro que concordo voluntariamente em realizar atendimento médico por
teleconsulta (vídeo e/ou mensagem), ciente de suas características e limitações.</p>

<p><strong>2. Sigilo médico.</strong> Todas as informações compartilhadas são protegidas pelo sigilo profissional
(CFM) e pela Lei Geral de Proteção de Dados (LGPD — Lei 13.709/2018).</p>

<p><strong>3. Limitações.</strong> Compreendo que a teleconsulta pode não ser adequada para todas as condições
clínicas e que, em casos de urgência ou emergência, devo procurar atendimento presencial imediatamente.</p>

<p><strong>4. Registro.</strong> Autorizo o registro do atendimento em prontuário eletrônico, com dados de
identificação, evoluções, prescrições e orientações médicas.</p>

<p><strong>5. Direito de recusa.</strong> Posso, a qualquer momento, interromper o atendimento e solicitar
encaminhamento para atendimento presencial, sem prejuízo ao meu cuidado.</p>

<p><strong>6. Privacidade.</strong> Devo realizar a teleconsulta em local privado e seguro, com áudio e vídeo
funcionando, garantindo a confidencialidade das informações.</p>

<p class="text-xs text-stone-500 mt-3">Versão do termo: <strong>v1.0</strong> · Em conformidade com a Resolução CFM
nº 2.314/2022, a LGPD e as diretrizes da LAC Centro Médico.</p>
HTML;
    }

    return <<<HTML
<h2>1. Objeto do consentimento</h2>
<p>Este Termo de Consentimento Livre e Esclarecido ("Termo") tem por objetivo registrar sua concordância em
realizar atendimento médico na modalidade <strong>teleconsulta</strong>, prestado pela LAC Centro Médico
(CNPJ 55.807.708/0001-40 / CNES 8133670), seja por meio de vídeo em tempo real ("LAC Atende" ou
"LAC Teleatendimento"), seja por mensagens assíncronas.</p>

<h2>2. Características da teleconsulta</h2>
<p>A teleconsulta consiste em atendimento médico realizado a distância, com uso de tecnologias de
informação e comunicação, podendo incluir: anamnese, avaliação de sintomas, orientações, prescrições,
solicitação de exames e emissão de atestados e declarações, quando aplicável.</p>

<h2>3. Sigilo médico e proteção de dados</h2>
<p>Todas as informações trocadas durante o atendimento são protegidas pelo sigilo médico, previsto no
Código de Ética Médica e na Resolução CFM nº 2.314/2022, e pela Lei Geral de Proteção de Dados
(LGPD — Lei nº 13.709/2018). Seus dados serão tratados para:</p>
<ul>
    <li>Realização do atendimento e continuidade do cuidado;</li>
    <li>Registro em prontuário eletrônico;</li>
    <li>Emissão de documentos médicos (receitas, atestados, declarações);</li>
    <li>Cumprimento de obrigações legais e regulatórias.</li>
</ul>
<p>Você pode, a qualquer momento, solicitar acesso, correção ou exclusão dos seus dados, nos termos da LGPD,
por meio dos canais de atendimento da clínica.</p>

<h2>4. Limitações e riscos</h2>
<p>A teleconsulta pode não ser adequada para todas as condições clínicas. Em casos de urgência ou emergência
(ex.: dor no peito, falta de ar intensa, sangramentos, perda de consciência), você deve procurar
imediatamente um serviço de pronto atendimento presencial ou acionar o SAMU (192).</p>
<p>Além disso, a qualidade da teleconsulta depende da sua conexão de internet e do funcionamento adequado
de câmera e microfone.</p>

<h2>5. Direitos do paciente</h2>
<p>Você tem o direito de:</p>
<ul>
    <li>Recusar-se a realizar a teleconsulta e solicitar atendimento presencial;</li>
    <li>Interromper o atendimento a qualquer momento, sem prejuízo ao cuidado;</li>
    <li>Solicitar esclarecimentos sobre o diagnóstico, tratamento e alternativas;</li>
    <li>Acessar seu prontuário e documentos emitidos;</li>
    <li>Solicitar segunda opinião médica.</li>
</ul>

<h2>6. Responsabilidades do paciente</h2>
<p>Ao aceitar este Termo, você se compromete a:</p>
<ul>
    <li>Fornecer informações verdadeiras e completas sobre seu estado de saúde;</li>
    <li>Realizar o atendimento em local privado, seguro e com boa iluminação;</li>
    <li>Garantir que sua conexão de internet e dispositivos estejam funcionando;</li>
    <li>Não gravar a consulta sem autorização expressa do médico;</li>
    <li>Cumprir as orientações e prescrições fornecidas pelo profissional.</li>
</ul>

<h2>7. Registro em prontuário</h2>
<p>Todos os atendimentos são registrados em prontuário eletrônico, com identificação do paciente, do médico
responsável (CRM/RQE), data, hora e conteúdo da consulta, conforme exigências do CFM.</p>

<h2>8. Disposições finais</h2>
<p>Este Termo é regido pela legislação brasileira, em especial:</p>
<ul>
    <li>Resolução CFM nº 2.314/2022 (Telemedicina);</li>
    <li>Lei nº 13.709/2018 (LGPD);</li>
    <li>Código de Ética Médica (Resolução CFM nº 2.217/2018).</li>
</ul>
<p>Ao marcar a caixa de aceite, você declara ter lido, compreendido e concordado com todos os termos
acima descritos.</p>

<p class="text-xs text-stone-500 mt-6">
    Versão do termo: <strong>v1.0</strong> · Última atualização: 15 de outubro de 2026<br>
    LAC Centro Médico · CNPJ 55.807.708/0001-40 · CNES 8133670<br>
    Av. Maringá, 2729 — Atuba, Pinhais/PR — CEP 83324-442<br>
    Telefone: (41) 3500-8072 · E-mail: atendimento@laccentromedico.com.br
</p>
HTML;
}