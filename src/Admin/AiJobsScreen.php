<?php
namespace DiviForge\Admin;

if (!defined('ABSPATH')) { exit; }

use DiviForge\Repository\AiJobRepositoryInterface;

final class AiJobsScreen {
    private AiJobRepositoryInterface $jobs;

    public function __construct(AiJobRepositoryInterface $jobs) {
        $this->jobs = $jobs;
    }

    public function register(): void {
        // Priority 20: must run after the legacy DiviForge_Admin::menu() (default
        // priority 10) registers the top-level "diviforge" menu, otherwise WP's
        // page-hookname resolution breaks and the page 403s despite $submenu
        // looking correct - confirmed against a real WP install, not just $submenu.
        add_action('admin_menu', array($this, 'menu'), 20);
    }

    public function menu(): void {
        add_submenu_page(
            'diviforge',
            __('AI Request Log', 'diviforge'),
            __('AI Request Log', 'diviforge'),
            'manage_options',
            'diviforge-ai-log',
            array($this, 'render')
        );
    }

    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to view this page.', 'diviforge'));
        }

        $jobId = isset($_GET['job']) ? absint($_GET['job']) : 0;

        echo '<div class="df-wrap">';
        echo '<p class="df-kicker">' . esc_html__('Core AI Engine', 'diviforge') . '</p>';

        if ($jobId) {
            $this->renderDetail($jobId);
        } else {
            $this->renderList();
        }

        echo '</div>';
    }

    private function renderList(): void {
        $jobs = $this->jobs->all(50);

        echo '<h1>' . esc_html__('AI Request Log', 'diviforge') . '</h1>';
        echo '<p>' . esc_html__('Every request handled by the DiviForge AI module, with provider, model, status, token and cost tracking.', 'diviforge') . '</p>';

        if (!$jobs) {
            echo '<div class="df-card df-empty-state"><span class="dashicons dashicons-update"></span><h2>' . esc_html__('No AI requests logged yet', 'diviforge') . '</h2><p>' . esc_html__('Requests submitted through a registered AI provider will appear here.', 'diviforge') . '</p></div>';
            return;
        }

        echo '<div class="df-ai-job-list">';
        foreach ($jobs as $job) {
            $detailUrl = add_query_arg(array('page' => 'diviforge-ai-log', 'job' => $job->id()), admin_url('admin.php'));
            echo '<article class="df-ai-job-card is-' . esc_attr($job->status()) . '">';
            echo '<div>';
            echo '<span class="df-status-pill">' . esc_html($job->status()) . '</span>';
            echo '<h3>' . esc_html($job->provider() . ' · ' . $job->model()) . '</h3>';
            echo '<p>' . esc_html(wp_trim_words($job->prompt(), 24)) . '</p>';
            echo '<small>' . esc_html($job->tokens() . ' tokens · $' . number_format($job->cost(), 4) . ' · ' . $job->createdAt()) . '</small>';
            echo '</div>';
            echo '<a class="df-btn df-btn-soft" href="' . esc_url($detailUrl) . '"><span class="dashicons dashicons-visibility"></span>' . esc_html__('View', 'diviforge') . '</a>';
            echo '</article>';
        }
        echo '</div>';
    }

    private function renderDetail(int $jobId): void {
        $job = $this->jobs->find($jobId);
        $backUrl = add_query_arg(array('page' => 'diviforge-ai-log'), admin_url('admin.php'));

        if (!$job) {
            echo '<div class="df-card df-empty-state"><span class="dashicons dashicons-warning"></span><h2>' . esc_html__('AI request not found', 'diviforge') . '</h2></div>';
            echo '<a class="df-btn df-btn-soft" href="' . esc_url($backUrl) . '"><span class="dashicons dashicons-arrow-left-alt2"></span>' . esc_html__('Back to log', 'diviforge') . '</a>';
            return;
        }

        echo '<h1>' . esc_html($job->provider() . ' · ' . $job->model()) . '</h1>';
        echo '<p><span class="df-status-pill">' . esc_html($job->status()) . '</span> &nbsp; ' . esc_html($job->tokens() . ' tokens · $' . number_format($job->cost(), 4) . ' · created ' . $job->createdAt() . ' · updated ' . $job->updatedAt()) . '</p>';

        echo '<div class="df-card"><p class="df-kicker">' . esc_html__('Prompt', 'diviforge') . '</p><textarea readonly>' . esc_textarea($job->prompt()) . '</textarea></div>';
        echo '<div class="df-card"><p class="df-kicker">' . esc_html__('Response', 'diviforge') . '</p><textarea readonly>' . esc_textarea($job->response()) . '</textarea></div>';

        echo '<a class="df-btn df-btn-soft" href="' . esc_url($backUrl) . '"><span class="dashicons dashicons-arrow-left-alt2"></span>' . esc_html__('Back to log', 'diviforge') . '</a>';
    }
}
