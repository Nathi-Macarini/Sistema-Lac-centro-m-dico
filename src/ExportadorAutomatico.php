<?php
namespace App;

class ExportadorAutomatico
{
    /**
     * Configuração: para cada tipo, quais períodos são gerados automaticamente
     * e com qual frequência (em dias).
     */
    public const REGRAS = [
        'logs'      => [
            'semanal'    => 7,
            'mensal'     => 30,
            'trimestral' => 90,
        ],
        'consultas' => [
            'semanal'    => 7,
            'mensal'     => 30,
        ],
        'atendimentos' => [
            'mensal'     => 30,
        ],
        'documentos' => [
            'mensal'    => 30,
        ],
    ];

    /**
     * Verifica quais exportações estão atrasadas e gera as pendentes.
     * Retorna array com o que foi gerado nesta execução.
     * 
     * @return array{gerados: array, verificados: int, erros: array}
     */
    public static function verificarEgerar(): array
    {
        $pdo = Database::conn();
        $gerados = [];
        $erros = [];
        $verificados = 0;

        foreach (self::REGRAS as $tipo => $periodos) {
            foreach ($periodos as $periodo => $frequenciaDias) {
                $verificados++;

                // -------- Última exportação deste tipo+periodo --------
                $st = $pdo->prepare("
                    SELECT periodo_fim, gerado_em
                    FROM exportacoes
                    WHERE tipo = ? AND periodo = ?
                    ORDER BY gerado_em DESC
                    LIMIT 1
                ");
                $st->execute([$tipo, $periodo]);
                $ultima = $st->fetch(\PDO::FETCH_ASSOC);

                // -------- Precisa gerar? --------
                $precisaGerar = false;
                if (!$ultima) {
                    // Nunca gerado — gera agora
                    $precisaGerar = true;
                } else {
                    $diasDesde = (int) floor(
                        (time() - strtotime($ultima['gerado_em'])) / 86400
                    );
                    if ($diasDesde >= $frequenciaDias) {
                        $precisaGerar = true;
                    }
                }

                if (!$precisaGerar) continue;

                // -------- Gera --------
                try {
                    [$ini, $fim] = ExportadorCSV::intervaloPeriodo($periodo);
                    $resultado = ExportadorCSV::gerar($tipo, $ini, $fim);

                    // Registra
                    $ins = $pdo->prepare("
                        INSERT INTO exportacoes
                            (tipo, periodo, periodo_inicio, periodo_fim, arquivo, tamanho_bytes, total_linhas, gerado_por)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
                    ");
                    $ins->execute([
                        $tipo, $periodo,
                        $resultado['periodo_inicio'], $resultado['periodo_fim'],
                        $resultado['arquivo'], $resultado['tamanho'], $resultado['linhas'],
                    ]);

                    $gerados[] = [
                        'tipo'     => $tipo,
                        'periodo'  => $periodo,
                        'linhas'   => $resultado['linhas'],
                        'arquivo'  => $resultado['arquivo'],
                    ];

                    // Registra no log de auditoria
                    if (class_exists('App\\ActivityLogger')) {
                        ActivityLogger::log(
                            action: 'exportacao_automatica',
                            entity: 'exportacao',
                            description: "Exportação automática gerada: {$tipo} ({$periodo}) com {$resultado['linhas']} linha(s)",
                            metadata: [
                                'tipo'    => $tipo,
                                'periodo' => $periodo,
                                'linhas'  => $resultado['linhas'],
                                'arquivo' => $resultado['arquivo'],
                            ],
                            tags: ['exportacao', 'automatica']
                        );
                    }

                } catch (\Throwable $e) {
                    $erros[] = "{$tipo}/{$periodo}: " . $e->getMessage();
                }
            }
        }

        return [
            'gerados'     => $gerados,
            'verificados' => $verificados,
            'erros'       => $erros,
        ];
    }

    /**
     * Verifica se é hora de rodar (evita rodar toda hora).
     * Salva um "último check" em arquivo para não sobrecarregar.
     * 
     * @param int $intervaloMinutos  Frequência mínima entre verificações (padrão: 60)
     */
    public static function deveVerificar(int $intervaloMinutos = 60): bool
    {
        $arquivo = __DIR__ . '/../storage/.last_check_export';

        // Nunca rodou → roda
        if (!is_file($arquivo)) {
            @file_put_contents($arquivo, time());
            return true;
        }

        $ultimoCheck = (int) @file_get_contents($arquivo);
        $passou = (time() - $ultimoCheck) > ($intervaloMinutos * 60);

        if ($passou) {
            @file_put_contents($arquivo, time());
        }

        return $passou;
    }

    /**
     * Retorna o status de cada tipo/período para exibir no admin.
     */
    public static function status(): array
    {
        $pdo = Database::conn();
        $status = [];

        foreach (self::REGRAS as $tipo => $periodos) {
            foreach ($periodos as $periodo => $frequenciaDias) {
                $st = $pdo->prepare("
                    SELECT periodo_fim, gerado_em
                    FROM exportacoes
                    WHERE tipo = ? AND periodo = ?
                    ORDER BY gerado_em DESC
                    LIMIT 1
                ");
                $st->execute([$tipo, $periodo]);
                $ultima = $st->fetch(\PDO::FETCH_ASSOC);

                $proximaEm = null;
                $diasRestantes = null;

                if ($ultima) {
                    $proximaEm = date('Y-m-d H:i', strtotime($ultima['gerado_em'] . " +{$frequenciaDias} days"));
                    $diasRestantes = (int) floor(
                        ($frequenciaDias) - ((time() - strtotime($ultima['gerado_em'])) / 86400)
                    );
                }

                $status[] = [
                    'tipo'            => $tipo,
                    'tipo_label'      => ExportadorCSV::TIPOS[$tipo] ?? $tipo,
                    'periodo'         => $periodo,
                    'periodo_label'   => ExportadorCSV::PERIODOS[$periodo] ?? $periodo,
                    'frequencia_dias' => $frequenciaDias,
                    'ultima_em'       => $ultima['gerado_em'] ?? null,
                    'proxima_em'      => $proximaEm,
                    'dias_restantes'  => $diasRestantes,
                    'nunca_gerado'    => !$ultima,
                ];
            }
        }

        return $status;
    }
}