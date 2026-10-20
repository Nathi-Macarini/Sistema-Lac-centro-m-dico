<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';

// ============================================================
// STATUS DE SEGURANÇA DO AMBIENTE ATUAL
// ============================================================
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

$headers = function_exists('getallheaders') ? getallheaders() : [];

$checks = [
    'https' => [
        'label'  => 'HTTPS ativo',
        'status' => $isHttps,
        'help'   => $isHttps ? 'Conexão criptografada.' : 'Conexão HTTP em texto puro — obrigatório HTTPS em produção.',
    ],
    'hsts' => [
        'label'  => 'Header HSTS',
        'status' => isset($headers['Strict-Transport-Security']),
        'help'   => isset($headers['Strict-Transport-Security'])
                    ? 'HSTS configurado: ' . htmlspecialchars($headers['Strict-Transport-Security'])
                    : 'Adicione no .htaccess de produção.',
    ],
    'xcto' => [
        'label'  => 'X-Content-Type-Options',
        'status' => isset($headers['X-Content-Type-Options']),
        'help'   => 'Previne sniffing de tipo MIME.',
    ],
    'xfo' => [
        'label'  => 'X-Frame-Options',
        'status' => isset($headers['X-Frame-Options']),
        'help'   => 'Previne clickjacking (iframe malicioso).',
    ],
    'ref' => [
        'label'  => 'Referrer-Policy',
        'status' => isset($headers['Referrer-Policy']),
        'help'   => 'Controla vazamento de URL em links externos.',
    ],
    'perm' => [
        'label'  => 'Permissions-Policy',
        'status' => isset($headers['Permissions-Policy']),
        'help'   => 'Restringe acesso a câmera, microfone, geolocalização.',
    ],
    'cookie' => [
        'label'  => 'Cookies seguros',
        'status' => (bool)ini_get('session.cookie_httponly'),
        'help'   => 'HttpOnly + SameSite + Secure.',
    ],
];

$totalOk = 0;
foreach ($checks as $c) if ($c['status']) $totalOk++;
$percentual = round(($totalOk / count($checks)) * 100);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segurança e Certificados - LGPD</title>
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
                <a href="index.php" class="text-sm font-medium text-stone-700 hover:text-[#8C6D36] transition">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Voltar para as Solicitações LGPD</span>
                </a>
                <span class="text-sm font-medium text-stone-700 hidden sm:inline"><?php echo htmlspecialchars($nomeAdmin); ?></span>
                <a href="../../logout.php" class="text-stone-400 hover:text-amber-700 transition" title="Sair">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Cabeçalho -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h1 class="text-2xl font-serif text-[#3E352E] flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-[#8C6D36]"></i>
                Segurança e Certificados
            </h1>
            <p class="text-sm text-stone-500 mt-1">
                Requisitos da LGPD (Art. 46) e do CFM para transmissão de dados de saúde.
            </p>
            <div class="mt-4 pt-4 border-t border-stone-100 flex gap-2 flex-wrap">
    <a href="certificado.php" class="inline-flex items-center gap-2 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
        <i class="fa-solid fa-certificate"></i> Monitorar Certificado SSL
    </a>
