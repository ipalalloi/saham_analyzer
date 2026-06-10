<div class="page-title">
  <div><h1>Dashboard Analisa Saham</h1><p class="muted">Sistem memprioritaskan dokumen resmi, periode data, bukti katalis, dan audit trail.</p></div>
</div>
<div class="grid cols-4" style="grid-template-columns:repeat(4,minmax(0,1fr));">
  <div class="stat"><span class="muted">Emiten</span><strong><?= e($stats['companies']) ?></strong></div>
  <div class="stat"><span class="muted">Dokumen Resmi</span><strong><?= e($stats['sources']) ?></strong></div>
  <div class="stat"><span class="muted">Siklus</span><strong><?= e($stats['cycles']) ?></strong></div>
  <div class="stat"><span class="muted">Memo</span><strong><?= e($stats['memos']) ?></strong></div>
</div>
<div class="grid cols-2">
  <section class="card">
    <h2>Prinsip Hard Constraint</h2>
    <div class="note">
      Angka tanpa dokumen, periode, atau status confirmed tidak boleh dipakai sebagai dasar kelulusan. Sistem akan menandai data sebagai <strong>DATA BELUM TERKONFIRMASI</strong> dan melakukan DROP/TUNDA sesuai decision gate.
    </div>
    <ol>
      <li>Data resmi: BEI, OJK, IR emiten, IDX Data Service, XBRL resmi.</li>
      <li>Setiap angka memiliki periode waktu dan dokumen sumber.</li>
      <li>Satu saham hanya boleh muncul sekali per siklus.</li>
      <li>≥2 pilar gagal: DROP otomatis.</li>
    </ol>
  </section>
  <section class="card">
    <h2>Siklus Terakhir</h2>
    <?php if ($cycle): ?>
      <div class="kpi-list">
        <div><span>Kode</span><strong><?= e($cycle['cycle_code']) ?></strong></div>
        <div><span>Periode</span><strong><?= e($cycle['period_start']) ?> s.d. <?= e($cycle['period_end']) ?></strong></div>
        <div><span>Status</span><strong><?= badge($cycle['status']) ?></strong></div>
      </div>
      <div class="actions"><a class="btn" href="<?= url('screening',['cycle_id'=>$cycle['id']]) ?>">Buka Screening</a></div>
    <?php else: ?>
      <p class="muted">Belum ada siklus. Buat siklus analisis pertama.</p>
      <a class="btn" href="<?= url('cycles') ?>">Buat Siklus</a>
    <?php endif; ?>
  </section>
</div>
<section class="card">
  <h2>Dokumen Resmi Terbaru</h2>
  <div class="table-wrap"><table><thead><tr><th>Emiten</th><th>Jenis</th><th>Judul</th><th>Periode</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($recent as $r): ?><tr>
    <td class="mono"><?= e($r['ticker'] ?? '-') ?></td><td><?= e($r['doc_type']) ?></td><td><?= e($r['title']) ?></td><td><?= e($r['period_start'] ?: '-') ?> — <?= e($r['period_end'] ?: '-') ?></td><td><?= badge($r['verification_status']) ?></td>
  </tr><?php endforeach; ?>
  <?php if (!$recent): ?><tr><td colspan="5" class="muted">Belum ada dokumen.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
