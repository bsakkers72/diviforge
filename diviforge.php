<?php
/**
 * Plugin Name: DiviForge
 * Plugin URI: https://barrysakkers.com
 * Description: AI-powered workflow toolkit for building professional Divi pages from structured packages, prompts, and guided onboarding.
 * Version: 3.5.0-dev-20260709-2340
 * Author: Barry Sakkers
 * Text Domain: diviforge
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DIVIFORGE_VERSION', '3.5.0-dev-20260709-2340');
define('DIVIFORGE_FILE', __FILE__);
define('DIVIFORGE_PATH', plugin_dir_path(__FILE__));
define('DIVIFORGE_URL', plugin_dir_url(__FILE__));

require_once DIVIFORGE_PATH . 'src/Core/Autoloader.php';
$diviforge_autoloader = new DiviForge\Core\Autoloader(DIVIFORGE_PATH . 'src');
$diviforge_autoloader->register();

require_once DIVIFORGE_PATH . 'includes/class-diviforge.php';

register_activation_hook(__FILE__, array('DiviForge', 'activate'));
register_activation_hook(__FILE__, array('DiviForge\\Core\\Lifecycle', 'activate'));
register_activation_hook(__FILE__, array('DiviForge\\Infrastructure\\Database\\AiJobsTable', 'install'));
register_deactivation_hook(__FILE__, array('DiviForge', 'deactivate'));
register_deactivation_hook(__FILE__, array('DiviForge\\Core\\Lifecycle', 'deactivate'));

add_action('plugins_loaded', function () {
    load_plugin_textdomain('diviforge', false, dirname(plugin_basename(__FILE__)) . '/languages');
    DiviForge\Core\Bootstrap::instance()->boot();
});
