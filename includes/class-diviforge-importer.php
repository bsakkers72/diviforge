<?php
if (!defined('ABSPATH')) { exit; }

class DiviForge_Importer {
    public static function import_upload($file, $target_page_id = 0, $page_title_override = '') {
        if (!current_user_can('edit_pages')) {
            return new WP_Error('diviforge_forbidden', __('You are not allowed to import pages.', 'diviforge'));
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('diviforge_no_file', __('No package file was uploaded.', 'diviforge'));
        }
        if (!class_exists('ZipArchive')) {
            return new WP_Error('diviforge_zip_missing', __('ZipArchive is not available on this server.', 'diviforge'));
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($file['tmp_name'])) {
            return new WP_Error('diviforge_zip_open_failed', __('The package could not be opened.', 'diviforge'));
        }

        $layout = self::read_json($zip, 'layout.json');
        $manifest = self::read_json($zip, 'manifest.json');
        $css = self::read_file($zip, 'page.css');
        if (!is_array($layout)) {
            $zip->close();
            return new WP_Error('diviforge_layout_missing', __('layout.json is missing or invalid.', 'diviforge'));
        }

        $content = self::layout_to_divi_shortcodes($layout);
        $title = !empty($manifest['title']) ? sanitize_text_field($manifest['title']) : (!empty($layout['title']) ? sanitize_text_field($layout['title']) : __('DiviForge Page', 'diviforge'));
        if (!$target_page_id && is_string($page_title_override) && trim($page_title_override) !== '') {
            $title = sanitize_text_field($page_title_override);
        }
        $import_mode = $target_page_id ? 'update' : 'new';

        if ($target_page_id) {
            $existing = get_post(absint($target_page_id));
            if (!$existing || $existing->post_type !== 'page') {
                $zip->close();
                return new WP_Error('diviforge_invalid_target_page', __('The selected target page does not exist.', 'diviforge'));
            }
            $postarr = array(
                'ID' => absint($target_page_id),
                'post_content' => $content,
            );
        } else {
            $postarr = array(
                'post_title' => $title,
                'post_content' => $content,
                'post_status' => 'draft',
                'post_type' => 'page',
            );
        }
        $post_id = wp_insert_post($postarr, true);
        if (is_wp_error($post_id)) {
            $zip->close();
            return $post_id;
        }

        update_post_meta($post_id, '_et_pb_use_builder', 'on');
        update_post_meta($post_id, '_et_pb_old_content', '');
        update_post_meta($post_id, '_diviforge_version', DIVIFORGE_VERSION);
        update_post_meta($post_id, '_diviforge_package_hash', hash_file('sha256', $file['tmp_name']));
        update_post_meta($post_id, '_diviforge_package_imported_at', current_time('mysql'));
        update_post_meta($post_id, '_diviforge_package_title', $title);
        update_post_meta($post_id, '_diviforge_package_filename', isset($file['name']) ? sanitize_file_name($file['name']) : '');
        if (is_array($manifest)) {
            update_post_meta($post_id, '_diviforge_package_manifest', wp_json_encode($manifest));
            if (!empty($manifest['version'])) {
                update_post_meta($post_id, '_diviforge_package_version', sanitize_text_field($manifest['version']));
            }
            if (!empty($manifest['author'])) {
                update_post_meta($post_id, '_diviforge_package_author', sanitize_text_field($manifest['author']));
            }
            if (!empty($manifest['tags']) && is_array($manifest['tags'])) {
                update_post_meta($post_id, '_diviforge_package_tags', wp_json_encode(array_map('sanitize_text_field', $manifest['tags'])));
            }
            if (!empty($manifest['category'])) {
                update_post_meta($post_id, '_diviforge_package_category', sanitize_text_field($manifest['category']));
            }
        }

        $stats = self::package_stats($zip, $layout, $css);
        update_post_meta($post_id, '_diviforge_package_stats', wp_json_encode($stats));
        foreach ($stats as $key => $value) {
            update_post_meta($post_id, '_diviforge_stat_' . sanitize_key($key), absint($value));
        }

        $thumbnail_id = self::import_package_thumbnail($zip, $post_id, $title);
        if ($thumbnail_id) {
            set_post_thumbnail($post_id, $thumbnail_id);
            update_post_meta($post_id, '_diviforge_package_thumbnail_id', $thumbnail_id);
        }

        $css_result = DiviForge_CSS_Importer::apply_to_page($post_id, $css, 'package');
        if (is_wp_error($css_result)) {
            $zip->close();
            return $css_result;
        }

        self::append_import_history($post_id, array(
            'mode' => $import_mode,
            'package_title' => $title,
            'package_version' => is_array($manifest) && !empty($manifest['version']) ? sanitize_text_field($manifest['version']) : '',
            'filename' => isset($file['name']) ? sanitize_file_name($file['name']) : '',
            'hash' => hash_file('sha256', $file['tmp_name']),
            'imported_at' => current_time('mysql'),
            'stats' => $stats,
            'content' => $content,
            'css' => is_string($css) ? $css : '',
            'release_note' => self::generate_release_note($import_mode, $title, $manifest, $stats, $css),
        ));

        $zip->close();

        return array('post_id' => $post_id, 'title' => $title, 'css' => $css_result);
    }

