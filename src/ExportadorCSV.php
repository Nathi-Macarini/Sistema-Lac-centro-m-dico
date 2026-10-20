<?php
namespace App;

use PDO;

class ExportadorCSV
{
    public const TIPOS = [
        'logs'         => 'Logs de atividades',
        'consultas'    => 'Consultas',
        'usuarios'     => 'Usuários',
        'atendimentos' => 'Atendimentos (prontuários)',
        'documentos'   => 'Documentos (receitas/atestados/declarações)',
    ];

    public const PERIODOS = [
        'semanal'     => 'Últimos 7 dias',
        'mensal'      => 'Últimos 30 dias',
        'trimestral'  => 'Últimos 90 dias',
        'personalizado' => 'Período personalizado',
    ];

    /**
     * Gera um CSV.
     * @return array{arquivo:string,tamanho:int,linhas:int,periodo_inicio:string,periodo_fim:string}
     */
    public static function gerar(string $tipo, string $dataIni, string $dataFim): array
    {
        if (!array_key_exists($tipo, self::TIPOS)) {
            throw new \InvalidArgumentException("Tipo inválido: {$tipo}");
        }

        $dir = __DIR__ . '/../storage/exportacoes';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $nomeArquivo = sprintf(
            '%s_%s_%s_%s.csv',
            $tipo,
            $dataIni,
            $dataFim,
            date('Ymd_His')
        );
        $caminhoCompleto = $dir . '/' . $nomeArquivo;
        $caminhoRelativo = 'storage/exportacoes/' . $nomeArquivo;

        $out = fopen($caminhoCompleto, 'w');
        if (!$out) {
            throw new \RuntimeException("Não foi possível criar o arquivo.");
        }

        // BOM para Excel abrir com acentos
        fputs($out, "\xEF\xBB\xBF");

        $totalLinhas = self::escreverTipo($out, $tipo, $dataIni, $dataFim);

        fclose($out);

        return [
            'arquivo'        => $caminhoRelativo,
            'tamanho'        => (int) filesize($caminhoCompleto),
            'linhas'         => $totalLinhas,
            'periodo_inicio' => $dataIni,
            'periodo_fim'    => $dataFim,
        ];
    }

