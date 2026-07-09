<?php
namespace DiviForge\Core;

if (!defined('ABSPATH')) { exit; }

final class Config {
    private string $version;
    private string $path;
    private string $url;

    public function __construct(string $version, string $path, string $url) {
        $this->version = $version;
        $this->path = trailingslashit($path);
        $this->url = trailingslashit($url);
    }

    public function version(): string { return $this->version; }
    public function path(): string { return $this->path; }
    public function url(): string { return $this->url; }

    public function srcPath(string $relative = ''): string {
        return $this->path . 'src/' . ltrim($relative, '/');
    }
}
