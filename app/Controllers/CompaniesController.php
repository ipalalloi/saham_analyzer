<?php
class CompaniesController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $q = trim($_GET['q'] ?? '');
        $params = [];
        $where = '';
        if ($q !== '') { $where = 'WHERE ticker LIKE ? OR name LIKE ? OR sector LIKE ?'; $params = ["%$q%","%$q%","%$q%"]; }
        $companies = Database::fetchAll("SELECT * FROM companies $where ORDER BY ticker ASC LIMIT 500", $params);
        $this->render('companies/index', compact('companies','q'));
    }
    public function save(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            strtoupper(trim($_POST['ticker'] ?? '')), trim($_POST['name'] ?? ''), trim($_POST['sector'] ?? ''), trim($_POST['subsector'] ?? ''),
            isset($_POST['is_idx30']) ? 1 : 0, isset($_POST['is_special_der_sector']) ? 1 : 0,
            isset($_POST['is_long_suspended']) ? 1 : 0, isset($_POST['financial_report_complete']) ? 1 : 0,
            trim($_POST['last_review_notes'] ?? '')
        ];
        if ($data[0] === '' || $data[1] === '' || $data[2] === '') { Session::flash('error','Ticker, nama, dan sektor wajib diisi.'); redirect('companies'); }
        if ($id > 0) {
            Database::execute('UPDATE companies SET ticker=?, name=?, sector=?, subsector=?, is_idx30=?, is_special_der_sector=?, is_long_suspended=?, financial_report_complete=?, last_review_notes=? WHERE id=?', array_merge($data, [$id]));
            Audit::log('update','companies',(string)$id,$data); Session::flash('success','Data emiten diperbarui.');
        } else {
            Database::execute('INSERT INTO companies(ticker,name,sector,subsector,is_idx30,is_special_der_sector,is_long_suspended,financial_report_complete,last_review_notes) VALUES (?,?,?,?,?,?,?,?,?)', $data);
            Audit::log('create','companies',Database::lastId(),$data); Session::flash('success','Data emiten ditambahkan.');
        }
        redirect('companies');
    }

    public function import(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        if (empty($_FILES['csv_file']['tmp_name'])) { Session::flash('error','File CSV wajib diupload.'); redirect('companies'); }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($handle); if (!$header) { Session::flash('error','CSV kosong.'); redirect('companies'); }
        $header = array_map(fn($h)=>strtolower(trim($h)), $header);
        $count=0;
        while(($row=fgetcsv($handle))!==false){
            $r = array_combine($header, array_pad($row, count($header), null));
            $ticker=strtoupper(trim($r['ticker'] ?? ''));
            if($ticker==='') continue;
            Database::execute('INSERT INTO companies(ticker,name,sector,subsector,is_idx30,is_special_der_sector,is_long_suspended,financial_report_complete,last_review_notes) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), sector=VALUES(sector), subsector=VALUES(subsector), is_idx30=VALUES(is_idx30), is_special_der_sector=VALUES(is_special_der_sector), is_long_suspended=VALUES(is_long_suspended), financial_report_complete=VALUES(financial_report_complete), last_review_notes=VALUES(last_review_notes)', [
                $ticker, trim($r['name'] ?? ''), trim($r['sector'] ?? ''), trim($r['subsector'] ?? ''), (int)($r['is_idx30'] ?? 0), (int)($r['is_special_der_sector'] ?? 0), (int)($r['is_long_suspended'] ?? 0), isset($r['financial_report_complete'])?(int)$r['financial_report_complete']:1, trim($r['last_review_notes'] ?? '')
            ]);
            $count++;
        }
        Audit::log('import','companies',null,['rows'=>$count]); Session::flash('success',$count.' data emiten diimport/diperbarui.'); redirect('companies');
    }

    public function delete(): void
    {
        Auth::requireRole(['admin']); Csrf::verify();
        $id = (int)($_POST['id'] ?? 0);
        Database::execute('DELETE FROM companies WHERE id=?', [$id]);
        Audit::log('delete','companies',(string)$id); Session::flash('success','Data emiten dihapus.'); redirect('companies');
    }
}
