CREATE DATABASE IF NOT EXISTS saham_analyzer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE palalloi_analyzer;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','analyst','viewer') NOT NULL DEFAULT 'analyst',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO users (name,email,password_hash,role,is_active)
VALUES ('Administrator','admin@example.com','$2y$12$4QjeKr7QdzhmaqNByW0e9Oszna4Ox55S3phIWoKpTTiQ4yTLA9teC','admin',1)
ON DUPLICATE KEY UPDATE email=email;

CREATE TABLE companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticker VARCHAR(12) NOT NULL UNIQUE,
  name VARCHAR(180) NOT NULL,
  sector VARCHAR(120) NOT NULL,
  subsector VARCHAR(120) NULL,
  is_idx30 TINYINT(1) NOT NULL DEFAULT 0,
  is_special_der_sector TINYINT(1) NOT NULL DEFAULT 0,
  is_long_suspended TINYINT(1) NOT NULL DEFAULT 0,
  financial_report_complete TINYINT(1) NOT NULL DEFAULT 1,
  last_review_notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sector (sector),
  INDEX idx_flags (is_idx30,is_long_suspended,financial_report_complete)
) ENGINE=InnoDB;

CREATE TABLE cycles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cycle_code VARCHAR(60) NOT NULL UNIQUE,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  status ENUM('draft','running','finalized','archived') NOT NULL DEFAULT 'draft',
  previous_cycle_id INT NULL,
  notes TEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  finalized_at DATETIME NULL,
  CONSTRAINT fk_cycles_prev FOREIGN KEY (previous_cycle_id) REFERENCES cycles(id) ON DELETE SET NULL,
  CONSTRAINT fk_cycles_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE source_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NULL,
  doc_type ENUM('financial_statement','annual_report','disclosure','rups','corporate_action','public_expose','investor_presentation','price_weekly','idx30_list','suspension','xbrl','other') NOT NULL,
  title VARCHAR(220) NOT NULL,
  official_source ENUM('IDX','OJK','Issuer_IR','IDX_Data_Service','Other_Official') NOT NULL DEFAULT 'IDX',
  source_url TEXT NULL,
  published_date DATE NULL,
  period_start DATE NULL,
  period_end DATE NULL,
  file_path VARCHAR(255) NULL,
  file_checksum VARCHAR(128) NULL,
  verification_status ENUM('confirmed','pending','rejected') NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sources_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
  CONSTRAINT fk_sources_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_doc_company_type (company_id,doc_type),
  INDEX idx_doc_period (period_end, verification_status)
) ENGINE=InnoDB;

CREATE TABLE financial_metrics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  fiscal_year INT NOT NULL,
  period_type ENUM('annual','q1','q2','q3','q4') NOT NULL,
  period_end DATE NOT NULL,
  revenue DECIMAL(22,2) NULL,
  revenue_yoy_pct DECIMAL(10,2) NULL,
  gross_profit DECIMAL(22,2) NULL,
  operating_profit DECIMAL(22,2) NULL,
  net_profit DECIMAL(22,2) NULL,
  net_profit_yoy_pct DECIMAL(10,2) NULL,
  total_assets DECIMAL(22,2) NULL,
  total_liabilities DECIMAL(22,2) NULL,
  total_equity DECIMAL(22,2) NULL,
  total_debt DECIMAL(22,2) NULL,
  der DECIMAL(10,2) NULL,
  operating_cash_flow DECIMAL(22,2) NULL,
  gross_margin_pct DECIMAL(10,2) NULL,
  operating_margin_pct DECIMAL(10,2) NULL,
  net_margin_pct DECIMAL(10,2) NULL,
  source_document_id INT NULL,
  is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fin_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_fin_source FOREIGN KEY (source_document_id) REFERENCES source_documents(id) ON DELETE SET NULL,
  UNIQUE KEY uq_fin_period (company_id, fiscal_year, period_type),
  INDEX idx_fin_company_period (company_id, period_end),
  INDEX idx_fin_growth (revenue_yoy_pct, net_profit_yoy_pct)
) ENGINE=InnoDB;

CREATE TABLE weekly_prices (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  week_end DATE NOT NULL,
  open_price DECIMAL(18,4) NULL,
  high_price DECIMAL(18,4) NULL,
  low_price DECIMAL(18,4) NULL,
  close_price DECIMAL(18,4) NOT NULL,
  volume BIGINT NOT NULL,
  source_document_id INT NULL,
  is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_price_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_price_source FOREIGN KEY (source_document_id) REFERENCES source_documents(id) ON DELETE SET NULL,
  UNIQUE KEY uq_price_week (company_id, week_end),
  INDEX idx_price_company_week (company_id, week_end)
) ENGINE=InnoDB;

CREATE TABLE catalysts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cycle_id INT NOT NULL,
  company_id INT NOT NULL,
  catalyst_type ENUM('turnaround','innovation_disruption','measured_expansion','structural_trend') NOT NULL,
  description TEXT NOT NULL,
  evidence_summary TEXT NOT NULL,
  source_document_id INT NULL,
  impact_core_business TINYINT(1) NOT NULL DEFAULT 0,
  repeatable TINYINT(1) NOT NULL DEFAULT 0,
  scalable TINYINT(1) NOT NULL DEFAULT 0,
  multi_quarter_impact TINYINT(1) NOT NULL DEFAULT 0,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cat_cycle FOREIGN KEY (cycle_id) REFERENCES cycles(id) ON DELETE CASCADE,
  CONSTRAINT fk_cat_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_cat_source FOREIGN KEY (source_document_id) REFERENCES source_documents(id) ON DELETE SET NULL,
  INDEX idx_cat_cycle_company (cycle_id, company_id),
  INDEX idx_cat_verified (verified)
) ENGINE=InnoDB;

