<?php
namespace DiviForge\AI\Provider;

if (!defined('ABSPATH')) { exit; }

final class AiPrompt {
    private string $model;
    private string $text;
    private array $options;

    public function __construct(string $model, string $text, array $options = array()) {
        $this->model = $model;
        $this->text = $text;
        $this->options = $options;
    }

    public function model(): string { return $this->model; }
    public function text(): string { return $this->text; }
    public function options(): array { return $this->options; }
}
