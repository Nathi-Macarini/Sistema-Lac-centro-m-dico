<?php
namespace App;

class TiposAtendimento
{
    public const LAC_TELEATENDIMENTO = 'lac_teleatendimento';
    public const LAC_ATENDE          = 'lac_atende';

    public const AGENDADO     = 'agendado';
    public const INICIADO     = 'iniciado';
    public const EM_ANDAMENTO = 'em_andamento';
    public const FINALIZADO   = 'finalizado';
    public const CANCELADO    = 'cancelado';

    public static function label(string $tag): string
    {
        return match ($tag) {
            self::LAC_TELEATENDIMENTO => 'LAC Teleatendimento',
            self::LAC_ATENDE          => 'LAC Atende',
            self::AGENDADO            => 'Agendado',
            self::INICIADO            => 'Iniciado',
            self::EM_ANDAMENTO        => 'Em andamento',
            self::FINALIZADO          => 'Finalizado',
            self::CANCELADO           => 'Cancelado',
            default                   => $tag,
        };
    }

    public static function badgeClass(string $tag): string
    {
        return match ($tag) {
            self::LAC_TELEATENDIMENTO => 'bg-primary',
            self::LAC_ATENDE          => 'bg-info text-dark',
            self::AGENDADO            => 'bg-warning text-dark',
            self::INICIADO            => 'bg-warning text-dark',
            self::EM_ANDAMENTO        => 'bg-success',
            self::FINALIZADO          => 'bg-secondary',
            self::CANCELADO           => 'bg-danger',
            default                   => 'bg-light text-dark',
        };
    }
}