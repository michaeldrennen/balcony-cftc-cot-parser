CREATE TABLE IF NOT EXISTS balcony_cftc_position_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    report_date DATE NOT NULL,
    contract_market_code VARCHAR(32) NOT NULL,
    contract_name VARCHAR(128) NOT NULL,
    open_interest BIGINT UNSIGNED NOT NULL,
    dealer_long BIGINT UNSIGNED NOT NULL,
    dealer_short BIGINT UNSIGNED NOT NULL,
    asset_mgr_long BIGINT UNSIGNED NOT NULL,
    asset_mgr_short BIGINT UNSIGNED NOT NULL,
    lev_funds_long BIGINT UNSIGNED NOT NULL,
    lev_funds_short BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY balcony_cftc_report_contract_unique (report_date, contract_market_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
