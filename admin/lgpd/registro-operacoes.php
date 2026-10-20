<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../conexao.php';

use App\ActivityLogger;

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nomeAdmin = $_SESSION['nome_usuario'] ?? 'Administrador';
$uid = (int)$_SESSION['usuario_id'];
$msg = ''; $msgTipo = '';

// ============================================================
// AÇÃO: SALVAR (novo ou editar)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $idEdit           = (int)($_POST['id'] ?? 0);
    $nome             = trim($_POST['nome'] ?? '');
    $descricao        = trim($_POST['descricao'] ?? '');
    $categoriaTit     = trim($_POST['categoria_titulares'] ?? '');
    $categoriaDad     = trim($_POST['categoria_dados'] ?? '');
    $finalidade       = trim($_POST['finalidade'] ?? '');
    $baseLegal        = trim($_POST['base_legal'] ?? '');
    $origemDados      = trim($_POST['origem_dados'] ?? '');
    $compartilhamento = trim($_POST['compartilhamento'] ?? '');
    $tempoRetencao    = trim($_POST['tempo_retencao'] ?? '');
    $medidasSeg       = trim($_POST['medidas_seguranca'] ?? '');
    $transfIntl       = trim($_POST['transferencia_intl'] ?? '');

    if ($nome === '') {
        $msg = "Informe o nome da operação.";
        $msgTipo = 'erro';
    } else {
        if ($idEdit > 0) {
            $st = $conn->prepare("
                UPDATE lgpd_operacoes
                SET nome=?, descricao=?, categoria_titulares=?, categoria_dados=?, finalidade=?,
                    base_legal=?, origem_dados=?, compartilhamento=?, tempo_retencao=?,
                    medidas_seguranca=?, transferencia_intl=?
                WHERE id = ?
            ");
            $st->bind_param(
                'sssssssssssi',
                $nome, $descricao, $categoriaTit, $categoriaDad, $finalidade,
                $baseLegal, $origemDados, $compartilhamento, $tempoRetencao,
                $medidasSeg, $transfIntl, $idEdit
            );
            $st->execute();
            $msg = "Operação atualizada.";
            $msgTipo = 'sucesso';
        } else {
            $st = $conn->prepare("
                INSERT INTO lgpd_operacoes
                    (nome, descricao, categoria_titulares, categoria_dados, finalidade,
                     base_legal, origem_dados, compartilhamento, tempo_retencao,
                     medidas_seguranca, transferencia_intl)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $st->bind_param(
                'sssssssssss',
                $nome, $descricao, $categoriaTit, $categoriaDad, $finalidade,
                $baseLegal, $origemDados, $compartilhamento, $tempoRetencao,
                $medidasSeg, $transfIntl
            );
            $st->execute();
            $msg = "Operação registrada.";
            $msgTipo = 'sucesso';
        }

        ActivityLogger::log(
            action: 'lgpd_operacao_' . ($idEdit > 0 ? 'editada' : 'criada'),
            entity: 'lgpd_operacao',
            entityId: $idEdit ?: (int)$conn->insert_id,
            description: "Admin {$nomeAdmin} " . ($idEdit > 0 ? 'editou' : 'criou') . " operação no ROPA: {$nome}",
            tags: ['lgpd', 'ropa']
        );
    }
}

// ============================================================
// AÇÃO: ATIVAR/DESATIVAR
// ============================================================
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $st = $conn->prepare("UPDATE lgpd_operacoes SET ativo = 1 - ativo WHERE id = ?");
    $st->bind_param('i', $id);
    $st->execute();
    header("Location: registro-operacoes.php?toggled=1");
    exit;
}

// ============================================================
// AÇÃO: EXCLUIR
// ============================================================
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    $st = $conn->prepare("DELETE FROM lgpd_operacoes WHERE id = ?");
    $st->bind_param('i', $id);
    $st->execute();
    header("Location: registro-operacoes.php?excluido=1");
    exit;
}

