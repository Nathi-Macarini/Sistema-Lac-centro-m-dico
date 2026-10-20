<?php
namespace App;

/**
 * Carregador simples de variáveis de ambiente (.env).
 * Sem dependência externa.
 */
class Env
{
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(string $caminho): void
    {
        if (self::$loaded) return;
        if (!is_file($caminho)) {
            self::$loaded = true;
            return;
        }

        $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($linhas as $linha) {
            $linha = trim($linha);

            // Ignora comentários e linhas vazias
            if ($linha === '' || str_starts_with($linha, '#')) continue;

            // Remove "export " se tiver
            if (str_starts_with($linha, 'export ')) {
                $linha = substr($linha, 7);
            }

            // Split por "="
            if (!str_contains($linha, '=')) continue;
            [$chave, $valor] = explode('=', $linha, 2);

            $chave = trim($chave);
            $valor = trim($valor);

            // Remove aspas
            if (strlen($valor) >= 2) {
                $primeiro = $valor[0];
                $ultimo   = $valor[strlen($valor) - 1];
                if (($primeiro === '"' && $ultimo === '"') || ($primeiro === "'" && $ultimo === "'")) {
                    $valor = substr($valor, 1, -1);
                }
            }

            self::$vars[$chave] = $valor;

            // Também disponibiliza via getenv()/putenv() para compatibilidade
            $_ENV[$chave]    = $valor;
            $_SERVER[$chave] = $valor;
        }

        self::$loaded = true;
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        return self::$vars[$chave] ?? $padrao;
    }

    public static function exigir(string $chave): string
    {
        $valor = self::get($chave);
        if ($valor === null || $valor === '') {
            throw new \RuntimeException("Variável de ambiente obrigatória ausente: {$chave}");
        }
        return $valor;
    }
}