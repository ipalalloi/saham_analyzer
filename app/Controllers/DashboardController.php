<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $stats = [
            'companies' => Database::fetch('SELECT COUNT(*) c FROM companies')['c'] ?? 0,
            'sources' => Database::fetch('SELECT COUNT(*) c FROM source_documents')['c'] ?? 0,
            'cycles' => Database::fetch('SELECT COUNT(*) c FROM cycles')['c'] ?? 0,
            'memos' => Database::fetch('SELECT COUNT(*) c FROM investment_memos')['c'] ?? 0,
        ];
        $cycle = Database::fetch('SELECT * FROM cycles ORDER BY id DESC LIMIT 1');
        $recent = Database::fetchAll('SELECT s.*, c.ticker, c.name FROM source_documents s LEFT JOIN companies c ON c.id=s.company_id ORDER BY s.id DESC LIMIT 8');
        $this->render('dashboard/index', compact('stats','cycle','recent'));
    }
}
