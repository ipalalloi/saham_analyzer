<?php $user = Auth::user(); ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
  <title><?= e(App::config('app_name')) ?></title>
  <link rel="stylesheet" href="<?= e(App::baseUrl('assets/css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">SA</div>
      <div><strong>SAHAM</strong><span>Audit Analyzer</span></div>
    </div>
    <nav>
      <a class="<?= active('dashboard') ?>" href="<?= url('dashboard') ?>">Dashboard</a>
      <a class="<?= active('companies') ?>" href="<?= url('companies') ?>">Emiten</a>
      <a class="<?= active('cycles') ?>" href="<?= url('cycles') ?>">Siklus</a>
      <a class="<?= active('sources') ?>" href="<?= url('sources') ?>">Dokumen Resmi</a>
      <a class="<?= active('financials') ?>" href="<?= url('financials') ?>">Data Finansial</a>
      <a class="<?= active('prices') ?>" href="<?= url('prices') ?>">Harga Weekly</a>
      <a class="<?= active('screening') ?>" href="<?= url('screening') ?>">Screening</a>
      <a class="<?= active('memos') ?>" href="<?= url('memos') ?>">Investment Memo</a>
    </nav>
  </aside>
  <main class="main">
    <header class="topbar">
      <button class="hamburger" data-toggle-sidebar>☰</button>
      <div>
        <strong><?= e(App::config('app_name')) ?></strong>
        <span class="muted">Evidence-first stock analysis</span>
      </div>
      <div class="userbox">
        <span><?= e($user['name'] ?? '') ?> · <?= e($user['role'] ?? '') ?></span>
        <a class="btn ghost" href="<?= url('logout') ?>">Logout</a>
      </div>
    </header>
    <?php if ($m = Session::flash('success')): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = Session::flash('error')): ?><div class="alert danger"><?= e($m) ?></div><?php endif; ?>