</div>
        </section>

        <!-- Painel de status -->
        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <h2 class="text-lg font-serif text-[#3E352E]">Status do ambiente</h2>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-stone-500"><?php echo $totalOk; ?> de <?php echo count($checks); ?> ativos</span>
                    <span class="text-2xl font-serif <?php echo $percentual >= 80 ? 'text-emerald-600' : ($percentual >= 50 ? 'text-amber-600' : 'text-red-600'); ?>">
                        <?php echo $percentual; ?>%
                    </span>
                </div>
            </div>

            <!-- Barra de progresso -->
            <div class="h-2 bg-stone-200 rounded-full overflow-hidden mb-5">
                <div class="h-full <?php echo $percentual >= 80 ? 'bg-emerald-500' : ($percentual >= 50 ? 'bg-amber-500' : 'bg-red-500'); ?> transition-all"
                     style="width: <?php echo $percentual; ?>%"></div>
            </div>

            <!-- Checklist -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($checks as $key => $c): ?>
                    <div class="border rounded-xl p-3 <?php echo $c['status'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-red-200 bg-red-50/40'; ?>">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid <?php echo $c['status'] ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-red-600'; ?> mt-0.5"></i>
                            <div class="min-w-0">
                                <div class="font-semibold text-sm <?php echo $c['status'] ? 'text-emerald-900' : 'text-red-900'; ?>"><?php echo htmlspecialchars($c['label']); ?></div>
                                <div class="text-xs <?php echo $c['status'] ? 'text-emerald-700' : 'text-red-700'; ?> mt-0.5"><?php echo htmlspecialchars($c['help']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$isHttps): ?>
                <div class="mt-4 bg-amber-50 border border-amber-200 p-3 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                    <span>
                        <strong>Ambiente de desenvolvimento:</strong> HTTPS desativado (normal em localhost).
                        Em produção, é <strong>obrigatório</strong> usar HTTPS com certificado válido.
                    </span>
                </div>
            <?php endif; ?>
        </section>

        <!-- Documentação -->
        <section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
            <h2 class="text-xl font-serif text-[#3E352E]">
                <i class="fa-solid fa-book text-[#8C6D36] mr-1"></i> Requisitos e recomendações
            </h2>

            <div class="prose prose-stone max-w-none
                        [&_h3]:text-base [&_h3]:font-bold [&_h3]:text-[#3E352E] [&_h3]:mt-5 [&_h3]:mb-2
                        [&_p]:text-sm [&_p]:text-stone-700 [&_p]:my-2
                        [&_ul]:text-sm [&_ul]:text-stone-700 [&_ul]:pl-5 [&_ul]:my-2 [&_ul]:list-disc
                        [&_li]:my-1 [&_strong]:text-[#3E352E]">

                <h3>1. Base legal — LGPD Art. 46</h3>
                <p>
                    "Os agentes de tratamento devem adotar medidas de segurança, técnicas e administrativas
                    aptas a proteger os dados pessoais de acessos não autorizados."
                </p>
                <p>
                    Dados de <strong>saúde</strong> são classificados como <strong>sensíveis</strong> (Art. 5º, II).
                    A ANPD espera criptografia em trânsito como <strong>mínimo obrigatório</strong>.
                </p>

                <h3>2. CFM — Resoluções aplicáveis</h3>
                <ul>
                    <li><strong>Resolução CFM 2.314/2022</strong> — Telemedicina (transmissão segura)</li>
                    <li><strong>Resolução CFM 1.821/2007</strong> — Prontuário eletrônico</li>
                    <li><strong>Resolução CFM 2.227/2018</strong> — Teleconsulta (sigilo)</li>
                </ul>

                <h3>3. Tipos de certificado</h3>
                <div class="overflow-x-auto my-3">
                    <table class="w-full text-xs border border-stone-200">
                        <thead class="bg-[#F9F4EC]">
                            <tr>
                                <th class="px-3 py-2 text-left border-b">Tipo</th>
                                <th class="px-3 py-2 text-left border-b">Validação</th>
                                <th class="px-3 py-2 text-left border-b">Preço</th>
                                <th class="px-3 py-2 text-left border-b">Para LAC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr><td class="px-3 py-2">Let's Encrypt</td><td class="px-3 py-2">Domínio</td><td class="px-3 py-2">Grátis</td><td class="px-3 py-2">✅ Site público</td></tr>
                            <tr><td class="px-3 py-2">DV pago</td><td class="px-3 py-2">Domínio</td><td class="px-3 py-2">R$ 50–200</td><td class="px-3 py-2">✅ Aceitável</td></tr>
                            <tr><td class="px-3 py-2">OV</td><td class="px-3 py-2">Empresa</td><td class="px-3 py-2">R$ 200–800</td><td class="px-3 py-2">⭐ Recomendado</td></tr>
                            <tr><td class="px-3 py-2">EV</td><td class="px-3 py-2">Empresa + física</td><td class="px-3 py-2">R$ 800–3.000</td><td class="px-3 py-2">⭐ Premium</td></tr>
                            <tr><td class="px-3 py-2"><strong>ICP-Brasil A1</strong></td><td class="px-3 py-2">PKI brasileira</td><td class="px-3 py-2">R$ 300–600</td><td class="px-3 py-2">✅ <strong>Obrigatório</strong> documentos médicos</td></tr>
                            <tr><td class="px-3 py-2">ICP-Brasil A3</td><td class="px-3 py-2">Token físico</td><td class="px-3 py-2">R$ 400–900</td><td class="px-3 py-2">✅ Assinatura presencial</td></tr>
                        </tbody>
                    </table>
                </div>

                <h3>4. Configuração mínima obrigatória</h3>
                <ul>
                    <li><strong>TLS 1.2+</strong> (ideal 1.3)</li>
                    <li><strong>AES-256-GCM</strong> para cifra</li>
                    <li><strong>HSTS</strong> com <code>max-age=31536000; includeSubDomains</code></li>
                    <li><strong>Cookies</strong> com <code>Secure</code> + <code>HttpOnly</code> + <code>SameSite=Strict</code></li>
                    <li><strong>Zero mixed content</strong> (sem http:// em recursos)</li>
                    <li><strong>Redirecionamento HTTP → HTTPS</strong> (301)</li>
                </ul>

                <h3>5. Headers de segurança recomendados</h3>
                <ul>
                    <li><code>X-Content-Type-Options: nosniff</code></li>
                    <li><code>X-Frame-Options: SAMEORIGIN</code></li>
                    <li><code>Referrer-Policy: strict-origin-when-cross-origin</code></li>
                    <li><code>Permissions-Policy: camera=(self), microphone=(self)</code></li>
                    <li><code>Content-Security-Policy</code> (avançado)</li>
                </ul>

                <h3>6. Após o deploy em produção</h3>
                <ul>
                    <li>Auditar o certificado em <a href="https://www.ssllabs.com/ssltest/" target="_blank">SSL Labs</a> (mínimo A)</li>
                    <li>Ativar monitoramento de expiração do certificado</li>
                    <li>Renovar automaticamente (Let's Encrypt: 90 dias)</li>
                    <li>Backup das chaves privadas</li>
                    <li>Configurar alerta se o certificado expirar em 30 dias</li>
                </ul>
            </div>
        </section>

        <!-- Aviso importante -->
        <section class="bg-amber-50 border border-amber-200 p-5 rounded-2xl flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-xl mt-0.5"></i>
            <div class="text-sm text-amber-900">
                <strong class="block mb-1">Certificado de segurança não é suficiente!</strong>
                <p>
                    O certificado SSL/TLS é <strong>a base</strong>, mas a conformidade com LGPD Saúde exige:
                </p>
                <ul class="list-disc pl-5 mt-2 space-y-0.5">
                    <li>As medidas técnicas já implementadas (criptografia de dados, hash, etc.)</li>
                    <li>Governança (DPO, ROPA, Plano de Incidentes)</li>
                    <li>Treinamento da equipe</li>
                    <li>Auditoria periódica</li>
                </ul>
            </div>
        </section>

    </main>

    <?php include __DIR__ . '/../../includes/rodape.php'; ?>
</body>
</html>