<?php
namespace DiviForge\Core;

if (!defined('ABSPATH')) { exit; }

final class Autoloader {
    private string $base_dir;
    private string $prefix;

    public function __construct(string $base_dir, string $prefix = 'DiviForge\\') {
        $this->base_dir = rtrim($base_dir, '/\\') . DIRECTORY_SEPARATOR;
        $this->prefix = $prefix;
    }

    public function register(): void {
        spl_autoload_register(array($this, 'load'));
    }

    public function load(string $class): void {
        $len = strlen($this->prefix);
        if (strncmp($this->prefix, $class, $len) !== 0) {
            return;
        }

        $relative = substr($class, $len);
        $file = $this->base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}
