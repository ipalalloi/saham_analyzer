<?php
class Csrf
{
    public static function token(): string
    {
        $key = App::config('security.csrf_key', 'csrf_token');
        if (!Session::get($key)) Session::put($key, bin2hex(random_bytes(32)));
        return Session::get($key);
    }
    public static function input(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }
    public static function verify(): void
    {
        $key = App::config('security.csrf_key', 'csrf_token');
        $sent = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$sent || !hash_equals(Session::get($key, ''), $sent)) {
            http_response_code(419); die('CSRF token tidak valid. Muat ulang halaman dan ulangi aksi.');
        }
    }
}
