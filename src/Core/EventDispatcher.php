<?php
namespace DiviForge\Core;

if (!defined('ABSPATH')) { exit; }

final class EventDispatcher {
    public function action(string $name, ...$args): void {
        do_action('diviforge/' . $name, ...$args);
    }

    public function filter(string $name, $value, ...$args) {
        return apply_filters('diviforge/' . $name, $value, ...$args);
    }
}
