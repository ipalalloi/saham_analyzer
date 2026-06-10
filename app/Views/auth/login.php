<div class="auth-card">
  <div class="brand" style="color:#172033;margin-bottom:18px">
    <div class="brand-mark">SA</div>
    <div><strong><?= e(App::config('app_name')) ?></strong><span>Audit-ready investment memo system</span></div>
  </div>
  <h1>Login</h1>
  <p class="muted">Default awal: admin@example.com / admin123. Segera ubah setelah instalasi.</p>
  <?php if ($m = Session::flash('error')): ?><div class="alert danger"><?= e($m) ?></div><?php endif; ?>
  <form method="post">
    <?= Csrf::input() ?>
    <div class="field"><label>Email</label><input type="email" name="email" required value="admin@example.com"></div><br>
    <div class="field"><label>Password</label><input type="password" name="password" required value="admin123"></div>
    <div class="actions"><button class="btn" type="submit">Masuk</button></div>
  </form>
</div>
