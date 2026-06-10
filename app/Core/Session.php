<?php
class Session
{
    public static function start(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name($config['security']['session_name'] ?? 'SAHAM_ANALYZER_SESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    public static function get(string $key, $default = null) { return $_SESSION[$key] ?? $default; }
    public static function put(string $key, $value): void { $_SESSION[$key] = $value; }
    public static function forget(string $key): void { unset($_SESSION[$key]); }
    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) { $_SESSION['_flash'][$key] = $value; return null; }
        $v = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $v;
    }
    public static function regenerate(): void { session_regenerate_id(true); }
}
