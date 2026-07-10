<?php
namespace DiviForge\AI\Provider\OpenAi;

if (!defined('ABSPATH')) { exit; }

use DiviForge\AI\Provider\AiCompletion;
use DiviForge\AI\Provider\AiPrompt;
use DiviForge\AI\Provider\AiProviderInterface;

final class OpenAiProvider implements AiProviderInterface {
    private const ENDPOINT = 'https://api.openai.com/v1/responses';

    private string $apiKey;
    private int $timeout;
    private int $maxTokens;
    private float $temperature;

    public function __construct(string $apiKey, int $timeout = 180, int $maxTokens = 50000, float $temperature = 0.2) {
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
        $this->maxTokens = $maxTokens;
        $this->temperature = $temperature;
    }

    public function key(): string {
        return 'openai';
    }

    public function label(): string {
        return 'OpenAI';
    }

    public function complete(AiPrompt $prompt): AiCompletion {
        if ($this->apiKey === '') {
            throw new \RuntimeException('Missing OpenAI API key.');
        }

        $response = wp_remote_post(self::ENDPOINT, array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => $prompt->model(),
                'input' => $prompt->text(),
                'temperature' => $this->temperature,
                'max_output_tokens' => $this->maxTokens,
            )),
        ));

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException(sprintf('OpenAI returned HTTP %d.', $code));
        }

        $data = json_decode($body, true);
        $text = $this->extractText(is_array($data) ? $data : null, $body);
        $tokens = is_array($data) ? (int) ($data['usage']['total_tokens'] ?? 0) : 0;

        return new AiCompletion($text, $tokens, 0.0);
    }

    private function extractText(?array $data, string $fallback): string {
        if ($data === null) {
            return $fallback;
        }

        if (isset($data['output_text'])) {
            return (string) $data['output_text'];
        }

        $text = '';
        if (!empty($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $out) {
                if (!empty($out['content']) && is_array($out['content'])) {
                    foreach ($out['content'] as $content) {
                        if (isset($content['text'])) {
                            $text .= $content['text'];
                        }
                    }
                }
            }
        }

        return $text !== '' ? $text : $fallback;
    }
}
