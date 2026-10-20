<?php
namespace App;

class Backup
{
    /**
     * Executa um backup completo do banco via mysqldump.
     * 
     * @return array{ok:bool, arquivo:?string, tamanho:int, erro:?string, duracao:float}
     */
    public static function executar(\mysqli $conn, int $uidExec = 0, string $tipo = 'manual'): array
    {
        $inicio = microtime(true);

        $dir = __DIR__ . '/../storage/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Nome do arquivo
        $nomeArquivo = sprintf(
            'backup_%s_%s.sql',
            $tipo === 'automatico' ? 'auto' : 'manual',
            date('Ymd_His')
        );
        $caminhoCompleto = $dir . '/' . $nomeArquivo;
        $caminhoRelativo = 'storage/backups/' . $nomeArquivo;

        // Configurações do banco
        $db = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => (int)(getenv('DB_PORT') ?: 3306),
            'user' => getenv('DB_USER') ?: 'root',
            'pass' => getenv('DB_PASS') ?: '',
            'name' => getenv('DB_NAME') ?: 'lac_centro_medico',
        ];

        // Caminho do mysqldump (Windows XAMPP ou Linux)
        $mysqldump = self::localizarMysqldump();
        if (!$mysqldump) {
            return [
                'ok' => false, 'arquivo' => null, 'tamanho' => 0,
                'erro' => 'mysqldump não encontrado', 'duracao' => 0,
            ];
        }

        // Comando
        if (PHP_OS_FAMILY === 'Windows') {
            // No Windows, usar --result-file evita problemas de redirecionamento
            $cmd = sprintf(
                '%s --host=%s --port=%d --user=%s --password=%s --single-transaction --routines --triggers --result-file=%s %s 2>&1',
                escapeshellarg($mysqldump),
                escapeshellarg($db['host']),
                $db['port'],
                escapeshellarg($db['user']),
                escapeshellarg($db['pass']),
                escapeshellarg($caminhoCompleto),
                escapeshellarg($db['name'])
            );
        } else {
            $cmd = sprintf(
                '%s --host=%s --port=%d --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
                escapeshellarg($mysqldump),
                escapeshellarg($db['host']),
                $db['port'],
                escapeshellarg($db['user']),
                escapeshellarg($db['pass']),
                escapeshellarg($db['name']),
                escapeshellarg($caminhoCompleto)
            );
        }

        exec($cmd, $output, $exit);
        $duracao = round(microtime(true) - $inicio, 2);

        // Verifica se o arquivo foi criado
        $tamanho = is_file($caminhoCompleto) ? (int) filesize($caminhoCompleto) : 0;
        $sucesso = ($exit === 0 && $tamanho > 100);

        // Conta tabelas e registros (rápido, apenas de metadados)
        $tabelas = 0;
        $registros = 0;
        if ($sucesso) {
            try {
                $res = $conn->query("SHOW TABLES");
                while ($t = $res->fetch_row()) {
                    $tabelas++;
                    // Estimativa (não conta exato, é só para relatório)
                    $count = $conn->query("SELECT COUNT(*) FROM `{$t[0]}`");
                    if ($count) $registros += (int)$count->fetch_row()[0];
                }
            } catch (\Throwable $e) {}
        }

        $erro = $sucesso ? null : (implode("\n", $output) ?: "exit={$exit}");

