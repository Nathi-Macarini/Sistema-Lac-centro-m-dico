<?php
namespace App;

/**
 * Status possíveis de uma consulta.
 * Padrão definido na reunião com a LAC (item 24).
 */
class StatusConsulta
{
    // ---- Status individuais ----
    public const AGENDADA          = 'Agendada';
    public const CONFIRMADA        = 'Confirmada';
    public const REAGENDADA        = 'Reagendada';
    public const PRESENTE          = 'Presente';
    public const EM_ATENDIMENTO    = 'Em atendimento';
    public const PACIENTE_ATENDIDO = 'Paciente atendido';
    public const REMARCAR          = 'Remarcar';
    public const CANCELAR          = 'Cancelar';

    // ---- Grupos ----
    public const ATIVAS = [
        self::AGENDADA,
        self::CONFIRMADA,
        self::REAGENDADA,
        self::PRESENTE,
        self::EM_ATENDIMENTO,
    ];

    public const ONLINE = [
        self::PRESENTE,
        self::EM_ATENDIMENTO,
    ];

    public const FINALIZADAS = [
        self::PACIENTE_ATENDIDO,
    ];

    public const ENCERRADAS = [
        self::CANCELAR,
        self::REMARCAR,
    ];

    // ---- Labels legíveis ----
    public static function label(string $status): string
    {
        return match ($status) {
            self::AGENDADA          => 'Agendada',
            self::CONFIRMADA        => 'Confirmada',
            self::REAGENDADA        => 'Reagendada',
            self::PRESENTE          => 'Presente',
            self::EM_ATENDIMENTO    => 'Em atendimento',
            self::PACIENTE_ATENDIDO => 'Paciente atendido',
            self::REMARCAR          => 'Remarcar',
            self::CANCELAR          => 'Cancelada',
            default                 => $status,
        };
    }

    // ---- Classes Tailwind para badge ----
    public static function badgeClasses(string $status): string
    {
        return match ($status) {
            self::AGENDADA          => 'bg-amber-50 text-amber-800 border-amber-200',
            self::CONFIRMADA        => 'bg-blue-50 text-blue-800 border-blue-200',
            self::REAGENDADA        => 'bg-purple-50 text-purple-800 border-purple-200',
            self::PRESENTE          => 'bg-teal-50 text-teal-800 border-teal-200',
            self::EM_ATENDIMENTO    => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            self::PACIENTE_ATENDIDO => 'bg-stone-100 text-stone-700 border-stone-200',
            self::REMARCAR          => 'bg-orange-50 text-orange-800 border-orange-200',
            self::CANCELAR          => 'bg-red-50 text-red-800 border-red-200',
            default                 => 'bg-stone-100 text-stone-600 border-stone-200',
        };
    }

    // ---- Ícone Font Awesome ----
    public static function icon(string $status): string
    {
        return match ($status) {
            self::AGENDADA          => 'fa-regular fa-calendar',
            self::CONFIRMADA        => 'fa-regular fa-circle-check',
            self::REAGENDADA        => 'fa-regular fa-calendar-plus',
            self::PRESENTE          => 'fa-solid fa-user-check',
            self::EM_ATENDIMENTO    => 'fa-solid fa-video',
            self::PACIENTE_ATENDIDO => 'fa-solid fa-clipboard-check',
            self::REMARCAR          => 'fa-regular fa-clock-rotate-left',
            self::CANCELAR          => 'fa-solid fa-ban',
            default                 => 'fa-solid fa-circle',
        };
    }

    public static function proximo(string $atual): ?string
    {
        return match ($atual) {
            self::AGENDADA       => self::CONFIRMADA,
            self::CONFIRMADA     => self::PRESENTE,
            self::PRESENTE       => self::EM_ATENDIMENTO,
            self::EM_ATENDIMENTO => self::PACIENTE_ATENDIDO,
            default              => null,
        };
    }

    public static function opcoes(): array
    {
        return [
            self::AGENDADA          => 'Agendada',
            self::CONFIRMADA        => 'Confirmada',
            self::REAGENDADA        => 'Reagendada',
            self::PRESENTE          => 'Presente',
            self::EM_ATENDIMENTO    => 'Em atendimento',
            self::PACIENTE_ATENDIDO => 'Paciente atendido',
            self::REMARCAR          => 'Remarcar',
            self::CANCELAR          => 'Cancelar',
        ];
    }

    public static function sqlIn(array $status): string
    {
        return "'" . implode("','", array_map('addslashes', $status)) . "'";
    }

    // ============================================================
    // 👇 NOVOS MÉTODOS PARA LOGS
    // ============================================================

    /**
     * Retorna o status em formato de tag (minúsculo, sem espaços).
     * Ex: 'Em atendimento' → 'em_atendimento'
     */
    public static function tag(string $status): string
    {
        return match ($status) {
            self::AGENDADA          => 'agendada',
            self::CONFIRMADA        => 'confirmada',
            self::REAGENDADA        => 'reagendada',
            self::PRESENTE          => 'presente',
            self::EM_ATENDIMENTO    => 'em_atendimento',
            self::PACIENTE_ATENDIDO => 'paciente_atendido',
            self::REMARCAR          => 'remarcar',
            self::CANCELAR          => 'cancelar',
            default                 => strtolower(str_replace(' ', '_', $status)),
        };
    }

    /**
     * Lista de tags de status disponíveis (para filtros).
     * Formato: ['tag' => 'Label']
     */
    public static function tagsFiltro(): array
    {
        return [
            self::tag(self::AGENDADA)          => 'Agendada',
            self::tag(self::CONFIRMADA)        => 'Confirmada',
            self::tag(self::REAGENDADA)        => 'Reagendada',
            self::tag(self::PRESENTE)          => 'Presente',
            self::tag(self::EM_ATENDIMENTO)    => 'Em atendimento',
            self::tag(self::PACIENTE_ATENDIDO) => 'Paciente atendido',
            self::tag(self::CANCELAR)          => 'Cancelada',
        ];
    }
}