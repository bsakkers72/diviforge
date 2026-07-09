<?php
namespace DiviForge\Core;

if (!defined('ABSPATH')) { exit; }

final class Lifecycle {
    public static function activate(): void {
        add_option('diviforge_core_architecture_version', '000');
        add_option('diviforge_core_bootstrapped_at', current_time('mysql'));
    }

    public static function deactivate(): void {
        // Keep data intentionally. DiviForge should never remove user settings on deactivate.
    }
}
