<?php
namespace DiviForge\Core;

if (!defined('ABSPATH')) { exit; }

final class Logger {
    private string $option_name = 'diviforge_core_log';
    private int $max_entries = 200;

    public function info(string $message, array $context = array()): void {
        $this->write('info', $message, $context);
    }

    public function warning(string $message, array $context = array()): void {
        $this->write('warning', $message, $context);
    }

    public function error(string $message, array $context = array()): void {
        $this->write('error', $message, $context);
    }

    public function entries(): array {
        $entries = get_option($this->option_name, array());
        return is_array($entries) ? $entries : array();
    }

    private function write(string $level, string $message, array $context): void {
        $entries = $this->entries();
        array_unshift($entries, array(
            'level' => sanitize_key($level),
            'message' => sanitize_text_field($message),
            'context' => $this->sanitizeContext($context),
            'created_at' => current_time('mysql'),
        ));
        update_option($this->option_name, array_slice($entries, 0, $this->max_entries), false);
    }

    private function sanitizeContext(array $context): array {
        $safe = array();
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $safe[sanitize_key((string) $key)] = sanitize_text_field((string) $value);
            }
        }
        return $safe;
    }
}
