<?php
/**
 * Copy file ini menjadi config.php lalu sesuaikan credential database server.
 * Simpan file config.php di root project, bukan di folder public.
 */
return [
    'app_name' => 'SAHAM Audit-Ready Analyzer',
    'base_url' => '', // contoh: https://domainanda.com/saham-analisa/public atau kosong jika di-root domain
    'timezone' => 'Asia/Makassar',
    'db' => [
        'host' => 'localhost',
        'name' => 'saham_analyzer',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'session_name' => 'SAHAM_ANALYZER_SESSID',
        'csrf_key' => 'csrf_token',
        'max_upload_mb' => 15,
        'allowed_upload_ext' => ['pdf','xlsx','xls','csv','jpg','jpeg','png'],
    ],
    'paths' => [
        'storage' => __DIR__ . '/storage',
        'uploads' => __DIR__ . '/storage/uploads',
        'logs' => __DIR__ . '/storage/logs',
    ],
];
