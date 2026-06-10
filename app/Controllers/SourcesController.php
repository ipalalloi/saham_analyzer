<?php
class SourcesController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $companies = Database::fetchAll('SELECT id,ticker,name FROM companies ORDER BY ticker');
        $docs = Database::fetchAll('SELECT d.*, c.ticker FROM source_documents d LEFT JOIN companies c ON c.id=d.company_id ORDER BY d.id DESC LIMIT 300');
        $this->render('sources/index', compact('docs','companies'));
    }
    public function save(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $id = (int)($_POST['id'] ?? 0);
        $filePath = $_POST['existing_file_path'] ?? null; $checksum = $_POST['existing_checksum'] ?? null;
        if (!empty($_FILES['document_file']['name'])) {
            $max = (int)App::config('security.max_upload_mb',15) * 1024 * 1024;
            if ($_FILES['document_file']['size'] > $max) { Session::flash('error','Ukuran file melebihi batas.'); redirect('sources'); }
            $ext = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, App::config('security.allowed_upload_ext',[]), true)) { Session::flash('error','Ekstensi file tidak diizinkan.'); redirect('sources'); }
            $safe = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dir = App::config('paths.uploads'); if (!is_dir($dir)) mkdir($dir,0755,true);
            $dest = $dir . '/' . $safe;
            if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $dest)) { Session::flash('error','Upload gagal.'); redirect('sources'); }
            $filePath = 'storage/uploads/' . $safe;
            $checksum = hash_file('sha256', $dest);
        }
        $data = [
            ($_POST['company_id'] ?? null) ?: null, $_POST['doc_type'] ?? 'other', trim($_POST['title'] ?? ''), $_POST['official_source'] ?? 'IDX',
            trim($_POST['source_url'] ?? ''), ($_POST['published_date'] ?? null) ?: null, ($_POST['period_start'] ?? null) ?: null, ($_POST['period_end'] ?? null) ?: null,
            $filePath, $checksum, $_POST['verification_status'] ?? 'pending', trim($_POST['notes'] ?? '')
        ];
        if ($data[2] === '') { Session::flash('error','Judul dokumen wajib diisi.'); redirect('sources'); }
        if ($id > 0) {
            Database::execute('UPDATE source_documents SET company_id=?, doc_type=?, title=?, official_source=?, source_url=?, published_date=?, period_start=?, period_end=?, file_path=?, file_checksum=?, verification_status=?, notes=? WHERE id=?', array_merge($data,[$id]));
            Audit::log('update','source_documents',(string)$id,$data); Session::flash('success','Dokumen diperbarui.');
        } else {
            Database::execute('INSERT INTO source_documents(company_id,doc_type,title,official_source,source_url,published_date,period_start,period_end,file_path,file_checksum,verification_status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($data,[Auth::id()]));
            Audit::log('create','source_documents',Database::lastId(),$data); Session::flash('success','Dokumen ditambahkan.');
        }
        redirect('sources');
    }
    public function delete(): void
    {
        Auth::requireRole(['admin']); Csrf::verify();
        $id = (int)($_POST['id'] ?? 0);
        Database::execute('DELETE FROM source_documents WHERE id=?', [$id]);
        Audit::log('delete','source_documents',(string)$id); Session::flash('success','Dokumen dihapus.'); redirect('sources');
    }
}
