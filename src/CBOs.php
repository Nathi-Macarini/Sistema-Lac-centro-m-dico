<?php
namespace App;

/**
 * Mapeamento de CBO (Classificação Brasileira de Ocupações) por especialidade.
 * Códigos baseados no CBO 2002 (Ministério do Trabalho).
 */
class CBOs
{
    /**
     * Retorna a lista de CBOs de uma especialidade.
     * Formato: ['codigo' => 'Descrição']
     */
    public static function paraEspecialidade(string $especialidade): array
    {
        // Sempre inclui os genéricos
        $genericos = [
            '225125' => 'Médico clínico',
            '225130' => 'Médico de família e comunidade',
            '225195' => 'Médico generalista',
        ];

        $porEsp = match (strtolower(trim($especialidade))) {
            'cardiologia' => [
                '225120' => 'Médico cardiologista',
            ],
            'dermatologia' => [
                '225135' => 'Médico dermatologista',
            ],
            'ortopedia' => [
                '225270' => 'Médico ortopedista e traumatologista',
            ],
            'ginecologia' => [
                '225225' => 'Médico ginecologista e obstetra',
                '225250' => 'Médico obstetra',
            ],
            'pediatria' => [
                '225124' => 'Médico pediatra',
                '225310' => 'Médico cirurgião pediátrico',
            ],
            'clínica geral', 'clinica geral' => [
                '225125' => 'Médico clínico',
                '225130' => 'Médico de família e comunidade',
            ],
            default => [],
        };

        // Especialidade + genéricos (sem duplicar)
        return $porEsp + $genericos;
    }

    /**
     * Retorna a descrição de um código CBO.
     */
    public static function descricao(string $codigo): string
    {
        $todos = [
            '225120' => 'Médico cardiologista',
            '225124' => 'Médico pediatra',
            '225125' => 'Médico clínico',
            '225130' => 'Médico de família e comunidade',
            '225135' => 'Médico dermatologista',
            '225195' => 'Médico generalista',
            '225225' => 'Médico ginecologista e obstetra',
            '225250' => 'Médico obstetra',
            '225270' => 'Médico ortopedista e traumatologista',
            '225310' => 'Médico cirurgião pediátrico',
        ];
        return $todos[$codigo] ?? $codigo;
    }

    /**
     * CBO padrão para uma especialidade (primeiro da lista).
     */
    public static function padrao(string $especialidade): string
    {
        $lista = self::paraEspecialidade($especialidade);
        return array_key_first($lista) ?: '225125';
    }

    /**
     * Opções de validade em dias (para o select).
     */
    public static function validades(): array
    {
        return [
            30  => '30 dias (padrão)',
            60  => '60 dias',
            90  => '90 dias',
            180 => '180 dias (6 meses)',
        ];
    }
}