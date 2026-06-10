<?php
class ScreeningController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $cycles = Database::fetchAll('SELECT * FROM cycles ORDER BY id DESC');
        $cycleId = (int)($_GET['cycle_id'] ?? ($cycles[0]['id'] ?? 0));
        $cycle = $cycleId ? Database::fetch('SELECT * FROM cycles WHERE id=?', [$cycleId]) : null;
        $companies = Database::fetchAll('SELECT id,ticker,name FROM companies ORDER BY ticker');
        $sources = Database::fetchAll("SELECT id,title FROM source_documents WHERE verification_status='confirmed' ORDER BY id DESC LIMIT 200");
        $results = $cycleId ? Database::fetchAll('SELECT r.*, c.ticker, c.name, c.sector FROM screening_results r JOIN companies c ON c.id=r.company_id WHERE r.cycle_id=? ORDER BY FIELD(r.status,\'PASS\',\'DATA_BELUM_TERKONFIRMASI\',\'DROP\'), r.score DESC, c.ticker', [$cycleId]) : [];
        $top5 = array_slice(array_values(array_filter($results, fn($r)=>$r['status']==='PASS')), 0, 5);
        $catalysts = $cycleId ? Database::fetchAll('SELECT ca.*, c.ticker, d.title source_title FROM catalysts ca JOIN companies c ON c.id=ca.company_id LEFT JOIN source_documents d ON d.id=ca.source_document_id WHERE ca.cycle_id=? ORDER BY ca.id DESC', [$cycleId]) : [];
        $techReviews = $cycleId ? Database::fetchAll('SELECT tr.*, c.ticker FROM technical_reviews tr JOIN companies c ON c.id=tr.company_id WHERE tr.cycle_id=? ORDER BY tr.id DESC', [$cycleId]) : [];
        $this->render('screening/index', compact('cycles','cycleId','cycle','companies','sources','results','top5','catalysts','techReviews'));
    }

    public function saveCatalyst(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $cycleId=(int)($_POST['cycle_id']??0); $companyId=(int)($_POST['company_id']??0);
        $data=[$cycleId,$companyId,$_POST['catalyst_type']??'turnaround',trim($_POST['description']??''),trim($_POST['evidence_summary']??''),($_POST['source_document_id']??null)?:null,isset($_POST['impact_core_business'])?1:0,isset($_POST['repeatable'])?1:0,isset($_POST['scalable'])?1:0,isset($_POST['multi_quarter_impact'])?1:0,isset($_POST['verified'])?1:0];
        if(!$cycleId || !$companyId || $data[3]==='' || $data[4]===''){ Session::flash('error','Katalis wajib memiliki siklus, emiten, deskripsi, dan bukti.'); redirect('screening',['cycle_id'=>$cycleId]); }
        Database::execute('INSERT INTO catalysts(cycle_id,company_id,catalyst_type,description,evidence_summary,source_document_id,impact_core_business,repeatable,scalable,multi_quarter_impact,verified) VALUES (?,?,?,?,?,?,?,?,?,?,?)',$data);
        Audit::log('create','catalysts',Database::lastId(),$data); Session::flash('success','Katalis tersimpan.'); redirect('screening',['cycle_id'=>$cycleId]);
    }

    public function saveTechnicalReview(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $cycleId=(int)($_POST['cycle_id']??0); $companyId=(int)($_POST['company_id']??0);
        $data=[$cycleId,$companyId,$_POST['review_week_end']??date('Y-m-d'),$_POST['phase']??'unconfirmed',isset($_POST['distribution_extreme'])?1:0,isset($_POST['ath_without_new_catalyst'])?1:0,trim($_POST['relative_strength_notes']??''),trim($_POST['analyst_notes']??''),($_POST['source_document_id']??null)?:null];
        Database::execute('INSERT INTO technical_reviews(cycle_id,company_id,review_week_end,phase,distribution_extreme,ath_without_new_catalyst,relative_strength_notes,analyst_notes,source_document_id) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE review_week_end=VALUES(review_week_end), phase=VALUES(phase), distribution_extreme=VALUES(distribution_extreme), ath_without_new_catalyst=VALUES(ath_without_new_catalyst), relative_strength_notes=VALUES(relative_strength_notes), analyst_notes=VALUES(analyst_notes), source_document_id=VALUES(source_document_id)', $data);
        Audit::log('upsert','technical_reviews',null,$data); Session::flash('success','Review teknikal tersimpan.'); redirect('screening',['cycle_id'=>$cycleId]);
    }

    public function run(): void
    {
        Auth::requireRole(['admin','analyst']); Csrf::verify();
        $cycleId=(int)($_POST['cycle_id']??0); $cycle=Database::fetch('SELECT * FROM cycles WHERE id=?',[$cycleId]);
        if(!$cycle){ Session::flash('error','Siklus tidak ditemukan.'); redirect('screening'); }
        Database::execute('DELETE FROM screening_results WHERE cycle_id=?',[$cycleId]);
        $companies=Database::fetchAll('SELECT * FROM companies ORDER BY ticker');
        $processed=0;
        foreach($companies as $c){ $this->evaluateCompany($cycle,$c); $processed++; }
        Audit::log('run','screening_results',(string)$cycleId,['processed'=>$processed]);
        Session::flash('success',"Screening selesai untuk $processed emiten. Periksa Top 5 PASS dan alasan DROP."); redirect('screening',['cycle_id'=>$cycleId]);
    }

    private function evaluateCompany(array $cycle, array $c): void
    {
        $reasons=[]; $score=0; $status='DROP';
        if((int)$c['is_idx30']===1) $reasons[]='Excluded: anggota IDX30.';
        if((int)$c['is_long_suspended']===1) $reasons[]='Excluded: suspend berkepanjangan.';
        if((int)$c['financial_report_complete']!==1) $reasons[]='Excluded: laporan keuangan tidak lengkap.';

        $cats=Database::fetchAll('SELECT * FROM catalysts WHERE cycle_id=? AND company_id=? AND verified=1',[$cycle['id'],$c['id']]);
        $catalystPass=count($cats)>0;
        if(!$catalystPass) $reasons[]='Gagal katalis: tidak ada katalis resmi/verified pada siklus ini.'; else $score += 15 + (count($cats)*3);

        $latest=Database::fetch('SELECT * FROM financial_metrics WHERE company_id=? AND is_confirmed=1 ORDER BY period_end DESC LIMIT 1',[$c['id']]);
        if(!$latest){
            $status='DATA_BELUM_TERKONFIRMASI'; $reasons[]='Data finansial confirmed belum tersedia.';
            $this->storeResult($cycle['id'],$c['id'],$status,$score,$catalystPass,0,0,0,0,$reasons,[]); return;
        }
        $prev=Database::fetch('SELECT * FROM financial_metrics WHERE company_id=? AND is_confirmed=1 AND period_end < ? ORDER BY period_end DESC LIMIT 1',[$c['id'],$latest['period_end']]);
        $revGrowth=(float)($latest['revenue_yoy_pct'] ?? -9999); $npGrowth=(float)($latest['net_profit_yoy_pct'] ?? -9999);
        $turnaround = $prev && $prev['net_profit'] !== null && $latest['net_profit'] !== null && (float)$prev['net_profit'] < 0 && (float)$latest['net_profit'] > 0;
        $quantPass = ($revGrowth >= 20) || ($npGrowth >= 30) || $turnaround;
        if(!$quantPass) $reasons[]='Gagal kuantitatif: revenue YoY <20%, net profit YoY <30%, dan bukan turnaround laba.';
        $score += max(0,min(100,$revGrowth))*0.25 + max(0,min(150,$npGrowth))*0.18 + ($turnaround?20:0);

        $derPass = ((int)$c['is_special_der_sector']===1) || ($latest['der'] !== null && (float)$latest['der'] <= 2.5);
        if(!$derPass) $reasons[]='Gagal DER: DER >2,5 atau belum confirmed.'; else $score += 8;
        $negOcf=Database::fetch('SELECT SUM(CASE WHEN operating_cash_flow < 0 THEN 1 ELSE 0 END) neg, COUNT(*) total FROM (SELECT operating_cash_flow FROM financial_metrics WHERE company_id=? AND is_confirmed=1 AND operating_cash_flow IS NOT NULL ORDER BY period_end DESC LIMIT 4) x',[$c['id']]);
        $ocfPass = ($negOcf && (int)$negOcf['total']>0 && (int)$negOcf['neg'] < 3);
        if(!$ocfPass) $reasons[]='Gagal OCF: arus kas operasi negatif kronis atau belum cukup data.'; else $score += 7;

        $tech=$this->technicalSnapshot((int)$c['id'], (int)$cycle['id']);
        $techPass=$tech['pass'];
        if(!$techPass) $reasons[]='Gagal teknikal weekly: '.$tech['reason']; else $score += 20 + min(25,($tech['volume_ratio20w'] ?? 0)*5);

        if(!$reasons && $catalystPass && $quantPass && $derPass && $ocfPass && $techPass) $status='PASS';
        $snapshot=['latest_financial_period'=>$latest['period_end'],'revenue_yoy_pct'=>$latest['revenue_yoy_pct'],'net_profit_yoy_pct'=>$latest['net_profit_yoy_pct'],'der'=>$latest['der'],'operating_cash_flow'=>$latest['operating_cash_flow'],'turnaround'=>$turnaround,'technical'=>$tech,'catalyst_count'=>count($cats)];
        $this->storeResult($cycle['id'],$c['id'],$status,$score,$catalystPass,$quantPass,$derPass,$ocfPass,$techPass,$reasons,$snapshot);
    }

    private function technicalSnapshot(int $companyId, int $cycleId): array
    {
        $prices=Database::fetchAll('SELECT * FROM weekly_prices WHERE company_id=? AND is_confirmed=1 ORDER BY week_end DESC LIMIT 60',[$companyId]);
        if(count($prices)<50) return ['pass'=>false,'reason'=>'butuh minimal 50 data weekly confirmed untuk MA30/MA50.'];
        $latest=$prices[0];
        $ma30=array_sum(array_map(fn($p)=>(float)$p['close_price'], array_slice($prices,0,30)))/30;
        $ma50=array_sum(array_map(fn($p)=>(float)$p['close_price'], array_slice($prices,0,50)))/50;
        $vol20Rows=array_slice($prices,1,20); if(count($vol20Rows)<20) return ['pass'=>false,'reason'=>'butuh rata-rata volume 20 minggu sebelumnya.'];
        $avgVol20=array_sum(array_map(fn($p)=>(float)$p['volume'],$vol20Rows))/20;
        $ratio=$avgVol20>0 ? (float)$latest['volume']/$avgVol20 : 0;
        $review=Database::fetch('SELECT * FROM technical_reviews WHERE cycle_id=? AND company_id=?',[$cycleId,$companyId]);
        if(!$review) return ['pass'=>false,'reason'=>'review manual fase harga/distribusi belum tersedia.','ma30'=>$ma30,'ma50'=>$ma50,'volume_ratio20w'=>$ratio];
        $pass=((float)$latest['close_price']>$ma30)&&((float)$latest['close_price']>$ma50)&&($ratio>=1.5)&&((int)$review['distribution_extreme']===0)&&((int)$review['ath_without_new_catalyst']===0)&&in_array($review['phase'],['base','breakout','re_accumulation'],true);
        $reason=[];
        if((float)$latest['close_price']<=$ma30) $reason[]='harga tidak di atas MA30 weekly';
        if((float)$latest['close_price']<=$ma50) $reason[]='harga tidak di atas MA50 weekly';
        if($ratio<1.5) $reason[]='volume <1,5× rata-rata 20 minggu';
        if((int)$review['distribution_extreme']===1) $reason[]='manual review: distribusi ekstrem';
        if((int)$review['ath_without_new_catalyst']===1) $reason[]='manual review: ATH tanpa katalis fundamental baru';
        if(!in_array($review['phase'],['base','breakout','re_accumulation'],true)) $reason[]='fase harga belum valid';
        return ['pass'=>$pass,'reason'=>$reason?implode('; ',$reason):'valid','week_end'=>$latest['week_end'],'close'=>$latest['close_price'],'ma30'=>round($ma30,4),'ma50'=>round($ma50,4),'volume_ratio20w'=>round($ratio,2),'phase'=>$review['phase']];
    }

    private function storeResult($cycleId,$companyId,$status,$score,$cat,$quant,$der,$ocf,$tech,$reasons,$snapshot): void
    {
        Database::execute('INSERT INTO screening_results(cycle_id,company_id,status,score,catalyst_pass,quantitative_pass,der_pass,ocf_pass,technical_pass,fail_reasons,data_snapshot) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[
            $cycleId,$companyId,$status,round($score,2),(int)$cat,(int)$quant,(int)$der,(int)$ocf,(int)$tech,implode("\n",$reasons),$snapshot?json_encode($snapshot,JSON_UNESCAPED_UNICODE):null
        ]);
    }
}
