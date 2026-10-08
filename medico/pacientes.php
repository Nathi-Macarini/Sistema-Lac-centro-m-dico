<?php
require __DIR__ . '/../conexao.php';
$medico = exigirMedico($conn);
require __DIR__ . '/layout.php';

$mid = (int)$medico['id'];
$esp = $medico['especialidade'];

// Busca todos os pacientes atendidos ou agendados para este médico/especialidade
$sql = "SELECT DISTINCT u.id, u.nome, u.email, u.telefone, u.data_nascimento,
        (SELECT MAX(c.data_hora) FROM consultas c WHERE c.usuario_id = u.id AND (c.medico_id = ? OR c.especialidade = ?)) AS ultima_consulta
        FROM usuarios u
        JOIN consultas c ON c.usuario_id = u.id
        WHERE c.medico_id = ? OR (c.medico_id IS NULL AND c.especialidade = ?)
        ORDER BY u.nome ASC";

$st = $conn->prepare($sql);
$st->bind_param('isis', $mid, $esp, $mid, $esp);
$st->execute();
$pacientes = $st->get_result()->fetch_all(MYSQLI_ASSOC);

cabecalho('Meus Pacientes', 'pacientes');
?>

<section class="bg-white p-8 rounded-2xl border border-[#E6D5B8]/40 shadow-sm space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-serif text-[#3E352E] mb-1">Meus Pacientes</h1>
            <p class="text-stone-600">Lista de pacientes vinculados à sua agenda e especialidade (<?php echo h($esp); ?>).</p>
        </div>
        <div class="bg-[#F9F4EC] border border-[#E6D5B8] px-4 py-2 rounded-xl text-stone-700 text-sm font-medium">
            Total: <strong><?php echo count($pacientes); ?></strong> pacientes
        </div>
    </div>

    <?php if ($pacientes): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-stone-200 text-stone-500 text-xs uppercase tracking-wider bg-[#FAF9F6]">
                        <th class="py-3 px-4 font-semibold">Nome do Paciente</th>
                        <th class="py-3 px-4 font-semibold">Idade / Nasc.</th>
                        <th class="py-3 px-4 font-semibold">Contato</th>
                        <th class="py-3 px-4 font-semibold">Última Consulta</th>
                        <th class="py-3 px-4 font-semibold text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 text-sm">
                    <?php foreach ($pacientes as $p): ?>
                        <tr class="hover:bg-[#FAF9F6]/60 transition">
                            <td class="py-4 px-4 font-medium text-stone-800">
                                <?php echo h($p['nome']); ?>
                            </td>
                            <td class="py-4 px-4 text-stone-600">
                                <?php echo h(textoIdade($p['data_nascimento'])); ?>
                            </td>
                            <td class="py-4 px-4 text-stone-600">
                                <div><i class="fa-solid fa-envelope text-xs text-[#8C6D36] mr-1"></i> <?php echo h($p['email']); ?></div>
                                <?php if (!empty($p['telefone'])): ?>
                                    <div class="text-xs text-stone-400 mt-0.5"><i class="fa-solid fa-phone text-xs mr-1"></i> <?php echo h($p['telefone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-stone-600">
                                <?php echo $p['ultima_consulta'] ? date('d/m/Y H:i', strtotime($p['ultima_consulta'])) : 'N/A'; ?>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <a href="prontuario.php?id=<?php echo (int)$p['id']; ?>"
                                   class="inline-flex items-center px-3 py-1.5 border border-stone-300 text-stone-700 rounded-lg text-xs font-medium hover:bg-white transition shadow-sm">
                                    <i class="fa-solid fa-notes-medical mr-1.5 text-[#8C6D36]"></i> Prontuário
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-12 border border-dashed border-stone-200 rounded-2xl bg-[#FAF9F6]">
            <i class="fa-solid fa-user-injured text-3xl text-stone-300 mb-3"></i>
            <p class="text-stone-500 font-medium">Nenhum paciente encontrado para esta especialidade até o momento.</p>
        </div>
    <?php endif; ?>
</section>

<?php rodape(); ?>