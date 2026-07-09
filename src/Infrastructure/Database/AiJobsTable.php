<?php
namespace DiviForge\Infrastructure\Database;

if (!defined('ABSPATH')) { exit; }

final class AiJobsTable {
    public const DB_VERSION = '1.0.0';
    private const OPTION_NAME = 'diviforge_ai_jobs_db_version';

    public static function tableName(): string {
        global $wpdb;
        return $wpdb->prefix . 'diviforge_ai_jobs';
    }

    public static function install(): void {
        global $wpdb;

        if (get_option(self::OPTION_NAME) === self::DB_VERSION) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::tableName();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            provider VARCHAR(50) NOT NULL,
            model VARCHAR(100) NOT NULL,
            prompt LONGTEXT NOT NULL,
            response LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'queued',
            tokens BIGINT UNSIGNED NOT NULL DEFAULT 0,
            cost DECIMAL(10,6) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY provider (provider)
        ) {$charset_collate};";

        dbDelta($sql);

        update_option(self::OPTION_NAME, self::DB_VERSION, false);
    }
}
