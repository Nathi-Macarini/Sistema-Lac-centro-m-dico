<?php
namespace App;

/**
 * Utilitários para CPF.
 * Armazenamento seguro: hash + últimos 4 + versão criptografada.
 */
class CPF
{
    /**
     * Remove tudo que não é dígito.
     */
    public static function limpar(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf);
    }

    /**
     * Hash determinístico para busca (SHA-256).
     * Não é reversível — usado apenas para localizar o registro.
     */
    public static function hash(string $cpf): string
    {
        $limpo = self::limpar($cpf);
        if ($limpo === '') return '';
        return hash('sha256', $limpo);
    }

    /**
     * Retorna os últimos 4 dígitos.
     */
    public static function ultimos4(string $cpf): string
    {
        $limpo = self::limpar($cpf);
        return strlen($limpo) >= 4 ? substr($limpo, -4) : $limpo;
    }

    /**
     * Criptografa o CPF (reversível).
     */
    public static function criptografar(string $cpf): ?string
    {
        $limpo = self::limpar($cpf);
        return $limpo !== '' ? Crypto::encrypt($limpo) : null;
    }

    /**
     * Descriptografa o CPF.
     */
    public static function descriptografar(?string $enc): ?string
    {
        return Crypto::decrypt($enc);
    }

    /**
     * Formata CPF: 000.000.000-00
     */
    public static function formatar(string $cpf): string
    {
        $c = self::limpar($cpf);
        if (strlen($c) !== 11) return $cpf;
        return substr($c, 0, 3) . '.' . substr($c, 3, 3) . '.' . substr($c, 6, 3) . '-' . substr($c, 9, 2);
    }

    /**
     * Mascara o CPF para exibição pública: ***.***.***-XX
     */
    public static function mascarar(?string $ultimos4): string
    {
        if (!$ultimos4 || strlen($ultimos4) !== 4) return '***.***.***-**';
        return '***.***.***-' . $ultimos4;
    }
}