// ============================================================
// LISTA
// ============================================================
$operacoes = $conn->query("SELECT * FROM lgpd_operacoes ORDER BY ativo DESC, nome ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Operações (ROPA) - LGPD</title>
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <section class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm">
            <h1 class="text-2xl font-serif text-[#3E352E] flex items-center gap-2">
                <i class="fa-solid fa-list-check text-[#8C6D36]"></i>
                Registro de Operações de Tratamento (ROPA)
            </h1>
            <p class="text-sm text-stone-500 mt-1">
                Inventário das operações de tratamento de dados pessoais (LGPD Art. 37).
            </p>
        </section>

        <?php if ($msg): ?>
            <div class="<?php echo $msgTipo === 'sucesso' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'; ?> p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid <?php echo $msgTipo === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['toggled']) || isset($_GET['excluido'])): ?>
            <div class="bg-blue-50 text-blue-800 border-blue-200 p-3.5 rounded-xl text-sm border flex items-start gap-2">
                <i class="fa-solid fa-circle-check mt-0.5"></i>
                <span><?php echo isset($_GET['excluido']) ? 'Operação excluída.' : 'Status atualizado.'; ?></span>
            </div>
        <?php endif; ?>

        <!-- Botão para adicionar -->
        <div class="flex justify-end">
            <button onclick="abrirModalOperacao()" class="inline-flex items-center gap-2 bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-5 py-3 rounded-xl text-sm font-medium transition shadow-sm">
                <i class="fa-solid fa-plus"></i> Nova operação
            </button>
        </div>

        <!-- Lista -->
        <section class="space-y-3">
            <?php if (!$operacoes): ?>
                <div class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm text-center text-stone-500">
                    <i class="fa-regular fa-folder-open text-3xl text-[#C5A059] mb-2 block"></i>
                    Nenhuma operação registrada.
                </div>
            <?php endif; ?>

            <?php foreach ($operacoes as $op): ?>
                <div class="bg-white p-6 rounded-2xl border border-[#E6D5B8]/40 shadow-sm <?php echo $op['ativo'] ? '' : 'opacity-60'; ?>">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <h3 class="font-serif text-lg text-[#3E352E]"><?php echo htmlspecialchars($op['nome']); ?></h3>
                                <?php if ($op['ativo']): ?>
                                    <span class="text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full">Ativo</span>
                                <?php else: ?>
                                    <span class="text-xs font-bold bg-stone-100 text-stone-600 border border-stone-200 px-2.5 py-1 rounded-full">Inativo</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($op['descricao']): ?>
                                <p class="text-sm text-stone-600 mt-1"><?php echo htmlspecialchars($op['descricao']); ?></p>
                            <?php endif; ?>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 text-xs">
                                <?php
                                    $campos = [
                                        'Titulares'         => $op['categoria_titulares'],
                                        'Categoria de dados'=> $op['categoria_dados'],
                                        'Finalidade'        => $op['finalidade'],
                                        'Base legal'        => $op['base_legal'],
                                        'Origem dos dados'  => $op['origem_dados'],
                                        'Compartilhamento'  => $op['compartilhamento'],
                                        'Retenção'          => $op['tempo_retencao'],
                                        'Transferência int.'=> $op['transferencia_intl'],
                                    ];
                                    foreach ($campos as $k => $v):
                                        if (!$v) continue;
                                ?>
                                    <div>
                                        <div class="font-bold text-stone-500 uppercase tracking-wider"><?php echo $k; ?></div>
                                        <div class="text-stone-700 mt-0.5"><?php echo htmlspecialchars($v); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($op['medidas_seguranca']): ?>
                                <div class="mt-3 text-xs">
                                    <div class="font-bold text-stone-500 uppercase tracking-wider">Medidas de segurança</div>
                                    <div class="text-stone-700 mt-0.5"><?php echo htmlspecialchars($op['medidas_seguranca']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-col gap-2 shrink-0">
                            <button onclick='abrirModalOperacao(<?php echo json_encode($op); ?>)'
                                    class="inline-flex items-center gap-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-1.5 rounded-lg text-xs font-medium transition">
                                <i class="fa-solid fa-pen-to-square text-[#8C6D36]"></i> Editar
                            </button>
                            <a href="?toggle=<?php echo (int)$op['id']; ?>"
                               class="inline-flex items-center gap-1.5 <?php echo $op['ativo'] ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'; ?> px-3 py-1.5 rounded-lg text-xs font-medium transition">
                                <i class="fa-solid <?php echo $op['ativo'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                <?php echo $op['ativo'] ? 'Desativar' : 'Ativar'; ?>
                            </a>
                            <a href="?excluir=<?php echo (int)$op['id']; ?>"
                               onclick="return confirm('Excluir esta operação?');"
                               class="inline-flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-700 px-3 py-1.5 rounded-lg text-xs font-medium transition">
                                <i class="fa-solid fa-trash"></i> Excluir
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

    </main>

    <!-- Modal: Nova/editar operação -->
    <div id="modal-op" class="fixed inset-0 bg-black/70 z-[120] hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[95vh] overflow-y-auto p-6 space-y-5 shadow-2xl border border-[#E6D5B8]">
            <div class="flex justify-between items-center border-b border-stone-100 pb-4">
                <h3 id="op-titulo" class="text-xl font-serif text-[#3E352E]">Nova operação</h3>
                <button onclick="fecharModalOperacao()" class="text-stone-400 hover:text-stone-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id" id="op-id" value="0">

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Nome da operação *</label>
                    <input type="text" name="nome" id="op-nome" required
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Descrição</label>
                    <textarea name="descricao" id="op-descricao" rows="2"
                              class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Categoria de titulares</label>
                        <input type="text" name="categoria_titulares" id="op-titulares"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                               placeholder="Ex.: Pacientes, Médicos">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Categoria de dados</label>
                        <input type="text" name="categoria_dados" id="op-dados"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                               placeholder="Ex.: Nome, CPF, dados de saúde">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Finalidade</label>
                    <textarea name="finalidade" id="op-finalidade" rows="2"
                              class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Base legal</label>
                    <input type="text" name="base_legal" id="op-base"
                           class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                           placeholder="Ex.: Execução de contrato (art. 7º, V)">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Origem dos dados</label>
                        <input type="text" name="origem_dados" id="op-origem"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Compartilhamento</label>
                        <input type="text" name="compartilhamento" id="op-compart"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Tempo de retenção</label>
                        <input type="text" name="tempo_retencao" id="op-retencao"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Transferência internacional</label>
                        <input type="text" name="transferencia_intl" id="op-intl"
                               class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"
                               placeholder="Ex.: Não há / Meta (EUA)">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1.5">Medidas de segurança</label>
                    <textarea name="medidas_seguranca" id="op-seguranca" rows="2"
                              class="w-full px-3 py-2.5 rounded-xl border border-stone-300 text-sm bg-[#FAF9F6] focus:outline-none focus:border-[#8C6D36]"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-stone-100">
                    <button type="button" onclick="fecharModalOperacao()" class="px-5 py-2.5 border border-stone-300 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-50">Cancelar</button>
                    <button type="submit" class="bg-[#3E352E] hover:bg-[#5A4A3A] text-white px-6 py-2.5 rounded-xl text-sm font-medium transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModalOperacao(op) {
        const modal = document.getElementById('modal-op');
        const titulo = document.getElementById('op-titulo');

        if (op && op.id) {
            titulo.textContent = 'Editar operação';
            document.getElementById('op-id').value           = op.id;
            document.getElementById('op-nome').value         = op.nome || '';
            document.getElementById('op-descricao').value    = op.descricao || '';
            document.getElementById('op-titulares').value    = op.categoria_titulares || '';
            document.getElementById('op-dados').value        = op.categoria_dados || '';
            document.getElementById('op-finalidade').value   = op.finalidade || '';
            document.getElementById('op-base').value         = op.base_legal || '';
            document.getElementById('op-origem').value       = op.origem_dados || '';
            document.getElementById('op-compart').value      = op.compartilhamento || '';
            document.getElementById('op-retencao').value     = op.tempo_retencao || '';
            document.getElementById('op-intl').value         = op.transferencia_intl || '';
            document.getElementById('op-seguranca').value    = op.medidas_seguranca || '';
        } else {
            titulo.textContent = 'Nova operação';
            document.querySelector('#modal-op form').reset();
            document.getElementById('op-id').value = 0;
        }

        modal.classList.remove('hidden');
    }

    function fecharModalOperacao() {
        document.getElementById('modal-op').classList.add('hidden');
    }
    </script>

</body>
</html>