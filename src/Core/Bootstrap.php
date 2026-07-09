<?php
namespace DiviForge\Core;

if (!defined('ABSPATH')) { exit; }

final class Bootstrap {
    private static ?Bootstrap $instance = null;
    private Container $container;

    public static function instance(): Bootstrap {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->container = new Container();
        (new ServiceRegistry($this->container))->registerDefaults();
    }

    public function boot(): void {
        /** @var Logger $logger */
        $logger = $this->container->get(Logger::class);
        $logger->info('DiviForge core bootstrap loaded', array('version' => DIVIFORGE_VERSION));

        /** @var EventDispatcher $events */
        $events = $this->container->get(EventDispatcher::class);
        $events->action('core_booted', $this->container);

        // Keep the current legacy plugin entrypoint active while the new architecture is introduced gradually.
        if (class_exists('DiviForge')) {
            \DiviForge::instance();
        }
    }

    public function container(): Container {
        return $this->container;
    }
}
