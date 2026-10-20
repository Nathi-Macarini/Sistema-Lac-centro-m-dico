<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/conexao.php';

use App\LGPD;

$dpoNome  = LGPD::config($conn, 'dpo_nome', '');
$dpoEmail = LGPD::config($conn, 'dpo_email', 'atendimento@laccentromedico.com.br');
$dpoTel   = LGPD::config($conn, 'dpo_telefone', '(41) 3500-8072');
$versao   = LGPD::config($conn, 'politica_versao', 'v1.0');

// Se o usuário está logado, mostra no header
$tipoUsuario = $_SESSION['tipo_usuario'] ?? null;
$nomeUsuario = $_SESSION['nome_usuario'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - LAC Centro Médico</title>
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
                    <a href="index.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
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

    <main class="max-w-4xl mx-auto px-4 py-8">
        <article class="bg-white p-8 md:p-12 rounded-3xl border border-[#E6D5B8]/40 shadow-sm">

            <header class="border-b border-[#E6D5B8] pb-6 mb-8">
                <span class="text-xs font-bold text-[#8C6D36] bg-[#F9F4EC] border border-[#E6D5B8] px-3 py-1 rounded-full uppercase tracking-wider">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Documento Oficial
                </span>
                <h1 class="text-3xl font-serif text-[#3E352E] mt-3">
                    Política de Privacidade
                </h1>
                <p class="text-sm text-stone-500 mt-2">
                    Versão <?php echo htmlspecialchars($versao); ?> · Última atualização: <?php echo date('d/m/Y'); ?>
                </p>
            </header>

            <div class="prose prose-stone max-w-none
                        [&_h2]:text-xl [&_h2]:font-serif [&_h2]:text-[#3E352E] [&_h2]:mt-8 [&_h2]:mb-3 [&_h2]:pb-2 [&_h2]:border-b [&_h2]:border-[#E6D5B8]
                        [&_h3]:text-base [&_h3]:font-bold [&_h3]:text-[#3E352E] [&_h3]:mt-5 [&_h3]:mb-2
                        [&_p]:text-sm [&_p]:leading-relaxed [&_p]:text-stone-700 [&_p]:my-3
                        [&_ul]:text-sm [&_ul]:text-stone-700 [&_ul]:pl-5 [&_ul]:my-3 [&_ul]:list-disc
                        [&_li]:my-1 [&_strong]:text-[#3E352E] [&_a]:text-[#8C6D36] [&_a]:underline">

                <h2>1. Quem somos</h2>
                <p>
                    A <strong>LAC Centro Médico</strong> (CNPJ <strong>55.807.708/0001-40</strong> · CNES <strong>8133670</strong>),
                    com sede na Av. Maringá, 2729 — Atuba, Pinhais/PR — CEP 83324-442, é a <strong>controladora</strong> dos
                    dados pessoais coletados por meio deste site e de seus serviços médicos presenciais e de teleconsulta
                    ("LAC Atende" e "LAC Teleatendimento").
                </p>

                <h2>2. Encarregado de Proteção de Dados (DPO)</h2>
                <p>
                    Nos termos do art. 41 da LGPD, indicamos como Encarregado de Proteção de Dados:
                </p>
                <ul>
                    <?php if ($dpoNome): ?>
                        <li><strong>Nome:</strong> <?php echo htmlspecialchars($dpoNome); ?></li>
                    <?php endif; ?>
                    <li><strong>E-mail:</strong> <a href="mailto:<?php echo htmlspecialchars($dpoEmail); ?>"><?php echo htmlspecialchars($dpoEmail); ?></a></li>
                    <?php if ($dpoTel): ?>
                        <li><strong>Telefone:</strong> <?php echo htmlspecialchars($dpoTel); ?></li>
                    <?php endif; ?>
                </ul>
                <p>
                    Você pode contatar o DPO para esclarecer dúvidas sobre esta Política ou exercer seus direitos como
                    titular de dados.
                </p>

                <h2>3. Quais dados coletamos</h2>

                <h3>3.1. Dados fornecidos por você</h3>
                <ul>
                    <li><strong>Cadastro:</strong> nome completo, nome da mãe, e-mail, CPF, telefone, data de nascimento, endereço;</li>
                    <li><strong>Dados de saúde (sensíveis):</strong> queixa principal, histórico da doença, exames, diagnóstico, prescrição, CID-10, alergias, comorbidades, medicamentos em uso;</li>
                    <li><strong>Dados de dependentes:</strong> nome, CPF, data de nascimento e parentesco;</li>
                    <li><strong>Plano de saúde:</strong> operadora, número de carteirinha, validade e acomodação.</li>
                </ul>

                <h3>3.2. Dados coletados automaticamente</h3>
                <ul>
                    <li>Endereço IP, user-agent, data e hora de acesso;</li>
                    <li>Registros de auditoria (login, logout, ações no sistema).</li>
                </ul>

                <h2>4. Finalidades e bases legais</h2>
                <p>Tratamos seus dados pessoais para as seguintes finalidades, com as respectivas bases legais:</p>
                <ul>
                    <li>
                        <strong>Prestação de serviços médicos e teleconsulta</strong> —
                        base legal: <em>execução de contrato</em> (art. 7º, V) e <em>tutela da saúde</em> (art. 11, II, f);
                    </li>
                    <li>
                        <strong>Registro em prontuário eletrônico</strong> —
                        base legal: <em>tutela da saúde</em> (art. 11, II, f) e <em>obrigação legal</em> (art. 7º, II);
                    </li>
                    <li>
                        <strong>Emissão de documentos médicos</strong> (receitas, atestados, declarações) —
                        base legal: <em>tutela da saúde</em> (art. 11, II, f);
                    </li>
                    <li>
                        <strong>Segurança da informação e prevenção a fraudes</strong> —
                        base legal: <em>legítimo interesse</em> (art. 7º, IX);
                    </li>
                    <li>
                        <strong>Comunicações via WhatsApp</strong> —
                        base legal: <em>consentimento</em> (art. 7º, I);
                    </li>
                    <li>
                        <strong>Cumprimento de obrigações legais e regulatórias</strong> (CFM, ANVISA, Receita Federal) —
                        base legal: <em>obrigação legal</em> (art. 7º, II).
                    </li>
                </ul>

                <h2>5. Com quem compartilhamos</h2>
                <p>Não vendemos nem alugamos seus dados. Podemos compartilhá-los apenas com:</p>
                <ul>
                    <li><strong>Conselhos profissionais (CRM/CFM)</strong>, quando exigido por lei;</li>
                    <li><strong>Operadoras de saúde e SUS</strong>, quando aplicável ao seu atendimento;</li>
                    <li><strong>Autoridades judiciais, administrativas ou regulatórias</strong>, mediante requisição legal;</li>
                    <li><strong>WhatsApp (Meta Platforms Inc.)</strong>, quando você nos contata por esse canal — sujeito à política
                        de privacidade da Meta e à transferência internacional de dados sob cláusulas contratuais padrão.</li>
                </ul>

                <h2>6. Por quanto tempo guardamos</h2>
                <ul>
                    <li><strong>Dados clínicos (prontuário, documentos):</strong> 20 anos (Resolução CFM nº 1.821/2007);</li>
                    <li><strong>Dados cadastrais:</strong> 5 anos após o último atendimento;</li>
                    <li><strong>Logs de acesso:</strong> 6 meses (Marco Civil da Internet);</li>
                    <li><strong>Dados financeiros:</strong> 5 anos (legislação fiscal).</li>
                </ul>
                <p>
                    Após esses prazos, os dados são <strong>anonimizados ou eliminados</strong>, salvo hipóteses legais de
                    conservação.
                </p>

                <h2>7. Seus direitos como titular</h2>
                <p>Você pode, a qualquer momento, exercer os direitos previstos no art. 18 da LGPD:</p>
                <ul>
                    <li>Confirmação da existência de tratamento;</li>
                    <li>Acesso aos dados;</li>
                    <li>Correção de dados incompletos, inexatos ou desatualizados;</li>
                    <li>Anonimização, bloqueio ou eliminação de dados desnecessários ou excessivos;</li>
                    <li>Portabilidade dos dados a outro fornecedor;</li>
                    <li>Eliminação dos dados tratados com consentimento;</li>
                    <li>Informação sobre compartilhamentos;</li>
                    <li>Revogação do consentimento.</li>
                </ul>
                <p>
                    Para exercer qualquer um desses direitos, acesse a área
                    <strong>Privacidade</strong> no seu painel de paciente ou envie um e-mail ao DPO.
                </p>

                <h2>8. Segurança da informação</h2>
                <p>Adotamos medidas técnicas e administrativas para proteger seus dados, incluindo:</p>
                <ul>
                    <li><strong>Criptografia AES-256-GCM</strong> para dados pessoais e clínicos sensíveis;</li>
                    <li><strong>Hash SHA-256</strong> para busca de CPF (não armazenamos CPF em texto puro para consulta);</li>
                    <li><strong>Senhas com bcrypt</strong> (algoritmo de hash robusto);</li>
                    <li><strong>Controle de acesso por perfil</strong> (paciente, médico, administrador);</li>
                    <li><strong>Registro de auditoria</strong> de acessos a prontuários;</li>
                    <li><strong>Sessões regeneradas</strong> a cada login;</li>
                    <li><strong>Backups periódicos</strong> com criptografia.</li>
                </ul>

                <h2>9. Transferência internacional de dados</h2>
                <p>
                    O uso do WhatsApp (Meta Platforms Inc., servidores nos EUA) pode implicar transferência internacional
                    de dados, realizada com base em cláusulas contratuais padrão, conforme art. 33 da LGPD.
                </p>
                <p>
                    Os demais dados são armazenados em servidores no Brasil.
                </p>

                <h2>10. Cookies</h2>
                <p>
                    Utilizamos apenas <strong>cookies essenciais</strong> para o funcionamento do site (sessão e autenticação).
                    Não usamos cookies de marketing ou rastreamento.
                </p>

                <h2>11. Incidentes de segurança</h2>
                <p>
                    Em caso de incidente de segurança que possa acarretar risco aos titulares, comunicaremos a
                    <strong>Autoridade Nacional de Proteção de Dados (ANPD)</strong> e os titulares afetados, nos termos do
                    art. 48 da LGPD.
                </p>

                <h2>12. Alterações desta Política</h2>
                <p>
                    Esta Política pode ser atualizada periodicamente. Recomendamos consultá-la regularmente.
                    A versão atual é a <strong><?php echo htmlspecialchars($versao); ?></strong>.
                </p>

                <h2>13. Legislação aplicável</h2>
                <ul>
                    <li><strong>LGPD</strong> — Lei nº 13.709/2018;</li>
                    <li><strong>Marco Civil da Internet</strong> — Lei nº 12.965/2014;</li>
                    <li><strong>Resolução CFM nº 2.314/2022</strong> — Telemedicina;</li>
                    <li><strong>Resolução CFM nº 1.821/2007</strong> — Prontuário eletrônico;</li>
                    <li><strong>Código de Ética Médica</strong> (Resolução CFM nº 2.217/2018).</li>
                </ul>

                <div class="mt-10 pt-6 border-t border-[#E6D5B8] text-xs text-stone-500">
                    <strong>LAC Centro Médico</strong> · CNPJ 55.807.708/0001-40 · CNES 8133670<br>
                    Av. Maringá, 2729 — Atuba, Pinhais/PR — CEP 83324-442<br>
                    Telefone: (41) 3500-8072 · E-mail: atendimento@laccentromedico.com.br
                </div>
            </div>

            <div class="mt-8 flex justify-center">
                <a href="<?php echo $nomeUsuario ? 'index.php' : 'login.php'; ?>"
                   class="inline-flex items-center gap-2 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-3 rounded-xl font-medium transition shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i> Voltar
                </a>
            </div>
        </article>
    </main>

    <?php include __DIR__ . '/includes/rodape.php'; ?>
</body>
</html>