<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE CERTIFICADO SSL ===\n\n";

echo "Data/hora do sistema: " . date('Y-m-d H:i:s') . "\n";
echo "Timestamp atual:      " . time() . "\n";
echo "Timezone PHP:         " . date_default_timezone_get() . "\n";
echo "Timezone sistema:     " . ini_get('date.timezone') . "\n\n";

$dominio = $_GET['domain'] ?? 'google.com';
$porta   = 443;

echo "Testando: {$dominio}:{$porta}\n\n";

// ============================================================
// 1. Conexão
// ============================================================
$context = stream_context_create([
    'ssl' => [
        'capture_peer_cert' => true,
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'SNI_enabled'       => true,
    ],
]);

$client = @stream_socket_client(
    "ssl://{$dominio}:{$porta}",
    $errno, $errstr, 10,
    STREAM_CLIENT_CONNECT,
    $context
);

if (!$client) {
    echo "❌ Falha na conexão: $errstr ($errno)\n";
    exit;
}

$params = stream_context_get_params($client);
fclose($client);

$cert = $params['options']['ssl']['peer_certificate'] ?? null;

if (!$cert) {
    echo "❌ Nenhum certificado obtido\n";
    exit;
}

echo "✅ Conexão TLS estabelecida\n\n";

// ============================================================
// 2. Dados crus do certificado
// ============================================================
$info = openssl_x509_parse($cert);

echo "=== DADOS DO CERTIFICADO ===\n\n";
echo "Subject CN:    " . ($info['subject']['CN'] ?? '—') . "\n";
echo "Subject O:     " . ($info['subject']['O']  ?? '—') . "\n";
echo "Issuer CN:     " . ($info['issuer']['CN']  ?? '—') . "\n";
echo "Issuer O:      " . ($info['issuer']['O']   ?? '—') . "\n";
echo "ValidFrom:     " . ($info['validFrom']     ?? '—') . "\n";
echo "ValidTo:       " . ($info['validTo']       ?? '—') . "\n";
echo "ValidFrom_ts:  " . var_export($info['validFrom_time_t'] ?? null, true) . "\n";
echo "ValidTo_ts:    " . var_export($info['validTo_time_t']   ?? null, true) . "\n\n";

// ============================================================
// 3. Cálculo
// ============================================================
if (!isset($info['validTo_time_t']) || (int)$info['validTo_time_t'] <= 0) {
    echo "❌ PROBLEMA: validTo_time_t inválido ou zero.\n";
    echo "   Isso é um BUG do parse do PHP.\n";
    exit;
}

$validoAte = (int)$info['validTo_time_t'];
$agora     = time();
$diff      = $validoAte - $agora;
$dias      = (int) floor($diff / 86400);

echo "=== CÁLCULO ===\n\n";
echo "Agora (timestamp):    $agora  (" . date('Y-m-d H:i:s') . ")\n";
echo "ValidTo (timestamp):  $validoAte  (" . date('Y-m-d H:i:s', $validoAte) . ")\n";
echo "Diferença (segundos): $diff\n";
echo "Dias restantes:       $dias\n\n";

if ($dias < 0) {
    echo "❌ CERTIFICADO EXPIRADO há " . abs($dias) . " dias\n";
} elseif ($dias <= 30) {
    echo "⚠️  EXPIRA EM $dias DIAS\n";
} else {
    echo "✅ VÁLIDO POR $dias DIAS\n";
}

// ============================================================
// 4. Detecção de antivírus / interceptação
// ============================================================
echo "\n=== DIAGNÓSTICO DE INTERCEPTAÇÃO ===\n\n";

$issuerO   = strtoupper($info['issuer']['O']   ?? '');
$issuerCN  = strtoupper($info['issuer']['CN']  ?? '');
$subjectCN = strtolower($info['subject']['CN'] ?? '');

$avs = [
    'KASPERSKY'   => 'Kaspersky',
    'ESET'        => 'ESET',
    'AVAST'       => 'Avast',
    'AVG'         => 'AVG',
    'BITDEFENDER' => 'Bitdefender',
    'AVIRA'       => 'Avira',
    'FORTINET'    => 'Fortinet',
    'SOPHOS'      => 'Sophos',
    'ZSCALER'     => 'Zscaler',
    'MCAFEE'      => 'McAfee',
    'NORTON'      => 'Norton',
    'COMODO'      => 'Comodo',
];

$detectado = null;
foreach ($avs as $chave => $nome) {
    if (str_contains($issuerO, $chave) || str_contains($issuerCN, $chave)) {
        $detectado = $nome;
        break;
    }
}

echo "Subject esperado: google.com (ou *.google.com)\n";
echo "Subject real:     " . ($subjectCN ?: '—') . "\n";
echo "Issuer real:      " . ($info['issuer']['O'] ?? '—') . " / " . ($info['issuer']['CN'] ?? '—') . "\n\n";

if ($detectado) {
    echo "🚨 ANTIVÍRUS DETECTADO: {$detectado}\n";
    echo "   O {$detectado} está interceptando a conexão HTTPS\n";
    echo "   e substituindo o certificado do Google pelo dele.\n";
    echo "   Esse certificado falso está expirado no seu sistema.\n\n";
    echo "   SOLUÇÃO: desative 'Verificação de SSL/HTTPS' no antivírus\n";
    echo "   ou adicione uma exceção para localhost.\n";
} elseif (!str_contains($subjectCN, 'google.com')) {
    echo "🚨 INTERCEPTAÇÃO: o Subject NÃO é do Google\n";
    echo "   Pode ser proxy corporativo ou outro AV.\n";
} else {
    echo "✅ Emissor e Subject legítimos do Google.\n";
    echo "   Se o cert está 'expirado', verifique o relógio do sistema\n";
    echo "   (o relógio está em " . date('Y') . ", o cert expira em " . date('Y', $validoAte) . ")\n";
}