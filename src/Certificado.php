<?php
namespace App;

class Certificado
{
    /**
     * Coleta o certificado SSL de um domínio.
     */
    public static function coletar(string $dominio, int $porta = 443, int $timeout = 10): ?array
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert'       => true,
                'capture_peer_cert_chain' => true,
                'verify_peer'             => false,
                'verify_peer_name'        => false,
                'SNI_enabled'             => true,
            ],
        ]);

        $client = @stream_socket_client(
            "ssl://{$dominio}:{$porta}",
            $errno, $errstr, $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$client) {
            return null;
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        if (!$cert) {
            return null;
        }

        $info = openssl_x509_parse($cert);
        if (!$info) {
            return null;
        }

        $validoDe  = $info['validFrom_time_t'] ?? null;
        $validoAte = $info['validTo_time_t']   ?? null;

        if (!$validoAte) {
            return null;
        }

        $diasRestantes = (int) floor(($validoAte - time()) / 86400);

        return [
            'dominio'        => $dominio,
            'porta'          => $porta,
            'emissor'        => $info['issuer']['CN']  ?? '—',
            'organizacao'    => $info['issuer']['O']   ?? '—',
            'subject'        => $info['subject']['CN'] ?? '—',
            'valido_de'      => $validoDe ? date('Y-m-d H:i:s', $validoDe) : '—',
            'valido_ate'     => date('Y-m-d H:i:s', $validoAte),
            'valido_ate_ts'  => $validoAte,
            'dias_restantes' => $diasRestantes,
            'expirado'       => $diasRestantes < 0,
            'alerta'         => $diasRestantes >= 0 && $diasRestantes <= 30,
        ];
    }

    /**
     * Salva o resultado na tabela lgpd_configuracoes.
     */
    public static function salvar(\mysqli $conn, array $r): void
    {
        LGPD::setConfig($conn, 'certificado_dominio',        $r['dominio']);
        LGPD::setConfig($conn, 'certificado_emissor',        $r['emissor']);
        LGPD::setConfig($conn, 'certificado_valido_ate',     $r['valido_ate']);
        LGPD::setConfig($conn, 'certificado_dias_restantes', (string)$r['dias_restantes']);
        LGPD::setConfig($conn, 'certificado_verificado_em',  date('Y-m-d H:i:s'));
    }

    /**
     * Registra alerta no log (se aplicável).
     */
    public static function logarAlerta(array $r): void
    {
        if (!class_exists('App\\ActivityLogger')) {
            return;
        }

        if ($r['expirado']) {
            ActivityLogger::log(
                action: 'certificado_expirado',
                entity: 'certificado',
                description: "ALERTA CRÍTICO: certificado SSL de {$r['dominio']} EXPIROU em {$r['valido_ate']}",
                metadata: $r,
                tags: ['seguranca', 'certificado', 'critico']
            );
        } elseif ($r['alerta']) {
            ActivityLogger::log(
                action: 'certificado_alerta',
                entity: 'certificado',
                description: "ALERTA: certificado SSL de {$r['dominio']} expira em {$r['dias_restantes']} dias",
                metadata: $r,
                tags: ['seguranca', 'certificado', 'alerta']
            );
        }
    }
}