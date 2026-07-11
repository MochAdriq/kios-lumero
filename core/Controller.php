<?php
abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'app'): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewPath)) { http_response_code(404); echo "View not found: {$view}"; return; }
        ob_start(); include $viewPath; $content = ob_get_clean();
        if ($layout) include __DIR__ . '/../views/layouts/' . $layout . '.php'; else echo $content;
    }
    protected function redirect(string $path): void { header('Location: ' . url($path)); exit; }
    protected function json($data, int $status = 200): void
    {
        http_response_code($status); header('Content-Type: application/json'); echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); exit;
    }
}
