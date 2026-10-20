<?php
namespace App;

class Retencao
{
    /**
     * Analisa sem alterar nada — retorna o que SERIA removido/anonimizado.
     * Modo "dry run" para conferência antes de executar.
     */
    public static function analisar(\mysqli $conn): array
    {
        $logsMeses    = (int) LGPD::config($conn, 'retencao_logs_meses', '6');
        $cookiesMeses = (int) LGPD::config($conn, 'retencao_cookies_meses', '6');
        $cadastroAnos = (int) LGPD::config($conn, 'retencao_cadastro_anos', '5');
        $clinicoAnos  = (int) LGPD::config($conn, 'retencao_clinico_anos', '20');

        $limiteLogs    = date('Y-m-d H:i:s', strtotime("-{$logsMeses} months"));
        $limiteCookies = date('Y-m-d H:i:s', strtotime("-{$cookiesMeses} months"));
        $limiteCad     = date('Y-m-d', strtotime("-{$cadastroAnos} years"));
        $limiteCli     = date('Y-m-d', strtotime("-{$clinicoAnos} years"));

        // ---------- Logs ----------
        $st = $conn->prepare("SELECT COUNT(*) AS n FROM activity_logs WHERE created_at < ?");
        $st->bind_param('s', $limiteLogs);
        $st->execute();
        $logsTotal = (int)$st->get_result()->fetch_assoc()['n'];

        // ---------- Cookies ----------
        $st = $conn->prepare("SELECT COUNT(*) AS n FROM cookies_consentimentos WHERE criado_em < ?");
        $st->bind_param('s', $limiteCookies);
        $st->execute();
        $cookiesTotal = (int)$st->get_result()->fetch_assoc()['n'];

        // ---------- Usuários inativos (5 anos) ----------
        $st = $conn->prepare("
            SELECT COUNT(*) AS n
            FROM usuarios u
            WHERE u.tipo = 'comum'
              AND u.anonimizado_em IS NULL
              AND u.criado_em < ?
              AND NOT EXISTS (
                  SELECT 1 FROM consultas c
                  WHERE c.usuario_id = u.id
                    AND c.data_hora >= ?
              )
        ");
        $st->bind_param('ss', $limiteCad, $limiteCad);
        $st->execute();
        $inativosTotal = (int)$st->get_result()->fetch_assoc()['n'];

        // ---------- Dados clínicos (>20 anos) — só reporta ----------
        $st = $conn->prepare("
            SELECT COUNT(*) AS n
            FROM atendimentos a
            WHERE a.criado_em < ?
        ");
        $st->bind_param('s', $limiteCli);
        $st->execute();
        $clinicosTotal = (int)$st->get_result()->fetch_assoc()['n'];

        // ---------- Amostras (primeiras 20 de cada) ----------
        $st = $conn->prepare("
            SELECT id, user_name, action, created_at
            FROM activity_logs
            WHERE created_at < ?
            ORDER BY created_at ASC
            LIMIT 20
        ");
        $st->bind_param('s', $limiteLogs);
        $st->execute();
        $amostraLogs = $st->get_result()->fetch_all(\MYSQLI_ASSOC);

        $st = $conn->prepare("
            SELECT id, nome, email, criado_em
            FROM usuarios u
            WHERE u.tipo = 'comum'
              AND u.anonimizado_em IS NULL
              AND u.criado_em < ?
              AND NOT EXISTS (
                  SELECT 1 FROM consultas c
                  WHERE c.usuario_id = u.id
                    AND c.data_hora >= ?
              )
            ORDER BY u.criado_em ASC
            LIMIT 20
        ");
        $st->bind_param('ss', $limiteCad, $limiteCad);
        $st->execute();
        $amostraUsuarios = $st->get_result()->fetch_all(\MYSQLI_ASSOC);

        return [
            'limites' => [
                'logs_meses'    => $logsMeses,
                'cookies_meses' => $cookiesMeses,
                'cadastro_anos' => $cadastroAnos,
                'clinico_anos'  => $clinicoAnos,
                'limite_logs'   => $limiteLogs,
                'limite_cookies'=> $limiteCookies,
                'limite_cad'    => $limiteCad,
                'limite_cli'    => $limiteCli,
            ],
            'totais' => [
                'logs'      => $logsTotal,
                'cookies'   => $cookiesTotal,
                'inativos'  => $inativosTotal,
                'clinicos'  => $clinicosTotal,
            ],
            'amostras' => [
                'logs'     => $amostraLogs,
                'usuarios' => $amostraUsuarios,
            ],
        ];
    }

    /**
     * Executa a retenção de verdade.
     */
    public static function executar(\mysqli $conn, int $uidExec = 0): array
    {
        $inicio = microtime(true);
        $erros  = [];

        $logsMeses    = (int) LGPD::config($conn, 'retencao_logs_meses', '6');
        $cookiesMeses = (int) LGPD::config($conn, 'retencao_cookies_meses', '6');
        $cadastroAnos = (int) LGPD::config($conn, 'retencao_cadastro_anos', '5');

        $limiteLogs    = date('Y-m-d H:i:s', strtotime("-{$logsMeses} months"));
        $limiteCookies = date('Y-m-d H:i:s', strtotime("-{$cookiesMeses} months"));
        $limiteCad     = date('Y-m-d', strtotime("-{$cadastroAnos} years"));

        // ============================================================
        // 1) Limpar logs antigos
        // ============================================================
        $logsRemovidos = 0;
        try {
            $st = $conn->prepare("DELETE FROM activity_logs WHERE created_at < ?");
            $st->bind_param('s', $limiteLogs);
            $st->execute();
            $logsRemovidos = (int)$st->affected_rows;
        } catch (\Throwable $e) {
            $erros[] = "logs: " . $e->getMessage();
        }

        // ============================================================
        // 2) Limpar consentimentos de cookies antigos
        // ============================================================
        $cookiesRemovidos = 0;
        try {
            $st = $conn->prepare("DELETE FROM cookies_consentimentos WHERE criado_em < ?");
            $st->bind_param('s', $limiteCookies);
            $st->execute();
            $cookiesRemovidos = (int)$st->affected_rows;
        } catch (\Throwable $e) {
            $erros[] = "cookies: " . $e->getMessage();
        }

        // ============================================================
        // 3) Anonimizar usuários inativos há 5+ anos
        // ============================================================
        $usuariosAnonimizados = 0;
        try {
            // Busca IDs candidatos
            $st = $conn->prepare("
                SELECT u.id
                FROM usuarios u
                WHERE u.tipo = 'comum'
                  AND u.anonimizado_em IS NULL
                  AND u.criado_em < ?
                  AND NOT EXISTS (
                      SELECT 1 FROM consultas c
                      WHERE c.usuario_id = u.id
                        AND c.data_hora >= ?
                  )
            ");
            $st->bind_param('ss', $limiteCad, $limiteCad);
            $st->execute();
            $candidatos = $st->get_result()->fetch_all(\MYSQLI_ASSOC);

            foreach ($candidatos as $u) {
                try {
                    LGPD::anonimizarUsuario($conn, (int)$u['id']);
                    $usuariosAnonimizados++;
                } catch (\Throwable $e) {
                    $erros[] = "anonimizar #{$u['id']}: " . $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $erros[] = "anonimização: " . $e->getMessage();
        }

        // ============================================================
        // 4) Registrar a execução
        // ============================================================
        $duracao = round(microtime(true) - $inicio, 2);
        $errosTxt = $erros ? implode("\n", $erros) : null;

        $st = $conn->prepare("
            INSERT INTO lgpd_retencao_execucoes
                (executado_por, modo, logs_removidos, cookies_removidos, usuarios_anonimizados, erros, duracao_seg)
            VALUES (?, 'execucao', ?, ?, ?, ?, ?)
        ");
        $uidExec = $uidExec ?: null;
        $st->bind_param('iiiisd', $uidExec, $logsRemovidos, $cookiesRemovidos, $usuariosAnonimizados, $errosTxt, $duracao);
        $st->execute();

        // ============================================================
        // 5) Log de auditoria
        // ============================================================
        if (class_exists('App\\ActivityLogger')) {
            ActivityLogger::log(
                action: 'lgpd_retencao_executada',
                entity: 'lgpd_retencao',
                entityId: (int)$conn->insert_id,
                description: "Retenção executada: {$logsRemovidos} logs, {$cookiesRemovidos} cookies, {$usuariosAnonimizados} usuários anonimizados",
                metadata: [
                    'logs_removidos'        => $logsRemovidos,
                    'cookies_removidos'     => $cookiesRemovidos,
                    'usuarios_anonimizados' => $usuariosAnonimizados,
                    'duracao_seg'           => $duracao,
                    'erros'                 => $erros,
                ],
                tags: ['lgpd', 'retencao']
            );
        }

        return [
            'logs_removidos'        => $logsRemovidos,
            'cookies_removidos'     => $cookiesRemovidos,
            'usuarios_anonimizados' => $usuariosAnonimizados,
            'duracao_seg'           => $duracao,
            'erros'                 => $erros,
        ];
    }

    /**
     * Verifica se já é hora de rodar (roda no máximo 1x a cada 24h).
     */
    public static function deveExecutar(int $intervaloHoras = 24): bool
    {
        $arquivo = __DIR__ . '/../storage/.last_check_retencao';

        if (!is_file($arquivo)) {
            @file_put_contents($arquivo, time());
            return true;
        }

        $ultimo = (int) @file_get_contents($arquivo);
        $passou = (time() - $ultimo) > ($intervaloHoras * 3600);

        if ($passou) {
            @file_put_contents($arquivo, time());
        }

        return $passou;
    }

    /**
     * Retorna o histórico de execuções.
     */
    public static function historico(\mysqli $conn, int $limite = 20): array
    {
        $st = $conn->prepare("
            SELECT r.*, u.nome AS executado_nome
            FROM lgpd_retencao_execucoes r
            LEFT JOIN usuarios u ON u.id = r.executado_por
            ORDER BY r.criado_em DESC
            LIMIT ?
        ");
        $st->bind_param('i', $limite);
        $st->execute();
        return $st->get_result()->fetch_all(\MYSQLI_ASSOC);
    }
}