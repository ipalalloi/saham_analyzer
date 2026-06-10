<?php
class MemosController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $cycles=Database::fetchAll('SELECT * FROM cycles ORDER BY id DESC');
        $cycleId=(int)($_GET['cycle_id'] ?? ($cycles[0]['id'] ?? 0));
        $cycle=$cycleId?Database::fetch('SELECT * FROM cycles WHERE id=?',[$cycleId]):null;
        $companies=$cycleId?Database::fetchAll('SELECT c.id,c.ticker,c.name,c.sector,r.status screening_status,r.score FROM screening_results r JOIN companies c ON c.id=r.company_id WHERE r.cycle_id=? ORDER BY FIELD(r.status,\'PASS\',\'DATA_BELUM_TERKONFIRMASI\',\'DROP\'), r.score DESC, c.ticker',[$cycleId]):[];
        $selectedCompanyId=(int)($_GET['company_id'] ?? ($companies[0]['id'] ?? 0));
        $company=$selectedCompanyId?Database::fetch('SELECT * FROM companies WHERE id=?',[$selectedCompanyId]):null;
        $pillars=$selectedCompanyId?Database::fetchAll('SELECT * FROM pillar_validations WHERE cycle_id=? AND company_id=? ORDER BY FIELD(pillar,\'catalyst\',\'financial_quality\',\'moat\',\'technical\',\'risk_reward\')',[$cycleId,$selectedCompanyId]):[];
        $pillarMap=[]; foreach($pillars as $p) $pillarMap[$p['pillar']]=$p;
        $valuation=$selectedCompanyId?Database::fetch('SELECT * FROM valuation_scenarios WHERE cycle_id=? AND company_id=?',[$cycleId,$selectedCompanyId]):null;
        $memo=$selectedCompanyId?Database::fetch('SELECT * FROM investment_memos WHERE cycle_id=? AND company_id=?',[$cycleId,$selectedCompanyId]):null;
        $sources=Database::fetchAll("SELECT id,title FROM source_documents WHERE verification_status='confirmed' ORDER BY id DESC LIMIT 200");
        $this->render('memos/index', compact('cycles','cycleId','cycle','companies','selectedCompanyId','company','pillars','pillarMap','valuation','memo','sources'));
    }
    public function savePillar(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $cycleId=(int)($_POST['cycle_id']??0); $companyId=(int)($_POST['company_id']??0); $pillar=$_POST['pillar']??'catalyst';
        $data=[$cycleId,$companyId,$pillar,$_POST['status']??'TUNDA',trim($_POST['thesis']??''),trim($_POST['success_indicators']??''),trim($_POST['failure_indicators']??''),trim($_POST['analysis_notes']??''),($_POST['source_document_id']??null)?:null,Auth::id()];
        Database::execute('INSERT INTO pillar_validations(cycle_id,company_id,pillar,status,thesis,success_indicators,failure_indicators,analysis_notes,source_document_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), thesis=VALUES(thesis), success_indicators=VALUES(success_indicators), failure_indicators=VALUES(failure_indicators), analysis_notes=VALUES(analysis_notes), source_document_id=VALUES(source_document_id), created_by=VALUES(created_by)', $data);
        Audit::log('upsert','pillar_validations',null,$data); Session::flash('success','Validasi pilar disimpan.'); redirect('memos',['cycle_id'=>$cycleId,'company_id'=>$companyId]);
    }
    public function saveValuation(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $cycleId=(int)($_POST['cycle_id']??0); $companyId=(int)($_POST['company_id']??0);
        $current=self::n($_POST['current_price']??null); $bear=self::n($_POST['bear_price']??null); $bull=self::n($_POST['bull_price']??null);
        $downside=null; $upside=null; $ratio=null;
        if($current && $bear){ $downside=round((($current-$bear)/$current)*100,2); }
        if($current && $bull){ $upside=round((($bull-$current)/$current)*100,2); }
        if($downside && $downside>0 && $upside!==null){ $ratio=round($upside/$downside,2); }
        $isConfirmed = isset($_POST['is_confirmed']) ? 1 : 0;
        $data=[$cycleId,$companyId,$_POST['period_end']??date('Y-m-d'),$current,$bear,self::n($_POST['base_price']??null),$bull,$downside,$upside,$ratio,trim($_POST['valuation_relative']??''),($_POST['source_document_id']??null)?:null,$isConfirmed];
        Database::execute('INSERT INTO valuation_scenarios(cycle_id,company_id,period_end,current_price,bear_price,base_price,bull_price,downside_pct,conservative_upside_pct,upside_downside_ratio,valuation_relative,source_document_id,is_confirmed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE period_end=VALUES(period_end), current_price=VALUES(current_price), bear_price=VALUES(bear_price), base_price=VALUES(base_price), bull_price=VALUES(bull_price), downside_pct=VALUES(downside_pct), conservative_upside_pct=VALUES(conservative_upside_pct), upside_downside_ratio=VALUES(upside_downside_ratio), valuation_relative=VALUES(valuation_relative), source_document_id=VALUES(source_document_id), is_confirmed=VALUES(is_confirmed)', $data);
        Audit::log('upsert','valuation_scenarios',null,$data); Session::flash('success','Skenario valuasi disimpan.'); redirect('memos',['cycle_id'=>$cycleId,'company_id'=>$companyId]);
    }
    public function generate(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $cycleId=(int)($_POST['cycle_id']??0); $companyId=(int)($_POST['company_id']??0);
        $company=Database::fetch('SELECT * FROM companies WHERE id=?',[$companyId]);
        $screen=Database::fetch('SELECT * FROM screening_results WHERE cycle_id=? AND company_id=?',[$cycleId,$companyId]);
        $financial=Database::fetch('SELECT * FROM financial_metrics WHERE company_id=? AND is_confirmed=1 ORDER BY period_end DESC LIMIT 1',[$companyId]);
        $cats=Database::fetchAll('SELECT * FROM catalysts WHERE cycle_id=? AND company_id=? AND verified=1',[$cycleId,$companyId]);
        $pillars=Database::fetchAll('SELECT * FROM pillar_validations WHERE cycle_id=? AND company_id=?',[$cycleId,$companyId]);
        $valuation=Database::fetch('SELECT * FROM valuation_scenarios WHERE cycle_id=? AND company_id=?',[$cycleId,$companyId]);
        $status=$this->decisionStatus($pillars,$valuation);
        if(!$company){ Session::flash('error','Emiten tidak ditemukan.'); redirect('memos',['cycle_id'=>$cycleId]); }
        $summary="{$company['ticker']} - {$company['name']} masuk evaluasi siklus berdasarkan hasil screening status ".($screen['status']??'DATA BELUM TERKONFIRMASI').". Tesis hanya dapat dinyatakan valid bila seluruh pilar five-pillar validation memiliki status LULUS dan risk-reward memenuhi rasio minimal 2x downside.";
        $keyData=$financial ? "Periode LK: {$financial['period_end']}; Revenue YoY: ".($financial['revenue_yoy_pct']??'DATA BELUM TERKONFIRMASI')."%; Net Profit YoY: ".($financial['net_profit_yoy_pct']??'DATA BELUM TERKONFIRMASI')."%; DER: ".($financial['der']??'DATA BELUM TERKONFIRMASI')."; OCF: ".($financial['operating_cash_flow']??'DATA BELUM TERKONFIRMASI')."." : 'DATA BELUM TERKONFIRMASI';
        $validatedCatalyst = $cats ? implode("\n", array_map(fn($c)=>'- '.$c['catalyst_type'].': '.$c['description'].' Bukti: '.$c['evidence_summary'], $cats)) : 'DATA BELUM TERKONFIRMASI';
        $risks=[]; foreach($pillars as $p){ if($p['status']!=='LULUS') $risks[]='Pilar '.$p['pillar'].' status '.$p['status'].': '.($p['failure_indicators'] ?: 'indikator kegagalan belum lengkap'); }
        if($valuation && (float)($valuation['upside_downside_ratio'] ?? 0) < 2) $risks[]='Risk-reward tidak memenuhi minimum 2x downside.';
        $mainRisks=$risks ? implode("\n", $risks) : 'Risiko utama harus tetap dipantau melalui indikator kegagalan pada setiap pilar.';
        $invalidation=$this->invalidationLevel($pillars,$valuation);
        $reason=$this->decisionReason($pillars,$valuation);
        Database::execute('INSERT INTO investment_memos(cycle_id,company_id,summary_thesis,key_data,validated_catalyst,main_risks,invalidation_level,final_status,decision_reason,created_by) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE summary_thesis=VALUES(summary_thesis), key_data=VALUES(key_data), validated_catalyst=VALUES(validated_catalyst), main_risks=VALUES(main_risks), invalidation_level=VALUES(invalidation_level), final_status=VALUES(final_status), decision_reason=VALUES(decision_reason), created_by=VALUES(created_by)',[$cycleId,$companyId,$summary,$keyData,$validatedCatalyst,$mainRisks,$invalidation,$status,$reason,Auth::id()]);
        Audit::log('generate','investment_memos',null,['cycle_id'=>$cycleId,'company_id'=>$companyId,'status'=>$status]); Session::flash('success','Investment memo dibuat/diperbarui.'); redirect('memos',['cycle_id'=>$cycleId,'company_id'=>$companyId]);
    }
    private function decisionStatus(array $pillars, ?array $valuation): string
    {
        $required=['catalyst','financial_quality','moat','technical','risk_reward']; $map=[]; foreach($pillars as $p) $map[$p['pillar']]=$p['status'];
        foreach($required as $r) if(!isset($map[$r])) return 'DATA_BELUM_TERKONFIRMASI';
        $fails=count(array_filter($map,fn($s)=>$s==='GAGAL')); $delays=count(array_filter($map,fn($s)=>$s==='TUNDA'));
        if($fails>=2) return 'DROP'; if($fails>=1 && $delays>=1) return 'WATCHLIST';
        if($valuation && (int)$valuation['is_confirmed']===1 && (float)($valuation['upside_downside_ratio']??0) < 2) return 'DROP';
        if($fails===0 && $delays===0) return 'INVEST'; return 'WATCHLIST';
    }
    private function decisionReason(array $pillars, ?array $valuation): string
    {
        $map=[]; foreach($pillars as $p) $map[$p['pillar']]=$p['status'];
        $fail=count(array_filter($map,fn($s)=>$s==='GAGAL')); $delay=count(array_filter($map,fn($s)=>$s==='TUNDA'));
        $ratio=$valuation['upside_downside_ratio'] ?? 'DATA BELUM TERKONFIRMASI';
        return "Pilar gagal: $fail; pilar tunda: $delay; upside/downside ratio: $ratio. Aturan: ≥2 GAGAL = DROP; 1 GAGAL + 1 TUNDA = WATCHLIST; semua LULUS + RR valid = INVEST.";
    }
    private function invalidationLevel(array $pillars, ?array $valuation): string
    {
        $texts=[]; foreach($pillars as $p){ if(trim((string)$p['failure_indicators'])!=='') $texts[]='- '.$p['pillar'].': '.$p['failure_indicators']; }
        if($valuation && $valuation['bear_price']!==null) $texts[]='- Harga mencapai/menembus skenario bear: '.$valuation['bear_price'].' pada periode '.$valuation['period_end'];
        return $texts ? implode("\n", $texts) : 'DATA BELUM TERKONFIRMASI';
    }
    private static function n($v){ $v=trim((string)$v); return $v==='' ? null : str_replace(',','.',str_replace('.','',$v)); }
}
