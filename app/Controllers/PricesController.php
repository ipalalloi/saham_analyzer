<?php
class PricesController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $rows = Database::fetchAll('SELECT p.*, c.ticker, c.name, d.title source_title FROM weekly_prices p JOIN companies c ON c.id=p.company_id LEFT JOIN source_documents d ON d.id=p.source_document_id ORDER BY p.week_end DESC, c.ticker LIMIT 500');
        $this->render('prices/index', compact('rows'));
    }
    public function import(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        if (empty($_FILES['csv_file']['tmp_name'])) { Session::flash('error','File CSV wajib diupload.'); redirect('prices'); }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($handle); if (!$header) { Session::flash('error','CSV kosong.'); redirect('prices'); }
        $header = array_map(fn($h)=>strtolower(trim($h)), $header);
        $count=0; $errors=[];
        while(($row=fgetcsv($handle))!==false){
            $r = array_combine($header, array_pad($row, count($header), null));
            $ticker=strtoupper(trim($r['ticker'] ?? '')); $company=Database::fetch('SELECT id FROM companies WHERE ticker=?',[$ticker]);
            if(!$company){ $errors[]="Ticker $ticker tidak ditemukan"; continue; }
            $sourceId = ($r['source_document_id'] ?? '') !== '' ? (int)$r['source_document_id'] : null;
            $sourceOk = $sourceId ? Database::fetch("SELECT id FROM source_documents WHERE id=? AND verification_status='confirmed'",[$sourceId]) : null;
            $isConfirmed = ((int)($r['is_confirmed'] ?? 0) === 1 && $sourceOk) ? 1 : 0;
            Database::execute('INSERT INTO weekly_prices(company_id,week_end,open_price,high_price,low_price,close_price,volume,source_document_id,is_confirmed) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE open_price=VALUES(open_price), high_price=VALUES(high_price), low_price=VALUES(low_price), close_price=VALUES(close_price), volume=VALUES(volume), source_document_id=VALUES(source_document_id), is_confirmed=VALUES(is_confirmed)', [
                $company['id'],$r['week_end'] ?? null,self::n($r['open'] ?? $r['open_price'] ?? null),self::n($r['high'] ?? $r['high_price'] ?? null),self::n($r['low'] ?? $r['low_price'] ?? null),self::n($r['close'] ?? $r['close_price'] ?? null),(int)($r['volume'] ?? 0),$sourceId,$isConfirmed
            ]);
            $count++;
        }
        Audit::log('import','weekly_prices',null,['rows'=>$count,'errors'=>$errors]);
        Session::flash($errors?'error':'success', $count.' baris harga weekly diproses. '.($errors ? 'Catatan: '.implode('; ',array_slice($errors,0,5)) : ''));
        redirect('prices');
    }
    public function delete(): void
    {
        Auth::requireRole(['admin']); Csrf::verify(); $id=(int)($_POST['id']??0);
        Database::execute('DELETE FROM weekly_prices WHERE id=?',[$id]); Audit::log('delete','weekly_prices',(string)$id); Session::flash('success','Data harga dihapus.'); redirect('prices');
    }
    private static function n($v){ $v=trim((string)$v); return $v==='' ? null : str_replace(',','.',str_replace('.','',$v)); }
}
