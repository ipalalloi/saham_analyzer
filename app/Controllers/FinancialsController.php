<?php
class FinancialsController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $rows = Database::fetchAll('SELECT f.*, c.ticker, c.name, d.title source_title FROM financial_metrics f JOIN companies c ON c.id=f.company_id LEFT JOIN source_documents d ON d.id=f.source_document_id ORDER BY f.period_end DESC, c.ticker LIMIT 400');
        $sources = Database::fetchAll("SELECT id,title FROM source_documents WHERE doc_type IN ('financial_statement','annual_report','xbrl') ORDER BY id DESC LIMIT 200");
        $this->render('financials/index', compact('rows','sources'));
    }
    public function import(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        if (empty($_FILES['csv_file']['tmp_name'])) { Session::flash('error','File CSV wajib diupload.'); redirect('financials'); }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($handle); if (!$header) { Session::flash('error','CSV kosong.'); redirect('financials'); }
        $header = array_map(fn($h)=>strtolower(trim($h)), $header);
        $count=0; $errors=[];
        while(($row=fgetcsv($handle))!==false){
            $r = array_combine($header, array_pad($row, count($header), null));
            $ticker = strtoupper(trim($r['ticker'] ?? ''));
            $company = Database::fetch('SELECT id FROM companies WHERE ticker=?', [$ticker]);
            if(!$company){ $errors[]="Ticker $ticker tidak ditemukan"; continue; }
            $sourceId = ($r['source_document_id'] ?? '') !== '' ? (int)$r['source_document_id'] : null;
            $sourceOk = $sourceId ? Database::fetch("SELECT id FROM source_documents WHERE id=? AND verification_status='confirmed'",[$sourceId]) : null;
            $isConfirmed = ((int)($r['is_confirmed'] ?? 0) === 1 && $sourceOk) ? 1 : 0;
            Database::execute('INSERT INTO financial_metrics(company_id,fiscal_year,period_type,period_end,revenue,revenue_yoy_pct,gross_profit,operating_profit,net_profit,net_profit_yoy_pct,total_assets,total_liabilities,total_equity,total_debt,der,operating_cash_flow,gross_margin_pct,operating_margin_pct,net_margin_pct,source_document_id,is_confirmed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE period_end=VALUES(period_end), revenue=VALUES(revenue), revenue_yoy_pct=VALUES(revenue_yoy_pct), gross_profit=VALUES(gross_profit), operating_profit=VALUES(operating_profit), net_profit=VALUES(net_profit), net_profit_yoy_pct=VALUES(net_profit_yoy_pct), total_assets=VALUES(total_assets), total_liabilities=VALUES(total_liabilities), total_equity=VALUES(total_equity), total_debt=VALUES(total_debt), der=VALUES(der), operating_cash_flow=VALUES(operating_cash_flow), gross_margin_pct=VALUES(gross_margin_pct), operating_margin_pct=VALUES(operating_margin_pct), net_margin_pct=VALUES(net_margin_pct), source_document_id=VALUES(source_document_id), is_confirmed=VALUES(is_confirmed)', [
                $company['id'], (int)($r['fiscal_year'] ?? 0), strtolower(trim($r['period_type'] ?? 'annual')), $r['period_end'] ?? null,
                self::n($r['revenue'] ?? null), self::n($r['revenue_yoy_pct'] ?? null), self::n($r['gross_profit'] ?? null), self::n($r['operating_profit'] ?? null), self::n($r['net_profit'] ?? null), self::n($r['net_profit_yoy_pct'] ?? null), self::n($r['total_assets'] ?? null), self::n($r['total_liabilities'] ?? null), self::n($r['total_equity'] ?? null), self::n($r['total_debt'] ?? null), self::n($r['der'] ?? null), self::n($r['operating_cash_flow'] ?? null), self::n($r['gross_margin_pct'] ?? null), self::n($r['operating_margin_pct'] ?? null), self::n($r['net_margin_pct'] ?? null), $sourceId, $isConfirmed
            ]);
            $count++;
        }
        Audit::log('import','financial_metrics',null,['rows'=>$count,'errors'=>$errors]);
        Session::flash($errors?'error':'success', $count.' baris finansial diproses. '.($errors ? 'Catatan: '.implode('; ',array_slice($errors,0,5)) : ''));
        redirect('financials');
    }
    public function delete(): void
    {
        Auth::requireRole(['admin']); Csrf::verify(); $id=(int)($_POST['id']??0);
        Database::execute('DELETE FROM financial_metrics WHERE id=?',[$id]); Audit::log('delete','financial_metrics',(string)$id); Session::flash('success','Data finansial dihapus.'); redirect('financials');
    }
    private static function n($v){ $v=trim((string)$v); return $v==='' ? null : str_replace(',','.',str_replace('.','',$v)); }
}
