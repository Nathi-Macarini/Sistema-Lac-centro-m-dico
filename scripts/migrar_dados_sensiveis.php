<?php
/**
 * Migração de dados sensíveis para formato seguro.
 * 
 * Popula as colunas *_hash, *_ultimos4, *_enc a partir dos dados em texto puro.
 * 
 * Uso:
 *   php scripts/migrar_dados_sensiveis.php
 *   ou
 *   acesse via navegador (apenas logado como admin, se preferir)
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../conexao.php';

use App\Crypto;
use App\CPF;

header('Content-Type: text/plain; charset=utf-8');

echo "=== MIGRAÇÃO DE DADOS SENSÍVEIS ===\n\n";

// ============================================================
// 1. CPF DOS USUÁRIOS
// ============================================================
echo "→ Migrando CPF de usuários...\n";

$res = $conn->query("SELECT id, cpf FROM usuarios WHERE cpf IS NOT NULL AND cpf <> ''");
$total = 0; $migrados = 0;

while ($u = $res->fetch_assoc()) {
    $total++;
    $cpfLimpo = CPF::limpar($u['cpf']);
    if ($cpfLimpo === '') continue;

    $hash = CPF::hash($cpfLimpo);
    $u4   = CPF::ultimos4($cpfLimpo);
    $enc  = CPF::criptografar($cpfLimpo);

    $st = $conn->prepare("UPDATE usuarios SET cpf_hash = ?, cpf_ultimos4 = ?, cpf_enc = ? WHERE id = ?");
    $st->bind_param('sssi', $hash, $u4, $enc, $u['id']);
    if ($st->execute()) $migrados++;
}
echo "   Total: {$total} | Migrados: {$migrados}\n\n";

// ============================================================
// 2. CPF DOS DEPENDENTES
// ============================================================
echo "→ Migrando CPF de dependentes...\n";

$res = $conn->query("SELECT id, cpf FROM dependentes WHERE cpf IS NOT NULL AND cpf <> ''");
$total = 0; $migrados = 0;

while ($d = $res->fetch_assoc()) {
    $total++;
    $cpfLimpo = CPF::limpar($d['cpf']);
    if ($cpfLimpo === '') continue;

    $hash = CPF::hash($cpfLimpo);
    $u4   = CPF::ultimos4($cpfLimpo);
    $enc  = CPF::criptografar($cpfLimpo);

    $st = $conn->prepare("UPDATE dependentes SET cpf_hash = ?, cpf_ultimos4 = ?, cpf_enc = ? WHERE id = ?");
    $st->bind_param('sssi', $hash, $u4, $enc, $d['id']);
    if ($st->execute()) $migrados++;
}
echo "   Total: {$total} | Migrados: {$migrados}\n\n";

// ============================================================
// 3. DADOS CLÍNICOS DOS PRONTUÁRIOS
// ============================================================
echo "→ Migrando prontuários...\n";

$res = $conn->query("SELECT id, alergias, doencas_cronicas, medicamentos_uso FROM prontuarios");
$total = 0; $migrados = 0;

while ($p = $res->fetch_assoc()) {
    $total++;

    $alergias = Crypto::encrypt($p['alergias']);
    $doencas  = Crypto::encrypt($p['doencas_cronicas']);
    $meds     = Crypto::encrypt($p['medicamentos_uso']);

    $st = $conn->prepare("UPDATE prontuarios SET alergias_enc = ?, doencas_cronicas_enc = ?, medicamentos_uso_enc = ? WHERE id = ?");
    $st->bind_param('sssi', $alergias, $doencas, $meds, $p['id']);
    if ($st->execute()) $migrados++;
}
echo "   Total: {$total} | Migrados: {$migrados}\n\n";

// ============================================================
// 4. ATENDIMENTOS
// ============================================================
echo "→ Migrando atendimentos...\n";

$res = $conn->query("SELECT id, diagnostico, prescricao FROM atendimentos");
$total = 0; $migrados = 0;

while ($a = $res->fetch_assoc()) {
    $total++;

    $diag = Crypto::encrypt($a['diagnostico']);
    $pres = Crypto::encrypt($a['prescricao']);

    $st = $conn->prepare("UPDATE atendimentos SET diagnostico_enc = ?, prescricao_enc = ? WHERE id = ?");
    $st->bind_param('ssi', $diag, $pres, $a['id']);
    if ($st->execute()) $migrados++;
}
echo "   Total: {$total} | Migrados: {$migrados}\n\n";

// ============================================================
// 5. DOCUMENTOS
// ============================================================
echo "→ Migrando documentos...\n";

$res = $conn->query("SELECT id, conteudo FROM documentos");
$total = 0; $migrados = 0;

while ($d = $res->fetch_assoc()) {
    $total++;
    $enc = Crypto::encrypt($d['conteudo']);

    $st = $conn->prepare("UPDATE documentos SET conteudo_enc = ? WHERE id = ?");
    $st->bind_param('si', $enc, $d['id']);
    if ($st->execute()) $migrados++;
}
echo "   Total: {$total} | Migrados: {$migrados}\n\n";

echo "=== MIGRAÇÃO CONCLUÍDA ===\n";