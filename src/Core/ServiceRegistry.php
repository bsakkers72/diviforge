<?php
namespace DiviForge\Core;

if (!defined('ABSPATH')) { exit; }

final class ServiceRegistry {
    private Container $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function registerDefaults(): void {
        $this->container->set(Config::class, function () {
            return new Config(DIVIFORGE_VERSION, DIVIFORGE_PATH, DIVIFORGE_URL);
        });

        $this->container->set(Logger::class, function () {
            return new Logger();
        });

        $this->container->set(EventDispatcher::class, function () {
            return new EventDispatcher();
        });
    }
}
