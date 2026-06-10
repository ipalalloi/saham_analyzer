# saham_analyzer
Aplikasi PHP &amp; MySQL untuk screening dan analisa saham BEI berbasis siklus, evidence, dan decision gate profesional.
# SAHAM Audit-Ready Analyzer

Aplikasi PHPâ€“MySQL untuk screening dan analisa saham BEI berbasis siklus, evidence, dan decision gate profesional.

## Prinsip Utama

- Hanya data resmi: laporan keuangan/tahunan emiten, keterbukaan informasi BEI, RUPS/aksi korporasi resmi, paparan publik/investor presentation, data harga BEI timeframe weekly.
- Setiap angka wajib memiliki periode dan dokumen sumber.
- Data tanpa sumber confirmed dianggap `DATA BELUM TERKONFIRMASI`.
- Sistem tidak membuat estimasi, tidak mengisi data kosong, dan tidak memakai rumor/opini media.
- Satu saham hanya muncul satu kali per siklus analisis.

## Kebutuhan Server

- PHP 8.1+
- MySQL 8 atau MariaDB 10.5+
- Extension PHP: pdo_mysql, fileinfo, mbstring
- Web server Apache/Nginx

## Instalasi

1. Upload folder `saham-analisa` ke server.
2. Buat database dan import `database/install.sql`.
3. Copy `config.example.php` menjadi `config.php`.
4. Ubah credential database pada `config.php`.
5. Arahkan document root ke folder `public`.
6. Login awal:
   - Email: `admin@example.com`
   - Password: `admin123`
7. Setelah login, ganti password admin melalui query/manual admin panel lanjutan.

## Workflow Operasional

1. Import master emiten BEI di menu **Emiten**.
2. Tambahkan dokumen resmi di menu **Dokumen Resmi** dan set `verification_status=confirmed` hanya setelah sumber dan periode divalidasi.
3. Import data finansial CSV di menu **Data Finansial**.
4. Import harga weekly CSV di menu **Harga Weekly**.
5. Buat siklus analisis di menu **Siklus**.
6. Input katalis resmi/verified dan review teknikal manual di menu **Screening**.
7. Jalankan screening.
8. Validasi five-pillar dan valuasi di menu **Investment Memo**.
9. Generate memo final.

## Format CSV

Contoh tersedia pada folder `samples/`.

### Master Emiten

`ticker,name,sector,subsector,is_idx30,is_special_der_sector,is_long_suspended,financial_report_complete,last_review_notes`

### Data Finansial

`ticker,fiscal_year,period_type,period_end,revenue,revenue_yoy_pct,gross_profit,operating_profit,net_profit,net_profit_yoy_pct,total_assets,total_liabilities,total_equity,total_debt,der,operating_cash_flow,gross_margin_pct,operating_margin_pct,net_margin_pct,source_document_id,is_confirmed`

### Harga Weekly

`ticker,week_end,open,high,low,close,volume,source_document_id,is_confirmed`

## Catatan Penting

- `is_confirmed=1` pada data finansial/harga hanya efektif jika `source_document_id` mengarah ke dokumen berstatus `confirmed`.
- Screening teknikal memerlukan minimal 50 data weekly confirmed.
- Sistem memerlukan review teknikal manual untuk memastikan tidak ada distribusi ekstrem dan tidak ATH tanpa katalis baru.
- Jika PASS kurang dari 5, sistem tidak memaksa output 5 saham karena itu melanggar hard constraint.

## Struktur Folder

- `public/` entry point aplikasi.
- `app/Controllers/` controller modul.
- `app/Core/` core security, database, session, CSRF, audit.
- `app/Views/` tampilan modular.
- `database/install.sql` schema database.
- `storage/uploads/` penyimpanan dokumen bukti.
- `samples/` contoh CSV.

