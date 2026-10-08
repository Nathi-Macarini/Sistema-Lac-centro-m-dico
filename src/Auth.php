<?php
namespace App;

class Auth
{
    public static function user(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 👇 ADMIN / PACIENTE
        if (!empty($_SESSION['usuario_id'])) {
            return [
                'id'   => $_SESSION['usuario_id'],
                'name' => $_SESSION['nome_usuario'] ?? 'Desconhecido',
                'role' => $_SESSION['tipo_usuario'] ?? null,
            ];
        }

        // 👇 MÉDICO
        if (!empty($_SESSION['medico_id'])) {
            return [
                'id'   => $_SESSION['medico_id'],
                'name' => $_SESSION['nome_usuario'] ?? 'Desconhecido',
                'role' => 'medico',
            ];
        }

        return null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === 'admin';
    }
}