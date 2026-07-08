<?php
if (!defined('ABSPATH')) { exit; }

require_once DIVIFORGE_PATH . 'includes/class-diviforge-package.php';
require_once DIVIFORGE_PATH . 'includes/class-diviforge-css-importer.php';
require_once DIVIFORGE_PATH . 'includes/class-diviforge-importer.php';
require_once DIVIFORGE_PATH . 'includes/admin/class-diviforge-admin.php';

final class DiviForge {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (is_admin()) {
            new DiviForge_Admin();
        }
    }

    public static function activate() {
        add_option('diviforge_version', DIVIFORGE_VERSION);
        add_option('diviforge_first_run_pending', '1');
        add_option('diviforge_wizard_completed', '0');
        add_option('diviforge_adr_log', array(
            array(
                'date' => gmdate('Y-m-d'),
                'decision' => 'Focus the first commercial foundation on one WordPress site and Divi as the primary builder.',
                'status' => 'accepted',
            ),
            array(
                'date' => gmdate('Y-m-d'),
                'decision' => 'Document first: roadmap, package specification, and architecture decisions drive development.',
                'status' => 'accepted',
            ),
        ));
    }

    public static function deactivate() {
        // Intentionally keep user data and settings.
    }
}
