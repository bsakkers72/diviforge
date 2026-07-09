<?php
namespace DiviForge\AI\Provider;

if (!defined('ABSPATH')) { exit; }

final class AiProviderRegistry {
    /** @var array<string, AiProviderInterface> */
    private array $providers = array();

    public function register(AiProviderInterface $provider): void {
        $this->providers[$provider->key()] = $provider;
    }

    public function has(string $key): bool {
        return isset($this->providers[$key]);
    }

    public function get(string $key): AiProviderInterface {
        if (!$this->has($key)) {
            throw new \RuntimeException(sprintf('DiviForge AI provider not registered: %s', $key));
        }

        return $this->providers[$key];
    }

    /** @return AiProviderInterface[] */
    public function all(): array {
        return $this->providers;
    }
}