    private static function generate_release_note($mode, $title, $manifest, $stats, $css) {
        $parts = array();
        $is_update = ($mode === 'update');
        $parts[] = $is_update
            ? __('Bestaande pagina bijgewerkt met een nieuw DiviForge package.', 'diviforge')
            : __('Nieuwe pagina aangemaakt vanuit een DiviForge package.', 'diviforge');

        $title = is_string($title) && trim($title) !== '' ? sanitize_text_field($title) : __('DiviForge package', 'diviforge');
        $version = is_array($manifest) && !empty($manifest['version']) ? sanitize_text_field($manifest['version']) : '';
        $category = is_array($manifest) && !empty($manifest['category']) ? sanitize_text_field($manifest['category']) : '';

        $meta = $title;
        if ($version !== '') { $meta .= ' ' . $version; }
        if ($category !== '') { $meta .= ' (' . $category . ')'; }
        $parts[] = sprintf(__('Package: %s.', 'diviforge'), $meta);

        $sections = !empty($stats['sections']) ? absint($stats['sections']) : 0;
        $modules = !empty($stats['modules']) ? absint($stats['modules']) : 0;
        $images = !empty($stats['images']) ? absint($stats['images']) : 0;
        $css_lines = !empty($stats['css_lines']) ? absint($stats['css_lines']) : 0;

        $parts[] = sprintf(
            __('Inhoud: %1$d secties, %2$d modules, %3$d afbeeldingen en %4$d regels page.css.', 'diviforge'),
            $sections,
            $modules,
            $images,
            $css_lines
        );

        if (is_string($css) && trim($css) !== '') {
            $parts[] = __('Page Custom CSS is automatisch toegepast op de Divi-pagina en opgeslagen in DiviForge metadata.', 'diviforge');
        } else {
            $parts[] = __('Er is geen page.css gevonden; alleen de layout en package-metadata zijn bijgewerkt.', 'diviforge');
        }

        if (is_array($manifest)) {
            $features = array();
            foreach (array('features', 'changes', 'highlights') as $key) {
                if (!empty($manifest[$key]) && is_array($manifest[$key])) {
                    foreach ($manifest[$key] as $item) {
                        if (is_string($item) && trim($item) !== '') {
                            $features[] = sanitize_text_field($item);
                        }
                    }
                }
            }
            if ($features) {
                $features = array_slice(array_unique($features), 0, 5);
                $parts[] = __('Package-highlights: ', 'diviforge') . implode('; ', $features) . '.';
            }
        }

        return implode(' ', $parts);
    }