        // Registra no banco
        $st = $conn->prepare("
            INSERT INTO lgpd_backups
                (arquivo, tamanho_bytes, tabelas, registros, tipo, status, erro, duracao_seg, executado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $statusStr = $sucesso ? 'sucesso' : 'erro';
        $uidExec   = $uidExec ?: null;
        $st->bind_param(
            'siiisssdi',
            $caminhoRelativo, $tamanho, $tabelas, $registros,
            $tipo, $statusStr, $erro, $duracao, $uidExec
        );
        $st->execute();

        // Log de auditoria
        if (class_exists('App\\ActivityLogger')) {
            ActivityLogger::log(
                action: $sucesso ? 'backup_realizado' : 'backup_falhou',
                entity: 'backup',
                entityId: (int)$conn->insert_id,
                description: $sucesso
                    ? "Backup {$tipo} gerado: {$nomeArquivo} (" . self::formatarTamanho($tamanho) . ")"
                    : "Falha no backup {$tipo}: " . substr($erro, 0, 200),
                metadata: [
                    'arquivo'   => $caminhoRelativo,
                    'tamanho'   => $tamanho,
                    'tabelas'   => $tabelas,
                    'duracao'   => $duracao,
                ],
                tags: ['seguranca', 'backup']
            );
        }

        // Limpeza automática (mantém apenas os últimos N dias)
        if ($sucesso) {
            try { self::limparAntigos($conn); } catch (\Throwable $e) {}
        }

        return [
            'ok'      => $sucesso,
            'arquivo' => $sucesso ? $caminhoRelativo : null,
            'tamanho' => $tamanho,
            'erro'    => $erro,
            'duracao' => $duracao,
        ];
    }

    /**
     * Lista backups existentes.
     */
    public static function listar(\mysqli $conn, int $limite = 50): array
    {
        $st = $conn->prepare("
            SELECT b.*, u.nome AS executado_nome
            FROM lgpd_backups b
            LEFT JOIN usuarios u ON u.id = b.executado_por
            ORDER BY b.criado_em DESC
            LIMIT ?
        ");
        $st->bind_param('i', $limite);
        $st->execute();
        return $st->get_result()->fetch_all(\MYSQLI_ASSOC);
    }

    /**
     * Remove backups antigos (> N dias) e seus arquivos físicos.
     */
    public static function limparAntigos(\mysqli $conn): int
    {
        $dias = (int) LGPD::config($conn, 'backup_manter_dias', '90');
        $limite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        $st = $conn->prepare("SELECT id, arquivo FROM lgpd_backups WHERE criado_em < ? AND status = 'sucesso'");
        $st->bind_param('s', $limite);
        $st->execute();
        $antigos = $st->get_result()->fetch_all(\MYSQLI_ASSOC);

        $removidos = 0;
        foreach ($antigos as $b) {
            $caminho = __DIR__ . '/../' . $b['arquivo'];
            if (is_file($caminho)) @unlink($caminho);

            $del = $conn->prepare("DELETE FROM lgpd_backups WHERE id = ?");
            $del->bind_param('i', $b['id']);
            $del->execute();
            $removidos++;
        }

        return $removidos;
    }

    /**
     * Remove um backup específico (registro + arquivo).
     */
    public static function excluir(\mysqli $conn, int $id): bool
    {
        $st = $conn->prepare("SELECT arquivo FROM lgpd_backups WHERE id = ?");
        $st->bind_param('i', $id);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();

        if (!$row) return false;

        $caminho = __DIR__ . '/../' . $row['arquivo'];
        if (is_file($caminho)) @unlink($caminho);

        $del = $conn->prepare("DELETE FROM lgpd_backups WHERE id = ?");
        $del->bind_param('i', $id);
        return $del->execute();
    }

    /**
     * Localiza o mysqldump conforme o SO.
     */
    private static function localizarMysqldump(): ?string
    {
        $possiveis = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $possiveis = [
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
                'mysqldump.exe',
            ];
        } else {
            $possiveis = [
                '/usr/bin/mysqldump',
                '/usr/local/bin/mysqldump',
                '/opt/lampp/bin/mysqldump',
                'mysqldump',
            ];
        }

        foreach ($possiveis as $p) {
            if ($p === 'mysqldump.exe' || $p === 'mysqldump') {
                // Testa via PATH
                $test = @shell_exec((PHP_OS_FAMILY === 'Windows' ? 'where ' : 'which ') . $p . ' 2>&1');
                if ($test && !str_contains(strtolower($test), 'not found')) {
                    return trim($test);
                }
            } elseif (is_file($p)) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Formata bytes.
     */
    public static function formatarTamanho(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}