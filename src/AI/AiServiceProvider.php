<?php
namespace DiviForge\AI;

if (!defined('ABSPATH')) { exit; }

use DiviForge\AI\Provider\AiProviderRegistry;
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
            return new AiProviderRegistry();
        });

        $this->container->set(AiService::class, function (Container $container) {
            return new AiService(
                $container->get(AiJobRepositoryInterface::class),
                $container->get(AiProviderRegistry::class),
                $container->get(Logger::class)
            );
        });
    }
}
