<?php
class App
{
    private static array $config = [];
    public static function init(array $config): void { self::$config = $config; }
    public static function config(?string $key = null, $default = null) {
        if ($key === null) return self::$config;
        $segments = explode('.', $key);
        $value = self::$config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return $default;
            $value = $value[$segment];
        }
        return $value;
    }
    public static function baseUrl(string $path = ''): string {
        $base = rtrim(self::config('base_url', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}
