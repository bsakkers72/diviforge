<?php
if (!defined('ABSPATH')) { exit; }

class DiviForge_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('admin_init', array($this, 'maybe_redirect_first_run'));
        add_action('admin_post_diviforge_complete_wizard', array($this, 'complete_wizard'));
        add_action('admin_post_diviforge_import_package', array($this, 'handle_import'));
        add_action('admin_post_diviforge_reapply_css', array($this, 'handle_reapply_css'));
        add_action('admin_post_diviforge_delete_page', array($this, 'handle_delete_page'));
        add_action('admin_post_diviforge_restore_history', array($this, 'handle_restore_history'));
        add_action('admin_post_diviforge_export_page_package', array($this, 'handle_export_page_package'));
        add_action('admin_post_diviforge_ai_studio_export', array($this, 'handle_ai_studio_export'));
        add_action('admin_post_diviforge_ai_improve', array($this, 'handle_ai_improve'));
        add_action('admin_post_diviforge_ai_chat_message', array($this, 'handle_ai_chat_message'));
        add_action('admin_post_diviforge_ai_import_result', array($this, 'handle_ai_import_result'));
        add_action('admin_post_diviforge_ai_discard_job', array($this, 'handle_ai_discard_job'));
        add_action('admin_post_diviforge_save_ai_settings', array($this, 'handle_save_ai_settings'));
        add_action('admin_post_diviforge_test_ai_connection', array($this, 'handle_test_ai_connection'));
        add_action('wp_ajax_diviforge_inspect_package', array($this, 'ajax_inspect_package'));
    }

    public function menu() {
        $cap = 'manage_options';
        add_menu_page(__('DiviForge', 'diviforge'), __('DiviForge', 'diviforge'), $cap, 'diviforge', array($this, 'dashboard'), 'dashicons-hammer', 58);
        add_submenu_page('diviforge', __('Dashboard', 'diviforge'), __('Dashboard', 'diviforge'), $cap, 'diviforge', array($this, 'dashboard'));
        add_submenu_page('diviforge', __('Pages', 'diviforge'), __('Pages', 'diviforge'), $cap, 'diviforge-pages', array($this, 'pages'));
        add_submenu_page('diviforge', __('Import Package', 'diviforge'), __('Import Package', 'diviforge'), $cap, 'diviforge-import', array($this, 'pages'));
        add_submenu_page('diviforge', __('Package Inspector', 'diviforge'), __('Package Inspector', 'diviforge'), $cap, 'diviforge-inspector', array($this, 'package_inspector'));
        add_submenu_page('diviforge', __('Packages', 'diviforge'), __('Packages', 'diviforge'), $cap, 'diviforge-packages', array($this, 'packages'));
        add_submenu_page('diviforge', __('Package History', 'diviforge'), __('Package History', 'diviforge'), $cap, 'diviforge-history', array($this, 'history'));
        add_submenu_page('diviforge', __('AI Studio', 'diviforge'), __('AI Studio', 'diviforge'), $cap, 'diviforge-ai-studio', array($this, 'ai_studio'));
        add_submenu_page('diviforge', __('AI Chat', 'diviforge'), __('AI Chat', 'diviforge'), $cap, 'diviforge-ai-chat', array($this, 'ai_chat'));
        add_submenu_page('diviforge', __('AI Preview', 'diviforge'), __('AI Preview', 'diviforge'), $cap, 'diviforge-ai-preview', array($this, 'ai_preview'));
        add_submenu_page('diviforge', __('AI Jobs', 'diviforge'), __('AI Jobs', 'diviforge'), $cap, 'diviforge-ai-jobs', array($this, 'ai_jobs'));
        add_submenu_page('diviforge', __('Components', 'diviforge'), __('Components', 'diviforge'), $cap, 'diviforge-components', array($this, 'components'));
        add_submenu_page('diviforge', __('Templates', 'diviforge'), __('Templates', 'diviforge'), $cap, 'diviforge-templates', array($this, 'templates'));
        add_submenu_page('diviforge', __('Prompt Library', 'diviforge'), __('Prompt Library', 'diviforge'), $cap, 'diviforge-prompts', array($this, 'prompt_library'));
        add_submenu_page('diviforge', __('Setup Wizard', 'diviforge'), __('Setup Wizard', 'diviforge'), $cap, 'diviforge-wizard', array($this, 'wizard'));
        add_submenu_page('diviforge', __('Academy', 'diviforge'), __('Academy', 'diviforge'), $cap, 'diviforge-academy', array($this, 'academy'));
        add_submenu_page('diviforge', __('Settings', 'diviforge'), __('Settings', 'diviforge'), $cap, 'diviforge-settings', array($this, 'settings'));
        add_submenu_page('diviforge', __('Versie overzicht', 'diviforge'), __('Versie overzicht', 'diviforge'), $cap, 'diviforge-versions', array($this, 'versions'));
    }

    public function assets($hook) {
        if (strpos($hook, 'diviforge') === false) { return; }
        wp_enqueue_style('diviforge-admin', DIVIFORGE_URL . 'assets/css/admin.css', array(), DIVIFORGE_VERSION);
        wp_enqueue_script('diviforge-admin', DIVIFORGE_URL . 'assets/js/admin.js', array('jquery'), DIVIFORGE_VERSION, true);
        wp_localize_script('diviforge-admin', 'DiviForgeAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('diviforge_admin'),
            'copied' => __('Prompt copied to clipboard.', 'diviforge'),
        ));
    }

    public function maybe_redirect_first_run() {
        if (!current_user_can('manage_options') || !get_option('diviforge_first_run_pending')) { return; }
        if (wp_doing_ajax() || (defined('DOING_CRON') && DOING_CRON)) { return; }
        $screen = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        delete_option('diviforge_first_run_pending');
        if ($screen !== 'diviforge-wizard') {
            wp_safe_redirect(admin_url('admin.php?page=diviforge-wizard'));
            exit;
        }
    }

    private function header($title, $subtitle = '', $active = 'dashboard') {
        echo '<div class="df-wrap df-studio-wrap">';
        echo '<div class="df-studio-hero"><div><p class="df-kicker">' . esc_html__('AI Design Studio for Divi', 'diviforge') . '</p><h1>' . esc_html($title) . '</h1>';
        if ($subtitle) { echo '<p>' . esc_html($subtitle) . '</p>'; }
        echo '</div><div class="df-version">v' . esc_html(DIVIFORGE_VERSION) . '</div></div>';
        $this->studio_nav($active);
    }

    private function studio_nav($active = 'dashboard') {
        $items = array(
            'dashboard' => array(__('Dashboard', 'diviforge'), 'diviforge', 'dashicons-dashboard'),
            'pages' => array(__('Pages', 'diviforge'), 'diviforge-pages', 'dashicons-media-document'),
            'packages' => array(__('Packages', 'diviforge'), 'diviforge-packages', 'dashicons-archive'),
            'history' => array(__('History', 'diviforge'), 'diviforge-history', 'dashicons-backup'),
            'ai-studio' => array(__('AI Studio', 'diviforge'), 'diviforge-ai-studio', 'dashicons-format-chat'),
            'ai-chat' => array(__('AI Chat', 'diviforge'), 'diviforge-ai-chat', 'dashicons-format-status'),
            'ai-preview' => array(__('AI Preview', 'diviforge'), 'diviforge-ai-preview', 'dashicons-visibility'),
            'ai-jobs' => array(__('AI Jobs', 'diviforge'), 'diviforge-ai-jobs', 'dashicons-update'),
            'components' => array(__('Components', 'diviforge'), 'diviforge-components', 'dashicons-screenoptions'),
            'templates' => array(__('Templates', 'diviforge'), 'diviforge-templates', 'dashicons-layout'),
            'inspector' => array(__('Inspector', 'diviforge'), 'diviforge-inspector', 'dashicons-search'),
            'settings' => array(__('Settings', 'diviforge'), 'diviforge-settings', 'dashicons-admin-generic'),
            'versions' => array(__('Versie overzicht', 'diviforge'), 'diviforge-versions', 'dashicons-backup'),
        );
        echo '<nav class="df-studio-nav">';
        foreach ($items as $key => $item) {
            $classes = array();
            if ($key === $active) { $classes[] = 'is-active'; }
            if ($key === 'versions') { $classes[] = 'df-nav-version-overview'; }
            $class = implode(' ', $classes);
            echo '<a class="' . esc_attr($class) . '" href="' . esc_url(admin_url('admin.php?page=' . $item[1])) . '"><span class="dashicons ' . esc_attr($item[2]) . '"></span>' . esc_html($item[0]) . '</a>';
        }
        echo '</nav>';
    }

    private function footer() { echo '</div>'; }

    private function get_all_pages() {
        return get_pages(array(
            'sort_column' => 'post_modified',
            'sort_order' => 'DESC',
            'post_status' => array('publish', 'draft', 'pending', 'private', 'future'),
            'number' => 999,
        ));
    }

    private function get_diviforge_pages() {
        $all = $this->get_all_pages();
        $out = array();
        foreach ($all as $page) {
            if (get_post_meta($page->ID, '_diviforge_package_imported_at', true) || get_post_meta($page->ID, '_diviforge_version', true)) {
                $out[] = $page;
            }
        }
        return $out;
    }

    public function dashboard() {
        $pages = $this->get_all_pages();
        $df_pages = $this->get_diviforge_pages();
        $recent = array_slice($df_pages, 0, 4);
        $today = 0;
        foreach ($df_pages as $page) {
            $date = get_post_meta($page->ID, '_diviforge_package_imported_at', true);
            if ($date && date('Y-m-d', strtotime($date)) === current_time('Y-m-d')) { $today++; }
        }
        $this->header(__('Welcome back to DiviForge', 'diviforge'), __('A cleaner studio dashboard for creating, updating and managing AI-generated Divi pages.', 'diviforge'), 'dashboard');

        echo '<section class="df-stat-grid">';
        $stats = array(
            array('dashicons-media-document', count($pages), __('WordPress pages', 'diviforge'), __('All pages available for package updates.', 'diviforge')),
            array('dashicons-hammer', count($df_pages), __('DiviForge pages', 'diviforge'), __('Pages with imported package metadata.', 'diviforge')),
            array('dashicons-upload', $today, __('Imports today', 'diviforge'), __('New or updated pages today.', 'diviforge')),
            array('dashicons-star-filled', '3.0', __('Roadmap', 'diviforge'), __('AI Provider Layer and AI Studio.', 'diviforge')),
        );
        foreach ($stats as $s) {
            echo '<article class="df-stat-card"><span class="dashicons ' . esc_attr($s[0]) . '"></span><strong>' . esc_html($s[1]) . '</strong><h2>' . esc_html($s[2]) . '</h2><p>' . esc_html($s[3]) . '</p></article>';
        }
        echo '</section>';

        echo '<section class="df-dashboard-grid">';
        echo '<div class="df-card df-quick-create"><p class="df-kicker">' . esc_html__('Quick action', 'diviforge') . '</p><h2>' . esc_html__('Create a new page from a package', 'diviforge') . '</h2><p>' . esc_html__('Give the page a name, upload a package and immediately open the result for review.', 'diviforge') . '</p>';
        $this->render_create_form('dashboard');
        echo '</div>';
        echo '<div class="df-card"><p class="df-kicker">' . esc_html__('Recent imports', 'diviforge') . '</p><h2>' . esc_html__('Latest DiviForge pages', 'diviforge') . '</h2>';
        if ($recent) {
            echo '<div class="df-recent-list">';
            foreach ($recent as $page) { $this->render_recent_item($page); }
            echo '</div>';
        } else {
            echo '<div class="df-empty-state"><span class="dashicons dashicons-archive"></span><p>' . esc_html__('No DiviForge imports yet. Create your first page from a package.', 'diviforge') . '</p></div>';
        }
        echo '<a class="df-btn df-btn-soft" href="' . esc_url(admin_url('admin.php?page=diviforge-pages')) . '"><span class="dashicons dashicons-arrow-right-alt2"></span>' . esc_html__('Open Pages', 'diviforge') . '</a></div>';
        echo '</section>';

        echo '<section class="df-roadmap-strip"><div><p class="df-kicker">' . esc_html__('Sprint 4', 'diviforge') . '</p><h2>' . esc_html__('UX & Workflow', 'diviforge') . '</h2><p>' . esc_html__('This release starts the transition from import page to a full AI Design Studio: dashboard, pages as cards, package overview and workflow polish.', 'diviforge') . '</p></div><a class="df-btn df-btn-primary" href="' . esc_url(admin_url('admin.php?page=diviforge-packages')) . '"><span class="dashicons dashicons-archive"></span>' . esc_html__('View Packages', 'diviforge') . '</a></section>';
        $this->footer();
    }

    private function render_create_form($context = 'pages') {
        echo '<form class="df-create-form df-upload-zone" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
        wp_nonce_field('diviforge_import_package');
        echo '<input type="hidden" name="action" value="diviforge_import_package">';
        echo '<input type="hidden" name="import_mode" value="new">';
        echo '<label><span>' . esc_html__('Page name', 'diviforge') . '</span><input type="text" name="page_title" placeholder="' . esc_attr__('Example: Products - DiviForge Premium', 'diviforge') . '" required></label>';
        echo '<label class="df-file-label"><span>' . esc_html__('Package ZIP / DFG', 'diviforge') . '</span><input type="file" name="package" accept=".zip,.dfg" required><em>' . esc_html__('Drop your package here or choose a file.', 'diviforge') . '</em></label>';
        echo '<button class="df-btn df-btn-primary"><span class="dashicons dashicons-plus-alt2"></span>' . esc_html__('Create new page', 'diviforge') . '</button>';
        echo '</form>';
    }

    public function pages() {
        $this->header(__('Pages', 'diviforge'), __('Browse pages as premium cards with thumbnails, package stats, search, filters and faster actions.', 'diviforge'), 'pages');
        $this->render_import_notices();
        echo '<div class="df-card df-page-create-panel"><div><p class="df-kicker">' . esc_html__('New page', 'diviforge') . '</p><h2>' . esc_html__('Create a new page from package', 'diviforge') . '</h2><p>' . esc_html__('Upload a DiviForge package and choose the page name before the page is created.', 'diviforge') . '</p></div>';
        $this->render_create_form('pages');
        echo '</div>';

        echo '<div class="df-toolbar df-toolbar-premium"><div class="df-search-wrap"><span class="dashicons dashicons-search"></span><input type="search" data-df-page-search placeholder="' . esc_attr__('Search pages, slugs or packages...', 'diviforge') . '"></div><div class="df-filter-pills"><button class="is-active" data-df-filter="all">' . esc_html__('All', 'diviforge') . '</button><button data-df-filter="publish">' . esc_html__('Published', 'diviforge') . '</button><button data-df-filter="draft">' . esc_html__('Drafts', 'diviforge') . '</button><button data-df-filter="diviforge">' . esc_html__('DiviForge', 'diviforge') . '</button><button data-df-filter="with-thumb">' . esc_html__('With thumbnail', 'diviforge') . '</button></div><div class="df-view-toggle"><button class="is-active" data-df-view="grid"><span class="dashicons dashicons-grid-view"></span></button><button data-df-view="compact"><span class="dashicons dashicons-list-view"></span></button></div></div>';

        echo '<p class="df-result-counter"><span data-df-result-count>0</span> ' . esc_html__('pages shown', 'diviforge') . '</p>';
        echo '<div class="df-page-card-grid">';
        foreach ($this->get_all_pages() as $page) {
            if (!current_user_can('edit_post', $page->ID)) { continue; }
            $this->render_page_card($page);
        }
        echo '</div>';
        $this->footer();
    }

    private function render_import_notices() {
        if (!empty($_GET['imported'])) {
            $page_id = !empty($_GET['page_id']) ? absint($_GET['page_id']) : 0;
            $mode = !empty($_GET['mode']) ? sanitize_key($_GET['mode']) : 'new';
            $message = $mode === 'update' ? __('Package uploaded and applied to the selected page.', 'diviforge') : __('Package imported. A new draft Divi page has been created.', 'diviforge');
            echo '<div class="notice notice-success inline"><p>' . esc_html($message) . '</p></div>';
            if ($page_id) {
                echo '<div class="df-card df-import-result"><div><p class="df-kicker">' . esc_html__('Import complete', 'diviforge') . '</p><h2>' . esc_html(get_the_title($page_id)) . '</h2><p>' . esc_html__('The layout and page.css have been applied.', 'diviforge') . '</p></div><div class="df-import-actions">';
                echo '<a class="df-btn df-btn-primary" target="_blank" href="' . esc_url($this->get_page_preview_url($page_id)) . '"><span class="dashicons dashicons-visibility"></span>' . esc_html__('View result', 'diviforge') . '</a>';
                echo '<a class="df-btn df-btn-soft" href="' . esc_url(admin_url('post.php?post=' . $page_id . '&action=edit&et_fb=1')) . '"><span class="dashicons dashicons-admin-customizer"></span>' . esc_html__('Open in Divi', 'diviforge') . '</a>';
                echo '</div></div>';
            }
        }
        if (!empty($_GET['css_reapplied'])) { echo '<div class="notice notice-success inline"><p>' . esc_html__('Stored CSS has been re-applied to Divi Page Custom CSS.', 'diviforge') . '</p></div>'; }
        if (!empty($_GET['page_deleted'])) { echo '<div class="notice notice-success inline"><p>' . esc_html__('The page has been moved to the trash.', 'diviforge') . '</p></div>'; }
    }

    /**
     * Returns the best available visual snapshot for a page card.
     * Priority:
     * 1. Featured image, because this is user-controlled and instant.
     * 2. WordPress.com mShots for published pages, so every live page can show a real page snapshot.
     * 3. Styled fallback for drafts/private pages that cannot be reached by an external screenshot service.
     */
    private function get_page_card_snapshot($page) {
        $featured = get_the_post_thumbnail_url($page->ID, 'medium_large');
        if ($featured) {
            return array(
                'url' => $featured,
                'source' => 'featured',
                'label' => __('Featured snapshot', 'diviforge'),
            );
        }

        if ($page->post_status === 'publish') {
            $permalink = get_permalink($page->ID);
            if ($permalink) {
                return array(
                    'url' => 'https://s.wordpress.com/mshots/v1/' . rawurlencode($permalink) . '?w=900',
                    'source' => 'mshot',
                    'label' => __('Live page snapshot', 'diviforge'),
                );
            }
        }

        return array(
            'url' => '',
            'source' => 'fallback',
            'label' => __('Draft preview placeholder', 'diviforge'),
        );
    }

    private function render_page_card($page) {
        $pkg_title = get_post_meta($page->ID, '_diviforge_package_title', true);
        $pkg_version = get_post_meta($page->ID, '_diviforge_package_version', true);
        $pkg_file = get_post_meta($page->ID, '_diviforge_package_filename', true);
        $imported_at = get_post_meta($page->ID, '_diviforge_package_imported_at', true);
        $css_status = get_post_meta($page->ID, '_diviforge_css_status', true);
        $is_df = $imported_at || get_post_meta($page->ID, '_diviforge_version', true);
        $snapshot = $this->get_page_card_snapshot($page);
        $thumb = $snapshot['url'];
        $style = $thumb ? ' style="background-image:url(' . esc_url($thumb) . ')"' : '';
        $stats = json_decode((string) get_post_meta($page->ID, '_diviforge_package_stats', true), true);
        $stats = is_array($stats) ? $stats : array();
        $modules = isset($stats['modules']) ? absint($stats['modules']) : 0;
        $sections = isset($stats['sections']) ? absint($stats['sections']) : 0;
        $images = isset($stats['images']) ? absint($stats['images']) : 0;
        $css_lines = isset($stats['css_lines']) ? absint($stats['css_lines']) : 0;
        $has_thumb = $thumb ? '1' : '0';
        $snapshot_label = !empty($snapshot['label']) ? $snapshot['label'] : __('Page snapshot', 'diviforge');
        $snapshot_source = !empty($snapshot['source']) ? $snapshot['source'] : 'placeholder';
        echo '<article class="df-page-card" data-df-page-card data-title="' . esc_attr(strtolower(get_the_title($page->ID) . ' ' . get_page_uri($page->ID) . ' ' . $pkg_title . ' ' . $pkg_file)) . '" data-status="' . esc_attr($page->post_status) . '" data-diviforge="' . esc_attr($is_df ? '1' : '0') . '" data-thumb="' . esc_attr($has_thumb) . '">';
        echo '<div class="df-page-thumb df-page-thumb-' . esc_attr($snapshot_source) . '"' . $style . '><span>' . esc_html($this->initials(get_the_title($page->ID))) . '</span><em class="df-snapshot-label">' . esc_html($snapshot_label) . '</em><b class="df-status-badge df-status-' . esc_attr($page->post_status) . '">' . esc_html($page->post_status) . '</b>';
        if ($is_df) { echo '<i class="df-forge-badge"><span class="dashicons dashicons-hammer"></span>DiviForge</i>'; }
        echo '</div>';
        echo '<div class="df-page-card-body"><div class="df-page-card-head"><h2>' . esc_html(get_the_title($page->ID) ?: __('Untitled page', 'diviforge')) . '</h2><small>ID ' . esc_html($page->ID) . ' · ' . esc_html(get_page_uri($page->ID)) . '</small></div>';
        echo '<div class="df-package-mini"><span class="dashicons dashicons-archive"></span><div><strong>' . esc_html($pkg_title ? $pkg_title : __('No package imported yet', 'diviforge')) . '</strong><small>' . esc_html($pkg_version ? 'v' . $pkg_version : ($pkg_file ? $pkg_file : __('Ready for package upload', 'diviforge'))) . '</small></div></div>';
        echo '<div class="df-package-stats"><span><b>' . esc_html($sections) . '</b>' . esc_html__('sections', 'diviforge') . '</span><span><b>' . esc_html($modules) . '</b>' . esc_html__('modules', 'diviforge') . '</span><span><b>' . esc_html($images) . '</b>' . esc_html__('images', 'diviforge') . '</span><span><b>' . esc_html($css_lines) . '</b>' . esc_html__('css lines', 'diviforge') . '</span></div>';
        echo '<div class="df-page-meta"><span><b>' . esc_html__('Updated', 'diviforge') . '</b>' . esc_html(get_the_modified_date('j M Y', $page)) . '</span><span><b>' . esc_html__('Imported', 'diviforge') . '</b>' . esc_html($imported_at ? $imported_at : __('Never', 'diviforge')) . '</span></div>';
        echo '<form class="df-card-upload" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
        wp_nonce_field('diviforge_import_package');
        echo '<input type="hidden" name="action" value="diviforge_import_package"><input type="hidden" name="import_mode" value="update"><input type="hidden" name="target_page_id" value="' . esc_attr($page->ID) . '"><input type="file" name="package" accept=".zip,.dfg" required><button class="df-btn df-btn-soft"><span class="dashicons dashicons-upload"></span>' . esc_html__('Upload package', 'diviforge') . '</button></form>';
        echo '<details class="df-ai-export"><summary><span class="dashicons dashicons-download"></span>' . esc_html__('Export for ChatGPT', 'diviforge') . '</summary>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('diviforge_export_page_package_' . $page->ID);
        echo '<input type="hidden" name="action" value="diviforge_export_page_package"><input type="hidden" name="page_id" value="' . esc_attr($page->ID) . '">';
        echo '<label><span>' . esc_html__('Vraag aan ChatGPT', 'diviforge') . '</span><textarea name="chatgpt_request" rows="3" placeholder="' . esc_attr__('Bijvoorbeeld: Maak deze pagina premiumer, verbeter de hero en lever een nieuw DiviForge package terug.', 'diviforge') . '"></textarea></label>';
        echo '<button class="df-btn df-btn-primary" type="submit"><span class="dashicons dashicons-download"></span>' . esc_html__('Export package', 'diviforge') . '</button>';
        echo '</form></details>';
        echo '<div class="df-card-actions"><a class="df-btn df-btn-primary" target="_blank" href="' . esc_url($this->get_page_preview_url($page->ID)) . '"><span class="dashicons dashicons-visibility"></span>' . esc_html__('Preview', 'diviforge') . '</a><a class="df-btn df-btn-soft" href="' . esc_url(admin_url('post.php?post=' . $page->ID . '&action=edit&et_fb=1')) . '"><span class="dashicons dashicons-admin-customizer"></span>' . esc_html__('Divi', 'diviforge') . '</a><a class="df-btn df-btn-soft" href="' . esc_url(get_edit_post_link($page->ID, '')) . '"><span class="dashicons dashicons-edit"></span>' . esc_html__('Edit', 'diviforge') . '</a>';
        if ($css_status === 'applied') { echo '<a class="df-btn df-btn-soft" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=diviforge_reapply_css&page_id=' . $page->ID), 'diviforge_reapply_css_' . $page->ID)) . '"><span class="dashicons dashicons-update"></span>' . esc_html__('CSS', 'diviforge') . '</a>'; }
        echo '<a class="df-btn df-btn-soft" href="' . esc_url(admin_url('admin.php?page=diviforge-history&history_page_id=' . $page->ID)) . '"><span class="dashicons dashicons-backup"></span>' . esc_html__('History', 'diviforge') . '</a>';
        echo '<a class="df-btn df-btn-danger" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=diviforge_delete_page&page_id=' . $page->ID), 'diviforge_delete_page_' . $page->ID)) . '" onclick="return confirm(\'' . esc_js(__('Move this page to the trash?', 'diviforge')) . '\');"><span class="dashicons dashicons-trash"></span>' . esc_html__('Delete', 'diviforge') . '</a></div>';
        echo '</div></article>';
    }

    private function render_recent_item($page) {
        echo '<a class="df-recent-item" href="' . esc_url(admin_url('admin.php?page=diviforge-pages')) . '"><span class="dashicons dashicons-media-document"></span><div><strong>' . esc_html(get_the_title($page->ID)) . '</strong><small>' . esc_html(get_post_meta($page->ID, '_diviforge_package_imported_at', true)) . '</small></div></a>';
    }

    private function initials($title) {
        $title = trim(wp_strip_all_tags($title));
        if (!$title) { return 'DF'; }
        $parts = preg_split('/\s+/', $title);
        $a = mb_substr($parts[0], 0, 1);
        $b = count($parts) > 1 ? mb_substr($parts[1], 0, 1) : mb_substr($parts[0], 1, 1);
        return strtoupper($a . $b);
    }


    public function ai_studio() {
        $settings = $this->get_ai_settings();
        $history = $this->get_ai_history();
        $has_key = !empty($settings['api_key']);
        $this->header(__('AI Studio', 'diviforge'), __('Roadmap 3.0 continues here: provider-independent AI workflows, prompt building and AI-ready Divi context.', 'diviforge'), 'ai-studio');
        if (!empty($_GET['ai_notice'])) {
            $notice = sanitize_key($_GET['ai_notice']);
            $msg = $notice === 'saved' ? __('AI settings saved.', 'diviforge') : ($notice === 'exported' ? __('AI package exported and request logged.', 'diviforge') : ($notice === 'ai_success' ? __('AI request completed. Review the response below before importing anything.', 'diviforge') : ($notice === 'ai_error' ? __('AI request failed. Check provider settings and request history.', 'diviforge') : ($notice === 'imported' ? __('The AI result was approved and applied to the page.', 'diviforge') : __('AI action completed.', 'diviforge')))));
            echo '<div class="notice notice-success inline"><p>' . esc_html($msg) . '</p></div>';
        }
        $pages = $this->get_all_pages();
        echo '<section class="df-ai-studio-hero df-card"><div><p class="df-kicker">' . esc_html__('AI Engine v3.4.0', 'diviforge') . '</p><h2>' . esc_html__('From package roundtrip to direct AI workflow', 'diviforge') . '</h2><p>' . esc_html__('This release makes the existing AI buttons functional: jobs are created first, provider calls run synchronously, results are parsed, validated and opened in AI Preview.', 'diviforge') . '</p></div><div class="df-ai-loop"><span>Context</span><i>→</i><span>Prompt</span><i>→</i><span>AI</span><i>→</i><span>Preview</span></div></section>';
        echo '<div class="df-ai-foundation-grid">';
        echo '<form class="df-card df-ai-studio-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('diviforge_ai_studio_export');
        echo '<input type="hidden" name="action" value="diviforge_ai_studio_export">';
        echo '<p class="df-kicker">' . esc_html__('Prompt Builder', 'diviforge') . '</p><h2>' . esc_html__('Build an AI request package', 'diviforge') . '</h2>';
        echo '<label class="df-field"><span>' . esc_html__('Pagina', 'diviforge') . '</span><select name="page_id" required><option value="">' . esc_html__('Kies een pagina...', 'diviforge') . '</option>';
        foreach ($pages as $page) {
            if (!current_user_can('edit_post', $page->ID)) { continue; }
            echo '<option value="' . esc_attr($page->ID) . '">' . esc_html(get_the_title($page->ID) . ' — ' . $page->post_status) . '</option>';
        }
        echo '</select></label>';
        echo '<p class="df-kicker df-kicker-spaced">' . esc_html__('Template', 'diviforge') . '</p><p class="df-ai-presets-hint">' . esc_html__('Kies een of meer templates. Ze worden samen met je eigen opdracht naar de AI gestuurd.', 'diviforge') . '</p><div class="df-ai-presets">';
        $presets = $this->ai_studio_presets();
        $first = true;
        foreach ($presets as $key => $preset) {
            echo '<label class="df-ai-preset-card"><input type="checkbox" name="ai_presets[]" value="' . esc_attr($key) . '" ' . checked($first, true, false) . '>';
            echo '<span class="df-ai-preset-art" style="background:' . esc_attr($preset['art']) . '"><span class="dashicons ' . esc_attr($preset['icon']) . '"></span></span>';
            echo '<span class="df-ai-preset-body"><strong>' . esc_html($preset['title']) . '</strong><small>' . esc_html($preset['description']) . '</small></span>';
            echo '<span class="df-ai-preset-check"><span class="dashicons dashicons-yes"></span></span>';
            echo '</label>';
            $first = false;
        }
        echo '</div>';
        echo '<label class="df-field df-kicker-spaced"><span>' . esc_html__('Vraag aan AI', 'diviforge') . '</span><textarea name="chatgpt_request" rows="7" placeholder="' . esc_attr__('Bijvoorbeeld: Verbeter de hero-slider, gebruik meer premium spacing en behoud de bestaande Divi-structuur.', 'diviforge') . '"></textarea></label>';
        echo '<div class="df-context-box"><strong>' . esc_html__('Context die wordt meegestuurd', 'diviforge') . '</strong><label><input type="checkbox" checked disabled> Builder Tree</label><label><input type="checkbox" checked disabled> Page CSS + CSS analyse</label><label><input type="checkbox" checked disabled> Assets + media manifest</label><label><input type="checkbox" checked disabled> Design tokens</label><label><input type="checkbox" checked disabled> Page metadata + history context</label></div>';
        echo '<div class="df-ai-action-row"><button class="df-btn df-btn-soft df-ai-submit" type="submit"><span class="dashicons dashicons-download"></span>' . esc_html__('Download AI package', 'diviforge') . '</button><button class="df-btn df-btn-primary df-ai-submit" type="submit" formaction="' . esc_url(admin_url('admin-post.php')) . '" name="action" value="diviforge_ai_improve"><span class="dashicons dashicons-superhero"></span>' . esc_html__('Generate with AI', 'diviforge') . '</button></div>';
        echo '</form>';
        echo '<aside class="df-card df-ai-studio-side"><p class="df-kicker">' . esc_html__('AI provider status', 'diviforge') . '</p><h2>' . esc_html($has_key ? __('Provider configured', 'diviforge') : __('Provider not configured yet', 'diviforge')) . '</h2><p>' . esc_html($has_key ? sprintf(__('Provider: %s · Model: %s. Direct generation with preview is active. Every request creates a reviewable AI job.', 'diviforge'), $this->get_ai_provider_label($settings['provider']), $settings['model']) : __('Configure an AI provider in Settings to prepare direct AI workflows.', 'diviforge')) . '</p><div class="df-ai-next"><h3>' . esc_html__('AI Package Validator', 'diviforge') . '</h3><ul class="df-checks"><li>Builder Tree required</li><li>layout.json required</li><li>page.css exported when available</li><li>AI_REQUEST.json generated</li><li>Package history logged</li></ul><div class="df-quality-score"><span>' . esc_html__('Foundation readiness', 'diviforge') . '</span><strong>82%</strong></div><a class="df-btn df-btn-soft" href="' . esc_url(admin_url('admin.php?page=diviforge-settings')) . '"><span class="dashicons dashicons-admin-generic"></span>' . esc_html__('Open AI settings', 'diviforge') . '</a></div></aside>';
        echo '</div>';
        $this->render_latest_ai_result();
        echo '<section class="df-card df-ai-history-card"><div class="df-section-head"><div><p class="df-kicker">' . esc_html__('AI Request History', 'diviforge') . '</p><h2>' . esc_html__('Recent AI workflow requests', 'diviforge') . '</h2><p>' . esc_html__('DiviForge now keeps a local log of AI exports, API tests and future direct AI requests.', 'diviforge') . '</p></div></div>';
        if (!$history) {
            echo '<div class="df-empty-state"><span class="dashicons dashicons-format-chat"></span><h2>' . esc_html__('No AI requests yet', 'diviforge') . '</h2><p>' . esc_html__('Export a page from AI Studio to start building the request history.', 'diviforge') . '</p></div>';
        } else {
            echo '<div class="df-ai-history-list">';
            foreach (array_slice($history, 0, 10) as $entry) {
                echo '<article><span class="dashicons dashicons-format-chat"></span><div><strong>' . esc_html($entry['title']) . '</strong><p>' . esc_html($entry['summary']) . '</p><small>' . esc_html($entry['created_at'] . ' · ' . $entry['status'] . ' · ' . $entry['model']) . '</small></div></article>';
            }
            echo '</div>';
        }
        echo '</section>';
        $this->footer();
    }

    private function ai_studio_presets() {
        return array(
            'premium_redesign' => array(
                'title' => __('Premium redesign', 'diviforge'),
                'description' => __('Meer wow, sterkere hero, betere visuele hiërarchie.', 'diviforge'),
                'prompt' => __('Maak een premium redesign van deze pagina met meer wow-effect, betere hero, sterkere visuele hiërarchie, betere spacing en elegantere CTA’s. Behoud het DiviForge package-format.', 'diviforge'),
                'icon' => 'dashicons-star-filled',
                'art' => 'linear-gradient(135deg,#4f46e5,#7c3aed)',
            ),
            'conversion' => array(
                'title' => __('Meer conversie', 'diviforge'),
                'description' => __('Sterkere CTA’s, duidelijker verhaal en meer vertrouwen.', 'diviforge'),
                'prompt' => __('Verbeter deze pagina voor conversie. Maak propositie, CTA’s, trust signals, sectievolgorde en microcopy sterker. Lever een nieuw DiviForge package terug.', 'diviforge'),
                'icon' => 'dashicons-chart-line',
                'art' => 'linear-gradient(135deg,#059669,#10b981)',
            ),
            'responsive' => array(
                'title' => __('Responsive polish', 'diviforge'),
                'description' => __('Focus op mobiel, tablet en spacing.', 'diviforge'),
                'prompt' => __('Verbeter de responsive ervaring van deze pagina. Optimaliseer mobiele spacing, typografie, knoppen, kaart-layouts en leesbaarheid. Lever layout.json en page.css terug.', 'diviforge'),
                'icon' => 'dashicons-smartphone',
                'art' => 'linear-gradient(135deg,#0284c7,#38bdf8)',
            ),
            'seo' => array(
                'title' => __('SEO & structuur', 'diviforge'),
                'description' => __('Betere koppen, secties en semantische content.', 'diviforge'),
                'prompt' => __('Verbeter de SEO-structuur en inhoudelijke opbouw van deze pagina. Maak headings logischer, teksten duidelijker en secties beter scanbaar. Houd de Divi-layout importeerbaar.', 'diviforge'),
                'icon' => 'dashicons-search',
                'art' => 'linear-gradient(135deg,#c2410c,#f97316)',
            ),
            'custom' => array(
                'title' => __('Eigen opdracht', 'diviforge'),
                'description' => __('Gebruik vooral mijn tekstvak als opdracht.', 'diviforge'),
                'prompt' => __('Voer de opdracht uit die in het tekstvak staat en lever een nieuw DiviForge package terug.', 'diviforge'),
                'icon' => 'dashicons-edit',
                'art' => 'linear-gradient(135deg,#334155,#64748b)',
            ),
        );
    }

    private function combine_selected_preset_prompts($presets, $selected_keys) {
        $selected_keys = array_values(array_filter(array_map('sanitize_key', (array) $selected_keys)));
        $selected_keys = array_intersect($selected_keys, array_keys($presets));
        if (!$selected_keys) {
            $selected_keys = array('custom');
        }
        $prompts = array();
        foreach ($selected_keys as $key) {
            $prompts[] = $presets[$key]['prompt'];
        }
        return implode("\n\n", $prompts);
    }


    private function get_latest_ai_result() {
        $result = get_transient('diviforge_latest_ai_result_' . get_current_user_id());
        return is_array($result) ? $result : array();
    }

    private function render_latest_ai_result() {
        $result = $this->get_latest_ai_result();
        if (empty($result)) { return; }
        $ok = !empty($result['ok']);
        echo '<section class="df-card df-ai-result-card ' . esc_attr($ok ? 'is-success' : 'is-error') . '">';
        echo '<div class="df-section-head"><div><p class="df-kicker">' . esc_html__('AI Improve result', 'diviforge') . '</p><h2>' . esc_html($ok ? __('Generated AI response ready for review', 'diviforge') : __('AI request did not complete', 'diviforge')) . '</h2><p>' . esc_html(!empty($result['message']) ? $result['message'] : '') . '</p></div></div>';
        if ($ok && !empty($result['content'])) {
            echo '<p class="df-ai-result-note">' . esc_html__('v3.4 stores every AI response as a job and opens it in AI Preview. Import only after validation.', 'diviforge') . '</p>';
            echo '<textarea class="df-ai-response-box" rows="14" readonly>' . esc_textarea($result['content']) . '</textarea>';
        } elseif (!empty($result['debug'])) {
            echo '<pre class="df-ai-error-box">' . esc_html($result['debug']) . '</pre>';
        }
        echo '</section>';
    }

    private function build_ai_improve_prompt($package, $request) {
        $manifest = $package['manifest'];
        $stats = !empty($package['statistics']) ? $package['statistics'] : array();
        $summary = array(
            'page_title' => $manifest['title'] ?? '',
            'source_url' => $manifest['source_page']['url'] ?? '',
            'sections' => $stats['sections'] ?? 0,
            'rows' => $stats['rows'] ?? 0,
            'columns' => $stats['columns'] ?? 0,
            'modules' => $stats['modules'] ?? 0,
            'images' => $stats['images'] ?? 0,
            'css_rules' => $package['css_analysis']['rule_count'] ?? 0,
        );
        $context = array(
            'manifest' => $package['manifest'],
            'statistics' => $package['statistics'],
            'semantic_structure' => $package['semantic_structure'],
            'design' => $package['design'],
            'class_map' => $package['class_map'],
            'builder_tree' => $package['builder_tree'],
            'layout' => $package['layout'],
            'page_css' => $package['css'],
        );
        $json = wp_json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (strlen($json) > 85000) {
            $json = substr($json, 0, 85000) . "\n\n[Context truncated by DiviForge v3.4 to keep the API request manageable. Use builder_tree, layout and page_css as leading sources.]";
        }
        return "You are DiviForge AI Studio. You receive a WordPress/Divi page package context.\n\n" .
            "Goal from the user:\n" . $request . "\n\n" .
            "Return ONLY a valid DiviForge import package as JSON with these top-level keys:\n" .
            "manifest, layout, page_css, change_summary, validation_notes.\n\n" .
            "Important rules:\n" .
            "- Use Divi-compatible sections, rows, columns and modules.\n" .
            "- Preserve useful CSS classes where possible.\n" .
            "- Put all page CSS in page_css.\n" .
            "- Do not use Elementor, Gutenberg blocks, raw full-page HTML or one-codeblock pages.\n" .
            "- Keep the result importable by DiviForge.\n" .
            "- Mention important changes in change_summary.\n\n" .
            "Page summary:\n" . wp_json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n" .
            "Full package context:\n" . $json;
    }

    private function call_ai_provider($prompt, $settings) {
        $provider = $settings['provider'] ?? 'openai';
        $timeout = max(30, absint($settings['timeout'] ?? 180));
        $max_tokens = max(1000, absint($settings['max_tokens'] ?? 50000));
        $temperature = floatval(str_replace(',', '.', $settings['temperature'] ?? '0.2'));
        if ($provider === 'openai') {
            if (empty($settings['api_key'])) { return new WP_Error('missing_key', __('Missing OpenAI API key.', 'diviforge')); }
            $response = wp_remote_post('https://api.openai.com/v1/responses', array(
                'timeout' => $timeout,
                'headers' => array('Authorization' => 'Bearer ' . $settings['api_key'], 'Content-Type' => 'application/json'),
                'body' => wp_json_encode(array(
                    'model' => $settings['model'],
                    'input' => $prompt,
                    'temperature' => $temperature,
                    'max_output_tokens' => $max_tokens,
                )),
            ));
            if (is_wp_error($response)) { return $response; }
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            if ($code < 200 || $code >= 300) { return new WP_Error('api_error', sprintf(__('OpenAI returned HTTP %d.', 'diviforge'), $code), $body); }
            $data = json_decode($body, true);
            $text = '';
            if (isset($data['output_text'])) { $text = (string) $data['output_text']; }
            if (!$text && !empty($data['output']) && is_array($data['output'])) {
                foreach ($data['output'] as $out) {
                    if (!empty($out['content']) && is_array($out['content'])) {
                        foreach ($out['content'] as $content) {
                            if (isset($content['text'])) { $text .= $content['text']; }
                        }
                    }
                }
            }
            return $text ? $text : $body;
        }
        return new WP_Error('provider_not_live', sprintf(__('%s is configured, but direct generation is currently implemented for OpenAI first. Use Download AI package for other providers until their live connectors are enabled.', 'diviforge'), $this->get_ai_provider_label($provider)));
    }

    public function handle_ai_improve() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to run AI Improve.', 'diviforge')); }
        check_admin_referer('diviforge_ai_studio_export');
        $page_id = !empty($_POST['page_id']) ? absint($_POST['page_id']) : 0;
        if (!$page_id || !current_user_can('edit_post', $page_id)) { wp_die(esc_html__('You are not allowed to use this page.', 'diviforge')); }
        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') { wp_die(esc_html__('Page not found.', 'diviforge')); }

        $presets = $this->ai_studio_presets();
        $preset_prompt = $this->combine_selected_preset_prompts($presets, $_POST['ai_presets'] ?? array());
        $request = !empty($_POST['chatgpt_request']) ? sanitize_textarea_field(wp_unslash($_POST['chatgpt_request'])) : '';
        $full_request = trim($preset_prompt . "\n\nAanvullende opdracht van gebruiker:\n" . $request);
        $settings = $this->get_ai_settings();
        $package = $this->build_export_package_data($page, $full_request);
        $prompt = $this->build_ai_improve_prompt($package, $full_request);

        $job_id = $this->create_ai_job(array(
            'page_id' => $page_id,
            'title' => sprintf(__('AI Improve running: %s', 'diviforge'), get_the_title($page_id)),
            'prompt' => $full_request,
            'status' => 'running',
            'model' => $settings['model'],
            'provider' => $settings['provider'],
            'source_package' => array(
                'manifest' => $package['manifest'] ?? array(),
                'layout' => $package['layout'] ?? array(),
                'css' => $package['css'] ?? '',
                'builder_tree' => $package['builder_tree'] ?? array(),
            ),
        ));

        $started = microtime(true);
        $result = $this->call_ai_provider($prompt, $settings);
        $job = $this->get_ai_job($job_id);

        if (is_wp_error($result)) {
            $message = $result->get_error_message();
            $job['title'] = sprintf(__('AI Improve failed: %s', 'diviforge'), get_the_title($page_id));
            $job['status'] = 'failed';
            $job['response'] = '';
            $job['error'] = $message . "\n" . (string) $result->get_error_data();
            $job['elapsed'] = round(microtime(true) - $started, 1);
            $job['validation'] = array('score' => 0, 'ok' => false, 'checks' => array(
                array('label' => __('AI provider response', 'diviforge'), 'ok' => false, 'message' => $message),
            ));
            $this->save_ai_job($job);
            set_transient('diviforge_latest_ai_result_' . get_current_user_id(), array('ok' => false, 'message' => $message, 'debug' => (string) $result->get_error_data(), 'job_id' => $job_id), 30 * MINUTE_IN_SECONDS);
            $this->log_ai_request(sprintf(__('AI Improve failed: %s', 'diviforge'), get_the_title($page_id)), $message, 'ai_error', $settings['model']);
            wp_safe_redirect(add_query_arg(array('page' => 'diviforge-ai-preview', 'job_id' => $job_id, 'ai_notice' => 'ai_error'), admin_url('admin.php')));
            exit;
        }

        $elapsed = round(microtime(true) - $started, 1);
        $parsed = $this->parse_ai_package_response($result);
        if (!is_wp_error($parsed)) {
            $parsed = $this->normalize_ai_package_result($parsed, $package);
        }
        $validation = $this->validate_ai_package_result($parsed);

        $job['title'] = sprintf(__('AI Improve generated: %s', 'diviforge'), get_the_title($page_id));
        $job['status'] = is_wp_error($parsed) ? 'needs_review' : (!empty($validation['ok']) ? 'completed' : 'needs_review');
        $job['response'] = $result;
        $job['parsed'] = is_wp_error($parsed) ? array() : $parsed;
        $job['validation'] = $validation;
        $job['elapsed'] = $elapsed;
        $this->save_ai_job($job);

        set_transient('diviforge_latest_ai_result_' . get_current_user_id(), array('ok' => !is_wp_error($parsed), 'message' => sprintf(__('AI generated a response in %ss. Review it before importing.', 'diviforge'), $elapsed), 'content' => $result, 'job_id' => $job_id), 30 * MINUTE_IN_SECONDS);
        $this->log_ai_request(sprintf(__('AI Improve generated: %s', 'diviforge'), get_the_title($page_id)), wp_trim_words(wp_strip_all_tags($full_request), 34), 'ai_success', $settings['model']);
        wp_safe_redirect(add_query_arg(array('page' => 'diviforge-ai-preview', 'job_id' => $job_id, 'ai_notice' => 'ai_success'), admin_url('admin.php')));
        exit;
    }

    public function handle_ai_studio_export() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to export pages.', 'diviforge')); }
        check_admin_referer('diviforge_ai_studio_export');
        $page_id = !empty($_POST['page_id']) ? absint($_POST['page_id']) : 0;
        if (!$page_id || !current_user_can('edit_post', $page_id)) { wp_die(esc_html__('You are not allowed to export this page.', 'diviforge')); }
        $_POST['_wpnonce'] = wp_create_nonce('diviforge_export_page_package_' . $page_id);
        $_POST['page_id'] = $page_id;
        $presets = $this->ai_studio_presets();
        $preset_prompt = $this->combine_selected_preset_prompts($presets, $_POST['ai_presets'] ?? array());
        $request = !empty($_POST['chatgpt_request']) ? sanitize_textarea_field(wp_unslash($_POST['chatgpt_request'])) : '';
        $_POST['chatgpt_request'] = trim($preset_prompt . "\n\nAanvullende opdracht van gebruiker:\n" . $request);
        $page_title = get_the_title($page_id);
        $this->log_ai_request(sprintf(__('AI package export: %s', 'diviforge'), $page_title), wp_trim_words(wp_strip_all_tags($_POST['chatgpt_request']), 34), 'package_exported');
        $this->handle_export_page_package();
    }

    public function packages() {
        $this->header(__('Packages', 'diviforge'), __('A first package library view built from package metadata stored on pages.', 'diviforge'), 'packages');
        $df_pages = $this->get_diviforge_pages();
        echo '<div class="df-package-grid">';
        if (!$df_pages) { echo '<div class="df-empty-state df-card"><span class="dashicons dashicons-archive"></span><h2>' . esc_html__('No packages yet', 'diviforge') . '</h2><p>' . esc_html__('Imported packages will appear here after you create or update a page.', 'diviforge') . '</p></div>'; }
        foreach ($df_pages as $page) {
            $title = get_post_meta($page->ID, '_diviforge_package_title', true);
            $version = get_post_meta($page->ID, '_diviforge_package_version', true);
            $file = get_post_meta($page->ID, '_diviforge_package_filename', true);
            echo '<article class="df-package-card"><div class="df-package-art"><span class="dashicons dashicons-archive"></span></div><div><p class="df-kicker">' . esc_html__('Package', 'diviforge') . '</p><h2>' . esc_html($title ? $title : get_the_title($page->ID)) . '</h2><p>' . esc_html($file ? $file : __('Imported package', 'diviforge')) . '</p><div class="df-card-actions"><span class="df-pill">' . esc_html($version ? 'v' . $version : 'no version') . '</span><a class="df-btn df-btn-soft" href="' . esc_url(admin_url('admin.php?page=diviforge-pages')) . '">' . esc_html__('Open page', 'diviforge') . '</a></div></div></article>';
        }
        echo '</div>';
        $this->footer();
    }


    private function get_import_history($page_id) {
        $history = get_post_meta($page_id, '_diviforge_import_history', true);
        if (is_string($history) && $history !== '') {
            $decoded = json_decode($history, true);
            if (is_array($decoded)) { return $decoded; }
        }
        return is_array($history) ? $history : array();
    }

    public function history() {
        $selected_page_id = !empty($_GET['history_page_id']) ? absint($_GET['history_page_id']) : 0;
        $this->header(__('Package History', 'diviforge'), __('Bekijk importhistorie per pagina en herstel een eerder geïmporteerde packageversie wanneer nodig.', 'diviforge'), 'history');
        if (!empty($_GET['history_restored'])) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__('History snapshot restored. The page content and stored CSS were set back to the selected import.', 'diviforge') . '</p></div>';
        }
        echo '<div class="df-card df-history-intro"><p class="df-kicker">' . esc_html__('Sprint 7', 'diviforge') . '</p><h2>' . esc_html__('Import history & rollback foundation', 'diviforge') . '</h2><p>' . esc_html__('Vanaf v2.8.0 bewaart DiviForge bij elke import een compacte snapshot van package metadata, statistieken, Divi-content en page.css. Zo kun je imports volgen en terugzetten.', 'diviforge') . '</p></div>';

        $pages = $this->get_diviforge_pages();
        if (!$pages) {
            echo '<div class="df-empty-state df-card"><span class="dashicons dashicons-backup"></span><h2>' . esc_html__('No history yet', 'diviforge') . '</h2><p>' . esc_html__('Import or update a page with a package to start building history.', 'diviforge') . '</p></div>';
            $this->footer();
            return;
        }

        echo '<div class="df-history-layout">';
        echo '<aside class="df-card df-history-pages"><h2>' . esc_html__('Pages', 'diviforge') . '</h2><div class="df-history-page-list">';
        foreach ($pages as $page) {
            $history = $this->get_import_history($page->ID);
            $count = count($history);
            $active = ($selected_page_id && $selected_page_id === $page->ID) || (!$selected_page_id && $page === reset($pages));
            if (!$selected_page_id && $active) { $selected_page_id = $page->ID; }
            echo '<a class="' . esc_attr($active ? 'is-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=diviforge-history&history_page_id=' . $page->ID)) . '"><span class="dashicons dashicons-media-document"></span><div><strong>' . esc_html(get_the_title($page->ID)) . '</strong><small>' . esc_html(sprintf(_n('%d snapshot', '%d snapshots', $count, 'diviforge'), $count)) . '</small></div></a>';
        }
        echo '</div></aside>';

        $page = $selected_page_id ? get_post($selected_page_id) : null;
        echo '<main class="df-history-main">';
        if (!$page) {
            echo '<div class="df-empty-state df-card"><span class="dashicons dashicons-warning"></span><h2>' . esc_html__('Page not found', 'diviforge') . '</h2></div>';
        } else {
            $history = array_reverse($this->get_import_history($page->ID), true);
            echo '<div class="df-card df-history-page-head"><div><p class="df-kicker">' . esc_html__('Selected page', 'diviforge') . '</p><h2>' . esc_html(get_the_title($page->ID)) . '</h2><p>' . esc_html__('Gebruik restore alleen wanneer je bewust terug wilt naar de content en CSS van een eerdere import.', 'diviforge') . '</p></div><div class="df-card-actions"><a class="df-btn df-btn-primary" target="_blank" href="' . esc_url($this->get_page_preview_url($page->ID)) . '"><span class="dashicons dashicons-visibility"></span>' . esc_html__('Preview', 'diviforge') . '</a><a class="df-btn df-btn-soft" href="' . esc_url(admin_url('post.php?post=' . $page->ID . '&action=edit&et_fb=1')) . '"><span class="dashicons dashicons-admin-customizer"></span>' . esc_html__('Divi', 'diviforge') . '</a></div></div>';
            if (!$history) {
                echo '<div class="df-empty-state df-card"><span class="dashicons dashicons-backup"></span><h2>' . esc_html__('No snapshots for this page yet', 'diviforge') . '</h2><p>' . esc_html__('This page has package metadata from an older DiviForge version, but snapshots start from v2.7.0.', 'diviforge') . '</p></div>';
            } else {
                echo '<div class="df-history-timeline">';
                foreach ($history as $index => $entry) {
                    $stats = !empty($entry['stats']) && is_array($entry['stats']) ? $entry['stats'] : array();
                    $version = !empty($entry['package_version']) ? 'v' . $entry['package_version'] : __('no version', 'diviforge');
                    echo '<article class="df-card df-history-snapshot"><div class="df-history-snapshot-top"><div><p class="df-kicker">' . esc_html($version) . '</p><h2>' . esc_html(!empty($entry['package_title']) ? $entry['package_title'] : __('Imported package', 'diviforge')) . '</h2><p>' . esc_html(!empty($entry['imported_at']) ? $entry['imported_at'] : '') . '</p></div><span class="df-pill">' . esc_html(!empty($entry['mode']) ? $entry['mode'] : 'import') . '</span></div>';
                    echo '<div class="df-package-stats df-history-stats"><span><b>' . esc_html(!empty($stats['sections']) ? absint($stats['sections']) : 0) . '</b>' . esc_html__('sections', 'diviforge') . '</span><span><b>' . esc_html(!empty($stats['modules']) ? absint($stats['modules']) : 0) . '</b>' . esc_html__('modules', 'diviforge') . '</span><span><b>' . esc_html(!empty($stats['images']) ? absint($stats['images']) : 0) . '</b>' . esc_html__('images', 'diviforge') . '</span><span><b>' . esc_html(!empty($stats['css_lines']) ? absint($stats['css_lines']) : 0) . '</b>' . esc_html__('css lines', 'diviforge') . '</span></div>';
                    echo '<div class="df-history-meta"><span><b>' . esc_html__('File', 'diviforge') . '</b>' . esc_html(!empty($entry['filename']) ? $entry['filename'] : '—') . '</span><span><b>' . esc_html__('Hash', 'diviforge') . '</b>' . esc_html(!empty($entry['hash']) ? substr($entry['hash'], 0, 12) . '…' : '—') . '</span></div>';
                    if (!empty($entry['release_note'])) {
                        echo '<div class="df-history-note"><strong>' . esc_html__('Automatische samenvatting', 'diviforge') . '</strong><p>' . esc_html($entry['release_note']) . '</p></div>';
                    }
                    $restore_url = wp_nonce_url(admin_url('admin-post.php?action=diviforge_restore_history&page_id=' . $page->ID . '&snapshot=' . absint($index)), 'diviforge_restore_history_' . $page->ID . '_' . absint($index));
                    echo '<div class="df-card-actions"><a class="df-btn df-btn-danger" href="' . esc_url($restore_url) . '" onclick="return confirm(\'' . esc_js(__('Restore this snapshot? Current page content and CSS will be replaced.', 'diviforge')) . '\');"><span class="dashicons dashicons-undo"></span>' . esc_html__('Restore snapshot', 'diviforge') . '</a></div>';
                    echo '</article>';
                }
                echo '</div>';
            }
        }
        echo '</main></div>';
        $this->footer();
    }

    public function handle_restore_history() {
        $page_id = !empty($_GET['page_id']) ? absint($_GET['page_id']) : 0;
        $snapshot = isset($_GET['snapshot']) ? absint($_GET['snapshot']) : -1;
        if (!$page_id || !current_user_can('edit_post', $page_id)) { wp_die(esc_html__('You are not allowed to restore this page.', 'diviforge')); }
        check_admin_referer('diviforge_restore_history_' . $page_id . '_' . $snapshot);
        $history = $this->get_import_history($page_id);
        if (!isset($history[$snapshot]) || !is_array($history[$snapshot])) { wp_die(esc_html__('Snapshot not found.', 'diviforge')); }
        $entry = $history[$snapshot];
        if (isset($entry['content'])) {
            wp_update_post(array('ID' => $page_id, 'post_content' => (string) $entry['content']));
        }
        if (isset($entry['css'])) {
            DiviForge_CSS_Importer::apply_to_page($page_id, (string) $entry['css'], 'history_restore');
        }
        update_post_meta($page_id, '_diviforge_package_title', sanitize_text_field($entry['package_title'] ?? get_the_title($page_id)));
        update_post_meta($page_id, '_diviforge_package_filename', sanitize_text_field($entry['filename'] ?? ''));
        update_post_meta($page_id, '_diviforge_package_version', sanitize_text_field($entry['package_version'] ?? ''));
        update_post_meta($page_id, '_diviforge_package_imported_at', current_time('mysql'));
        update_post_meta($page_id, '_diviforge_package_restored_at', current_time('mysql'));
        wp_safe_redirect(admin_url('admin.php?page=diviforge-history&history_page_id=' . $page_id . '&history_restored=1'));
        exit;
    }

    public function package_inspector() {
        $this->header(__('Package Preview', 'diviforge'), __('Inspect and visually preview a .dfg or .zip package before importing it into Divi.', 'diviforge'), 'inspector');
        echo '<section class="df-inspector-layout">';
        echo '<div class="df-card df-inspector-upload"><p class="df-kicker">' . esc_html__('Sprint 6', 'diviforge') . '</p><h2>' . esc_html__('Inspect & preview before import', 'diviforge') . '</h2><p>' . esc_html__('Upload a package to check metadata, validate the package and see a lightweight visual preview before you create or update a page.', 'diviforge') . '</p><form id="df-inspector-form" enctype="multipart/form-data"><label class="df-file-label"><span>' . esc_html__('Package ZIP / DFG', 'diviforge') . '</span><input type="file" name="package" accept=".zip,.dfg" required><em>' . esc_html__('No import happens here. This is a safe inspection and preview step.', 'diviforge') . '</em></label><button class="df-btn df-btn-primary"><span class="dashicons dashicons-search"></span>' . esc_html__('Inspect & Preview', 'diviforge') . '</button></form></div>';
        echo '<div class="df-card df-inspector-help"><h2>' . esc_html__('What is checked?', 'diviforge') . '</h2><ul class="df-checks"><li>manifest.json metadata</li><li>layout.json structure</li><li>page.css presence and size</li><li>section, row, column and module counts</li><li>image and preview assets</li><li>basic Divi compatibility</li><li>lightweight desktop, tablet and mobile preview</li></ul></div>';
        echo '</section><div id="df-inspector-result" class="df-inspector-result"></div>';
        $this->footer();
    }

    public function ajax_inspect_package() {
        check_ajax_referer('diviforge_admin', 'nonce');
        if (empty($_FILES['package'])) { wp_send_json_error(array('message' => __('No file uploaded.', 'diviforge'))); }
        $result = DiviForge_Package::inspect_upload($_FILES['package']);
        if (is_wp_error($result)) { wp_send_json_error(array('message' => $result->get_error_message())); }
        wp_send_json_success($result);
    }

    private function get_page_preview_url($page_id) {
        $page_id = absint($page_id);
        if (!$page_id) { return home_url('/'); }
        $preview = get_preview_post_link($page_id);
        if ($preview) { return $preview; }
        return add_query_arg('preview', 'true', get_permalink($page_id));
    }

    public function handle_export_page_package() {
        $page_id = !empty($_POST['page_id']) ? absint($_POST['page_id']) : 0;
        if (!$page_id || !current_user_can('edit_post', $page_id)) {
            wp_die(esc_html__('You are not allowed to export this page.', 'diviforge'));
        }
        check_admin_referer('diviforge_export_page_package_' . $page_id);
        if (!class_exists('ZipArchive')) {
            wp_die(esc_html__('ZipArchive is not available on this server.', 'diviforge'));
        }
        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') {
            wp_die(esc_html__('Page not found.', 'diviforge'));
        }

        $request = !empty($_POST['chatgpt_request']) ? sanitize_textarea_field(wp_unslash($_POST['chatgpt_request'])) : '';
        $package = $this->build_export_package_data($page, $request);
        $filename = sanitize_file_name('diviforge-export-' . $page->post_name . '-' . gmdate('Ymd-His') . '.zip');
        $tmp = wp_tempnam($filename);
        if (!$tmp) {
            wp_die(esc_html__('Could not create temporary export file.', 'diviforge'));
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($tmp, ZipArchive::OVERWRITE)) {
            wp_die(esc_html__('Could not create export ZIP.', 'diviforge'));
        }
        $zip->addFromString('manifest.json', wp_json_encode($package['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('layout.json', wp_json_encode($package['layout'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('builder-tree.json', wp_json_encode($package['builder_tree'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('page-meta.json', wp_json_encode($package['page_meta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('theme.json', wp_json_encode($package['theme'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('css/variables.json', wp_json_encode($package['css_variables'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('css/analysis.json', wp_json_encode($package['css_analysis'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('semantic-structure.json', wp_json_encode($package['semantic_structure'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('statistics.json', wp_json_encode($package['statistics'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('relations.json', wp_json_encode($package['relations'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('assets/assets.json', wp_json_encode($package['assets_manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('scripts/scripts.json', wp_json_encode($package['scripts_manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('page.css', $package['css']);
        $zip->addFromString('page.html', $package['html']);
        $zip->addFromString('design.json', wp_json_encode($package['design'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('divi-context.json', wp_json_encode($package['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('class-map.json', wp_json_encode($package['class_map'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('AI_REQUEST.json', wp_json_encode($package['ai_request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('scripts/page-scripts.js', $package['scripts']);
        $zip->addFromString('preview/README.md', $package['preview_readme']);
        $zip->addFromString('assets/README.md', $package['assets_readme']);
        if (!empty($package['asset_files']) && is_array($package['asset_files'])) {
            foreach ($package['asset_files'] as $asset_file) {
                if (!empty($asset_file['local_path']) && !empty($asset_file['zip_path']) && file_exists($asset_file['local_path']) && is_readable($asset_file['local_path'])) {
                    $zip->addFile($asset_file['local_path'], $asset_file['zip_path']);
                }
            }
        }
        $zip->addFromString('chatgpt-instructions.md', $package['instructions']);
        $zip->addFromString('prompt.txt', $package['prompt']);
        $zip->addFromString('README.md', $package['readme']);
        $zip->close();

        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    private function get_complete_page_css($page_id) {
        $sources = array(
            '_et_pb_custom_css',
            '_diviforge_page_css',
            '_royal_mcp_page_custom_css',
            '_et_pb_page_custom_css',
            '_et_pb_custom_css_page',
            '_et_pb_post_custom_css',
        );
        $parts = array();
        foreach ($sources as $meta_key) {
            $value = get_post_meta($page_id, $meta_key, true);
            if (is_array($value)) { $value = implode("\n", array_filter(array_map('strval', $value))); }
            if (is_string($value) && trim($value) !== '') {
                $parts[] = "/* Source: " . $meta_key . " */\n" . trim($value);
            }
        }
        $all_meta = get_post_meta($page_id);
        foreach ((array) $all_meta as $meta_key => $values) {
            if (in_array($meta_key, $sources, true)) { continue; }
            if (stripos($meta_key, 'css') === false && stripos($meta_key, 'style') === false) { continue; }
            foreach ((array) $values as $value) {
                if (!is_string($value) || trim($value) === '') { continue; }
                $maybe = maybe_unserialize($value);
                if (is_array($maybe)) { $maybe = wp_json_encode($maybe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); }
                if (!is_string($maybe)) { continue; }
                if (strpos($maybe, '{') !== false || strpos($maybe, ':') !== false || strpos($maybe, '.') !== false) {
                    $parts[] = "/* Source: " . $meta_key . " */\n" . trim($maybe);
                }
            }
        }
        $combined = trim(implode("\n\n", array_values(array_unique($parts))));
        return $combined;
    }

    private function build_export_package_data($page, $request = '') {
        $css = $this->get_complete_page_css($page->ID);
        $content = (string) $page->post_content;
        $permalink = get_permalink($page->ID);
        $parsed = $this->parse_divi_shortcodes($content);
        $page_meta = $this->build_page_meta_export($page);
        $theme = $this->build_theme_export();
        $css_variables = $this->build_css_variables_export($css);
        $css_analysis = $this->build_css_analysis($css, $parsed);
        $builder_tree = $this->build_builder_tree($page, $content, $parsed, $css_analysis);
        $class_map = $this->build_class_map($content, $css, $parsed);
        $scripts = $this->extract_scripts_from_content_and_meta($page, $content);
        $scripts_manifest = $this->build_scripts_manifest($scripts, $page);
        $assets = $this->collect_export_assets($content, $css, $parsed);
        $stats = $this->estimate_export_stats($content, $css, $parsed, $assets);
        $design = $this->build_design_tokens($content, $css, $parsed);
        $semantic_structure = $this->build_semantic_structure($parsed, $css_analysis);
        $statistics = $this->build_statistics_export($content, $css, $parsed, $assets, $css_analysis, $semantic_structure);
        $relations = $this->build_relation_model($parsed, $css_analysis, $assets, $semantic_structure);

        $manifest = array(
            'schema' => 'diviforge-package/v6',
            'type' => 'page-export',
            'source' => 'existing-wordpress-page',
            'title' => get_the_title($page->ID),
            'name' => sanitize_title(get_the_title($page->ID)),
            'version' => 'export-' . gmdate('Ymd-His'),
            'author' => wp_get_current_user()->display_name,
            'exported_at' => current_time('mysql'),
            'diviforge_version' => defined('DIVIFORGE_VERSION') ? DIVIFORGE_VERSION : '',
            'package_format' => 'AI Package v6 / Extraction Quality Export',
            'source_page' => array(
                'id' => absint($page->ID),
                'slug' => $page->post_name,
                'status' => $page->post_status,
                'url' => $permalink ? $permalink : '',
                'post_type' => $page->post_type,
                'template' => get_page_template_slug($page->ID),
                'parent' => absint($page->post_parent),
                'menu_order' => intval($page->menu_order),
                'modified' => get_post_modified_time('Y-m-d H:i:s', false, $page),
            ),
            'stats' => $statistics,
            'chatgpt_request' => $request,
            'assets' => $assets['manifest'],
            'features' => array(
                'Full raw Divi shortcode content in layout.json',
                'Canonical builder-tree.json with sections, rows, columns, modules, hierarchy, ids and positions',
                'Parsed Divi structure with sections, rows, columns and modules',
                'All detected shortcode attributes are included where possible',
                'Page Custom CSS, full CSS analysis, selector-to-node hints and relation model are included',
                'Detected inline scripts and script-like page meta are included',
                'Page meta, theme context, CSS variables and script manifest are exported as separate context files',
                'Local WordPress media assets referenced by shortcode attributes, content or CSS are exported into assets/images where possible',
                'AI_REQUEST.json provides an AI-neutral instruction payload',
                'statistics.json, semantic-structure.json and relations.json give AI a higher-level understanding of the page',
            ),
        );

        $context = array(
            'schema' => 'diviforge-context/v1',
            'wordpress' => array(
                'site_url' => home_url('/'),
                'wp_version' => get_bloginfo('version'),
                'language' => get_bloginfo('language'),
                'theme' => wp_get_theme()->get('Name'),
            ),
            'page_meta' => $page_meta,
            'shortcode_tags_detected' => $parsed['tags'],
            'css_classes' => $class_map,
            'css_analysis' => $css_analysis,
            'semantic_structure' => $semantic_structure,
            'relations_file' => 'relations.json',
            'scripts' => array(
                'inline_script_blocks' => substr_count($scripts, '<script'),
                'export_file' => 'scripts/page-scripts.js',
            ),
        );

        $layout = array(
            'schema' => 'diviforge-layout/v5',
            'title' => get_the_title($page->ID),
            'source_page_id' => absint($page->ID),
            'export_mode' => 'raw_plus_structured_divi',
            'divi_content' => $content,
            'shortcodes' => $parsed['flat'],
            'sections' => $parsed['sections'],
            'rows' => $parsed['rows'],
            'columns' => $parsed['columns'],
            'modules' => $parsed['modules'],
            'builder_tree_file' => 'builder-tree.json',
            'notes' => 'The raw Divi shortcode content remains available for import fidelity. builder-tree.json is the AI-friendly canonical structure for understanding and editing the page hierarchy. semantic-structure.json and relations.json provide higher-level AI context.',
        );

        $html = $this->build_export_page_html($page, $content, $css);
        $ai_request = $this->build_ai_request($manifest, $layout, $design, $request);
        $prompt = $this->build_chatgpt_prompt($manifest, $request);
        $instructions = $this->build_chatgpt_instructions($manifest, $request);
        $readme = "# DiviForge Page Export\n\nThis ZIP contains an existing WordPress/Divi page exported to the DiviForge package format.\n\n## Files\n- `manifest.json`: metadata, source page details, stats, assets and the user request.\n- `layout.json`: raw Divi content plus structured sections, rows, columns and modules.\n- `page.css`: Divi Page Custom CSS for this page.\n- `design.json`: detected colors, fonts, radius, spacing and class hints.\n- `divi-context.json`: WordPress, page meta, shortcode and script context.\n- `class-map.json`: detected classes from CSS and Divi shortcodes.\n- `AI_REQUEST.json`: AI-neutral task/instruction payload.\n- `assets/images/`: local WordPress media files used by the page where available.\n- `scripts/page-scripts.js`: detected inline scripts and page script meta.
- `scripts/scripts.json`: script manifest with counts and source hints.\n- `chatgpt-instructions.md` and `prompt.txt`: human-friendly AI instructions.\n\n";
        return array(
            'manifest' => $manifest,
            'layout' => $layout,
            'builder_tree' => $builder_tree,
            'page_meta' => $page_meta,
            'theme' => $theme,
            'css_variables' => $css_variables,
            'css' => $css,
            'css_analysis' => $css_analysis,
            'semantic_structure' => $semantic_structure,
            'statistics' => $statistics,
            'relations' => $relations,
            'assets_manifest' => $assets['manifest'],
            'html' => $html,
            'design' => $design,
            'context' => $context,
            'class_map' => $class_map,
            'ai_request' => $ai_request,
            'scripts' => $scripts,
            'scripts_manifest' => $scripts_manifest,
            'asset_files' => $assets['files'],
            'preview_readme' => "# Preview folder\n\nReserved for generated desktop/tablet/mobile snapshots. The current export includes page.html as AI context.\n",
            'assets_readme' => $assets['readme'],
            'prompt' => $prompt,
            'instructions' => $instructions,
            'readme' => $readme,
        );
    }


    private function build_builder_tree($page, $content, $parsed, $css_analysis = array()) {
        $tree = array(
            'schema' => 'diviforge-builder-tree/v4',
            'id' => 'page-' . absint($page->ID),
            'type' => 'page',
            'title' => get_the_title($page->ID),
            'source_page_id' => absint($page->ID),
            'content_hash' => md5((string) $content),
            'canonical' => true,
            'css_context' => array(
                'analysis_file' => 'css/analysis.json',
                'selectors' => isset($css_analysis['rules']) ? count($css_analysis['rules']) : 0,
            ),
            'children' => array(),
            'indexes' => array(
                'sections' => count($parsed['sections'] ?? array()),
                'rows' => count($parsed['rows'] ?? array()),
                'columns' => count($parsed['columns'] ?? array()),
                'modules' => count($parsed['modules'] ?? array()),
            ),
            'notes' => 'This AI-fidelity tree is generated from Divi shortcodes. It preserves raw attributes, normalized settings, responsive variants, content excerpts, semantic hints, CSS bindings and asset references where detectable. Raw divi_content remains available in layout.json for import fidelity.',
        );

        $section_nodes = array();
        foreach (($parsed['sections'] ?? array()) as $section) {
            $section_index = intval($section['index'] ?? count($section_nodes));
            $section_nodes[$section_index] = $this->build_tree_node($section, 'section', 'section-' . ($section_index + 1), $section_index, null);
            $section_nodes[$section_index]['children'] = array();
        }

        foreach (($parsed['rows'] ?? array()) as $row) {
            $section_index = intval($row['section_index'] ?? -1);
            if (!isset($section_nodes[$section_index])) { continue; }
            $row_index = intval($row['index'] ?? count($section_nodes[$section_index]['children']));
            $row_node = $this->build_tree_node($row, 'row', 'section-' . ($section_index + 1) . '-row-' . ($row_index + 1), $row_index, $section_nodes[$section_index]['id']);
            $row_node['children'] = array();
            $section_nodes[$section_index]['children'][$row_index] = $row_node;
        }

        foreach (($parsed['columns'] ?? array()) as $column) {
            $section_index = intval($column['section_index'] ?? -1);
            $row_index = intval($column['row_index'] ?? -1);
            if (!isset($section_nodes[$section_index]['children'][$row_index])) { continue; }
            $column_index = intval($column['index'] ?? count($section_nodes[$section_index]['children'][$row_index]['children']));
            $column_node = $this->build_tree_node($column, 'column', 'section-' . ($section_index + 1) . '-row-' . ($row_index + 1) . '-column-' . ($column_index + 1), $column_index, $section_nodes[$section_index]['children'][$row_index]['id']);
            $column_node['children'] = array();
            $section_nodes[$section_index]['children'][$row_index]['children'][$column_index] = $column_node;
        }

        foreach (($parsed['modules'] ?? array()) as $module) {
            $section_index = intval($module['section_index'] ?? -1);
            $row_index = intval($module['row_index'] ?? -1);
            $column_index = intval($module['column_index'] ?? -1);
            if (!isset($section_nodes[$section_index]['children'][$row_index]['children'][$column_index])) { continue; }
            $module_index = intval($module['index'] ?? count($section_nodes[$section_index]['children'][$row_index]['children'][$column_index]['children']));
            $module_node = $this->build_tree_node($module, 'module', 'section-' . ($section_index + 1) . '-row-' . ($row_index + 1) . '-column-' . ($column_index + 1) . '-module-' . ($module_index + 1), $module_index, $section_nodes[$section_index]['children'][$row_index]['children'][$column_index]['id']);
            $module_node['content'] = $this->extract_shortcode_inner_content((string) $content, $module);
            $section_nodes[$section_index]['children'][$row_index]['children'][$column_index]['children'][] = $module_node;
        }

        foreach ($section_nodes as $section_node) {
            $section_node['children'] = array_values(array_map(function($row) {
                $row['children'] = array_values(array_map(function($column) {
                    $column['children'] = array_values($column['children'] ?? array());
                    return $column;
                }, $row['children'] ?? array()));
                return $row;
            }, $section_node['children'] ?? array()));
            $tree['children'][] = $section_node;
        }
        return $tree;
    }

    private function build_tree_node($item, $node_type, $id, $position, $parent_id = null) {
        $attrs = !empty($item['attrs']) && is_array($item['attrs']) ? $item['attrs'] : array();
        $classes = $this->extract_classes_from_attrs($attrs);
        return array(
            'id' => $id,
            'node_type' => $node_type,
            'divi_tag' => $item['tag'] ?? '',
            'divi_type' => $item['type'] ?? '',
            'semantic_role' => $this->detect_semantic_role_for_item($item),
            'parent_id' => $parent_id,
            'position' => intval($position),
            'raw' => array(
                'open_shortcode' => $item['raw_open'] ?? '',
                'attr_text' => $item['attr_text'] ?? '',
                'offset' => intval($item['offset'] ?? 0),
            ),
            'attrs' => $attrs,
            'classes' => $classes,
            'settings' => $this->categorize_divi_attrs($attrs),
            'responsive' => $this->extract_responsive_attrs($attrs),
            'advanced' => array(
                'css_id' => $attrs['module_id'] ?? ($attrs['custom_css_id'] ?? ''),
                'css_classes' => $classes,
                'admin_label' => $attrs['admin_label'] ?? '',
                'disabled_on' => $attrs['disabled_on'] ?? '',
            ),
            'assets' => $this->extract_asset_refs_from_attrs($attrs),
            'css_bindings' => $this->build_node_css_bindings($classes, $id),
            'offset' => intval($item['offset'] ?? 0),
        );
    }

    private function categorize_divi_attrs($attrs) {
        $out = array(
            'layout' => array(),
            'spacing' => array(),
            'sizing' => array(),
            'background' => array(),
            'typography' => array(),
            'border' => array(),
            'shadow' => array(),
            'effects' => array(),
            'animation' => array(),
            'visibility' => array(),
            'custom_css' => array(),
            'responsive' => array(),
            'module_specific' => array(),
            'other' => array(),
        );
        foreach ((array) $attrs as $key => $value) {
            $k = strtolower((string) $key);
            if (strpos($k, '_tablet') !== false || strpos($k, '_phone') !== false || strpos($k, 'responsive') !== false) {
                $out['responsive'][$key] = $value;
            } elseif (strpos($k, 'padding') !== false || strpos($k, 'margin') !== false) {
                $out['spacing'][$key] = $value;
            } elseif (strpos($k, 'width') !== false || strpos($k, 'height') !== false || strpos($k, 'max_') !== false || strpos($k, 'min_') !== false || strpos($k, 'gutter') !== false || strpos($k, 'equal') !== false) {
                $out['sizing'][$key] = $value;
            } elseif (strpos($k, 'background') !== false || strpos($k, 'gradient') !== false || strpos($k, 'parallax') !== false || strpos($k, 'video') !== false || strpos($k, 'mask') !== false || strpos($k, 'pattern') !== false) {
                $out['background'][$key] = $value;
            } elseif (strpos($k, 'font') !== false || strpos($k, 'text_') !== false || strpos($k, 'letter_spacing') !== false || strpos($k, 'line_height') !== false || strpos($k, 'header_') !== false || strpos($k, 'body_') !== false) {
                $out['typography'][$key] = $value;
            } elseif (strpos($k, 'border') !== false || strpos($k, 'radius') !== false || strpos($k, 'rounded') !== false) {
                $out['border'][$key] = $value;
            } elseif (strpos($k, 'shadow') !== false || strpos($k, 'box_shadow') !== false || strpos($k, 'text_shadow') !== false) {
                $out['shadow'][$key] = $value;
            } elseif (strpos($k, 'filter') !== false || strpos($k, 'blend') !== false || strpos($k, 'opacity') !== false || strpos($k, 'transform') !== false) {
                $out['effects'][$key] = $value;
            } elseif (strpos($k, 'animation') !== false || strpos($k, 'hover') !== false || strpos($k, 'transition') !== false) {
                $out['animation'][$key] = $value;
            } elseif (strpos($k, 'disabled') !== false || strpos($k, 'visibility') !== false || strpos($k, 'hidden') !== false || strpos($k, 'sticky') !== false || strpos($k, 'overflow') !== false) {
                $out['visibility'][$key] = $value;
            } elseif (strpos($k, 'custom_css') !== false || strpos($k, 'module_class') !== false || strpos($k, 'module_id') !== false || strpos($k, 'css') !== false) {
                $out['custom_css'][$key] = $value;
            } elseif (in_array($k, array('title','button_text','button_url','url','src','alt','content','icon','image','background_image','admin_label'), true)) {
                $out['module_specific'][$key] = $value;
            } else {
                $out['other'][$key] = $value;
            }
        }
        return $out;
    }

    private function extract_responsive_attrs($attrs) {
        $responsive = array('desktop' => array(), 'tablet' => array(), 'phone' => array(), 'raw' => array());
        foreach ((array) $attrs as $key => $value) {
            $k = (string) $key;
            if (substr($k, -7) === '_tablet') {
                $responsive['tablet'][substr($k, 0, -7)] = $value;
                $responsive['raw'][$key] = $value;
            } elseif (substr($k, -6) === '_phone') {
                $responsive['phone'][substr($k, 0, -6)] = $value;
                $responsive['raw'][$key] = $value;
            } elseif (is_string($value) && strpos($value, '|') !== false) {
                $parts = explode('|', $value);
                if (count($parts) >= 3) {
                    $responsive['desktop'][$key] = $parts[0];
                    $responsive['tablet'][$key] = $parts[1];
                    $responsive['phone'][$key] = $parts[2];
                    $responsive['raw'][$key] = $value;
                }
            }
        }
        return $responsive;
    }

    private function extract_asset_refs_from_attrs($attrs) {
        $assets = array();
        foreach ((array) $attrs as $key => $value) {
            if (!is_scalar($value)) { continue; }
            $value = (string) $value;
            if (preg_match_all('/(?:https?:\/\/[^\s"\'\)]+|\/[^\s"\'\)]+|[^\s"\'\)]+wp-content\/uploads\/[^\s"\'\)]+)\.(?:png|jpe?g|webp|gif|svg|mp4|webm)(?:\?[^\s"\'\)]*)?/i', $value, $m)) {
                foreach ($m[0] as $url) {
                    $assets[] = array('attr' => $key, 'url' => html_entity_decode($url));
                }
            }
        }
        return $assets;
    }

    private function build_node_css_bindings($classes, $node_id = '') {
        $bindings = array();
        foreach ((array) $classes as $class) {
            $bindings[] = array(
                'class' => $class,
                'selector_hint' => '.' . $class,
                'analysis_file' => 'css/analysis.json',
                'node_id' => $node_id,
            );
        }
        return $bindings;
    }

    private function extract_shortcode_inner_content($content, $module) {
        $tag = !empty($module['tag']) ? preg_quote($module['tag'], '/') : '';
        $offset = intval($module['offset'] ?? 0);
        if ($tag === '') { return array('html' => '', 'text' => ''); }
        $slice = substr($content, $offset);
        if (preg_match('/\[' . $tag . '\b[^\]]*\](.*?)\[\/' . $tag . '\]/is', $slice, $m)) {
            $html = trim($m[1]);
            return array(
                'html' => $html,
                'text' => trim(wp_strip_all_tags($html)),
                'excerpt' => wp_trim_words(wp_strip_all_tags($html), 32, '…'),
            );
        }
        return array('html' => '', 'text' => '', 'excerpt' => '');
    }

    private function build_page_meta_export($page) {
        $meta = get_post_meta($page->ID);
        $all = array();
        foreach ($meta as $key => $values) {
            if (strpos($key, '_edit_') === 0 || strpos($key, '_wp_old_') === 0) { continue; }
            $clean_values = array();
            foreach ((array) $values as $value) { $clean_values[] = maybe_unserialize($value); }
            $all[$key] = $clean_values;
        }
        return array(
            'schema' => 'diviforge-page-meta/v1',
            'page' => array(
                'id' => absint($page->ID),
                'title' => get_the_title($page->ID),
                'slug' => $page->post_name,
                'status' => $page->post_status,
                'type' => $page->post_type,
                'template' => get_page_template_slug($page->ID),
                'parent' => absint($page->post_parent),
                'menu_order' => intval($page->menu_order),
                'created' => $page->post_date,
                'modified' => $page->post_modified,
            ),
            'meta' => $all,
            'divi_relevant_meta' => $this->export_relevant_page_meta($page->ID),
        );
    }

    private function build_theme_export() {
        $theme = wp_get_theme();
        return array(
            'schema' => 'diviforge-theme-context/v1',
            'wordpress' => array('version' => get_bloginfo('version'), 'language' => get_bloginfo('language'), 'site_url' => home_url('/')),
            'theme' => array(
                'name' => $theme->get('Name'),
                'version' => $theme->get('Version'),
                'template' => $theme->get_template(),
                'stylesheet' => $theme->get_stylesheet(),
                'author' => $theme->get('Author'),
            ),
            'plugins_hint' => array('divi_builder_expected' => true, 'diviforge_version' => defined('DIVIFORGE_VERSION') ? DIVIFORGE_VERSION : ''),
        );
    }

    private function build_css_variables_export($css) {
        $css = is_string($css) ? $css : '';
        preg_match_all('/--([a-zA-Z0-9_-]+)\s*:\s*([^;{}]+)/', $css, $var_matches, PREG_SET_ORDER);
        preg_match_all('/([^{}]+)\{([^{}]+)\}/', $css, $rule_matches, PREG_SET_ORDER);
        $variables = array();
        foreach ($var_matches as $m) { $variables['--' . trim($m[1])] = trim($m[2]); }
        $selectors = array();
        foreach ($rule_matches as $m) {
            $selectors[] = array('selector' => trim($m[1]), 'declarations_excerpt' => wp_trim_words(trim(preg_replace('/\s+/', ' ', $m[2])), 36, '…'));
            if (count($selectors) >= 100) { break; }
        }
        return array(
            'schema' => 'diviforge-css-context/v1',
            'variables' => $variables,
            'selectors' => $selectors,
            'line_count' => trim($css) === '' ? 0 : substr_count(trim($css), "\n") + 1,
            'notes' => 'Full CSS is in page.css. This file gives AI a quick machine-readable overview of variables and selectors.',
        );
    }

    private function build_scripts_manifest($scripts, $page) {
        $scripts = is_string($scripts) ? $scripts : '';
        preg_match_all('/<script\b([^>]*)>(.*?)<\/script>/is', $scripts, $blocks, PREG_SET_ORDER);
        $inline = array();
        foreach ($blocks as $index => $block) {
            $inline[] = array(
                'index' => $index,
                'attrs' => trim($block[1] ?? ''),
                'excerpt' => wp_trim_words(wp_strip_all_tags($block[2] ?? ''), 32, '…'),
            );
        }
        return array(
            'schema' => 'diviforge-scripts/v1',
            'source_page_id' => absint($page->ID),
            'inline_blocks' => $inline,
            'raw_export' => 'scripts/page-scripts.js',
            'has_scripts' => trim($scripts) !== '',
        );
    }

    private function build_ai_request($manifest, $layout, $design, $request = '') {
        $request = trim((string) $request);
        if ($request === '') {
            $request = 'Analyseer deze Divi pagina en lever een verbeterd importeerbaar DiviForge package terug.';
        }
        return array(
            'schema' => 'diviforge-ai-request/v1',
            'task' => 'modify-existing-divi-page',
            'user_request' => $request,
            'source_files' => array('manifest.json', 'layout.json', 'builder-tree.json', 'semantic-structure.json', 'statistics.json', 'relations.json', 'page-meta.json', 'theme.json', 'page.css', 'css/variables.json', 'css/analysis.json', 'design.json', 'divi-context.json', 'class-map.json', 'page.html'),
            'canonical_sources' => array('builder-tree.json', 'layout.json.divi_content', 'page.css'),
            'page_summary' => array(
                'title' => $manifest['title'] ?? '',
                'stats' => $manifest['stats'] ?? array(),
                'sections_exported' => count($layout['sections'] ?? array()),
                'modules_exported' => count($layout['modules'] ?? array()),
                'colors' => $design['colors'] ?? array(),
                'fonts' => $design['fonts'] ?? array(),
            ),
            'rules' => array(
                'Use Divi-compatible modules and shortcode-compatible structure.',
                'Preserve existing CSS classes unless the user explicitly asks to replace them.',
                'Keep all final page-specific CSS in page.css.',
                'If images are reused, reference exported assets from assets/images or preserve existing URLs.',
                'Return a new DiviForge package with at least manifest.json, layout.json and page.css.',
            ),
            'expected_output' => array('manifest.json', 'layout.json', 'page.css'),
        );
    }

    private function parse_divi_shortcodes($content) {
        $content = is_string($content) ? $content : '';
        $pattern = '/\[(\/)?(et_pb_[a-zA-Z0-9_]+)\b([^\]]*)\]/';
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        $flat = array(); $sections = array(); $rows = array(); $columns = array(); $modules = array();
        $current_section = -1; $current_row = -1; $current_column = -1;
        $row_module_counts = array();

        foreach ($matches[0] as $i => $full) {
            $is_close = !empty($matches[1][$i][0]);
            $tag = $matches[2][$i][0];
            if ($is_close) {
                if ($tag === 'et_pb_column') { $current_column = -1; }
                if ($tag === 'et_pb_row' || $tag === 'et_pb_row_inner') { $current_row = -1; $current_column = -1; }
                if ($tag === 'et_pb_section') { $current_section = -1; $current_row = -1; $current_column = -1; }
                continue;
            }

            $attr_text = $matches[3][$i][0];
            $attrs = shortcode_parse_atts($attr_text);
            if (!is_array($attrs)) { $attrs = array(); }
            $item = array(
                'tag' => $tag,
                'type' => str_replace('et_pb_', '', $tag),
                'attrs' => $attrs,
                'attr_text' => trim($attr_text),
                'raw_open' => $full[0],
                'classes' => $this->extract_classes_from_attrs($attrs),
                'offset' => intval($full[1]),
            );
            $flat[] = $item;

            if ($tag === 'et_pb_section') {
                $current_section = count($sections);
                $current_row = -1;
                $current_column = -1;
                $item['index'] = $current_section;
                $item['rows'] = array();
                $sections[] = $item;
                continue;
            }

            if ($tag === 'et_pb_row' || $tag === 'et_pb_row_inner') {
                if ($current_section < 0) {
                    $current_section = count($sections);
                    $sections[] = array(
                        'tag' => 'et_pb_section', 'type' => 'section', 'attrs' => array(), 'attr_text' => '', 'raw_open' => '',
                        'classes' => array(), 'offset' => 0, 'index' => $current_section, 'rows' => array(), 'implicit' => true,
                    );
                }
                $current_row = count($sections[$current_section]['rows']);
                $current_column = -1;
                $item['index'] = $current_row;
                $item['section_index'] = $current_section;
                $item['columns'] = array();
                $rows[] = $item;
                $sections[$current_section]['rows'][$current_row] = $item;
                continue;
            }

            if ($tag === 'et_pb_column') {
                if ($current_section < 0) {
                    $current_section = count($sections);
                    $sections[] = array('tag'=>'et_pb_section','type'=>'section','attrs'=>array(),'attr_text'=>'','raw_open'=>'','classes'=>array(),'offset'=>0,'index'=>$current_section,'rows'=>array(),'implicit'=>true);
                }
                if ($current_row < 0) {
                    $current_row = count($sections[$current_section]['rows']);
                    $row = array('tag'=>'et_pb_row','type'=>'row','attrs'=>array(),'attr_text'=>'','raw_open'=>'','classes'=>array(),'offset'=>0,'index'=>$current_row,'section_index'=>$current_section,'columns'=>array(),'implicit'=>true);
                    $rows[] = $row; $sections[$current_section]['rows'][$current_row] = $row;
                }
                $current_column = count($sections[$current_section]['rows'][$current_row]['columns']);
                $item['index'] = $current_column;
                $item['section_index'] = $current_section;
                $item['row_index'] = $current_row;
                $item['modules'] = array();
                $columns[] = $item;
                $sections[$current_section]['rows'][$current_row]['columns'][$current_column] = $item;
                continue;
            }

            // Divi often omits explicit et_pb_column shortcodes in stored content. Create an implicit column so AI export keeps a complete hierarchy.
            if ($current_section < 0) {
                $current_section = count($sections);
                $sections[] = array('tag'=>'et_pb_section','type'=>'section','attrs'=>array(),'attr_text'=>'','raw_open'=>'','classes'=>array(),'offset'=>0,'index'=>$current_section,'rows'=>array(),'implicit'=>true);
            }
            if ($current_row < 0) {
                $current_row = count($sections[$current_section]['rows']);
                $row = array('tag'=>'et_pb_row','type'=>'row','attrs'=>array(),'attr_text'=>'','raw_open'=>'','classes'=>array(),'offset'=>0,'index'=>$current_row,'section_index'=>$current_section,'columns'=>array(),'implicit'=>true);
                $rows[] = $row; $sections[$current_section]['rows'][$current_row] = $row;
            }
            if ($current_column < 0 || empty($sections[$current_section]['rows'][$current_row]['columns'])) {
                $current_column = 0;
                $col = array(
                    'tag' => 'et_pb_column', 'type' => 'column', 'attrs' => array('type' => '4_4'), 'attr_text' => '', 'raw_open' => '',
                    'classes' => array(), 'offset' => intval($full[1]), 'index' => 0, 'section_index' => $current_section, 'row_index' => $current_row,
                    'modules' => array(), 'implicit' => true,
                );
                $columns[] = $col;
                $sections[$current_section]['rows'][$current_row]['columns'][0] = $col;
            }
            $module_key = $current_section . ':' . $current_row . ':' . $current_column;
            if (!isset($row_module_counts[$module_key])) { $row_module_counts[$module_key] = 0; }
            $item['index'] = $row_module_counts[$module_key]++;
            $item['section_index'] = $current_section;
            $item['row_index'] = $current_row;
            $item['column_index'] = $current_column;
            $modules[] = $item;
            $sections[$current_section]['rows'][$current_row]['columns'][$current_column]['modules'][] = $item;
        }

        return array(
            'flat' => $flat,
            'sections' => array_values($sections),
            'rows' => array_values($rows),
            'columns' => array_values($columns),
            'modules' => array_values($modules),
            'tags' => array_values(array_unique(array_map(function($x){ return $x['tag']; }, $flat))),
            'parser_notes' => array('implicit_columns_created' => true, 'reason' => 'Divi stored content can contain modules directly inside rows; export normalizes this into section > row > column > module.'),
        );
    }

    private function extract_classes_from_attrs($attrs) {
        $classes = array();
        foreach ($attrs as $key => $value) {
            if (stripos((string) $key, 'class') !== false && is_string($value)) {
                foreach (preg_split('/\s+/', trim($value)) as $class) {
                    if ($class !== '') { $classes[] = $class; }
                }
            }
        }
        return array_values(array_unique($classes));
    }

    private function build_css_analysis($css, $parsed = null) {
        $css = is_string($css) ? $css : '';
        $clean_css = preg_replace('/\/\*.*?\*\//s', '', $css);
        $rules = array();
        $known_classes = array();
        if ($parsed && !empty($parsed['flat'])) {
            foreach ($parsed['flat'] as $item) {
                $node_id = $this->node_id_from_item($item);
                foreach (($item['classes'] ?? array()) as $class) {
                    $known_classes[$class][] = array(
                        'node_id' => $node_id,
                        'tag' => $item['tag'] ?? '',
                        'type' => $item['type'] ?? '',
                        'semantic_role' => $this->detect_semantic_role_for_item($item),
                        'section_index' => $item['section_index'] ?? null,
                        'row_index' => $item['row_index'] ?? null,
                        'column_index' => $item['column_index'] ?? null,
                        'module_index' => $item['index'] ?? null,
                    );
                }
            }
        }

        $media_blocks = array();
        if (preg_match_all('/@media\s*([^\{]+)\{((?:[^{}]+\{[^{}]*\})+)\s*\}/is', $clean_css, $media_matches, PREG_SET_ORDER)) {
            foreach ($media_matches as $media) {
                $media_blocks[] = array('query' => trim($media[1]), 'css' => $media[2]);
                $clean_css = str_replace($media[0], '', $clean_css);
            }
        }

        $parse_rule_block = function($block_css, $media_query = '') use (&$rules, $known_classes) {
            preg_match_all('/([^{}@]+)\{([^{}]+)\}/', $block_css, $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $selector = trim(preg_replace('/\s+/', ' ', $m[1]));
                $body = trim($m[2]);
                if ($selector === '' || $body === '') { continue; }
                $declarations = array();
                foreach (explode(';', $body) as $decl) {
                    if (strpos($decl, ':') === false) { continue; }
                    list($prop, $val) = array_map('trim', explode(':', $decl, 2));
                    if ($prop !== '') { $declarations[$prop] = $val; }
                }
                preg_match_all('/\.([a-zA-Z_-][a-zA-Z0-9_-]*)/', $selector, $class_matches);
                preg_match_all('/#([a-zA-Z_-][a-zA-Z0-9_-]*)/', $selector, $id_matches);
                $classes = array_values(array_unique($class_matches[1] ?? array()));
                $ids = array_values(array_unique($id_matches[1] ?? array()));
                $matched_nodes = array();
                foreach ($classes as $class) {
                    if (!empty($known_classes[$class])) {
                        foreach ($known_classes[$class] as $node) { $matched_nodes[] = $node + array('matched_by' => 'class', 'value' => $class); }
                    }
                }
                $rules[] = array(
                    'index' => count($rules),
                    'selector' => $selector,
                    'media_query' => $media_query,
                    'classes' => $classes,
                    'ids' => $ids,
                    'matched_builder_nodes' => $matched_nodes,
                    'node_ids' => array_values(array_unique(array_filter(array_map(function($n){ return $n['node_id'] ?? ''; }, $matched_nodes)))),
                    'declarations' => $declarations,
                    'declaration_count' => count($declarations),
                    'property_groups' => $this->group_css_declarations($declarations),
                    'raw' => $selector . ' {' . $body . '}',
                );
            }
        };

        $parse_rule_block($clean_css, '');
        foreach ($media_blocks as $media) { $parse_rule_block($media['css'], $media['query']); }

        return array(
            'schema' => 'diviforge-css-analysis/v2',
            'line_count' => trim($css) === '' ? 0 : substr_count(trim($css), "\n") + 1,
            'rule_count' => count($rules),
            'media_query_count' => count($media_blocks),
            'rules' => $rules,
            'class_index' => array_keys($known_classes),
            'notes' => 'Maps CSS selectors, declarations, media queries and property groups back to detected Divi shortcode classes and builder node ids where possible. Full CSS remains in page.css.',
        );
    }

    private function group_css_declarations($declarations) {
        $groups = array('layout'=>array(), 'spacing'=>array(), 'typography'=>array(), 'color'=>array(), 'background'=>array(), 'border'=>array(), 'shadow'=>array(), 'motion'=>array(), 'other'=>array());
        foreach ((array) $declarations as $prop => $value) {
            $p = strtolower((string) $prop);
            if (preg_match('/display|position|grid|flex|width|height|overflow|z-index|align|justify/', $p)) { $groups['layout'][$prop] = $value; }
            elseif (preg_match('/margin|padding|gap|top|right|bottom|left/', $p)) { $groups['spacing'][$prop] = $value; }
            elseif (preg_match('/font|line-height|letter-spacing|text-|white-space/', $p)) { $groups['typography'][$prop] = $value; }
            elseif (preg_match('/color|opacity/', $p)) { $groups['color'][$prop] = $value; }
            elseif (preg_match('/background|gradient|image/', $p)) { $groups['background'][$prop] = $value; }
            elseif (preg_match('/border|radius|outline/', $p)) { $groups['border'][$prop] = $value; }
            elseif (preg_match('/shadow|filter/', $p)) { $groups['shadow'][$prop] = $value; }
            elseif (preg_match('/transition|animation|transform/', $p)) { $groups['motion'][$prop] = $value; }
            else { $groups['other'][$prop] = $value; }
        }
        return array_filter($groups);
    }

    private function build_class_map($content, $css, $parsed = null) {
        $classes = array();
        if ($parsed && !empty($parsed['flat'])) {
            foreach ($parsed['flat'] as $item) {
                foreach (($item['classes'] ?? array()) as $class) {
                    $classes[$class]['source'][] = 'divi_shortcode';
                    $classes[$class]['used_by'][] = $item['tag'];
                }
            }
        }
        preg_match_all('/\.([a-zA-Z_-][a-zA-Z0-9_-]*)/', (string) $css, $css_matches);
        foreach (($css_matches[1] ?? array()) as $class) {
            $classes[$class]['source'][] = 'page_css';
        }
        ksort($classes);
        $out = array();
        foreach ($classes as $class => $data) {
            $out[] = array(
                'class' => $class,
                'sources' => array_values(array_unique($data['source'] ?? array())),
                'used_by' => array_values(array_unique($data['used_by'] ?? array())),
            );
        }
        return $out;
    }

    private function export_relevant_page_meta($page_id) {
        $meta = get_post_meta($page_id);
        $out = array();
        foreach ($meta as $key => $values) {
            if (strpos($key, '_edit_') === 0 || strpos($key, '_wp_') === 0) { continue; }
            if (stripos($key, 'divi') !== false || stripos($key, 'et_pb') !== false || stripos($key, 'css') !== false || stripos($key, 'script') !== false || stripos($key, 'layout') !== false) {
                $clean_values = array();
                foreach ((array) $values as $value) { $clean_values[] = maybe_unserialize($value); }
                $out[$key] = $clean_values;
            }
        }
        return $out;
    }

    private function extract_scripts_from_content_and_meta($page, $content) {
        $scripts = array();
        preg_match_all('/<script\b[^>]*>.*?<\/script>/is', (string) $content, $matches);
        foreach (($matches[0] ?? array()) as $script) { $scripts[] = $script; }
        $meta = get_post_meta($page->ID);
        foreach ($meta as $key => $values) {
            if (stripos($key, 'script') !== false || stripos($key, 'js') !== false) {
                foreach ((array) $values as $value) {
                    $value = maybe_unserialize($value);
                    if (is_scalar($value) && trim((string) $value) !== '') {
                        $scripts[] = "/* Meta: " . $key . " */\n" . (string) $value;
                    }
                }
            }
        }
        return implode("\n\n", $scripts);
    }

    private function collect_export_assets($content, $css, $parsed = null) {
        $source = (string) $content . "\n" . (string) $css;
        preg_match_all('/wp-image-([0-9]+)/', $source, $image_id_matches);
        foreach (($image_id_matches[1] ?? array()) as $img_id) {
            $u = wp_get_attachment_url(absint($img_id));
            if ($u) { $source .= "\n" . $u; }
        }
        preg_match_all('/https?:\/\/[^\s\"\'\)]+\.(?:png|jpe?g|webp|gif|svg|mp4|webm)(?:\?[^\s\"\'\)]*)?/i', $source, $url_matches);
        preg_match_all('/(?:src|background_image|image|href)=["\']?([^"\'\s\)]+\.(?:png|jpe?g|webp|gif|svg|mp4|webm)(?:\?[^"\'\s\)]*)?)/i', $source, $rel_matches);
        preg_match_all('/url\(\s*["\']?([^"\'\)]+\.(?:png|jpe?g|webp|gif|svg|mp4|webm)(?:\?[^"\'\)]*)?)["\']?\s*\)/i', $source, $css_url_matches);
        $urls = array_merge($url_matches[0] ?? array(), $rel_matches[1] ?? array(), $css_url_matches[1] ?? array());
        $urls = array_values(array_unique(array_filter(array_map('html_entity_decode', $urls))));
        $uploads = wp_get_upload_dir();
        $manifest = array(); $files = array(); $readme_lines = array('# Assets folder', '', 'Detected page image assets. Local WordPress uploads are copied into `assets/images/` when possible.', '');
        foreach ($urls as $url) {
            $clean_url = strtok($url, '?');
            if ($clean_url && strpos($clean_url, '//') === 0) { $clean_url = (is_ssl() ? 'https:' : 'http:') . $clean_url; }
            if ($clean_url && strpos($clean_url, '/wp-content/') === 0) { $clean_url = home_url($clean_url); }
            $local_path = '';
            if (!empty($uploads['baseurl']) && strpos($clean_url, $uploads['baseurl']) === 0) {
                $relative = ltrim(substr($clean_url, strlen($uploads['baseurl'])), '/');
                $local_path = trailingslashit($uploads['basedir']) . $relative;
            }
            $attachment_id = function_exists('attachment_url_to_postid') ? attachment_url_to_postid($clean_url) : 0;
            if ($attachment_id) {
                $attached = get_attached_file($attachment_id);
                if ($attached) { $local_path = $attached; }
            }
            $basename = sanitize_file_name(basename(parse_url($clean_url, PHP_URL_PATH)));
            if ($basename === '') { $basename = 'asset-' . md5($clean_url); }
            $zip_path = 'assets/images/' . $basename;
            $entry = array('url' => $url, 'filename' => $basename, 'exported' => false, 'zip_path' => $zip_path, 'attachment_id' => absint($attachment_id), 'alt' => $attachment_id ? get_post_meta($attachment_id, '_wp_attachment_image_alt', true) : '', 'caption' => $attachment_id ? wp_get_attachment_caption($attachment_id) : '', 'mime' => '', 'width' => 0, 'height' => 0, 'filesize' => 0, 'hash' => '', 'used_by' => $this->find_asset_usage($url, $parsed, $content, $css));
            if ($local_path && file_exists($local_path) && is_readable($local_path)) {
                $entry['exported'] = true;
                $entry['filesize'] = filesize($local_path);
                $entry['hash'] = md5_file($local_path);
                $entry['mime'] = function_exists('mime_content_type') ? mime_content_type($local_path) : '';
                $size = @getimagesize($local_path);
                if (is_array($size)) { $entry['width'] = intval($size[0]); $entry['height'] = intval($size[1]); }
                $files[] = array('local_path' => $local_path, 'zip_path' => $zip_path, 'url' => $url);
            }
            $manifest[] = $entry;
            $readme_lines[] = '- ' . $basename . ' — ' . ($entry['exported'] ? 'exported' : 'URL only') . ' — ' . $url;
        }
        return array('manifest' => $manifest, 'files' => $files, 'readme' => implode("\n", $readme_lines) . "\n");
    }


    private function node_id_from_item($item) {
        $type = $item['type'] ?? '';
        $section = intval($item['section_index'] ?? -1) + 1;
        $row = intval($item['row_index'] ?? -1) + 1;
        $column = intval($item['column_index'] ?? -1) + 1;
        $index = intval($item['index'] ?? 0) + 1;
        if (($item['tag'] ?? '') === 'et_pb_section' || $type === 'section') { return 'section-' . $index; }
        if (($item['tag'] ?? '') === 'et_pb_row' || ($item['tag'] ?? '') === 'et_pb_row_inner' || $type === 'row') { return 'section-' . $section . '-row-' . $index; }
        if (($item['tag'] ?? '') === 'et_pb_column' || $type === 'column') { return 'section-' . $section . '-row-' . $row . '-column-' . $index; }
        return 'section-' . $section . '-row-' . $row . '-column-' . $column . '-module-' . $index;
    }

    private function detect_semantic_role_for_item($item) {
        $tag = strtolower((string) ($item['tag'] ?? ''));
        $type = strtolower((string) ($item['type'] ?? ''));
        $attrs = !empty($item['attrs']) && is_array($item['attrs']) ? $item['attrs'] : array();
        $haystack = $tag . ' ' . $type . ' ' . strtolower(wp_json_encode($attrs));
        $map = array(
            'hero' => '/hero|intro|banner|above[-_ ]the[-_ ]fold|headline/',
            'cta' => '/cta|call[-_ ]to[-_ ]action|button|start|contact|quote|offerte/',
            'faq' => '/faq|accordion|toggle|veelgestelde|questions/',
            'pricing' => '/pricing|price|pakket|package|tarief|plan/',
            'testimonial' => '/testimonial|review|quote|client|klant/',
            'features' => '/feature|benefit|service|diensten|cards|blurb/',
            'gallery' => '/gallery|portfolio|image|media|logo/',
            'footer' => '/footer|copyright|legal|privacy/',
            'navigation' => '/menu|nav|header/',
            'form' => '/form|contact_form|email|subscribe/',
        );
        foreach ($map as $role => $regex) { if (preg_match($regex, $haystack)) { return $role; } }
        return 'content';
    }

    private function build_semantic_structure($parsed, $css_analysis = array()) {
        $roles = array();
        $sequence = array();
        foreach (($parsed['sections'] ?? array()) as $section) {
            $role = $this->detect_semantic_role_for_item($section);
            $node_id = $this->node_id_from_item($section);
            if (empty($roles[$role])) { $roles[$role] = array(); }
            $roles[$role][] = array('node_id' => $node_id, 'type' => 'section', 'index' => intval($section['index'] ?? 0), 'classes' => $section['classes'] ?? array());
            $sequence[] = array('node_id' => $node_id, 'role' => $role, 'position' => intval($section['index'] ?? 0));
        }
        foreach (($parsed['modules'] ?? array()) as $module) {
            $role = $this->detect_semantic_role_for_item($module);
            if ($role === 'content') { continue; }
            if (empty($roles[$role])) { $roles[$role] = array(); }
            $roles[$role][] = array('node_id' => $this->node_id_from_item($module), 'type' => 'module', 'divi_type' => $module['type'] ?? '', 'classes' => $module['classes'] ?? array());
        }
        return array(
            'schema' => 'diviforge-semantic-structure/v1',
            'detected_roles' => array_keys($roles),
            'roles' => $roles,
            'section_sequence' => $sequence,
            'notes' => 'Semantic roles are automatically inferred from Divi tags, classes, labels and attributes. Treat as AI guidance, not as a destructive source of truth.',
        );
    }

    private function build_statistics_export($content, $css, $parsed, $assets, $css_analysis, $semantic_structure) {
        $module_types = array();
        foreach (($parsed['modules'] ?? array()) as $module) {
            $type = $module['type'] ?? 'unknown';
            if (!isset($module_types[$type])) { $module_types[$type] = 0; }
            $module_types[$type]++;
        }
        ksort($module_types);
        return array(
            'schema' => 'diviforge-statistics/v1',
            'sections' => count($parsed['sections'] ?? array()),
            'rows' => count($parsed['rows'] ?? array()),
            'columns' => count($parsed['columns'] ?? array()),
            'modules' => count($parsed['modules'] ?? array()),
            'module_types' => $module_types,
            'shortcodes' => count($parsed['flat'] ?? array()),
            'css_lines' => trim((string) $css) === '' ? 0 : substr_count(trim((string) $css), "\n") + 1,
            'css_rules' => intval($css_analysis['rule_count'] ?? 0),
            'css_media_queries' => intval($css_analysis['media_query_count'] ?? 0),
            'assets_detected' => is_array($assets) && isset($assets['manifest']) ? count($assets['manifest']) : 0,
            'assets_exported' => is_array($assets) && isset($assets['files']) ? count($assets['files']) : 0,
            'semantic_roles_detected' => $semantic_structure['detected_roles'] ?? array(),
            'content_characters' => strlen((string) $content),
        );
    }

    private function build_relation_model($parsed, $css_analysis, $assets, $semantic_structure) {
        $nodes = array();
        foreach (($parsed['flat'] ?? array()) as $item) {
            $node_id = $this->node_id_from_item($item);
            $nodes[$node_id] = array(
                'node_id' => $node_id,
                'tag' => $item['tag'] ?? '',
                'type' => $item['type'] ?? '',
                'classes' => $item['classes'] ?? array(),
                'semantic_role' => $this->detect_semantic_role_for_item($item),
                'css_rules' => array(),
                'assets' => array(),
            );
        }
        foreach (($css_analysis['rules'] ?? array()) as $rule) {
            foreach (($rule['node_ids'] ?? array()) as $node_id) {
                if (!isset($nodes[$node_id])) { continue; }
                $nodes[$node_id]['css_rules'][] = array('selector' => $rule['selector'] ?? '', 'media_query' => $rule['media_query'] ?? '', 'declaration_count' => $rule['declaration_count'] ?? 0);
            }
        }
        foreach (($assets['manifest'] ?? array()) as $asset) {
            foreach (($asset['used_by'] ?? array()) as $usage) {
                $node_id = $usage['node_id'] ?? '';
                if ($node_id && isset($nodes[$node_id])) { $nodes[$node_id]['assets'][] = array('filename' => $asset['filename'] ?? '', 'url' => $asset['url'] ?? '', 'source' => $usage['source'] ?? ''); }
            }
        }
        return array(
            'schema' => 'diviforge-relations/v1',
            'nodes' => array_values($nodes),
            'semantic_structure_file' => 'semantic-structure.json',
            'css_analysis_file' => 'css/analysis.json',
            'assets_manifest_file' => 'assets/assets.json',
            'notes' => 'Relation model joins builder nodes, CSS rules, detected classes, semantic roles and exported assets so AI can reason about what belongs together.',
        );
    }

    private function find_asset_usage($url, $parsed = null, $content = '', $css = '') {
        $usage = array();
        $basename = basename(parse_url((string) $url, PHP_URL_PATH));
        foreach (($parsed['flat'] ?? array()) as $item) {
            $attrs_json = wp_json_encode($item['attrs'] ?? array());
            if (($url && strpos($attrs_json, $url) !== false) || ($basename && strpos($attrs_json, $basename) !== false)) {
                $usage[] = array('source' => 'divi_attrs', 'node_id' => $this->node_id_from_item($item), 'tag' => $item['tag'] ?? '', 'type' => $item['type'] ?? '');
            }
        }
        if ($basename && strpos((string) $css, $basename) !== false) { $usage[] = array('source' => 'page_css', 'node_id' => '', 'selector_hint' => 'see css/analysis.json'); }
        if ($basename && strpos((string) $content, $basename) !== false && empty($usage)) { $usage[] = array('source' => 'raw_content', 'node_id' => ''); }
        return $usage;
    }

    private function build_export_page_html($page, $content, $css) {
        $title = get_the_title($page->ID);
        $safe_content = esc_html($content);
        $safe_css = is_string($css) ? $css : '';
        $body = '<main class="diviforge-exported-page"><section class="diviforge-export-context"><h1>' . esc_html($title) . '</h1><p>This HTML file is context for AI. The canonical import source remains layout.json.</p><h2>Raw Divi content</h2><pre>' . $safe_content . '</pre></section></main>';
        return "<!doctype html>\n<html lang=\"nl\">\n<head>\n<meta charset=\"utf-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n<title>" . esc_html($title) . " - DiviForge Export</title>\n<style>body{font-family:Inter,Arial,sans-serif;margin:0;background:#f7f8fc;color:#101828}.diviforge-exported-page{max-width:1120px;margin:0 auto;padding:40px 22px}.diviforge-export-context{background:#fff;border:1px solid #e5e7eb;border-radius:24px;padding:28px;box-shadow:0 18px 45px rgba(16,24,40,.08)}pre{white-space:pre-wrap;word-break:break-word;background:#0f172a;color:#e5e7eb;border-radius:18px;padding:20px;overflow:auto}\n" . $safe_css . "\n</style>\n</head>\n<body>\n" . $body . "\n</body>\n</html>";
    }

    private function build_design_tokens($content, $css, $parsed = null) {
        $css = is_string($css) ? $css : '';
        preg_match_all('/#[0-9a-fA-F]{3,8}\b|rgba?\([^\)]+\)|hsla?\([^\)]+\)/', $css, $color_matches);
        preg_match_all('/font-family\s*:\s*([^;{}]+)/i', $css, $font_matches);
        preg_match_all('/border-radius\s*:\s*([^;{}]+)/i', $css, $radius_matches);
        preg_match_all('/(?:margin|padding|gap|top|right|bottom|left)\s*:\s*([^;{}]+)/i', $css, $spacing_matches);
        preg_match_all('/box-shadow\s*:\s*([^;{}]+)/i', $css, $shadow_matches);
        preg_match_all('/(?:linear-gradient|radial-gradient)\([^;{}]+\)/i', $css, $gradient_matches);
        preg_match_all('/transition\s*:\s*([^;{}]+)/i', $css, $transition_matches);
        preg_match_all('/animation\s*:\s*([^;{}]+)/i', $css, $animation_matches);
        preg_match_all('/@media\s*([^\{]+)/i', $css, $media_matches);
        $colors = array_values(array_unique(array_slice(array_map('trim', $color_matches[0] ?? array()), 0, 80)));
        $fonts = array();
        foreach (($font_matches[1] ?? array()) as $font) {
            foreach (explode(',', $font) as $one) {
                $one = trim(str_replace(array('"', "'"), '', $one));
                if ($one !== '') { $fonts[] = $one; }
            }
        }
        $fonts = array_values(array_unique(array_slice($fonts, 0, 30)));
        $radius = array_values(array_unique(array_slice(array_map('trim', $radius_matches[1] ?? array()), 0, 40)));
        $spacing = array_values(array_unique(array_slice(array_map('trim', $spacing_matches[1] ?? array()), 0, 80)));
        $shadows = array_values(array_unique(array_slice(array_map('trim', $shadow_matches[1] ?? array()), 0, 40)));
        $gradients = array_values(array_unique(array_slice(array_map('trim', $gradient_matches[0] ?? array()), 0, 40)));
        $transitions = array_values(array_unique(array_slice(array_map('trim', $transition_matches[1] ?? array()), 0, 30)));
        $animations = array_values(array_unique(array_slice(array_map('trim', $animation_matches[1] ?? array()), 0, 30)));
        $breakpoints = array_values(array_unique(array_slice(array_map('trim', $media_matches[1] ?? array()), 0, 30)));
        $divi_colors = array(); $divi_fonts = array(); $divi_spacing = array(); $divi_radius = array();
        foreach (($parsed['flat'] ?? array()) as $item) {
            foreach (($item['attrs'] ?? array()) as $key => $value) {
                if (!is_scalar($value)) { continue; }
                $k = strtolower((string) $key); $v = trim((string) $value);
                if ($v === '') { continue; }
                if (preg_match('/#[0-9a-fA-F]{3,8}\b|rgba?\([^\)]+\)|hsla?\([^\)]+\)/', $v)) { $divi_colors[] = $v; }
                if (strpos($k, 'font') !== false) { $divi_fonts[] = str_replace(array('\"', "'"), '', $v); }
                if (strpos($k, 'padding') !== false || strpos($k, 'margin') !== false || strpos($k, 'gap') !== false) { $divi_spacing[] = $v; }
                if (strpos($k, 'radius') !== false || strpos($k, 'rounded') !== false) { $divi_radius[] = $v; }
            }
        }
        $colors = array_values(array_unique(array_slice(array_merge($colors, $divi_colors), 0, 100)));
        $fonts = array_values(array_unique(array_slice(array_merge($fonts, $divi_fonts), 0, 50)));
        $spacing = array_values(array_unique(array_slice(array_merge($spacing, $divi_spacing), 0, 120)));
        $radius = array_values(array_unique(array_slice(array_merge($radius, $divi_radius), 0, 60)));
        return array(
            'schema' => 'diviforge-design/v2',
            'detected_at' => current_time('mysql'),
            'colors' => $colors,
            'fonts' => $fonts,
            'radius' => $radius,
            'spacing_values' => $spacing,
            'shadows' => $shadows,
            'gradients' => $gradients,
            'transitions' => $transitions,
            'animations' => $animations,
            'breakpoints' => $breakpoints,
            'stats' => $this->estimate_export_stats($content, $css, $parsed),
            'notes' => 'Design tokens are detected from page.css and provided as AI guidance. They should be preserved or deliberately evolved based on the user request.',
        );
    }

    private function estimate_export_stats($content, $css, $parsed = null, $assets = null) {
        $content = is_string($content) ? $content : '';
        $css = is_string($css) ? $css : '';
        return array(
            'sections' => $parsed && isset($parsed['sections']) ? count($parsed['sections']) : preg_match_all('/\[et_pb_section\b/i', $content),
            'rows' => $parsed && isset($parsed['rows']) ? count($parsed['rows']) : preg_match_all('/\[et_pb_row\b/i', $content),
            'columns' => $parsed && isset($parsed['columns']) ? count($parsed['columns']) : preg_match_all('/\[et_pb_column\b/i', $content),
            'modules' => $parsed && isset($parsed['modules']) ? count($parsed['modules']) : preg_match_all('/\[et_pb_(?!section\b|row\b|column\b)([a-z0-9_]+)/i', $content),
            'images' => is_array($assets) && isset($assets['manifest']) ? count($assets['manifest']) : preg_match_all('/\.(png|jpe?g|webp|gif|svg)(\?|\"|\'|\s|$)/i', $content),
            'exported_images' => is_array($assets) && isset($assets['files']) ? count($assets['files']) : 0,
            'css_lines' => trim($css) === '' ? 0 : substr_count(trim($css), "\n") + 1,
            'shortcodes' => $parsed && isset($parsed['flat']) ? count($parsed['flat']) : preg_match_all('/\[et_pb_[a-z0-9_]+\b/i', $content),
        );
    }

    private function build_chatgpt_prompt($manifest, $request = '') {
        $title = !empty($manifest['title']) ? $manifest['title'] : 'DiviForge exported page';
        $request = trim((string) $request);
        if ($request === '') {
            $request = 'Analyseer deze geëxporteerde DiviForge-pagina en lever een verbeterd DiviForge package terug met manifest.json, layout.json en page.css.';
        }
        return "Ik lever een DiviForge export-package aan van een bestaande WordPress/Divi pagina.\n\nPagina: " . $title . "\n\nMijn vraag aan ChatGPT:\n" . $request . "\n\nGebruik de bestanden in de ZIP als bron. Gebruik vooral builder-tree.json, layout.json en page.css als canonieke bron, page.html als visuele/contextuele hulp en design.json voor stijltokens. Behoud het DiviForge package-format. Lever als resultaat een nieuw importeerbaar DiviForge package terug met minimaal manifest.json, layout.json en page.css. Als je layout wijzigt, zorg dat layout.json weer door DiviForge geïmporteerd kan worden. Als je CSS wijzigt, plaats alle pagina-CSS in page.css.";
    }

    private function build_chatgpt_instructions($manifest, $request = '') {
        $title = !empty($manifest['title']) ? $manifest['title'] : 'DiviForge exported page';
        $stats = !empty($manifest['stats']) && is_array($manifest['stats']) ? $manifest['stats'] : array();
        $request = trim((string) $request);
        if ($request === '') { $request = 'Verbeter deze pagina en lever een nieuw DiviForge package terug.'; }
        return "# Instructie voor ChatGPT\n\nJe ontvangt een DiviForge export-package van een bestaande WordPress/Divi pagina.\n\n## Wat zit er in dit package?\n\n- `manifest.json` bevat metadata over de pagina, bronpagina, exportdatum, statistieken en de vraag van de gebruiker.\n- `layout.json` bevat de Divi layout. Bij bestaande pagina's kan dit raw Divi shortcode content zijn in het veld `divi_content`.\n- `page.css` bevat de Page Custom CSS die bij deze pagina hoort.\n- `prompt.txt` bevat de concrete vraag van de gebruiker.
- `page.html` geeft extra HTML-context, maar is niet de importbron.
- `design.json` bevat automatisch gedetecteerde stijltokens uit page.css.
- `preview/` en `assets/` zijn voorbereid voor screenshots en assets in latere releases.\n\n## Bronpagina\n\n- Titel: " . $title . "\n- Secties: " . intval($stats['sections'] ?? 0) . "\n- Modules: " . intval($stats['modules'] ?? 0) . "\n- CSS regels: " . intval($stats['css_lines'] ?? 0) . "\n\n## Vraag van de gebruiker\n\n" . $request . "\n\n## Verwachte output\n\nLever een nieuw DiviForge package terug dat opnieuw geïmporteerd kan worden in DiviForge. Het package bevat minimaal:\n\n1. `manifest.json`\n2. `layout.json`\n3. `page.css`\n\nGebruik geen losse uitleg als eindresultaat wanneer de gebruiker om een package vraagt; lever de nieuwe packagebestanden op. Behoud waar mogelijk bestaande content en verbeter alleen wat nodig is voor de vraag.\n";
    }

    public function handle_import() {
        check_admin_referer('diviforge_import_package');
        if (empty($_FILES['package'])) { wp_die(esc_html__('No package file was uploaded.', 'diviforge')); }
        $target_page_id = !empty($_POST['target_page_id']) ? absint($_POST['target_page_id']) : 0;
        $mode = $target_page_id ? 'update' : 'new';
        if ($target_page_id && !current_user_can('edit_post', $target_page_id)) { wp_die(esc_html__('You are not allowed to update this page.', 'diviforge')); }
        $page_title = (!$target_page_id && !empty($_POST['page_title'])) ? sanitize_text_field(wp_unslash($_POST['page_title'])) : '';
        $result = DiviForge_Importer::import_upload($_FILES['package'], $target_page_id, $page_title);
        if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message())); }
        wp_safe_redirect(admin_url('admin.php?page=diviforge-pages&imported=1&mode=' . $mode . '&page_id=' . absint($result['post_id'])));
        exit;
    }

    public function handle_reapply_css() {
        $page_id = !empty($_GET['page_id']) ? absint($_GET['page_id']) : 0;
        if (!$page_id || !current_user_can('edit_post', $page_id)) { wp_die(esc_html__('You are not allowed to update CSS for this page.', 'diviforge')); }
        check_admin_referer('diviforge_reapply_css_' . $page_id);
        $result = DiviForge_CSS_Importer::reapply_stored_css($page_id);
        if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message())); }
        wp_safe_redirect(admin_url('admin.php?page=diviforge-pages&css_reapplied=1&page_id=' . $page_id));
        exit;
    }

    public function handle_delete_page() {
        $page_id = !empty($_GET['page_id']) ? absint($_GET['page_id']) : 0;
        if (!$page_id || !current_user_can('delete_post', $page_id)) { wp_die(esc_html__('You are not allowed to delete this page.', 'diviforge')); }
        check_admin_referer('diviforge_delete_page_' . $page_id);
        wp_trash_post($page_id);
        wp_safe_redirect(admin_url('admin.php?page=diviforge-pages&page_deleted=1'));
        exit;
    }

    public function components() {
        $this->header(__('Components', 'diviforge'), __('Herbruikbare design componenten voor je Divi websites. In deze MVP-fase zijn dit voorbereide component-templates voor Sprint 5.', 'diviforge'), 'components');

        echo '<section class="df-components-intro df-card">';
        echo '<div><p class="df-kicker">' . esc_html__('Sprint 4 UX polish', 'diviforge') . '</p><h2>' . esc_html__('Component Library foundation', 'diviforge') . '</h2><p>' . esc_html__('Een visueel overzicht van de componenten die straks importeerbaar worden. Deze versie houdt het bewust simpel: betere headers, betere cards en duidelijke status.', 'diviforge') . '</p></div>';
        echo '<span class="df-pill df-warn">' . esc_html__('MVP preview', 'diviforge') . '</span>';
        echo '</section>';

        $components = array(
            array('Hero Apple', 'hero-apple', __('Premium hero section with minimalist design inspired by Apple.', 'diviforge'), 'Hero', '7 modules', 'Apple, Premium'),
            array('Hero SaaS', 'hero-saas', __('SaaS style hero with dashboard rhythm, gradients and strong call-to-action.', 'diviforge'), 'Hero', '9 modules', 'SaaS, Landingpage'),
            array('Hero Medical', 'hero-medical', __('Trusted and clean hero section for medical and healthcare websites.', 'diviforge'), 'Hero', '6 modules', 'Medical, Trust'),
            array('CTA Premium', 'cta-premium', __('High converting call-to-action section with modern visual impact.', 'diviforge'), 'CTA', '4 modules', 'Conversion'),
            array('FAQ Clean', 'faq-clean', __('Clean and minimal FAQ section with accordion-style visual rhythm.', 'diviforge'), 'FAQ', '5 modules', 'Support, UX'),
            array('Footer Simple', 'footer-simple', __('Simple and elegant footer with columns, links and social proof.', 'diviforge'), 'Footer', '8 modules', 'Navigation'),
        );
        echo '<div class="df-package-grid df-component-grid df-component-grid-v243">';
        foreach ($components as $component) {
            echo '<article class="df-package-card df-component-card df-component-card-v243 df-component-' . esc_attr($component[1]) . '">';
            echo '<div class="df-component-art df-component-art-v243">' . $this->component_svg($component[1]) . '</div>';
            echo '<div class="df-component-body">';
            echo '<div class="df-component-title-row"><p class="df-kicker">' . esc_html($component[3]) . '</p><span class="df-component-status">' . esc_html__('Coming soon', 'diviforge') . '</span></div>';
            echo '<h2>' . esc_html($component[0]) . '</h2><p>' . esc_html($component[2]) . '</p>';
            echo '<div class="df-component-meta"><span><span class="dashicons dashicons-screenoptions"></span>' . esc_html($component[4]) . '</span><span><span class="dashicons dashicons-tag"></span>' . esc_html($component[5]) . '</span></div>';
            echo '<div class="df-card-actions"><button class="df-btn df-btn-soft" disabled><span class="dashicons dashicons-visibility"></span>' . esc_html__('Preview soon', 'diviforge') . '</button><button class="df-btn df-btn-primary" disabled><span class="dashicons dashicons-download"></span>' . esc_html__('Import soon', 'diviforge') . '</button></div>';
            echo '</div></article>';
        }
        echo '</div>';
        $this->footer();
    }

    private function component_svg($type) {
        $svgs = array(
            'hero-apple' => '<svg viewBox="0 0 560 230" role="img" aria-label="Apple style hero preview"><defs><linearGradient id="dfg1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#28164f"/><stop offset=".52" stop-color="#5b2de1"/><stop offset="1" stop-color="#111827"/></linearGradient><filter id="dfshadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="18" stdDeviation="18" flood-color="#000" flood-opacity=".22"/></filter></defs><rect width="560" height="230" rx="26" fill="url(#dfg1)"/><circle cx="468" cy="48" r="92" fill="#fff" opacity=".08"/><circle cx="86" cy="40" r="72" fill="#c4b5fd" opacity=".18"/><rect x="42" y="56" width="172" height="18" rx="9" fill="#fff" opacity=".95"/><rect x="42" y="92" width="250" height="15" rx="7" fill="#fff" opacity=".62"/><rect x="42" y="124" width="198" height="15" rx="7" fill="#fff" opacity=".32"/><rect x="42" y="164" width="94" height="28" rx="14" fill="#fff" opacity=".92"/><g filter="url(#dfshadow)"><rect x="320" y="48" width="164" height="116" rx="24" fill="#fff" opacity=".16" stroke="#fff" stroke-opacity=".24"/><rect x="348" y="80" width="116" height="12" rx="6" fill="#fff" opacity=".55"/><rect x="348" y="108" width="82" height="12" rx="6" fill="#fff" opacity=".25"/></g></svg>',
            'hero-saas' => '<svg viewBox="0 0 560 230" role="img" aria-label="SaaS hero preview"><defs><linearGradient id="dfg2" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#063047"/><stop offset=".5" stop-color="#0ea5e9"/><stop offset="1" stop-color="#065f73"/></linearGradient></defs><rect width="560" height="230" rx="26" fill="url(#dfg2)"/><path d="M0 180 C100 120 160 235 260 170 S430 110 560 175 V230 H0Z" fill="#fff" opacity=".10"/><rect x="42" y="50" width="172" height="18" rx="9" fill="#fff"/><rect x="42" y="86" width="230" height="13" rx="7" fill="#fff" opacity=".62"/><rect x="42" y="116" width="178" height="13" rx="7" fill="#fff" opacity=".35"/><rect x="336" y="48" width="154" height="122" rx="24" fill="#0f172a" opacity=".34" stroke="#fff" stroke-opacity=".18"/><rect x="360" y="78" width="34" height="58" rx="10" fill="#67e8f9"/><rect x="406" y="98" width="34" height="38" rx="10" fill="#22c55e"/><rect x="452" y="68" width="34" height="68" rx="10" fill="#a7f3d0"/></svg>',
            'hero-medical' => '<svg viewBox="0 0 560 230" role="img" aria-label="Medical hero preview"><defs><linearGradient id="dfg3" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#061a3a"/><stop offset=".56" stop-color="#2563eb"/><stop offset="1" stop-color="#082f6f"/></linearGradient></defs><rect width="560" height="230" rx="26" fill="url(#dfg3)"/><polyline points="34,154 90,154 108,118 126,182 150,138 174,154 246,154" fill="none" stroke="#bfdbfe" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" opacity=".75"/><rect x="42" y="54" width="180" height="16" rx="8" fill="#fff" opacity=".9"/><rect x="42" y="88" width="226" height="13" rx="7" fill="#fff" opacity=".45"/><circle cx="404" cy="108" r="58" fill="#eff6ff" opacity=".92"/><rect x="390" y="72" width="28" height="72" rx="7" fill="#2563eb"/><rect x="368" y="94" width="72" height="28" rx="7" fill="#2563eb"/></svg>',
            'cta-premium' => '<svg viewBox="0 0 560 230" role="img" aria-label="Premium CTA preview"><defs><linearGradient id="dfg4" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#571c78"/><stop offset=".55" stop-color="#a21caf"/><stop offset="1" stop-color="#5b21b6"/></linearGradient></defs><rect width="560" height="230" rx="26" fill="url(#dfg4)"/><circle cx="438" cy="106" r="90" fill="#f0abfc" opacity=".22"/><rect x="44" y="58" width="200" height="18" rx="9" fill="#fff"/><rect x="44" y="96" width="260" height="13" rx="7" fill="#fff" opacity=".58"/><rect x="44" y="126" width="214" height="13" rx="7" fill="#fff" opacity=".34"/><rect x="44" y="166" width="126" height="30" rx="15" fill="#fff" opacity=".95"/><path d="M421 48 L449 98 L505 114 L449 132 L421 182 L394 132 L338 114 L394 98Z" fill="#fdf4ff" opacity=".86"/></svg>',
            'faq-clean' => '<svg viewBox="0 0 560 230" role="img" aria-label="FAQ preview"><defs><linearGradient id="dfg5" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#064e55"/><stop offset=".58" stop-color="#14b8a6"/><stop offset="1" stop-color="#0f766e"/></linearGradient></defs><rect width="560" height="230" rx="26" fill="url(#dfg5)"/><rect x="42" y="48" width="294" height="28" rx="14" fill="#fff" opacity=".9"/><rect x="42" y="96" width="360" height="30" rx="15" fill="#fff" opacity=".42"/><rect x="42" y="146" width="320" height="30" rx="15" fill="#fff" opacity=".28"/><circle cx="438" cy="94" r="52" fill="#ecfdf5" opacity=".92"/><text x="421" y="116" font-size="68" font-family="Arial" font-weight="700" fill="#0f766e">?</text></svg>',
            'footer-simple' => '<svg viewBox="0 0 560 230" role="img" aria-label="Footer preview"><defs><linearGradient id="dfg6" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#0f172a"/><stop offset=".6" stop-color="#1e3a8a"/><stop offset="1" stop-color="#334155"/></linearGradient></defs><rect width="560" height="230" rx="26" fill="url(#dfg6)"/><rect x="42" y="48" width="118" height="18" rx="9" fill="#fff"/><rect x="42" y="88" width="92" height="12" rx="6" fill="#fff" opacity=".45"/><rect x="42" y="118" width="120" height="12" rx="6" fill="#fff" opacity=".28"/><rect x="238" y="50" width="82" height="12" rx="6" fill="#fff" opacity=".72"/><rect x="238" y="84" width="106" height="10" rx="5" fill="#fff" opacity=".34"/><rect x="238" y="112" width="88" height="10" rx="5" fill="#fff" opacity=".24"/><rect x="386" y="50" width="82" height="12" rx="6" fill="#fff" opacity=".72"/><rect x="386" y="84" width="104" height="10" rx="5" fill="#fff" opacity=".34"/><rect x="386" y="112" width="72" height="10" rx="5" fill="#fff" opacity=".24"/><circle cx="52" cy="174" r="13" fill="#fff" opacity=".22"/><circle cx="91" cy="174" r="13" fill="#fff" opacity=".16"/><circle cx="130" cy="174" r="13" fill="#fff" opacity=".12"/></svg>'
        );
        return isset($svgs[$type]) ? $svgs[$type] : '';
    }

    public function templates() {
        $this->header(__('Templates', 'diviforge'), __('Template library shell for future full-page package presets.', 'diviforge'), 'templates');
        echo '<div class="df-empty-state df-card"><span class="dashicons dashicons-layout"></span><h2>' . esc_html__('Template library is ready for Sprint 5+', 'diviforge') . '</h2><p>' . esc_html__('This release adds the menu and UI foundation so templates can be added cleanly later.', 'diviforge') . '</p></div>';
        $this->footer();
    }

    public function prompt_library() {
        $this->header(__('Prompt Library', 'diviforge'), __('Copy proven prompts designed for DiviForge packages and Divi page creation.', 'diviforge'), 'settings');
        $prompts = $this->get_prompts();
        echo '<div class="df-tabs">';
        foreach ($prompts as $type => $items) { echo '<button class="df-tab" data-tab="' . esc_attr(sanitize_title($type)) . '">' . esc_html($type) . '</button>'; }
        echo '</div>';
        foreach ($prompts as $type => $items) {
            echo '<div class="df-tab-panel" id="df-tab-' . esc_attr(sanitize_title($type)) . '"><div class="df-grid df-grid-3">';
            foreach ($items as $label => $prompt) {
                echo '<div class="df-card"><h2>' . esc_html($label) . '</h2><p>' . esc_html__('Use this prompt to generate a DiviForge package.', 'diviforge') . '</p><textarea readonly>' . esc_textarea($prompt) . '</textarea><button class="df-btn df-btn-primary df-copy-prompt"><span class="dashicons dashicons-clipboard"></span>' . esc_html__('Copy Prompt', 'diviforge') . '</button></div>';
            }
            echo '</div></div>';
        }
        $this->footer();
    }

    private function get_prompts() {
        $base = 'Create a DiviForge Package for a WordPress Divi page. Return a ZIP-compatible package structure with manifest.json, layout.json, page.css, and optional images/. Use English labels. The layout.json must contain sections, rows, columns, and modules. The page.css must be scoped and compatible with Divi Page Custom CSS.';
        return array(
            'Business' => array(
                'Premium Homepage' => $base . ' Create a premium business homepage with cinematic hero, benefits, process, proof, pricing and CTA.',
                'Service Page' => $base . ' Create a service page with problem, promise, packages, FAQ and conversion CTA.',
            ),
            'Style' => array(
                'Apple Inspired' => $base . ' Use an Apple-inspired visual language: calm, premium, spacious, minimal and high contrast.',
                'Dark SaaS' => $base . ' Use a dark SaaS style with glassmorphism, gradient glows, dashboard cards and elegant CTA buttons.',
            ),
        );
    }

    public function wizard() {
        $this->header(__('Setup Wizard', 'diviforge'), __('A guided first-run experience for building your first DiviForge page.', 'diviforge'), 'settings');
        echo '<div class="df-card"><h2>' . esc_html__('Workflow', 'diviforge') . '</h2><ol class="df-steps"><li>Copy a prompt</li><li>Generate a package</li><li>Inspect the package</li><li>Create or update a page</li><li>Preview and refine in Divi</li></ol><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('diviforge_complete_wizard');
        echo '<input type="hidden" name="action" value="diviforge_complete_wizard"><button class="df-btn df-btn-primary">' . esc_html__('Finish setup', 'diviforge') . '</button></form></div>';
        $this->footer();
    }

    public function complete_wizard() {
        check_admin_referer('diviforge_complete_wizard');
        update_option('diviforge_wizard_completed', '1');
        wp_safe_redirect(admin_url('admin.php?page=diviforge&diviforge_notice=wizard_done'));
        exit;
    }

    public function academy() {
        $this->header(__('Academy', 'diviforge'), __('Learn the DiviForge workflow without leaving WordPress.', 'diviforge'), 'settings');
        echo '<div class="df-grid df-grid-2"><article class="df-card"><h2>Quick Start</h2><p>Build your first homepage: prompt, package, inspect, import, polish in Divi.</p></article><article class="df-card"><h2>Package Guide</h2><p>Use manifest.json, layout.json, page.css and optional assets.</p></article><article class="df-card"><h2>Roadmap</h2><p>Sprint 4 focuses on UX and workflow. Sprint 5 starts the component library.</p></article></div>';
        $this->footer();
    }


    private function version_entries() {
        return array(
            'v3.4.0' => array(
                'title' => __('AI Engine backend', 'diviforge'),
                'body' => __('Deze release maakt de bestaande AI-knoppen functioneel: Generate with AI maakt direct een AI Job, roept de OpenAI Responses API aan, parseert het DiviForge JSON-resultaat, valideert het package, toont het in AI Preview en laat pas na goedkeuring import toe. Geen nieuwe schermen; focus op werkende backend.', 'diviforge'),
            ),
            'v3.3.0' => array(
                'title' => __('AI Chat per page', 'diviforge'),
                'body' => __('Iedere pagina krijgt een eigen AI-chatthread. Gebruikers kunnen iteratief vragen stellen, DiviForge bewaart de conversatie per pagina, stuurt de Builder Tree/context mee naar de actieve AI-provider en koppelt gegenereerde antwoorden aan AI Preview-jobs voor gecontroleerde import.', 'diviforge'),
            ),
            'v3.2.0' => array(
                'title' => __('AI Preview and Jobs', 'diviforge'),
                'body' => __('Introductie van AI Jobs, AI Preview, responsive result preview, packagevalidatie en een veilige approve/discard workflow voordat AI-output een pagina bijwerkt.', 'diviforge'),
            ),
            'v3.1.0' => array(
                'title' => __('AI Improve direct generation', 'diviforge'),
                'body' => __('Eerste directe AI Improve-workflow vanuit AI Studio. DiviForge bouwt een AI-context uit de pagina, verstuurt die naar de actieve OpenAI-provider en toont het gegenereerde DiviForge-package-resultaat eerst ter review. Preview en automatische import volgen in v3.2.', 'diviforge'),
            ),
            'v3.0.1' => array(
                'title' => __('AI Provider Layer', 'diviforge'),
                'body' => __('De OpenAI-instellingen zijn vervangen door een provider-onafhankelijke AI Providers-laag. DiviForge ondersteunt nu een actieve provider, modelkeuze via presets, connection test 2.0, usage dashboard, capability matrix en centrale prompt templates als basis voor v3.1 AI Improve.', 'diviforge'),
            ),
            'v3.0.0' => array(
                'title' => __('Roadmap 3.0: AI Foundation', 'diviforge'),
                'body' => __('Roadmap 3.0 is vastgezet. Deze release voegt de AI Foundation toe: OpenAI-instellingen, AI Studio als werkruimte, Prompt Builder, contextselectie, AI Package Validator, API-test en AI Request History. Directe AI-generatie volgt in v3.1.', 'diviforge'),
            ),
            'v2.9.5' => array(
                'title' => __('Export Extraction Quality', 'diviforge'),
                'body' => __('Deze release verbetert de daadwerkelijke data-extractie: Page CSS wordt uit meerdere DiviForge/Divi meta-bronnen gelezen, CSS-analyse en design tokens worden daardoor gevuld, Divi-rijen zonder expliciete kolommen krijgen een genormaliseerde kolomstructuur en CSS url()-assets worden beter herkend en meegenomen.', 'diviforge'),
            ),
            'v2.9.4' => array(
                'title' => __('AI Fidelity Analysis', 'diviforge'),
                'body' => __('De export bevat nu extra AI-intelligentie: statistics.json, semantic-structure.json en relations.json. CSS-regels, classes, assets en builder nodes worden sterker aan elkaar gekoppeld, gebruikte media krijgen used_by-context en de AI krijgt een semantisch overzicht van hero, CTA, FAQ, footer en andere paginadelen.', 'diviforge'),
            ),
            'v2.9.3' => array(
                'title' => __('AI Fidelity Export', 'diviforge'),
                'body' => __('De AI-export is verdiept met builder-tree v2 als canonieke structuur, volledige raw shortcode-attributen, genormaliseerde settings per sectie/rij/kolom/module, responsive varianten, CSS selectoranalyse, asset-manifest en rijkere design tokens. Hierdoor krijgt ChatGPT/Claude veel meer Divi-context mee.', 'diviforge'),
            ),
            'v2.9.2' => array(
                'title' => __('Builder Tree Package', 'diviforge'),
                'body' => __('Introductie van builder-tree.json, page-meta.json, class-map.json, divi-context.json en AI_REQUEST.json. Daarmee werd de export niet langer alleen een shortcodepakket, maar een AI-vriendelijke boomstructuur van de pagina.', 'diviforge'),
            ),
            'v2.9.1' => array(
                'title' => __('Deep Divi Export', 'diviforge'),
                'body' => __('Export is uitgebreid naar een veel rijker DiviForge AI Package: layout.json bevat nu naast raw Divi content ook gestructureerde secties, rijen, kolommen en modules inclusief attributen en classes. Daarnaast worden divi-context.json, class-map.json, AI_REQUEST.json, scripts/page-scripts.js en lokale WordPress-afbeeldingen in assets/images toegevoegd waar mogelijk.', 'diviforge'),
            ),
            'v2.9.0' => array(
                'title' => __('AI Studio and AI Roundtrip', 'diviforge'),
                'body' => __('Nieuwe AI Studio voor het exporteren van bestaande Divi-pagina’s als AI Package v2. Het exportpakket bevat nu naast manifest.json, layout.json en page.css ook page.html, design.json, preview/README.md, assets/README.md, prompt.txt en chatgpt-instructions.md. Hiermee ontstaat de volledige roundtrip: WordPress → DiviForge Export → ChatGPT/Claude → DiviForge Import.', 'diviforge'),
            ),
            'v2.8.2' => array(
                'title' => __('Export existing pages for ChatGPT', 'diviforge'),
                'body' => __('Bestaande WordPress/Divi-pagina’s kunnen nu als DiviForge package worden geëxporteerd. De ZIP bevat manifest.json, layout.json, page.css, README, prompt.txt en een ChatGPT-instructiebestand. Per pagina kan een vraag worden ingevoerd die automatisch in het exportpakket wordt opgenomen.', 'diviforge'),
            ),
            'v2.8.1' => array(
                'title' => __('Page snapshots', 'diviforge'),
                'body' => __('De Pages-weergave toont nu in de grafische header van elke page-card een visuele snapshot van de betreffende pagina. Featured images krijgen prioriteit; gepubliceerde pagina\'s gebruiken automatisch een live page snapshot; concepten krijgen een nette fallback.', 'diviforge'),
            ),
            'v2.8.0' => array(
                'title' => __('Package History release notes', 'diviforge'),
                'body' => __('Importhistorie uitgebreid met automatisch gegenereerde release notes per package-import. DiviForge vat nu zelf samen wat er is toegepast: importtype, packageversie, secties, modules, afbeeldingen, CSS en eventuele package-highlights uit het manifest.', 'diviforge'),
            ),
            'v2.7.0' => array(
                'title' => __('Package History', 'diviforge'),
                'body' => __('Nieuwe Package History-pagina met import-snapshots per DiviForge-pagina. Vanaf deze versie bewaart DiviForge metadata, statistieken, content en CSS per import, inclusief een restore-actie voor eerdere snapshots.', 'diviforge'),
            ),
            'v2.6.1' => array(
                'title' => __('Versie overzicht', 'diviforge'),
                'body' => __('Nieuw scherm met een helder overzicht van de DiviForge-releasegeschiedenis. De optie staat rechts in de Studio-navigatie naast Settings en toont per versie de belangrijkste toevoegingen.', 'diviforge'),
            ),
            'v2.6.0' => array(
                'title' => __('Package Preview foundation', 'diviforge'),
                'body' => __('Basis voor package preview en inspectie. Packages kunnen inhoudelijk beter worden beoordeeld voordat ze definitief worden toegepast. Dit vormt de stap richting veiligere imports binnen de MVP-roadmap.', 'diviforge'),
            ),
            'v2.5.0' => array(
                'title' => __('Package Inspector', 'diviforge'),
                'body' => __('Introductie van package intelligence: metadata, validatie en een duidelijker beeld van wat een package bevat. Hiermee wordt het importproces controleerbaarder en minder blind.', 'diviforge'),
            ),
            'v2.4.3' => array(
                'title' => __('UX polish', 'diviforge'),
                'body' => __('Visuele verfijning van de Studio-interface, met betere componentkaarten, consistentere styling, verbeterde spacing, statusbadges en hover-effecten. Geen grote nieuwe functionaliteit; focus op MVP-polish.', 'diviforge'),
            ),
            'v2.4.2' => array(
                'title' => __('Components visual update', 'diviforge'),
                'body' => __('Verbetering van de Components-pagina met grafische componentheaders en een betere presentatie van toekomstige componenttypes zoals Hero, CTA, FAQ en Footer.', 'diviforge'),
            ),
            'v2.4.1' => array(
                'title' => __('Pages cards', 'diviforge'),
                'body' => __('Introductie van een modernere Pages-weergave met cards, zoekmogelijkheden, betere acties en een duidelijkere beheerervaring voor bestaande Divi-pagina\'s.', 'diviforge'),
            ),
            'v2.4.0' => array(
                'title' => __('Studio dashboard', 'diviforge'),
                'body' => __('Nieuwe Studio-basis met dashboard, hoofdmenu, KPI-tegels en een professionelere UX-fundering. Sprint 4 richt zich op workflow, consistentie en bruikbaarheid.', 'diviforge'),
            ),
            'v2.3.1' => array(
                'title' => __('Page Package Manager polish', 'diviforge'),
                'body' => __('Toevoeging van een paginanaamveld bij het aanmaken van een nieuwe pagina, bredere layout, elegantere knoppen met iconen en een delete-knop per pagina.', 'diviforge'),
            ),
            'v2.3.0' => array(
                'title' => __('Page Package Manager', 'diviforge'),
                'body' => __('Alle pagina\'s zichtbaar op één beheerpagina. Per bestaande pagina kan een nieuw package worden geüpload. Bovenin kan een nieuwe pagina worden aangemaakt vanuit een package, met previewknoppen na import.', 'diviforge'),
            ),
            'v2.2.0' => array(
                'title' => __('CSS Import Engine', 'diviforge'),
                'body' => __('page.css wordt automatisch toegepast op Divi Page Custom CSS via _et_pb_custom_css. Daarnaast blijft de CSS beschikbaar in DiviForge-metadata voor hergebruik en debugging.', 'diviforge'),
            ),
            'v2.1.0' => array(
                'title' => __('Package specification', 'diviforge'),
                'body' => __('Verdere uitwerking van het package-format met manifest.json, layout.json en page.css als basiscontract tussen AI-output en DiviForge-import.', 'diviforge'),
            ),
            'v2.0.0' => array(
                'title' => __('Foundation', 'diviforge'),
                'body' => __('Eerste stabiele basis voor DiviForge als importtool voor AI-gegenereerde Divi-pagina\'s. De focus lag op het kunnen verwerken van gestructureerde packages.', 'diviforge'),
            ),
        );
    }



    private function get_ai_jobs() {
        $jobs = get_option('diviforge_ai_jobs', array());
        return is_array($jobs) ? $jobs : array();
    }

    private function update_ai_jobs($jobs) {
        update_option('diviforge_ai_jobs', array_slice(array_values($jobs), 0, 100), false);
    }

    private function create_ai_job($data) {
        $jobs = $this->get_ai_jobs();
        $job_id = 'dfjob_' . gmdate('YmdHis') . '_' . wp_generate_password(6, false, false);
        $jobs[$job_id] = wp_parse_args($data, array(
            'id' => $job_id,
            'created_at' => current_time('Y-m-d H:i:s'),
            'updated_at' => current_time('Y-m-d H:i:s'),
            'status' => 'queued',
            'page_id' => 0,
            'title' => __('AI Job', 'diviforge'),
            'prompt' => '',
            'provider' => '',
            'model' => '',
            'response' => '',
            'parsed' => array(),
            'validation' => array(),
            'error' => '',
            'elapsed' => 0,
            'imported_at' => '',
        ));
        $jobs[$job_id]['id'] = $job_id;
        $this->update_ai_jobs($jobs);
        return $job_id;
    }

    private function get_ai_job($job_id) {
        $jobs = $this->get_ai_jobs();
        return isset($jobs[$job_id]) && is_array($jobs[$job_id]) ? $jobs[$job_id] : array();
    }

    private function save_ai_job($job) {
        if (empty($job['id'])) { return; }
        $jobs = $this->get_ai_jobs();
        $job['updated_at'] = current_time('Y-m-d H:i:s');
        $jobs[$job['id']] = $job;
        $this->update_ai_jobs($jobs);
    }

    private function latest_ai_job_id() {
        $jobs = $this->get_ai_jobs();
        foreach ($jobs as $id => $job) { return $id; }
        return '';
    }

    private function extract_json_from_ai_text($text) {
        $text = trim((string) $text);
        if ($text === '') { return ''; }
        if (preg_match('/```(?:json)?\s*(.*?)```/is', $text, $m)) {
            return trim($m[1]);
        }
        $first = strpos($text, '{');
        $last = strrpos($text, '}');
        if ($first !== false && $last !== false && $last > $first) {
            return substr($text, $first, $last - $first + 1);
        }
        return $text;
    }

    private function parse_ai_package_response($response) {
        $json = $this->extract_json_from_ai_text($response);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return new WP_Error('invalid_ai_json', __('The AI response could not be parsed as JSON.', 'diviforge'));
        }
        if (isset($data['page_css']) && !isset($data['page.css'])) { $data['page.css'] = $data['page_css']; }
        if (isset($data['css']) && !isset($data['page.css'])) { $data['page.css'] = $data['css']; }
        return $data;
    }

    private function validate_ai_package_result($parsed) {
        if (is_wp_error($parsed)) {
            return array('score' => 15, 'ok' => false, 'checks' => array(
                array('label' => __('Valid JSON', 'diviforge'), 'ok' => false, 'message' => $parsed->get_error_message()),
            ));
        }
        $checks = array();
        $checks[] = array('label' => 'manifest', 'ok' => !empty($parsed['manifest']) && is_array($parsed['manifest']), 'message' => __('manifest.json equivalent present', 'diviforge'));
        $checks[] = array('label' => 'layout', 'ok' => !empty($parsed['layout']) && is_array($parsed['layout']), 'message' => __('layout.json equivalent present', 'diviforge'));
        $checks[] = array('label' => 'page.css', 'ok' => isset($parsed['page.css']) && is_string($parsed['page.css']), 'message' => __('page CSS present', 'diviforge'));
        $checks[] = array('label' => 'change_summary', 'ok' => !empty($parsed['change_summary']), 'message' => __('change summary present', 'diviforge'));
        $ok_count = 0;
        foreach ($checks as $check) { if (!empty($check['ok'])) { $ok_count++; } }
        $score = (int) round(($ok_count / max(1, count($checks))) * 100);
        return array('score' => $score, 'ok' => $score >= 50 && !empty($parsed['layout']), 'checks' => $checks);
    }

    private function normalize_ai_package_result($data, $source_package = array()) {
        if (!is_array($data)) { return $data; }
        if (isset($data['page_css']) && !isset($data['page.css'])) { $data['page.css'] = (string) $data['page_css']; }
        if (isset($data['css']) && !isset($data['page.css'])) { $data['page.css'] = is_array($data['css']) ? wp_json_encode($data['css']) : (string) $data['css']; }
        if (isset($data['layout_json']) && !isset($data['layout'])) { $data['layout'] = $data['layout_json']; }
        if (isset($data['builder_tree']) && !isset($data['layout']) && !empty($source_package['layout'])) { $data['layout'] = $source_package['layout']; }
        if (empty($data['manifest']) || !is_array($data['manifest'])) {
            $data['manifest'] = is_array($source_package['manifest'] ?? null) ? $source_package['manifest'] : array();
        }
        $data['manifest']['generated_by'] = 'DiviForge AI Engine';
        $data['manifest']['generated_with_diviforge'] = DIVIFORGE_VERSION;
        if (empty($data['manifest']['version'])) { $data['manifest']['version'] = DIVIFORGE_VERSION; }
        if (!isset($data['page.css']) && isset($source_package['css'])) { $data['page.css'] = (string) $source_package['css']; }
        return $data;
    }

    private function layout_preview_items($layout) {
        $items = array();
        if (!is_array($layout) || empty($layout['sections']) || !is_array($layout['sections'])) { return $items; }
        foreach ($layout['sections'] as $section_index => $section) {
            $section_label = !empty($section['label']) ? $section['label'] : sprintf(__('Section %d', 'diviforge'), $section_index + 1);
            $module_texts = array();
            foreach (($section['rows'] ?? array()) as $row) {
                foreach (($row['columns'] ?? array()) as $column) {
                    foreach (($column['modules'] ?? array()) as $module) {
                        $type = $module['type'] ?? 'module';
                        $text = '';
                        if (!empty($module['content'])) { $text = wp_trim_words(wp_strip_all_tags((string) $module['content']), 12); }
                        if (!$text && !empty($module['text'])) { $text = wp_trim_words(wp_strip_all_tags((string) $module['text']), 12); }
                        $module_texts[] = trim($type . ($text ? ': ' . $text : ''));
                    }
                }
            }
            $items[] = array('label' => $section_label, 'modules' => array_slice($module_texts, 0, 4));
            if (count($items) >= 5) { break; }
        }
        return $items;
    }

    private function render_ai_preview_frame($label, $class, $job) {
        $parsed = !empty($job['parsed']) && is_array($job['parsed']) ? $job['parsed'] : array();
        $summary = !empty($parsed['change_summary']) ? $parsed['change_summary'] : __('No structured change summary found. Review the raw AI response before importing.', 'diviforge');
        if (is_array($summary)) { $summary = implode("\n", array_map('strval', $summary)); }
        $items = !empty($parsed['layout']) ? $this->layout_preview_items($parsed['layout']) : array();
        echo '<div class="df-ai-preview-frame ' . esc_attr($class) . '"><div class="df-ai-preview-bar"><span></span><span></span><span></span><strong>' . esc_html($label) . '</strong></div><div class="df-ai-preview-canvas">';
        echo '<h3>' . esc_html(get_the_title(absint($job['page_id'] ?? 0))) . '</h3><p>' . esc_html(wp_trim_words((string) $summary, 38)) . '</p>';
        if ($items) {
            echo '<div class="df-ai-layout-preview">';
            foreach ($items as $item) {
                echo '<article><strong>' . esc_html($item['label']) . '</strong>';
                if (!empty($item['modules'])) {
                    echo '<ul>';
                    foreach ($item['modules'] as $module_label) { echo '<li>' . esc_html($module_label) . '</li>'; }
                    echo '</ul>';
                }
                echo '</article>';
            }
            echo '</div>';
        } else {
            echo '<div class="df-ai-preview-skeleton"><i></i><i></i><i></i></div>';
        }
        echo '</div></div>';
    }

    public function ai_preview() {
        $job_id = !empty($_GET['job_id']) ? sanitize_text_field(wp_unslash($_GET['job_id'])) : $this->latest_ai_job_id();
        $job = $job_id ? $this->get_ai_job($job_id) : array();
        $this->header(__('AI Preview', 'diviforge'), __('Review generated AI output, validate the package structure, compare intent and approve only when it is safe.', 'diviforge'), 'ai-preview');
        if (!$job) {
            echo '<section class="df-card df-empty-state"><span class="dashicons dashicons-visibility"></span><h2>' . esc_html__('No AI preview yet', 'diviforge') . '</h2><p>' . esc_html__('Run Generate with AI from AI Studio to create a preview job.', 'diviforge') . '</p><a class="df-btn df-btn-primary" href="' . esc_url(admin_url('admin.php?page=diviforge-ai-studio')) . '">' . esc_html__('Open AI Studio', 'diviforge') . '</a></section>';
            $this->footer();
            return;
        }
        $validation = !empty($job['validation']) && is_array($job['validation']) ? $job['validation'] : $this->validate_ai_package_result($job['parsed'] ?? array());
        $score = absint($validation['score'] ?? 0);
        echo '<section class="df-card df-ai-preview-header"><div><p class="df-kicker">' . esc_html__('AI Result', 'diviforge') . '</p><h2>' . esc_html($job['title'] ?? __('AI Preview', 'diviforge')) . '</h2><p>' . esc_html(sprintf(__('Status: %s · Model: %s · Created: %s', 'diviforge'), $job['status'] ?? '', $job['model'] ?? '', $job['created_at'] ?? '')) . '</p></div><div class="df-ai-score"><span>' . esc_html__('Package score', 'diviforge') . '</span><strong>' . esc_html($score) . '%</strong></div></section>';
        echo '<section class="df-ai-preview-grid"><div class="df-card"><p class="df-kicker">' . esc_html__('Responsive Preview', 'diviforge') . '</p><div class="df-ai-preview-frames">';
        $this->render_ai_preview_frame(__('Desktop', 'diviforge'), 'is-desktop', $job);
        $this->render_ai_preview_frame(__('Tablet', 'diviforge'), 'is-tablet', $job);
        $this->render_ai_preview_frame(__('Phone', 'diviforge'), 'is-phone', $job);
        echo '</div></div>';
        echo '<aside class="df-card"><p class="df-kicker">' . esc_html__('Validation', 'diviforge') . '</p><ul class="df-checks df-ai-validation-list">';
        foreach (($validation['checks'] ?? array()) as $check) {
            echo '<li class="' . esc_attr(!empty($check['ok']) ? 'is-ok' : 'is-bad') . '">' . esc_html($check['label']) . '<small>' . esc_html($check['message'] ?? '') . '</small></li>';
        }
        echo '</ul><div class="df-ai-preview-actions">';
        if (!empty($validation['ok'])) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('diviforge_ai_import_result_' . $job['id']);
            echo '<input type="hidden" name="action" value="diviforge_ai_import_result"><input type="hidden" name="job_id" value="' . esc_attr($job['id']) . '"><button class="df-btn df-btn-primary" type="submit"><span class="dashicons dashicons-yes-alt"></span>' . esc_html__('Approve & update page', 'diviforge') . '</button></form>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('diviforge_ai_discard_job_' . $job['id']);
        echo '<input type="hidden" name="action" value="diviforge_ai_discard_job"><input type="hidden" name="job_id" value="' . esc_attr($job['id']) . '"><button class="df-btn df-btn-soft" type="submit"><span class="dashicons dashicons-dismiss"></span>' . esc_html__('Discard', 'diviforge') . '</button></form></div></aside></section>';
        echo '<section class="df-card df-ai-diff-card"><p class="df-kicker">' . esc_html__('Diff Summary', 'diviforge') . '</p><div class="df-ai-diff-grid"><div><h3>' . esc_html__('Prompt', 'diviforge') . '</h3><pre>' . esc_html($job['prompt'] ?? '') . '</pre></div><div><h3>' . esc_html__('AI response', 'diviforge') . '</h3><pre>' . esc_html(wp_trim_words((string)($job['response'] ?? ''), 220)) . '</pre></div></div></section>';
        $this->footer();
    }



    private function get_ai_chat_threads() {
        $threads = get_option('diviforge_ai_chat_threads', array());
        return is_array($threads) ? $threads : array();
    }

    private function get_ai_chat_thread($page_id) {
        $threads = $this->get_ai_chat_threads();
        $key = 'page_' . absint($page_id);
        if (empty($threads[$key]) || !is_array($threads[$key])) {
            return array(
                'page_id' => absint($page_id),
                'created_at' => current_time('Y-m-d H:i:s'),
                'updated_at' => current_time('Y-m-d H:i:s'),
                'messages' => array(),
            );
        }
        return $threads[$key];
    }

    private function save_ai_chat_thread($page_id, $thread) {
        $threads = $this->get_ai_chat_threads();
        $key = 'page_' . absint($page_id);
        $thread['page_id'] = absint($page_id);
        $thread['updated_at'] = current_time('Y-m-d H:i:s');
        if (!empty($thread['messages']) && is_array($thread['messages'])) {
            $thread['messages'] = array_slice($thread['messages'], -40);
        }
        $threads[$key] = $thread;
        update_option('diviforge_ai_chat_threads', $threads, false);
    }

    private function add_ai_chat_message($page_id, $role, $content, $meta = array()) {
        $thread = $this->get_ai_chat_thread($page_id);
        if (empty($thread['messages']) || !is_array($thread['messages'])) { $thread['messages'] = array(); }
        $thread['messages'][] = array(
            'id' => 'dfmsg_' . gmdate('YmdHis') . '_' . wp_generate_password(5, false, false),
            'role' => sanitize_key($role),
            'content' => sanitize_textarea_field($content),
            'created_at' => current_time('Y-m-d H:i:s'),
            'user' => wp_get_current_user()->display_name,
            'meta' => is_array($meta) ? $meta : array(),
        );
        $this->save_ai_chat_thread($page_id, $thread);
    }

    private function build_ai_chat_prompt($page, $thread, $message) {
        $history = array_slice(!empty($thread['messages']) && is_array($thread['messages']) ? $thread['messages'] : array(), -12);
        $history_lines = array();
        foreach ($history as $item) {
            $history_lines[] = strtoupper($item['role'] ?? 'message') . ': ' . wp_strip_all_tags($item['content'] ?? '');
        }
        $request = "AI Chat follow-up for page: " . $page->post_title . "\n\n" .
            "Conversation history:\n" . implode("\n\n", $history_lines) . "\n\n" .
            "Latest user message:\n" . $message . "\n\n" .
            "Answer as a DiviForge page-improvement assistant. Give practical guidance and, when a page update is requested, return a DiviForge import package JSON with manifest, layout, page_css, change_summary and validation_notes.";
        $package = $this->build_export_package_data($page, $request);
        return $this->build_ai_improve_prompt($package, $request);
    }

    public function ai_chat() {
        $pages = $this->get_all_pages();
        $page_id = !empty($_GET['page_id']) ? absint($_GET['page_id']) : 0;
        if (!$page_id && $pages) { $page_id = absint($pages[0]->ID); }
        $page = $page_id ? get_post($page_id) : null;
        $thread = ($page && $page->post_type === 'page') ? $this->get_ai_chat_thread($page_id) : array('messages' => array());
        $notice = !empty($_GET['chat_notice']) ? sanitize_key($_GET['chat_notice']) : '';
        $this->header(__('AI Chat', 'diviforge'), __('Per-page conversation memory for iterative Divi improvements.', 'diviforge'), 'ai-chat');
        if ($notice) {
            $messages = array(
                'sent' => __('AI Chat message sent and stored on this page thread.', 'diviforge'),
                'ai_error' => __('AI provider returned an error. The user message was stored so the conversation is not lost.', 'diviforge'),
            );
            echo '<div class="notice ' . esc_attr($notice === 'sent' ? 'notice-success' : 'notice-error') . ' inline"><p>' . esc_html($messages[$notice] ?? __('AI Chat updated.', 'diviforge')) . '</p></div>';
        }
        echo '<section class="df-ai-chat-layout">';
        echo '<aside class="df-card df-ai-chat-pages"><p class="df-kicker">' . esc_html__('Pages', 'diviforge') . '</p><h2>' . esc_html__('Choose page thread', 'diviforge') . '</h2><div class="df-ai-chat-page-list">';
        foreach ($pages as $p) {
            if (!current_user_can('edit_post', $p->ID)) { continue; }
            $selected = absint($p->ID) === $page_id ? ' is-active' : '';
            $t = $this->get_ai_chat_thread($p->ID);
            $count = !empty($t['messages']) && is_array($t['messages']) ? count($t['messages']) : 0;
            echo '<a class="df-ai-chat-page' . esc_attr($selected) . '" href="' . esc_url(admin_url('admin.php?page=diviforge-ai-chat&page_id=' . absint($p->ID))) . '"><strong>' . esc_html($p->post_title ?: __('Untitled page', 'diviforge')) . '</strong><small>' . esc_html(sprintf(_n('%d message', '%d messages', $count, 'diviforge'), $count)) . '</small></a>';
        }
        echo '</div></aside>';
        echo '<main class="df-card df-ai-chat-main">';
        if (!$page || $page->post_type !== 'page') {
            echo '<div class="df-empty-state"><span class="dashicons dashicons-format-status"></span><h2>' . esc_html__('No page selected', 'diviforge') . '</h2></div>';
        } else {
            echo '<div class="df-ai-chat-head"><div><p class="df-kicker">' . esc_html__('Page conversation', 'diviforge') . '</p><h2>' . esc_html($page->post_title ?: __('Untitled page', 'diviforge')) . '</h2><p>' . esc_html__('The thread keeps previous prompts and AI answers for this page. Future AI Improve actions can use this history as context.', 'diviforge') . '</p></div><a class="df-btn df-btn-soft" target="_blank" href="' . esc_url($this->get_page_preview_url($page_id)) . '"><span class="dashicons dashicons-visibility"></span>' . esc_html__('View page', 'diviforge') . '</a></div>';
            echo '<div class="df-ai-chat-messages">';
            $messages = !empty($thread['messages']) && is_array($thread['messages']) ? $thread['messages'] : array();
            if (!$messages) {
                echo '<div class="df-empty-state"><span class="dashicons dashicons-format-chat"></span><h2>' . esc_html__('Start the conversation', 'diviforge') . '</h2><p>' . esc_html__('Ask DiviForge to improve this page. The message, AI response and related job will be saved in this thread.', 'diviforge') . '</p></div>';
            }
            foreach ($messages as $msg) {
                $role = sanitize_key($msg['role'] ?? 'user');
                echo '<article class="df-ai-chat-message is-' . esc_attr($role) . '"><div><strong>' . esc_html($role === 'assistant' ? __('DiviForge AI', 'diviforge') : __('You', 'diviforge')) . '</strong><small>' . esc_html($msg['created_at'] ?? '') . '</small></div><p>' . nl2br(esc_html($msg['content'] ?? '')) . '</p>';
                if (!empty($msg['meta']['job_id'])) {
                    echo '<a class="df-btn df-btn-mini" href="' . esc_url(admin_url('admin.php?page=diviforge-ai-preview&job_id=' . rawurlencode($msg['meta']['job_id']))) . '">' . esc_html__('Open AI Preview', 'diviforge') . '</a>';
                }
                echo '</article>';
            }
            echo '</div>';
            echo '<form class="df-ai-chat-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('diviforge_ai_chat_message_' . $page_id);
            echo '<input type="hidden" name="action" value="diviforge_ai_chat_message"><input type="hidden" name="page_id" value="' . esc_attr($page_id) . '">';
            echo '<label class="df-field"><span>' . esc_html__('Message to AI', 'diviforge') . '</span><textarea name="chat_message" rows="5" placeholder="' . esc_attr__('Bijvoorbeeld: Maak de hero rustiger, verbeter de CTA en behoud de huidige huisstijl.', 'diviforge') . '" required></textarea></label>';
            echo '<div class="df-card-actions"><button class="df-btn df-btn-primary" type="submit"><span class="dashicons dashicons-format-status"></span>' . esc_html__('Send to AI Chat', 'diviforge') . '</button><a class="df-btn df-btn-soft" href="' . esc_url(admin_url('admin.php?page=diviforge-ai-studio&page_id=' . $page_id)) . '">' . esc_html__('Open in AI Studio', 'diviforge') . '</a></div>';
            echo '</form>';
        }
        echo '</main></section>';
        $this->footer();
    }

    public function handle_ai_chat_message() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to use AI Chat.', 'diviforge')); }
        $page_id = !empty($_POST['page_id']) ? absint($_POST['page_id']) : 0;
        if (!$page_id || !current_user_can('edit_post', $page_id)) { wp_die(esc_html__('You are not allowed to use this page.', 'diviforge')); }
        check_admin_referer('diviforge_ai_chat_message_' . $page_id);
        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') { wp_die(esc_html__('Page not found.', 'diviforge')); }
        $message = !empty($_POST['chat_message']) ? sanitize_textarea_field(wp_unslash($_POST['chat_message'])) : '';
        if ($message === '') { wp_safe_redirect(admin_url('admin.php?page=diviforge-ai-chat&page_id=' . $page_id)); exit; }
        $this->add_ai_chat_message($page_id, 'user', $message);
        $settings = $this->get_ai_settings();
        $thread = $this->get_ai_chat_thread($page_id);
        $prompt = $this->build_ai_chat_prompt($page, $thread, $message);
        $started = microtime(true);
        $result = $this->call_ai_provider($prompt, $settings);
        if (is_wp_error($result)) {
            $this->add_ai_chat_message($page_id, 'assistant', $result->get_error_message(), array('status' => 'error'));
            $this->log_ai_request(sprintf(__('AI Chat failed: %s', 'diviforge'), get_the_title($page_id)), $result->get_error_message(), 'ai_error', $settings['model']);
            wp_safe_redirect(add_query_arg(array('page' => 'diviforge-ai-chat', 'page_id' => $page_id, 'chat_notice' => 'ai_error'), admin_url('admin.php')));
            exit;
        }
        $elapsed = round(microtime(true) - $started, 1);
        $parsed = $this->parse_ai_package_response($result);
        $job_id = $this->create_ai_job(array(
            'page_id' => $page_id,
            'title' => sprintf(__('AI Chat result: %s', 'diviforge'), get_the_title($page_id)),
            'prompt' => $message,
            'status' => is_wp_error($parsed) ? 'needs_review' : 'completed',
            'model' => $settings['model'],
            'provider' => $settings['provider'],
            'response' => $result,
            'parsed' => is_wp_error($parsed) ? array() : $parsed,
            'validation' => $this->validate_ai_package_result($parsed),
            'elapsed' => $elapsed,
        ));
        $assistant_text = is_wp_error($parsed) ? wp_trim_words(wp_strip_all_tags((string) $result), 120) : __('AI generated a DiviForge package. Review it in AI Preview before importing.', 'diviforge');
        $this->add_ai_chat_message($page_id, 'assistant', $assistant_text, array('job_id' => $job_id, 'elapsed' => $elapsed));
        $this->log_ai_request(sprintf(__('AI Chat response: %s', 'diviforge'), get_the_title($page_id)), wp_trim_words(wp_strip_all_tags($message), 34), 'ai_chat', $settings['model']);
        wp_safe_redirect(add_query_arg(array('page' => 'diviforge-ai-chat', 'page_id' => $page_id, 'chat_notice' => 'sent'), admin_url('admin.php')));
        exit;
    }

    public function ai_jobs() {
        $this->header(__('AI Jobs', 'diviforge'), __('Queue-style overview of AI requests with status, model, target page and next action.', 'diviforge'), 'ai-jobs');
        $jobs = $this->get_ai_jobs();
        echo '<section class="df-card"><div class="df-section-head"><div><p class="df-kicker">' . esc_html__('Job Queue', 'diviforge') . '</p><h2>' . esc_html__('Recent AI jobs', 'diviforge') . '</h2><p>' . esc_html__('v3.4 uses the local AI job queue as the central backend for generation, review, approve and discard workflows.', 'diviforge') . '</p></div><a class="df-btn df-btn-primary" href="' . esc_url(admin_url('admin.php?page=diviforge-ai-studio')) . '"><span class="dashicons dashicons-superhero"></span>' . esc_html__('New AI job', 'diviforge') . '</a></div>';
        if (!$jobs) { echo '<div class="df-empty-state"><span class="dashicons dashicons-update"></span><h2>' . esc_html__('No jobs yet', 'diviforge') . '</h2></div></section>'; $this->footer(); return; }
        echo '<div class="df-ai-job-list">';
        foreach ($jobs as $job) {
            $status = sanitize_key($job['status'] ?? 'queued');
            echo '<article class="df-ai-job-card is-' . esc_attr($status) . '"><div><span class="df-status-pill">' . esc_html($status) . '</span><h3>' . esc_html($job['title'] ?? __('AI Job', 'diviforge')) . '</h3><p>' . esc_html(wp_trim_words($job['prompt'] ?? '', 24)) . '</p><small>' . esc_html(($job['provider'] ?? '') . ' · ' . ($job['model'] ?? '') . ' · ' . ($job['created_at'] ?? '')) . '</small></div><a class="df-btn df-btn-soft" href="' . esc_url(admin_url('admin.php?page=diviforge-ai-preview&job_id=' . rawurlencode($job['id']))) . '"><span class="dashicons dashicons-visibility"></span>' . esc_html__('Review', 'diviforge') . '</a></article>';
        }
        echo '</div></section>';
        $this->footer();
    }

    public function handle_ai_import_result() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to import AI results.', 'diviforge')); }
        $job_id = !empty($_POST['job_id']) ? sanitize_text_field(wp_unslash($_POST['job_id'])) : '';
        $job = $job_id ? $this->get_ai_job($job_id) : array();
        if (!$job) { wp_die(esc_html__('AI job not found.', 'diviforge')); }
        check_admin_referer('diviforge_ai_import_result_' . $job_id);
        $page_id = absint($job['page_id'] ?? 0);
        if (!$page_id || !current_user_can('edit_post', $page_id)) { wp_die(esc_html__('You are not allowed to edit this page.', 'diviforge')); }
        $parsed = !empty($job['parsed']) ? $job['parsed'] : $this->parse_ai_package_response($job['response'] ?? '');
        $validation = $this->validate_ai_package_result($parsed);
        if (empty($validation['ok'])) { wp_die(esc_html__('AI result is not valid enough to import.', 'diviforge')); }
        $content = DiviForge_Importer::layout_to_divi_shortcodes($parsed['layout']);
        if ($content === '') { wp_die(esc_html__('AI layout could not be converted to Divi shortcodes.', 'diviforge')); }
        wp_update_post(array('ID' => $page_id, 'post_content' => $content));
        update_post_meta($page_id, '_et_pb_use_builder', 'on');
        if (isset($parsed['page.css'])) { DiviForge_CSS_Importer::apply_to_page($page_id, (string)$parsed['page.css'], 'ai_result'); }
        update_post_meta($page_id, '_diviforge_ai_last_job', $job_id);
        update_post_meta($page_id, '_diviforge_ai_updated_at', current_time('mysql'));
        $job['status'] = 'imported';
        $job['imported_at'] = current_time('Y-m-d H:i:s');
        $this->save_ai_job($job);
        $this->log_ai_request(sprintf(__('AI result imported: %s', 'diviforge'), get_the_title($page_id)), __('Approved from AI Preview and applied to the page.', 'diviforge'), 'imported', $job['model'] ?? '');
        wp_safe_redirect(add_query_arg(array('page' => 'diviforge-ai-studio', 'ai_notice' => 'imported'), admin_url('admin.php')));
        exit;
    }

    public function handle_ai_discard_job() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to discard AI jobs.', 'diviforge')); }
        $job_id = !empty($_POST['job_id']) ? sanitize_text_field(wp_unslash($_POST['job_id'])) : '';
        $job = $job_id ? $this->get_ai_job($job_id) : array();
        if (!$job) { wp_die(esc_html__('AI job not found.', 'diviforge')); }
        check_admin_referer('diviforge_ai_discard_job_' . $job_id);
        $job['status'] = 'discarded';
        $this->save_ai_job($job);
        wp_safe_redirect(add_query_arg(array('page' => 'diviforge-ai-jobs', 'ai_notice' => 'discarded'), admin_url('admin.php')));
        exit;
    }

    private function get_ai_settings() {
        $defaults = array(
            'provider' => 'openai',
            'api_key' => '',
            'anthropic_api_key' => '',
            'google_api_key' => '',
            'azure_api_key' => '',
            'azure_endpoint' => '',
            'azure_deployment' => '',
            'openrouter_api_key' => '',
            'ollama_endpoint' => 'http://localhost:11434',
            'model' => 'gpt-5.1',
            'temperature' => '0.2',
            'max_tokens' => '50000',
            'timeout' => '180',
            'daily_budget' => '10',
        );
        $settings = get_option('diviforge_ai_settings', array());
        $settings = wp_parse_args(is_array($settings) ? $settings : array(), $defaults);
        if (empty($settings['provider'])) { $settings['provider'] = 'openai'; }
        if (empty($settings['model'])) { $settings['model'] = $this->get_default_model_for_provider($settings['provider']); }
        return $settings;
    }

    private function get_ai_providers() {
        return array(
            'openai' => array(
                'label' => 'OpenAI',
                'description' => __('Recommended default for DiviForge AI Studio and structured JSON output.', 'diviforge'),
                'models' => array('gpt-5.1', 'gpt-5.1-mini', 'gpt-5.1-nano'),
                'capabilities' => array('json' => true, 'vision' => true, 'large_context' => true, 'streaming' => true),
            ),
            'anthropic' => array(
                'label' => 'Anthropic Claude',
                'description' => __('Prepared provider for UX, copy and large redesign reasoning.', 'diviforge'),
                'models' => array('claude-sonnet-4-5', 'claude-opus-4-1'),
                'capabilities' => array('json' => true, 'vision' => true, 'large_context' => true, 'streaming' => true),
            ),
            'google' => array(
                'label' => 'Google Gemini',
                'description' => __('Prepared provider for multimodal analysis and long-context review.', 'diviforge'),
                'models' => array('gemini-2.5-pro', 'gemini-2.5-flash'),
                'capabilities' => array('json' => true, 'vision' => true, 'large_context' => true, 'streaming' => true),
            ),
            'azure' => array(
                'label' => 'Azure OpenAI',
                'description' => __('Prepared provider for Microsoft/Azure enterprise environments.', 'diviforge'),
                'models' => array('azure-deployment'),
                'capabilities' => array('json' => true, 'vision' => true, 'large_context' => true, 'streaming' => true),
            ),
            'openrouter' => array(
                'label' => 'OpenRouter',
                'description' => __('Prepared provider for routing to multiple commercial models.', 'diviforge'),
                'models' => array('openai/gpt-5.1', 'anthropic/claude-sonnet-4.5', 'google/gemini-2.5-pro'),
                'capabilities' => array('json' => true, 'vision' => true, 'large_context' => true, 'streaming' => true),
            ),
            'ollama' => array(
                'label' => 'Ollama Local',
                'description' => __('Prepared local provider for privacy-first experiments. Capabilities depend on the local model.', 'diviforge'),
                'models' => array('llama3.1', 'qwen2.5-coder', 'mistral'),
                'capabilities' => array('json' => 'partial', 'vision' => 'model', 'large_context' => 'model', 'streaming' => true),
            ),
        );
    }

    private function get_ai_provider_label($provider) {
        $providers = $this->get_ai_providers();
        return isset($providers[$provider]['label']) ? $providers[$provider]['label'] : ucfirst((string) $provider);
    }

    private function get_default_model_for_provider($provider) {
        $providers = $this->get_ai_providers();
        if (!empty($providers[$provider]['models'][0])) { return $providers[$provider]['models'][0]; }
        return 'gpt-5.1';
    }

    private function get_active_provider_key($settings) {
        $provider = $settings['provider'] ?? 'openai';
        if ($provider === 'anthropic') { return $settings['anthropic_api_key'] ?? ''; }
        if ($provider === 'google') { return $settings['google_api_key'] ?? ''; }
        if ($provider === 'azure') { return $settings['azure_api_key'] ?? ''; }
        if ($provider === 'openrouter') { return $settings['openrouter_api_key'] ?? ''; }
        if ($provider === 'ollama') { return $settings['ollama_endpoint'] ?? ''; }
        return $settings['api_key'] ?? '';
    }

    private function get_ai_usage_summary() {
        $history = $this->get_ai_history();
        $today = current_time('Y-m-d');
        $requests_today = 0;
        $last = null;
        foreach ($history as $entry) {
            if (!$last) { $last = $entry; }
            $created = isset($entry['created_at']) ? substr($entry['created_at'], 0, 10) : '';
            if ($created === $today) { $requests_today++; }
        }
        return array(
            'requests_today' => $requests_today,
            'estimated_tokens' => $requests_today * 2500,
            'estimated_cost' => number_format($requests_today * 0.03, 2),
            'last_action' => $last ? ($last['title'] ?? '') : __('No AI actions yet', 'diviforge'),
        );
    }

    private function get_ai_history() {
        $history = get_option('diviforge_ai_request_history', array());
        return is_array($history) ? $history : array();
    }

    private function log_ai_request($title, $summary, $status = 'logged', $model = '') {
        $settings = $this->get_ai_settings();
        $history = $this->get_ai_history();
        array_unshift($history, array(
            'created_at' => current_time('Y-m-d H:i:s'),
            'title' => sanitize_text_field($title),
            'summary' => sanitize_textarea_field($summary),
            'status' => sanitize_key($status),
            'model' => sanitize_text_field($model ? $model : $settings['model']),
            'user' => wp_get_current_user()->display_name,
        ));
        $history = array_slice($history, 0, 75);
        update_option('diviforge_ai_request_history', $history, false);
    }

    private function mask_api_key($key) {
        if (!$key) { return ''; }
        $len = strlen($key);
        if ($len <= 10) { return str_repeat('•', $len); }
        return substr($key, 0, 7) . str_repeat('•', max(4, $len - 11)) . substr($key, -4);
    }

    public function handle_save_ai_settings() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to change DiviForge settings.', 'diviforge')); }
        check_admin_referer('diviforge_save_ai_settings');
        $current = $this->get_ai_settings();
        $providers = $this->get_ai_providers();
        $provider = isset($_POST['ai_provider']) ? sanitize_key($_POST['ai_provider']) : $current['provider'];
        if (!isset($providers[$provider])) { $provider = 'openai'; }
        $current['provider'] = $provider;

        $key_fields = array(
            'api_key' => 'openai_api_key',
            'anthropic_api_key' => 'anthropic_api_key',
            'google_api_key' => 'google_api_key',
            'azure_api_key' => 'azure_api_key',
            'openrouter_api_key' => 'openrouter_api_key',
        );
        foreach ($key_fields as $setting_key => $post_key) {
            $new_key = isset($_POST[$post_key]) ? trim(sanitize_text_field(wp_unslash($_POST[$post_key]))) : '';
            $clear_key = 'clear_' . $post_key;
            if (!empty($_POST[$clear_key])) { $current[$setting_key] = ''; }
            elseif ($new_key !== '') { $current[$setting_key] = $new_key; }
        }

        $current['azure_endpoint'] = isset($_POST['azure_endpoint']) ? esc_url_raw(wp_unslash($_POST['azure_endpoint'])) : $current['azure_endpoint'];
        $current['azure_deployment'] = isset($_POST['azure_deployment']) ? sanitize_text_field(wp_unslash($_POST['azure_deployment'])) : $current['azure_deployment'];
        $current['ollama_endpoint'] = isset($_POST['ollama_endpoint']) ? esc_url_raw(wp_unslash($_POST['ollama_endpoint'])) : $current['ollama_endpoint'];
        $current['model'] = isset($_POST['ai_model']) ? sanitize_text_field(wp_unslash($_POST['ai_model'])) : $this->get_default_model_for_provider($provider);
        $current['temperature'] = isset($_POST['ai_temperature']) ? sanitize_text_field(wp_unslash($_POST['ai_temperature'])) : $current['temperature'];
        $current['max_tokens'] = isset($_POST['ai_max_tokens']) ? sanitize_text_field(wp_unslash($_POST['ai_max_tokens'])) : $current['max_tokens'];
        $current['timeout'] = isset($_POST['ai_timeout']) ? sanitize_text_field(wp_unslash($_POST['ai_timeout'])) : $current['timeout'];
        $current['daily_budget'] = isset($_POST['ai_daily_budget']) ? sanitize_text_field(wp_unslash($_POST['ai_daily_budget'])) : $current['daily_budget'];
        update_option('diviforge_ai_settings', $current, false);
        $this->log_ai_request(__('AI provider settings updated', 'diviforge'), sprintf(__('Active provider: %s · model: %s.', 'diviforge'), $this->get_ai_provider_label($current['provider']), $current['model']), 'settings_saved', $current['model']);
        wp_safe_redirect(add_query_arg(array('page' => 'diviforge-settings', 'ai_notice' => 'saved'), admin_url('admin.php')));
        exit;
    }

    public function handle_test_ai_connection() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to test DiviForge settings.', 'diviforge')); }
        check_admin_referer('diviforge_test_ai_connection');
        $settings = $this->get_ai_settings();
        $provider = $settings['provider'];
        $status = 'missing_key';
        $message = __('No API key or endpoint configured for the active provider.', 'diviforge');
        $started = microtime(true);
        $key = $this->get_active_provider_key($settings);
        if (!empty($key)) {
            $response = null;
            if ($provider === 'openai') {
                $response = wp_remote_post('https://api.openai.com/v1/responses', array(
                    'timeout' => max(15, absint($settings['timeout'])),
                    'headers' => array('Authorization' => 'Bearer ' . $settings['api_key'], 'Content-Type' => 'application/json'),
                    'body' => wp_json_encode(array('model' => $settings['model'], 'input' => 'Reply with exactly: DiviForge OK', 'max_output_tokens' => 32)),
                ));
            } elseif ($provider === 'openrouter') {
                $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
                    'timeout' => max(15, absint($settings['timeout'])),
                    'headers' => array('Authorization' => 'Bearer ' . $settings['openrouter_api_key'], 'Content-Type' => 'application/json', 'HTTP-Referer' => home_url('/'), 'X-Title' => 'DiviForge'),
                    'body' => wp_json_encode(array('model' => $settings['model'], 'messages' => array(array('role' => 'user', 'content' => 'Reply with exactly: DiviForge OK')), 'max_tokens' => 32)),
                ));
            } elseif ($provider === 'anthropic') {
                $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
                    'timeout' => max(15, absint($settings['timeout'])),
                    'headers' => array('x-api-key' => $settings['anthropic_api_key'], 'anthropic-version' => '2023-06-01', 'Content-Type' => 'application/json'),
                    'body' => wp_json_encode(array('model' => $settings['model'], 'max_tokens' => 32, 'messages' => array(array('role' => 'user', 'content' => 'Reply with exactly: DiviForge OK')))),
                ));
            } elseif ($provider === 'ollama') {
                $endpoint = rtrim($settings['ollama_endpoint'], '/') . '/api/tags';
                $response = wp_remote_get($endpoint, array('timeout' => max(5, absint($settings['timeout']))));
            } else {
                $status = 'provider_ready';
                $message = sprintf(__('%s settings are stored. Live connection test for this provider will be expanded after the OpenAI engine is stable.', 'diviforge'), $this->get_ai_provider_label($provider));
            }
            if ($response) {
                if (is_wp_error($response)) {
                    $status = 'api_error';
                    $message = $response->get_error_message();
                } else {
                    $code = wp_remote_retrieve_response_code($response);
                    $latency = round((microtime(true) - $started) * 1000);
                    $status = ($code >= 200 && $code < 300) ? 'api_ok' : 'api_error';
                    $message = ($status === 'api_ok') ? sprintf(__('%s connection successful. Latency: %sms.', 'diviforge'), $this->get_ai_provider_label($provider), $latency) : sprintf(__('%s returned HTTP %d.', 'diviforge'), $this->get_ai_provider_label($provider), $code);
                }
            }
        }
        $this->log_ai_request(__('AI provider connection test', 'diviforge'), $message, $status, $settings['model']);
        wp_safe_redirect(add_query_arg(array('page' => 'diviforge-settings', 'ai_notice' => $status), admin_url('admin.php')));
        exit;
    }

    public function versions() {
        $this->header(__('Versie overzicht', 'diviforge'), __('Bekijk per DiviForge-versie welke toevoegingen zijn gedaan richting de eerste MVP.', 'diviforge'), 'versions');
        echo '<div class="df-version-overview">';
        echo '<div class="df-card df-version-intro"><p class="df-kicker">' . esc_html__('Release history', 'diviforge') . '</p><h2>' . esc_html__('Van importtool naar DiviForge MVP', 'diviforge') . '</h2><p>' . esc_html__('Dit overzicht helpt om de ontwikkeling strak langs de roadmap te volgen. Grote ideeën blijven op de backlog; dit scherm toont alleen wat per versie is toegevoegd.', 'diviforge') . '</p></div>';
        echo '<div class="df-version-timeline">';
        foreach ($this->version_entries() as $version => $entry) {
            echo '<article class="df-version-card">';
            echo '<div class="df-version-marker"><span>' . esc_html($version) . '</span></div>';
            echo '<div class="df-version-content"><h2>' . esc_html($entry['title']) . '</h2><p>' . esc_html($entry['body']) . '</p></div>';
            echo '</article>';
        }
        echo '</div></div>';
        $this->footer();
    }

    public function settings() {
        $settings = $this->get_ai_settings();
        $providers = $this->get_ai_providers();
        $usage = $this->get_ai_usage_summary();
        $notice = !empty($_GET['ai_notice']) ? sanitize_key($_GET['ai_notice']) : '';
        $this->header(__('Settings', 'diviforge'), __('Configure the Roadmap 3.0 AI Provider Layer for direct AI workflows.', 'diviforge'), 'settings');
        if ($notice) {
            $messages = array(
                'saved' => __('AI provider settings saved.', 'diviforge'),
                'api_ok' => __('AI provider connection successful.', 'diviforge'),
                'provider_ready' => __('Provider configuration saved. Full live test support for this provider follows in the next AI integration step.', 'diviforge'),
                'api_error' => __('AI provider connection failed. Check your key, model and server connectivity.', 'diviforge'),
                'missing_key' => __('Add an API key or local endpoint before testing the connection.', 'diviforge'),
            );
            echo '<div class="notice ' . esc_attr(in_array($notice, array('api_ok','saved','provider_ready'), true) ? 'notice-success' : 'notice-error') . ' inline"><p>' . esc_html($messages[$notice] ?? __('Settings updated.', 'diviforge')) . '</p></div>';
        }

        echo '<div class="df-settings-grid df-provider-settings-grid">';
        echo '<form class="df-card df-ai-settings-card" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('diviforge_save_ai_settings');
        echo '<input type="hidden" name="action" value="diviforge_save_ai_settings">';
        echo '<p class="df-kicker">' . esc_html__('AI Providers', 'diviforge') . '</p><h2>' . esc_html__('AI Provider Layer', 'diviforge') . '</h2><p>' . esc_html__('Choose the active provider and model. DiviForge keeps the rest of the AI workflow provider-independent.', 'diviforge') . '</p>';

        echo '<div class="df-provider-card-grid">';
        foreach ($providers as $key => $provider) {
            echo '<label class="df-provider-card ' . esc_attr($settings['provider'] === $key ? 'is-active' : '') . '"><input type="radio" name="ai_provider" value="' . esc_attr($key) . '" ' . checked($settings['provider'], $key, false) . '><strong>' . esc_html($provider['label']) . '</strong><small>' . esc_html($provider['description']) . '</small></label>';
        }
        echo '</div>';

        echo '<div class="df-form-grid df-provider-model-grid"><label class="df-field"><span>' . esc_html__('Model preset', 'diviforge') . '</span><select name="ai_model">';
        foreach ($providers as $provider_key => $provider) {
            echo '<optgroup label="' . esc_attr($provider['label']) . '">';
            foreach ($provider['models'] as $model) {
                echo '<option value="' . esc_attr($model) . '" ' . selected($settings['model'], $model, false) . '>' . esc_html($model) . '</option>';
            }
            echo '</optgroup>';
        }
        echo '</select></label><label class="df-field"><span>' . esc_html__('Temperature', 'diviforge') . '</span><input type="number" step="0.1" min="0" max="2" name="ai_temperature" value="' . esc_attr($settings['temperature']) . '"></label><label class="df-field"><span>' . esc_html__('Timeout seconds', 'diviforge') . '</span><input type="number" min="15" max="300" name="ai_timeout" value="' . esc_attr($settings['timeout']) . '"></label></div>';
        echo '<div class="df-form-grid"><label class="df-field"><span>' . esc_html__('Max output tokens', 'diviforge') . '</span><input type="number" min="1000" name="ai_max_tokens" value="' . esc_attr($settings['max_tokens']) . '"></label><label class="df-field"><span>' . esc_html__('Daily budget hint $', 'diviforge') . '</span><input type="number" min="1" step="1" name="ai_daily_budget" value="' . esc_attr($settings['daily_budget']) . '"></label></div>';

        echo '<div class="df-provider-secret-grid">';
        $this->render_secret_field('openai_api_key', __('OpenAI API key', 'diviforge'), $settings['api_key'], 'sk-...');
        $this->render_secret_field('anthropic_api_key', __('Anthropic API key', 'diviforge'), $settings['anthropic_api_key'], 'sk-ant-...');
        $this->render_secret_field('google_api_key', __('Google API key', 'diviforge'), $settings['google_api_key'], 'AIza...');
        $this->render_secret_field('azure_api_key', __('Azure OpenAI key', 'diviforge'), $settings['azure_api_key'], 'azure key...');
        $this->render_secret_field('openrouter_api_key', __('OpenRouter API key', 'diviforge'), $settings['openrouter_api_key'], 'sk-or-...');
        echo '<label class="df-field"><span>' . esc_html__('Azure endpoint', 'diviforge') . '</span><input type="text" name="azure_endpoint" value="' . esc_attr($settings['azure_endpoint']) . '" placeholder="https://your-resource.openai.azure.com"></label>';
        echo '<label class="df-field"><span>' . esc_html__('Azure deployment', 'diviforge') . '</span><input type="text" name="azure_deployment" value="' . esc_attr($settings['azure_deployment']) . '" placeholder="deployment-name"></label>';
        echo '<label class="df-field"><span>' . esc_html__('Ollama endpoint', 'diviforge') . '</span><input type="text" name="ollama_endpoint" value="' . esc_attr($settings['ollama_endpoint']) . '" placeholder="http://localhost:11434"></label>';
        echo '</div>';

        echo '<div class="df-card-actions"><button class="df-btn df-btn-primary" type="submit"><span class="dashicons dashicons-saved"></span>' . esc_html__('Save AI provider settings', 'diviforge') . '</button></div>';
        echo '</form>';

        echo '<aside class="df-settings-side">';
        echo '<div class="df-card"><p class="df-kicker">' . esc_html__('Connection test 2.0', 'diviforge') . '</p><h2>' . esc_html__('Validate active provider', 'diviforge') . '</h2><p>' . esc_html__('Tests the selected provider where a lightweight API check is available and logs latency/status in AI Request History.', 'diviforge') . '</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('diviforge_test_ai_connection');
        echo '<input type="hidden" name="action" value="diviforge_test_ai_connection"><button class="df-btn df-btn-soft" type="submit"><span class="dashicons dashicons-admin-links"></span>' . esc_html__('Test active provider', 'diviforge') . '</button></form></div>';

        echo '<div class="df-card"><p class="df-kicker">' . esc_html__('Usage dashboard', 'diviforge') . '</p><h2>' . esc_html__('AI usage snapshot', 'diviforge') . '</h2><div class="df-usage-list"><span>' . esc_html__('Active provider', 'diviforge') . '<strong>' . esc_html($this->get_ai_provider_label($settings['provider'])) . '</strong></span><span>' . esc_html__('Requests today', 'diviforge') . '<strong>' . esc_html($usage['requests_today']) . '</strong></span><span>' . esc_html__('Estimated tokens', 'diviforge') . '<strong>' . esc_html($usage['estimated_tokens']) . '</strong></span><span>' . esc_html__('Estimated cost', 'diviforge') . '<strong>$' . esc_html($usage['estimated_cost']) . '</strong></span><span>' . esc_html__('Last action', 'diviforge') . '<strong>' . esc_html($usage['last_action']) . '</strong></span></div></div>';

        echo '<div class="df-card"><p class="df-kicker">' . esc_html__('Capability matrix', 'diviforge') . '</p><h2>' . esc_html__('Provider readiness', 'diviforge') . '</h2><table class="df-capability-table"><thead><tr><th>Provider</th><th>JSON</th><th>Vision</th><th>Context</th><th>Stream</th></tr></thead><tbody>';
        foreach ($providers as $provider) {
            echo '<tr><td>' . esc_html($provider['label']) . '</td>';
            foreach (array('json','vision','large_context','streaming') as $cap) {
                $value = $provider['capabilities'][$cap] ?? false;
                echo '<td>' . ($value === true ? '✅' : esc_html(is_string($value) ? $value : '—')) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        echo '</aside></div>';
        $this->footer();
    }

    private function render_secret_field($name, $label, $value, $placeholder) {
        echo '<label class="df-field"><span>' . esc_html($label) . '</span><input type="password" name="' . esc_attr($name) . '" placeholder="' . esc_attr($value ? $this->mask_api_key($value) : $placeholder) . '"></label>';
        if ($value) {
            echo '<label class="df-checkbox df-clear-secret"><input type="checkbox" name="clear_' . esc_attr($name) . '" value="1"> ' . esc_html(sprintf(__('Clear %s', 'diviforge'), $label)) . '</label>';
        }
    }

}
