<?php
namespace DiviForge\AI\Provider;

if (!defined('ABSPATH')) { exit; }

interface AiProviderInterface {
    public function key(): string;

    public function label(): string;

    public function complete(AiPrompt $prompt): AiCompletion;
}