    private static function append_import_history($post_id, $entry) {
        $history = get_post_meta($post_id, '_diviforge_import_history', true);
        if (is_string($history) && $history !== '') {
            $decoded = json_decode($history, true);
            $history = is_array($decoded) ? $decoded : array();
        }
        if (!is_array($history)) { $history = array(); }

        $safe = array(
            'mode' => sanitize_key($entry['mode'] ?? 'import'),
            'package_title' => sanitize_text_field($entry['package_title'] ?? ''),
            'package_version' => sanitize_text_field($entry['package_version'] ?? ''),
            'filename' => sanitize_file_name($entry['filename'] ?? ''),
            'hash' => sanitize_text_field($entry['hash'] ?? ''),
            'imported_at' => sanitize_text_field($entry['imported_at'] ?? current_time('mysql')),
            'stats' => is_array($entry['stats'] ?? null) ? array_map('absint', $entry['stats']) : array(),
            'content' => is_string($entry['content'] ?? null) ? $entry['content'] : '',
            'css' => is_string($entry['css'] ?? null) ? $entry['css'] : '',
            'release_note' => sanitize_textarea_field($entry['release_note'] ?? ''),
        );

        $history[] = $safe;
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }
        update_post_meta($post_id, '_diviforge_import_history', wp_json_encode($history));
        update_post_meta($post_id, '_diviforge_history_count', count($history));
    }

    private static function read_json($zip, $basename) {
        $raw = self::read_file($zip, $basename);
        if (!$raw) { return null; }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private static function read_file($zip, $basename) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat && basename($stat['name']) === $basename) {
                return $zip->getFromIndex($i);
            }
        }
        return null;
    }

    private static function package_stats($zip, $layout, $css) {
        $stats = array(
            'sections' => 0,
            'rows' => 0,
            'columns' => 0,
            'modules' => 0,
            'images' => 0,
            'css_lines' => 0,
        );

        if (is_array($layout) && !empty($layout['sections']) && is_array($layout['sections'])) {
            $stats['sections'] = count($layout['sections']);
            foreach ($layout['sections'] as $section) {
                $rows = isset($section['rows']) && is_array($section['rows']) ? $section['rows'] : array();
                $stats['rows'] += count($rows);
                foreach ($rows as $row) {
                    $columns = isset($row['columns']) && is_array($row['columns']) ? $row['columns'] : array();
                    $stats['columns'] += count($columns);
                    foreach ($columns as $column) {
                        $modules = isset($column['modules']) && is_array($column['modules']) ? $column['modules'] : array();
                        $stats['modules'] += count($modules);
                    }
                }
            }
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat || empty($stat['name'])) { continue; }
            if (preg_match('#(^|/)(images|assets|preview)/.*\.(png|jpe?g|webp|gif)$#i', $stat['name'])) {
                $stats['images']++;
            }
        }

        if (is_string($css) && trim($css) !== '') {
            $stats['css_lines'] = substr_count(trim($css), "\n") + 1;
        }

        return $stats;
    }

    private static function import_package_thumbnail($zip, $post_id, $title) {
        $preferred = array();
        $fallback = array();

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat || empty($stat['name'])) { continue; }
            $name = ltrim($stat['name'], '/');
            if (!preg_match('/\.(png|jpe?g|webp|gif)$/i', $name)) { continue; }
            if (preg_match('#(^|/)(thumbnail|preview|screenshot)\.(png|jpe?g|webp|gif)$#i', basename($name))) {
                $preferred[] = array($i, $name);
            } elseif (preg_match('#(^|/)(preview|thumb|thumbnail|screenshots?)/#i', $name)) {
                $preferred[] = array($i, $name);
            } elseif (preg_match('#(^|/)images/#i', $name)) {
                $fallback[] = array($i, $name);
            }
        }

        $choice = !empty($preferred) ? $preferred[0] : (!empty($fallback) ? $fallback[0] : null);
        if (!$choice) { return 0; }

        $bytes = $zip->getFromIndex($choice[0]);
        if (!$bytes) { return 0; }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $extension = strtolower(pathinfo($choice[1], PATHINFO_EXTENSION));
        $filename = sanitize_file_name(sanitize_title($title) . '-diviforge-preview.' . $extension);
        $upload = wp_upload_bits($filename, null, $bytes);
        if (!empty($upload['error'])) { return 0; }

        $filetype = wp_check_filetype($upload['file'], null);
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_text_field($title . ' DiviForge preview'),
            'post_content' => '',
            'post_status' => 'inherit',
        );
        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
        if (is_wp_error($attachment_id)) { return 0; }

        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        if (!is_wp_error($metadata) && is_array($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        return absint($attachment_id);
    }

    public static function layout_to_divi_shortcodes($layout) {
        if (is_array($layout) && !empty($layout['divi_content']) && is_string($layout['divi_content'])) {
            return (string) $layout['divi_content'];
        }
        if (is_array($layout) && !empty($layout['content']) && is_string($layout['content'])) {
            return (string) $layout['content'];
        }
        $out = '';
        if (empty($layout['sections']) || !is_array($layout['sections'])) {
            return '';
        }
        foreach ($layout['sections'] as $section) {
            $class = !empty($section['class']) ? ' custom_css_main_element="' . esc_attr($section['class']) . '"' : '';
            $out .= '[et_pb_section' . $class . ']';
            $rows = isset($section['rows']) && is_array($section['rows']) ? $section['rows'] : array(array('columns' => array(array('modules' => array()))));
            foreach ($rows as $row) {
                $out .= '[et_pb_row]';
                $columns = isset($row['columns']) && is_array($row['columns']) ? $row['columns'] : array();
                foreach ($columns as $column) {
                    $out .= '[et_pb_column type="4_4"]';
                    $modules = isset($column['modules']) && is_array($column['modules']) ? $column['modules'] : array();
                    foreach ($modules as $module) {
                        $type = isset($module['type']) ? sanitize_key($module['type']) : 'text';
                        $content = isset($module['content']) ? wp_kses_post($module['content']) : '';
                        if ($type === 'button') {
                            $text = isset($module['text']) ? esc_html($module['text']) : __('Learn more', 'diviforge');
                            $url = isset($module['url']) ? esc_url($module['url']) : '#';
                            $out .= '[et_pb_button button_text="' . esc_attr($text) . '" button_url="' . esc_url($url) . '"][/et_pb_button]';
                        } elseif ($type === 'image') {
                            $src = isset($module['src']) ? esc_url($module['src']) : '';
                            $out .= '[et_pb_image src="' . esc_url($src) . '"][/et_pb_image]';
                        } else {
                            $out .= '[et_pb_text]' . $content . '[/et_pb_text]';
                        }
                    }
                    $out .= '[/et_pb_column]';
                }
                $out .= '[/et_pb_row]';
            }
            $out .= '[/et_pb_section]';
        }
        return $out;
    }
}
