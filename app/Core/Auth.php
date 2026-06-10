<?php
class Auth
{
    public static function user(): ?array { return Session::get('user'); }
    public static function id(): ?int { $u = self::user(); return $u ? (int)$u['id'] : null; }
    public static function check(): bool { return self::user() !== null; }
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetch('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1', [$email]);
        if (!$user || !password_verify($password, $user['password_hash'])) return false;
        Session::regenerate();
        unset($user['password_hash']);
        Session::put('user', $user);
        Audit::log('login', 'users', (string)$user['id']);
        return true;
    }
    public static function requireLogin(): void
    {
        if (!self::check()) redirect('login');
    }
    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        if (!in_array(self::user()['role'], $roles, true)) {
            http_response_code(403); die('Akses ditolak.');
        }
    }
    public static function logout(): void
    {
        Audit::log('logout', 'users', (string)self::id());
        Session::forget('user'); Session::regenerate();
    }
}
