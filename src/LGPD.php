<?php
namespace App;

/**
 * Helper centralizado para direitos do titular (LGPD).
 */
class LGPD
{
    /**
     * Tipos de solicitação disponíveis.
     */
    public const TIPOS = [
    'acesso'                     => 'Acesso aos meus dados',
    'correcao'                   => 'Correção de dados',
    'portabilidade'              => 'Portabilidade dos dados',
    'exclusao'                   => 'Exclusão da conta',
    'revogacao_consentimento'    => 'Revogação de consentimento',
    'anonimizacao'               => 'Anonimização de dados',
    'informacao_compartilhamento'=> 'Informação sobre compartilhamento',
    'duvida_dpo'                 => 'Dúvida / contato com o DPO',
];

    /**
     * Gera um protocolo único.
     */
    public static function gerarProtocolo(): string
    {
        return 'LGPD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Cria uma solicitação.
     */
    public static function criarSolicitacao(\mysqli $conn, int $usuarioId, string $tipo, ?string $descricao = null): array
    {
        if (!array_key_exists($tipo, self::TIPOS)) {
            throw new \InvalidArgumentException("Tipo inválido: {$tipo}");
        }

        $protocolo = self::gerarProtocolo();
        $prazo     = date('Y-m-d', strtotime('+15 days')); // LGPD: prazo razoável
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $st = $conn->prepare("
            INSERT INTO lgpd_solicitacoes
                (usuario_id, protocolo, tipo, descricao, prazo_resposta, ip_solicitante)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $st->bind_param('isssss', $usuarioId, $protocolo, $tipo, $descricao, $prazo, $ip);
        $st->execute();

        return [
            'id'        => (int)$conn->insert_id,
            'protocolo' => $protocolo,
            'prazo'     => $prazo,
        ];
    }

    /**
     * Exporta todos os dados do usuário em JSON.
     */
    public static function exportarDados(\mysqli $conn, int $usuarioId): array
    {
        $dados = [
            'exportado_em'   => date('c'),
            'base_legal'     => 'LGPD - Art. 18, II e V',
            'usuario'        => null,
            'dependentes'    => [],
            'planos'         => [],
            'consultas'      => [],
            'atendimentos'   => [],
            'documentos'     => [],
            'termos_aceitos' => [],
            'logs_acesso'    => [],
        ];

        // ---------- Usuário ----------
        $st = $conn->prepare("SELECT id, nome, nome_mae, email, cpf_enc, telefone, data_nascimento, endereco, tipo, criado_em FROM usuarios WHERE id = ?");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        $u = $st->get_result()->fetch_assoc();
        if (!$u) throw new \RuntimeException('Usuário não encontrado.');

        // Decifra CPF
        $u['cpf'] = CPF::descriptografar($u['cpf_enc'] ?? null);
        unset($u['cpf_enc']);

        $dados['usuario'] = $u;

        // ---------- Dependentes ----------
        $st = $conn->prepare("SELECT id, nome, cpf_enc, data_nascimento, parentesco FROM dependentes WHERE titular_id = ?");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        foreach ($st->get_result()->fetch_all(\MYSQLI_ASSOC) as $d) {
            $d['cpf'] = CPF::descriptografar($d['cpf_enc'] ?? null);
            unset($d['cpf_enc']);
            $dados['dependentes'][] = $d;
        }

        // ---------- Planos ----------
        $st = $conn->prepare("SELECT id, operadora, numero_carteirinha, validade, acomodacao, criado_em FROM planos WHERE usuario_id = ?");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        $dados['planos'] = $st->get_result()->fetch_all(\MYSQLI_ASSOC);

        // ---------- Consultas ----------
        $st = $conn->prepare("
            SELECT c.id, c.especialidade, c.modalidade, c.tipo, c.data_hora, c.status,
                   m.nome AS medico_nome
            FROM consultas c
            LEFT JOIN medicos m ON m.id = c.medico_id
            WHERE c.usuario_id = ?
            ORDER BY c.data_hora DESC
        ");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        $dados['consultas'] = $st->get_result()->fetch_all(\MYSQLI_ASSOC);

        // ---------- Atendimentos ----------
        $st = $conn->prepare("
            SELECT a.id, a.criado_em, a.queixa_principal, a.historia_doenca, a.exame_fisico,
                   a.pressao_arterial, a.freq_cardiaca, a.temperatura, a.saturacao, a.peso_kg, a.altura_cm,
                   a.cid10, a.conduta, a.exames_solicitados, a.retorno,
                   a.diagnostico, a.diagnostico_enc,
                   a.prescricao, a.prescricao_enc,
                   m.nome AS medico_nome
            FROM atendimentos a
            LEFT JOIN medicos m ON m.id = a.medico_id
            WHERE a.paciente_id = ?
            ORDER BY a.criado_em DESC
        ");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        foreach ($st->get_result()->fetch_all(\MYSQLI_ASSOC) as $a) {
            if (!empty($a['diagnostico_enc'])) {
                $a['diagnostico'] = Crypto::decrypt($a['diagnostico_enc']) ?? $a['diagnostico'];
            }
            if (!empty($a['prescricao_enc'])) {
                $a['prescricao'] = Crypto::decrypt($a['prescricao_enc']) ?? $a['prescricao'];
            }
            unset($a['diagnostico_enc'], $a['prescricao_enc']);
            $dados['atendimentos'][] = $a;
        }

        // ---------- Documentos ----------
        $st = $conn->prepare("
            SELECT d.id, d.tipo, d.criado_em, d.dias_afastamento, d.validade_dias, d.cid10, d.cbo, d.codigo,
                   d.conteudo, d.conteudo_enc,
                   m.nome AS medico_nome
            FROM documentos d
            LEFT JOIN medicos m ON m.id = d.medico_id
            WHERE d.paciente_id = ?
            ORDER BY d.criado_em DESC
        ");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        foreach ($st->get_result()->fetch_all(\MYSQLI_ASSOC) as $d) {
            if (!empty($d['conteudo_enc'])) {
                $d['conteudo'] = Crypto::decrypt($d['conteudo_enc']) ?? $d['conteudo'];
            }
            unset($d['conteudo_enc']);
            $dados['documentos'][] = $d;
        }

        // ---------- Termos aceitos ----------
        $st = $conn->prepare("SELECT termo_tipo, termo_versao, aceito_em, ip_address FROM termos_aceitos WHERE usuario_id = ? ORDER BY aceito_em DESC");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        $dados['termos_aceitos'] = $st->get_result()->fetch_all(\MYSQLI_ASSOC);

        // ---------- Acessos ao prontuário ----------
        $st = $conn->prepare("
            SELECT a.acao, a.ip_address, a.criado_em,
                   m.nome AS medico_nome, u.nome AS admin_nome
            FROM acessos_prontuario a
            LEFT JOIN medicos m ON m.id = a.medico_id
            LEFT JOIN usuarios u ON u.id = a.admin_id
            WHERE a.paciente_id = ?
            ORDER BY a.criado_em DESC
            LIMIT 200
        ");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        $dados['logs_acesso'] = $st->get_result()->fetch_all(\MYSQLI_ASSOC);

        return $dados;
    }

    /**
     * Lista solicitações de um usuário.
     */
    public static function listarSolicitacoes(\mysqli $conn, int $usuarioId): array
    {
        $st = $conn->prepare("
            SELECT s.*, u.nome AS respondido_nome
            FROM lgpd_solicitacoes s
            LEFT JOIN usuarios u ON u.id = s.respondido_por
            WHERE s.usuario_id = ?
            ORDER BY s.criado_em DESC
        ");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        return $st->get_result()->fetch_all(\MYSQLI_ASSOC);
    }

    /**
     * Verifica se o usuário tem solicitação pendente de um tipo.
     */
    public static function temPendente(\mysqli $conn, int $usuarioId, string $tipo): bool
    {
        $st = $conn->prepare("
            SELECT id FROM lgpd_solicitacoes
            WHERE usuario_id = ? AND tipo = ? AND status IN ('pendente', 'em_analise')
            LIMIT 1
        ");
        $st->bind_param('is', $usuarioId, $tipo);
        $st->execute();
        return (bool)$st->get_result()->fetch_assoc();
    }

    public static function registrarAcessoProntuario(
    \mysqli $conn, int $pacienteId, ?int $medicoId, ?int $adminId,
    string $acao, ?int $prontuarioId = null
): void {
    // ============================================================
    // Deduplicação: não grava se já houve acesso idêntico
    // nos últimos 30 minutos (mesmo médico + mesmo paciente + mesma ação)
    // ============================================================
    $janelaMinutos = 30;
    $limite = date('Y-m-d H:i:s', time() - ($janelaMinutos * 60));

    $st = $conn->prepare("
        SELECT id
        FROM acessos_prontuario
        WHERE paciente_id = ?
          AND acao = ?
          AND (medico_id <=> ? OR admin_id <=> ?)
          AND criado_em >= ?
        LIMIT 1
    ");
    $st->bind_param('issis', $pacienteId, $acao, $medicoId, $adminId, $limite);
    $st->execute();

    if ($st->get_result()->fetch_assoc()) {
        // Já registrou esse acesso recentemente — não duplica
        return;
    }

    // ============================================================
    // Grava novo acesso
    // ============================================================
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $st = $conn->prepare("
        INSERT INTO acessos_prontuario
            (prontuario_id, paciente_id, medico_id, admin_id, acao, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $st->bind_param('iiiisss', $prontuarioId, $pacienteId, $medicoId, $adminId, $acao, $ip, $ua);
    $st->execute();
}

    /**
     * Retorna configurações LGPD.
     */
    public static function config(\mysqli $conn, string $chave, ?string $padrao = null): ?string
    {
        $st = $conn->prepare("SELECT valor FROM lgpd_configuracoes WHERE chave = ?");
        $st->bind_param('s', $chave);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        return $row['valor'] ?? $padrao;
    }

    /**
     * Atualiza configuração LGPD.
     */
    public static function setConfig(\mysqli $conn, string $chave, ?string $valor): void
    {
        $st = $conn->prepare("
            INSERT INTO lgpd_configuracoes (chave, valor) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ");
        $st->bind_param('ss', $chave, $valor);
        $st->execute();
    }

    /**
     * Anonimiza um usuário (mantém dados clínicos, remove identificadores).
     */
    public static function anonimizarUsuario(\mysqli $conn, int $usuarioId): bool
    {
        $novoNome   = 'Paciente Anonimizado #' . $usuarioId;
        $novoEmail  = 'anonimo_' . $usuarioId . '@anonimizado.local';
        $novoCpf    = 'ANONIMIZADO_' . $usuarioId;
        $novoCpfHash = hash('sha256', $novoCpf);
        $novoSenha  = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $st = $conn->prepare("
            UPDATE usuarios
            SET nome = ?, nome_mae = 'Anonimizado',
                email = ?, cpf = NULL, cpf_hash = ?, cpf_ultimos4 = NULL, cpf_enc = NULL,
                telefone = NULL, endereco = NULL, data_nascimento = NULL,
                senha = ?, anonimizado_em = NOW()
            WHERE id = ?
        ");
        $st->bind_param('ssssi', $novoNome, $novoEmail, $novoCpfHash, $novoSenha, $usuarioId);
        $st->execute();

        // Anonimiza dependentes
        $st = $conn->prepare("
            UPDATE dependentes
            SET nome = CONCAT('Dependente Anonimizado #', id),
                cpf = NULL, cpf_hash = NULL, cpf_ultimos4 = NULL, cpf_enc = NULL,
                data_nascimento = NULL
            WHERE titular_id = ?
        ");
        $st->bind_param('i', $usuarioId);
        $st->execute();

        // Anonimiza dados clínicos do prontuário
        $st = $conn->prepare("
            UPDATE prontuarios
            SET alergias = NULL, doencas_cronicas = NULL, medicamentos_uso = NULL,
                cirurgias_previas = NULL, historico_familiar = NULL,
                habitos_vida = NULL, observacoes = NULL,
                alergias_enc = NULL, doencas_cronicas_enc = NULL, medicamentos_uso_enc = NULL
            WHERE paciente_id = ?
        ");
        $st->bind_param('i', $usuarioId);
        $st->execute();

        return true;
    }
}