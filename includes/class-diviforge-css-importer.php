<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Handles CSS imported from DiviForge packages.
 *
 * The CSS is stored in two places:
 * 1. _diviforge_page_css: DiviForge's canonical copy for registry, re-import and rollback.
 * 2. _et_pb_custom_css: Divi's page-level Custom CSS field used by Divi Page Settings.
 */
class DiviForge_CSS_Importer {
    const DIVIFORGE_META_KEY = '_diviforge_page_css';
    const DIVI_META_KEY = '_et_pb_custom_css';

    public static function apply_to_page($post_id, $css, $source = 'package') {
        $post_id = absint($post_id);
        if (!$post_id) {
            return new WP_Error('diviforge_css_invalid_post', __('Invalid page ID for CSS import.', 'diviforge'));
        }

        $css = self::normalize_css($css);
        if ($css === '') {
            update_post_meta($post_id, '_diviforge_css_status', 'missing');
            return array(
                'applied' => false,
                'message' => __('No page.css found in package.', 'diviforge'),
            );
        }

        update_post_meta($post_id, self::DIVIFORGE_META_KEY, $css);
        update_post_meta($post_id, self::DIVI_META_KEY, $css);
        update_post_meta($post_id, '_diviforge_css_source', sanitize_key($source));
        update_post_meta($post_id, '_diviforge_css_status', 'applied');
        update_post_meta($post_id, '_diviforge_css_applied_at', current_time('mysql'));
        update_post_meta($post_id, '_diviforge_css_hash', hash('sha256', $css));
        update_post_meta($post_id, '_diviforge_css_target_meta', self::DIVI_META_KEY);

        self::clear_divi_static_css_cache($post_id);

        return array(
            'applied' => true,
            'message' => __('CSS applied to Divi Page Custom CSS.', 'diviforge'),
            'meta_key' => self::DIVI_META_KEY,
        );
    }

    public static function reapply_stored_css($post_id) {
        $post_id = absint($post_id);
        $css = get_post_meta($post_id, self::DIVIFORGE_META_KEY, true);
        if ($css === '') {
            return new WP_Error('diviforge_no_stored_css', __('No stored DiviForge CSS was found for this page.', 'diviforge'));
        }
        return self::apply_to_page($post_id, $css, 'stored-css');
    }

    private static function normalize_css($css) {
        if (!is_string($css)) { return ''; }
        $css = wp_check_invalid_utf8($css);
        $css = str_replace("\0", '', $css);
        $css = preg_replace('#<\s*/?\s*(script|style|php)[^>]*>#i', '', $css);
        $css = preg_replace('#<\?php.*?\?>#is', '', $css);
        return trim($css);
    }

    private static function clear_divi_static_css_cache($post_id) {
        if (class_exists('ET_Core_PageResource')) {
            if (method_exists('ET_Core_PageResource', 'remove_static_resources')) {
                ET_Core_PageResource::remove_static_resources('all', 'all');
            } elseif (method_exists('ET_Core_PageResource', 'remove_static_resources_for')) {
                ET_Core_PageResource::remove_static_resources_for($post_id);
            }
        }

        if (function_exists('et_core_page_resource_auto_clear')) {
            et_core_page_resource_auto_clear($post_id);
        }

        do_action('diviforge_after_page_css_import', $post_id);
    }
}