CREATE TABLE technical_reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cycle_id INT NOT NULL,
  company_id INT NOT NULL,
  review_week_end DATE NOT NULL,
  phase ENUM('base','breakout','re_accumulation','downtrend','distribution','unconfirmed') NOT NULL DEFAULT 'unconfirmed',
  distribution_extreme TINYINT(1) NOT NULL DEFAULT 0,
  ath_without_new_catalyst TINYINT(1) NOT NULL DEFAULT 0,
  relative_strength_notes TEXT NULL,
  analyst_notes TEXT NULL,
  source_document_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tech_cycle FOREIGN KEY (cycle_id) REFERENCES cycles(id) ON DELETE CASCADE,
  CONSTRAINT fk_tech_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_tech_source FOREIGN KEY (source_document_id) REFERENCES source_documents(id) ON DELETE SET NULL,
  UNIQUE KEY uq_tech_cycle_company (cycle_id, company_id)
) ENGINE=InnoDB;

CREATE TABLE screening_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cycle_id INT NOT NULL,
  company_id INT NOT NULL,
  status ENUM('PASS','DROP','DATA_BELUM_TERKONFIRMASI') NOT NULL,
  score DECIMAL(10,2) NOT NULL DEFAULT 0,
  catalyst_pass TINYINT(1) NOT NULL DEFAULT 0,
  quantitative_pass TINYINT(1) NOT NULL DEFAULT 0,
  der_pass TINYINT(1) NOT NULL DEFAULT 0,
  ocf_pass TINYINT(1) NOT NULL DEFAULT 0,
  technical_pass TINYINT(1) NOT NULL DEFAULT 0,
  fail_reasons TEXT NULL,
  data_snapshot JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_screen_cycle FOREIGN KEY (cycle_id) REFERENCES cycles(id) ON DELETE CASCADE,
  CONSTRAINT fk_screen_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  UNIQUE KEY uq_screen_cycle_company (cycle_id, company_id),
  INDEX idx_screen_status (cycle_id,status,score)
) ENGINE=InnoDB;

CREATE TABLE pillar_validations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cycle_id INT NOT NULL,
  company_id INT NOT NULL,
  pillar ENUM('catalyst','financial_quality','moat','technical','risk_reward') NOT NULL,
  status ENUM('LULUS','GAGAL','TUNDA') NOT NULL DEFAULT 'TUNDA',
  thesis TEXT NULL,
  success_indicators TEXT NULL,
  failure_indicators TEXT NULL,
  analysis_notes TEXT NULL,
  source_document_id INT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pillar_cycle FOREIGN KEY (cycle_id) REFERENCES cycles(id) ON DELETE CASCADE,
  CONSTRAINT fk_pillar_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_pillar_source FOREIGN KEY (source_document_id) REFERENCES source_documents(id) ON DELETE SET NULL,
  CONSTRAINT fk_pillar_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_pillar_cycle_company (cycle_id, company_id, pillar)
) ENGINE=InnoDB;

CREATE TABLE valuation_scenarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cycle_id INT NOT NULL,
  company_id INT NOT NULL,
  period_end DATE NOT NULL,
  current_price DECIMAL(18,4) NULL,
  bear_price DECIMAL(18,4) NULL,
  base_price DECIMAL(18,4) NULL,
  bull_price DECIMAL(18,4) NULL,
  downside_pct DECIMAL(10,2) NULL,
  conservative_upside_pct DECIMAL(10,2) NULL,
  upside_downside_ratio DECIMAL(10,2) NULL,
  valuation_relative TEXT NULL,
  source_document_id INT NULL,
  is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_val_cycle FOREIGN KEY (cycle_id) REFERENCES cycles(id) ON DELETE CASCADE,
  CONSTRAINT fk_val_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_val_source FOREIGN KEY (source_document_id) REFERENCES source_documents(id) ON DELETE SET NULL,
  UNIQUE KEY uq_val_cycle_company (cycle_id, company_id)
) ENGINE=InnoDB;

CREATE TABLE investment_memos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cycle_id INT NOT NULL,
  company_id INT NOT NULL,
  summary_thesis TEXT NOT NULL,
  key_data TEXT NOT NULL,
  validated_catalyst TEXT NOT NULL,
  main_risks TEXT NOT NULL,
  invalidation_level TEXT NOT NULL,
  final_status ENUM('INVEST','WATCHLIST','DROP','DATA_BELUM_TERKONFIRMASI') NOT NULL,
  decision_reason TEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_memo_cycle FOREIGN KEY (cycle_id) REFERENCES cycles(id) ON DELETE CASCADE,
  CONSTRAINT fk_memo_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_memo_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_memo_cycle_company (cycle_id, company_id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(120) NOT NULL,
  entity VARCHAR(120) NOT NULL,
  entity_id VARCHAR(60) NULL,
  meta JSON NULL,
  ip_address VARCHAR(60) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_entity (entity, entity_id),
  INDEX idx_audit_date (created_at)
) ENGINE=InnoDB;
