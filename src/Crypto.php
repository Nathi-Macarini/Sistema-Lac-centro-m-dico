<?php
namespace App;

/**
 * Criptografia simétrica AES-256-GCM (reversível).
 * 
 * Formato do retorno: base64(IV[12] || TAG[16] || CIPHERTEXT)
 */
class Crypto
{
    private const METODO = 'aes-256-gcm';
    private const IV_LEN = 12;   // recomendado pelo NIST para GCM
    private const TAG_LEN = 16;

    private static ?string $chave = null;

    private static function chave(): string
    {
        if (self::$chave === null) {
            $hex = Env::exigir('ENCRYPTION_KEY');

            if (strlen($hex) !== 64) {
                throw new \RuntimeException('ENCRYPTION_KEY deve ter 64 caracteres hex (32 bytes).');
            }
            if (!ctype_xdigit($hex)) {
                throw new \RuntimeException('ENCRYPTION_KEY contém caracteres inválidos.');
            }

            self::$chave = hex2bin($hex);
        }
        return self::$chave;
    }

    /**
     * Criptografa uma string. Retorna null se o input for null/vazio.
     */
    public static function encrypt(?string $texto): ?string
    {
        if ($texto === null || $texto === '') return null;

        $iv  = random_bytes(self::IV_LEN);
        $tag = '';

        $cifrado = openssl_encrypt(
            $texto,
            self::METODO,
            self::chave(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LEN
        );

        if ($cifrado === false) {
            throw new \RuntimeException('Falha ao criptografar.');
        }

        return base64_encode($iv . $tag . $cifrado);
    }

    /**
     * Descriptografa uma string previamente criptografada.
     */
    public static function decrypt(?string $enc): ?string
    {
        if ($enc === null || $enc === '') return null;

        $bin = base64_decode($enc, true);
        if ($bin === false || strlen($bin) < (self::IV_LEN + self::TAG_LEN)) {
            return null; // dado corrompido ou não criptografado
        }

        $iv       = substr($bin, 0, self::IV_LEN);
        $tag      = substr($bin, self::IV_LEN, self::TAG_LEN);
        $cifrado  = substr($bin, self::IV_LEN + self::TAG_LEN);

        $texto = openssl_decrypt(
            $cifrado,
            self::METODO,
            self::chave(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $texto === false ? null : $texto;
    }
}