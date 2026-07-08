<?php
if (!defined('ABSPATH')) { exit; }

class DiviForge_Package {
    public static function inspect_upload($file) {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('diviforge_no_file', __('No package file was uploaded.', 'diviforge'));
        }

        $filename = isset($file['name']) ? sanitize_file_name($file['name']) : '';
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, array('zip', 'dfg'), true)) {
            return new WP_Error('diviforge_bad_type', __('Please upload a .zip or .dfg package.', 'diviforge'));
        }

        if (!class_exists('ZipArchive')) {
            return new WP_Error('diviforge_zip_missing', __('ZipArchive is not available on this server.', 'diviforge'));
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($file['tmp_name'])) {
            return new WP_Error('diviforge_zip_open_failed', __('The package could not be opened.', 'diviforge'));
        }

        $entries = array();
        $images = array();
        $sections = array();
        $manifest = null;
        $layout = null;
        $has_css = false;
        $preview_images = array();
        $css_lines = 0;
        $css_bytes = 0;
        $css_preview = '';
        $package_bytes = 0;
        $layout_errors = array();
        $module_types = array();

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat || empty($stat['name'])) { continue; }
            $name = ltrim($stat['name'], '/');
            $package_bytes += isset($stat['size']) ? absint($stat['size']) : 0;
            $entries[] = $name;
            if (preg_match('#(^|/)images/.*\.(png|jpe?g|webp|gif|svg)$#i', $name)) {
                $images[] = $name;
            }
            if (preg_match('#(^|/)(preview|thumb|thumbnail|screenshots?)/.*\.(png|jpe?g|webp|gif)$#i', $name) || preg_match('#(^|/)(preview|thumbnail|screenshot)\.(png|jpe?g|webp|gif)$#i', basename($name))) {
                $preview_images[] = $name;
            }
            if (basename($name) === 'page.css') {
                $has_css = true;
                $css_raw = $zip->getFromIndex($i);
                if (is_string($css_raw)) {
                    $css_bytes = strlen($css_raw);
                    $css_lines = trim($css_raw) === '' ? 0 : substr_count(trim($css_raw), "\n") + 1;
                    $css_preview = self::safe_preview_css($css_raw);
                }
            }
            if (basename($name) === 'manifest.json') {
                $raw = $zip->getFromIndex($i);
                $manifest = json_decode($raw, true);
            }
            if (basename($name) === 'layout.json') {
                $raw = $zip->getFromIndex($i);
                $layout = json_decode($raw, true);
            }
        }
        $zip->close();

        $stats = array('sections' => 0, 'rows' => 0, 'columns' => 0, 'modules' => 0, 'images' => count($images), 'preview_images' => count($preview_images), 'css_lines' => $css_lines, 'css_bytes' => $css_bytes, 'package_bytes' => $package_bytes);
        if (is_array($layout)) {
            if (!empty($layout['sections']) && is_array($layout['sections'])) {
                $stats['sections'] = count($layout['sections']);
                foreach ($layout['sections'] as $section) {
                    $sections[] = isset($section['label']) ? sanitize_text_field($section['label']) : __('Untitled section', 'diviforge');
                    $rows = isset($section['rows']) && is_array($section['rows']) ? $section['rows'] : array();
                    $stats['rows'] += count($rows);
                    foreach ($rows as $row) {
                        $columns = isset($row['columns']) && is_array($row['columns']) ? $row['columns'] : array();
                        $stats['columns'] += count($columns);
                        foreach ($columns as $column) {
                            $modules = isset($column['modules']) && is_array($column['modules']) ? $column['modules'] : array();
                            $stats['modules'] += count($modules);
                            foreach ($modules as $module) {
                                if (!empty($module['type'])) {
                                    $type = sanitize_key($module['type']);
                                    if (!isset($module_types[$type])) { $module_types[$type] = 0; }
                                    $module_types[$type]++;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!is_array($layout)) {
            $layout_errors[] = __('layout.json could not be parsed as valid JSON.', 'diviforge');
        } elseif (empty($layout['sections']) || !is_array($layout['sections'])) {
            $layout_errors[] = __('layout.json does not contain a sections array.', 'diviforge');
        }

        $readiness = self::readiness_score($manifest, $layout, $has_css);
        $manifest_summary = self::manifest_summary($manifest);

        $title = $filename;
        if (is_array($manifest) && !empty($manifest['title'])) {
            $title = sanitize_text_field($manifest['title']);
        } elseif (is_array($layout) && !empty($layout['title'])) {
            $title = sanitize_text_field($layout['title']);
        }

        return array(
            'filename' => $filename,
            'title' => $title,
            'entries' => $entries,
            'has_manifest' => is_array($manifest),
            'has_layout' => is_array($layout),
            'has_css' => $has_css,
            'images' => $images,
            'preview_images' => $preview_images,
            'sections' => $sections,
            'stats' => $stats,
            'manifest' => is_array($manifest) ? $manifest : array(),
            'compatible' => self::is_compatible($manifest),
            'warnings' => array_merge(self::warnings($manifest, $layout, $has_css), $layout_errors),
            'readiness' => $readiness,
            'manifest_summary' => $manifest_summary,
            'module_types' => $module_types,
            'preview_html' => self::preview_html($layout),
            'preview_css' => $css_preview,
        );
    }



    private static function safe_preview_css($css) {
        if (!is_string($css)) { return ''; }
        $css = wp_strip_all_tags($css);
        // Keep the inspector light and avoid extremely large AJAX payloads.
        if (strlen($css) > 60000) {
            $css = substr($css, 0, 60000) . "\n/* DiviForge preview truncated for performance. */";
        }
        return $css;
    }

    private static function preview_html($layout) {
        if (!is_array($layout) || empty($layout['sections']) || !is_array($layout['sections'])) {
            return '';
        }
        $out = '';
        foreach ($layout['sections'] as $section) {
            $class = !empty($section['class']) ? ' ' . sanitize_html_class($section['class']) : '';
            $label = !empty($section['label']) ? sanitize_text_field($section['label']) : '';
            $out .= '<section class="dfpkg-section' . esc_attr($class) . '">';
            if ($label) {
                $out .= '<div class="dfpkg-section-label">' . esc_html($label) . '</div>';
            }
            $rows = isset($section['rows']) && is_array($section['rows']) ? $section['rows'] : array();
            foreach ($rows as $row) {
                $row_class = !empty($row['class']) ? ' ' . sanitize_html_class($row['class']) : '';
                $columns = isset($row['columns']) && is_array($row['columns']) ? $row['columns'] : array();
                $count = max(1, count($columns));
                $out .= '<div class="dfpkg-row' . esc_attr($row_class) . '" style="--dfpkg-cols:' . absint($count) . '">';
                foreach ($columns as $column) {
                    $col_class = !empty($column['class']) ? ' ' . sanitize_html_class($column['class']) : '';
                    $out .= '<div class="dfpkg-column' . esc_attr($col_class) . '">';
                    $modules = isset($column['modules']) && is_array($column['modules']) ? $column['modules'] : array();
                    foreach ($modules as $module) {
                        $out .= self::preview_module($module);
                    }
                    $out .= '</div>';
                }
                $out .= '</div>';
            }
            $out .= '</section>';
        }
        return $out;
    }

    private static function preview_module($module) {
        $type = isset($module['type']) ? sanitize_key($module['type']) : 'text';
        $class = !empty($module['class']) ? ' ' . sanitize_html_class($module['class']) : '';
        if ($type === 'button') {
            $text = isset($module['text']) ? sanitize_text_field($module['text']) : __('Button', 'diviforge');
            $url = isset($module['url']) ? esc_url($module['url']) : '#';
            return '<div class="dfpkg-module dfpkg-button-module' . esc_attr($class) . '"><a href="' . esc_url($url) . '">' . esc_html($text) . '</a></div>';
        }
        if ($type === 'image') {
            $src = isset($module['src']) ? esc_url($module['src']) : '';
            if (!$src) {
                return '<div class="dfpkg-module dfpkg-image-placeholder' . esc_attr($class) . '">Image</div>';
            }
            return '<div class="dfpkg-module dfpkg-image-module' . esc_attr($class) . '"><img src="' . esc_url($src) . '" alt="" /></div>';
        }
        $content = isset($module['content']) ? wp_kses_post($module['content']) : '';
        return '<div class="dfpkg-module dfpkg-text-module' . esc_attr($class) . '">' . $content . '</div>';
    }

    private static function readiness_score($manifest, $layout, $has_css) {
        $score = 100;
        if (!is_array($manifest)) { $score -= 20; }
        if (!is_array($layout)) { $score -= 45; }
        if (is_array($layout) && (empty($layout['sections']) || !is_array($layout['sections']))) { $score -= 25; }
        if (!$has_css) { $score -= 15; }
        if (is_array($manifest) && !empty($manifest['builder']) && strtolower((string) $manifest['builder']) !== 'divi') { $score -= 20; }
        return max(0, min(100, $score));
    }

    private static function manifest_summary($manifest) {
        if (!is_array($manifest)) { return array(); }
        $keys = array('title', 'name', 'version', 'author', 'builder', 'category');
        $out = array();
        foreach ($keys as $key) {
            if (!empty($manifest[$key]) && is_scalar($manifest[$key])) {
                $out[$key] = sanitize_text_field((string) $manifest[$key]);
            }
        }
        if (!empty($manifest['tags']) && is_array($manifest['tags'])) {
            $out['tags'] = array_map('sanitize_text_field', $manifest['tags']);
        }
        return $out;
    }

    private static function is_compatible($manifest) {
        if (!is_array($manifest) || empty($manifest['builder'])) {
            return true;
        }
        return strtolower((string) $manifest['builder']) === 'divi';
    }

    private static function warnings($manifest, $layout, $has_css) {
        $warnings = array();
        if (!is_array($manifest)) { $warnings[] = __('manifest.json is missing. The package can still be inspected, but metadata is limited.', 'diviforge'); }
        if (!is_array($layout)) { $warnings[] = __('layout.json is missing or invalid.', 'diviforge'); }
        if (!$has_css) { $warnings[] = __('page.css is missing. The page may import without custom styling.', 'diviforge'); }
        if (is_array($manifest) && !empty($manifest['builder']) && strtolower((string) $manifest['builder']) !== 'divi') {
            $warnings[] = __('This package is not marked as a Divi package.', 'diviforge');
        }
        return $warnings;
    }
}
