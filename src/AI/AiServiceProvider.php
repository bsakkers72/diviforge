<?php
namespace DiviForge\AI;

if (!defined('ABSPATH')) { exit; }

use DiviForge\AI\Provider\AiProviderRegistry;
use DiviForge\AI\Provider\OpenAi\OpenAiProvider;
use DiviForge\Core\Container;
use DiviForge\Core\Logger;
use DiviForge\Repository\AiJobRepository;
use DiviForge\Repository\AiJobRepositoryInterface;

final class AiServiceProvider {
    private Container $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function register(): void {
        $this->container->set(AiJobRepositoryInterface::class, function () {
            return new AiJobRepository();
        });

        $this->container->set(AiProviderRegistry::class, function () {
            $registry = new AiProviderRegistry();
            $registry->register($this->makeOpenAiProvider());

            return $registry;
        });

        $this->container->set(AiService::class, function (Container $container) {
            return new AiService(
                $container->get(AiJobRepositoryInterface::class),
                $container->get(AiProviderRegistry::class),
                $container->get(Logger::class)
            );
        });
    }

    private function makeOpenAiProvider(): OpenAiProvider {
        $settings = get_option('diviforge_ai_settings', array());
        $settings = is_array($settings) ? $settings : array();

        return new OpenAiProvider(
            (string) ($settings['api_key'] ?? ''),
            (int) ($settings['timeout'] ?? 180),
            (int) ($settings['max_tokens'] ?? 50000),
            (float) str_replace(',', '.', (string) ($settings['temperature'] ?? '0.2'))
        );
    }
}
