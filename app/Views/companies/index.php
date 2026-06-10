<div class="page-title">
  <div><h1>Master Emiten</h1><p class="muted">Tandai IDX30, suspend berkepanjangan, dan kelengkapan laporan agar filter gugur otomatis berjalan.</p></div>
  <button class="btn" data-modal-open="#companyModal">Tambah Emiten</button>
</div>
<section class="card"><h2>Import Master Emiten CSV</h2><div class="note">Kolom CSV: <span class="mono">ticker,name,sector,subsector,is_idx30,is_special_der_sector,is_long_suspended,financial_report_complete,last_review_notes</span></div><form method="post" action="<?= url('company.import') ?>" enctype="multipart/form-data" class="actions"><?= Csrf::input() ?><input type="file" name="csv_file" accept=".csv" required><button class="btn">Import Emiten</button></form></section>
<section class="card">
  <form method="get" class="actions" style="margin-top:0"><input type="hidden" name="route" value="companies"><div class="field" style="min-width:320px"><input name="q" placeholder="Cari ticker/nama/sektor" value="<?= e($q) ?>"></div><button class="btn ghost">Cari</button></form>
  <div class="table-wrap"><table><thead><tr><th>Ticker</th><th>Nama</th><th>Sektor</th><th>Flags</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($companies as $c): $json=e(json_encode($c,JSON_UNESCAPED_UNICODE)); ?>
    <tr>
      <td class="mono"><strong><?= e($c['ticker']) ?></strong></td><td><?= e($c['name']) ?></td><td><?= e($c['sector']) ?><br><span class="muted"><?= e($c['subsector']) ?></span></td>
      <td>
        <?= $c['is_idx30']?badge('IDX30'):'' ?> <?= $c['is_long_suspended']?badge('SUSPEND'):'' ?> <?= !$c['financial_report_complete']?badge('LK TIDAK LENGKAP'):'' ?> <?= $c['is_special_der_sector']?badge('DER KHUSUS'):'' ?>
      </td>
      <td><?= e($c['last_review_notes']) ?></td>
      <td class="nowrap"><button class="btn small ghost" data-row='<?= $json ?>' onclick="fillCompanyForm(this)">Edit</button>
      <form method="post" action="<?= url('company.delete') ?>" style="display:inline" onsubmit="return confirmDelete('Hapus emiten dan seluruh data turunannya?')"><?= Csrf::input() ?><input type="hidden" name="id" value="<?= e($c['id']) ?>"><button class="btn small danger">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?><?php if(!$companies): ?><tr><td colspan="6" class="muted">Belum ada data emiten.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<div class="modal" id="companyModal"><div class="modal-dialog"><div class="modal-head"><h2>Form Emiten</h2><button class="close" data-modal-close>×</button></div>
<form method="post" action="<?= url('company.save') ?>"><?= Csrf::input() ?><input type="hidden" name="id">
  <div class="form-grid">
    <div class="field"><label>Ticker</label><input name="ticker" required maxlength="12" placeholder="BBRI"></div>
    <div class="field"><label>Nama Emiten</label><input name="name" required></div>
    <div class="field"><label>Sektor</label><input name="sector" required></div>
    <div class="field"><label>Subsektor</label><input name="subsector"></div>
    <div class="field full"><label>Catatan Review</label><textarea name="last_review_notes"></textarea></div>
    <label><input type="checkbox" name="is_idx30" value="1"> Anggota IDX30</label>
    <label><input type="checkbox" name="is_special_der_sector" value="1"> Sektor khusus DER</label>
    <label><input type="checkbox" name="is_long_suspended" value="1"> Suspend berkepanjangan</label>
    <label><input type="checkbox" name="financial_report_complete" value="1" checked> Laporan keuangan lengkap</label>
  </div><div class="actions"><button class="btn">Simpan</button></div>
</form></div></div>
