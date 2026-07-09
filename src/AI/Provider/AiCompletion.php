<?php
namespace DiviForge\AI\Provider;

if (!defined('ABSPATH')) { exit; }

final class AiCompletion {
    private string $text;
    private int $tokens;
    private float $cost;

    public function __construct(string $text, int $tokens = 0, float $cost = 0.0) {
        $this->text = $text;
        $this->tokens = $tokens;
        $this->cost = $cost;
    }

    public function text(): string { return $this->text; }
    public function tokens(): int { return $this->tokens; }
    public function cost(): float { return $this->cost; }
}