    /**
     * Escreve o conteúdo do CSV e retorna o total de linhas de dados.
     */
    private static function escreverTipo($out, string $tipo, string $ini, string $fim): int
    {
        $pdo = Database::conn();
        $iniDt = $ini . ' 00:00:00';
        $fimDt = $fim . ' 23:59:59';
        $linhas = 0;

        switch ($tipo) {
            case 'logs':
                fputcsv($out, ['ID', 'Data/Hora', 'Usuário', 'Papel', 'Ação', 'Entidade', 'Tags', 'Descrição', 'IP']);
                $st = $pdo->prepare("
                    SELECT id, created_at, user_name, user_role, action, entity, tags, description, ip_address
                    FROM activity_logs
                    WHERE created_at BETWEEN ? AND ?
                    ORDER BY id
                ");
                $st->execute([$iniDt, $fimDt]);
                while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($out, [
                        $r['id'], $r['created_at'], $r['user_name'], $r['user_role'],
                        $r['action'], $r['entity'], $r['tags'],
                        $r['description'], $r['ip_address'],
                    ]);
                    $linhas++;
                }
                break;

            case 'consultas':
                fputcsv($out, ['ID', 'Data/Hora', 'Paciente', 'Médico', 'Especialidade', 'Modalidade', 'Tipo', 'Atendimento', 'Status']);
                $st = $pdo->prepare("
                    SELECT c.id, c.data_hora,
                           u.nome AS paciente, m.nome AS medico,
                           c.especialidade, c.modalidade, c.tipo,
                           c.atendimento_tipo, c.status
                    FROM consultas c
                    LEFT JOIN usuarios u ON u.id = c.usuario_id
                    LEFT JOIN medicos  m ON m.id = c.medico_id
                    WHERE c.data_hora BETWEEN ? AND ?
                    ORDER BY c.data_hora
                ");
                $st->execute([$iniDt, $fimDt]);
                while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($out, [
                        $r['id'], $r['data_hora'], $r['paciente'], $r['medico'],
                        $r['especialidade'], $r['modalidade'], $r['tipo'],
                        $r['atendimento_tipo'], $r['status'],
                    ]);
                    $linhas++;
                }
                break;

            case 'usuarios':
                fputcsv($out, ['ID', 'Nome', 'Nome da Mãe', 'E-mail', 'CPF', 'Telefone', 'Nascimento', 'Tipo', 'Cadastrado em']);
                $st = $pdo->prepare("
                    SELECT id, nome, nome_mae, email, cpf, telefone, data_nascimento, tipo, criado_em
                    FROM usuarios
                    WHERE criado_em BETWEEN ? AND ?
                    ORDER BY id
                ");
                $st->execute([$iniDt, $fimDt]);
                while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($out, [
                        $r['id'], $r['nome'], $r['nome_mae'], $r['email'], $r['cpf'],
                        $r['telefone'], $r['data_nascimento'], $r['tipo'], $r['criado_em'],
                    ]);
                    $linhas++;
                }
                break;

            case 'atendimentos':
                fputcsv($out, ['ID', 'Data', 'Paciente', 'Médico', 'Diagnóstico', 'CID-10', 'Prescrição']);
                $st = $pdo->prepare("
                    SELECT a.id, a.criado_em,
                           p.nome AS paciente, m.nome AS medico,
                           a.diagnostico, a.cid10, a.prescricao
                    FROM atendimentos a
                    LEFT JOIN usuarios p ON p.id = a.paciente_id
                    LEFT JOIN medicos  m ON m.id = a.medico_id
                    WHERE a.criado_em BETWEEN ? AND ?
                    ORDER BY a.id
                ");
                $st->execute([$iniDt, $fimDt]);
                while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($out, [
                        $r['id'], $r['criado_em'], $r['paciente'], $r['medico'],
                        $r['diagnostico'], $r['cid10'], $r['prescricao'],
                    ]);
                    $linhas++;
                }
                break;

            case 'documentos':
                fputcsv($out, ['ID', 'Data', 'Tipo', 'Paciente', 'Médico', 'CID-10', 'CBO', 'Validade (dias)', 'Código']);
                $st = $pdo->prepare("
                    SELECT d.id, d.criado_em, d.tipo,
                           p.nome AS paciente, m.nome AS medico,
                           d.cid10, d.cbo, d.validade_dias, d.codigo
                    FROM documentos d
                    LEFT JOIN usuarios p ON p.id = d.paciente_id
                    LEFT JOIN medicos  m ON m.id = d.medico_id
                    WHERE d.criado_em BETWEEN ? AND ?
                    ORDER BY d.id
                ");
                $st->execute([$iniDt, $fimDt]);
                while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($out, [
                        $r['id'], $r['criado_em'], $r['tipo'], $r['paciente'],
                        $r['medico'], $r['cid10'], $r['cbo'], $r['validade_dias'], $r['codigo'],
                    ]);
                    $linhas++;
                }
                break;
        }

        return $linhas;
    }

    /**
     * Retorna o intervalo de datas para um período pré-definido.
     * @return array{0:string,1:string}
     */
    public static function intervaloPeriodo(string $periodo): array
    {
        $fim = date('Y-m-d');
        switch ($periodo) {
            case 'semanal':
                $ini = date('Y-m-d', strtotime('-7 days'));
                break;
            case 'mensal':
                $ini = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'trimestral':
                $ini = date('Y-m-d', strtotime('-90 days'));
                break;
            default:
                $ini = $fim;
        }
        return [$ini, $fim];
    }
}