<?php
namespace DiviForge\Licensing;

if (!defined('ABSPATH')) { exit; }

use DiviForge\Core\Container;

final class LicensingServiceProvider {
    private Container $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function register(): void {
        $this->container->set(LicenseServiceInterface::class, function () {
            return new OptionBackedLicenseService();
        });

        $this->container->set(PageLimitGuard::class, function (Container $container) {
            return new PageLimitGuard($container->get(LicenseServiceInterface::class));
        });
    }
}
