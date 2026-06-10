<?php
class CyclesController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $cycles = Database::fetchAll('SELECT c.*, u.name created_name FROM cycles c LEFT JOIN users u ON u.id=c.created_by ORDER BY c.id DESC');
        $this->render('cycles/index', compact('cycles'));
    }
    public function save(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $id = (int)($_POST['id'] ?? 0);
        $data = [trim($_POST['cycle_code'] ?? ''), $_POST['period_start'] ?? '', $_POST['period_end'] ?? '', $_POST['status'] ?? 'draft', ($_POST['previous_cycle_id'] ?? null) ?: null, trim($_POST['notes'] ?? '')];
        if ($data[0] === '' || !$data[1] || !$data[2]) { Session::flash('error','Kode dan periode siklus wajib diisi.'); redirect('cycles'); }
        if ($id > 0) {
            Database::execute('UPDATE cycles SET cycle_code=?, period_start=?, period_end=?, status=?, previous_cycle_id=?, notes=? WHERE id=?', array_merge($data, [$id]));
            Audit::log('update','cycles',(string)$id,$data); Session::flash('success','Siklus diperbarui.');
        } else {
            Database::execute('INSERT INTO cycles(cycle_code,period_start,period_end,status,previous_cycle_id,notes,created_by) VALUES (?,?,?,?,?,?,?)', array_merge($data,[Auth::id()]));
            Audit::log('create','cycles',Database::lastId(),$data); Session::flash('success','Siklus dibuat.');
        }
        redirect('cycles');
    }
    public function finalize(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $id = (int)($_POST['id'] ?? 0);
        Database::execute("UPDATE cycles SET status='finalized', finalized_at=NOW() WHERE id=?", [$id]);
        Audit::log('finalize','cycles',(string)$id); Session::flash('success','Siklus difinalisasi.'); redirect('cycles');
    }
}
