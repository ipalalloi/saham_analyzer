<?php
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function url(string $route, array $params = []): string {
    $params = array_merge(['route' => $route], $params);
    return App::baseUrl('index.php?' . http_build_query($params));
}
function redirect(string $route, array $params = []): void { header('Location: ' . url($route, $params)); exit; }
function method_is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function format_num($n, int $dec = 2): string { return $n === null || $n === '' ? 'DATA BELUM TERKONFIRMASI' : number_format((float)$n, $dec, ',', '.'); }
function checked($v): string { return (int)$v === 1 ? 'checked' : ''; }
function active(string $route): string { return ($_GET['route'] ?? 'dashboard') === $route ? 'active' : ''; }
function badge(string $status): string {
    $s = strtoupper($status);
    $class = match($s) {
        'PASS','LULUS','INVEST','INVESTABLE IDEA','CONFIRMED' => 'success',
        'DROP','GAGAL','REJECTED' => 'danger',
        'WATCHLIST','TUNDA','PENDING','DATA_BELUM_TERKONFIRMASI' => 'warning',
        default => 'secondary'
    };
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}
