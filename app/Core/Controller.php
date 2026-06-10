<?php
class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if (!file_exists($viewFile)) { http_response_code(404); die('View tidak ditemukan: ' . e($view)); }
        require __DIR__ . '/../Views/layout/header.php';
        require $viewFile;
        require __DIR__ . '/../Views/layout/footer.php';
    }
    protected function renderAuth(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layout/auth_header.php';
        require $viewFile;
        require __DIR__ . '/../Views/layout/auth_footer.php';
    }
    protected function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
    }
}
