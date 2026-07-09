<?php
namespace DiviForge\Licensing;

if (!defined('ABSPATH')) { exit; }

/**
 * Placeholder license backend until Barry picks a licensing provider
 * (e.g. Freemius or a self-hosted store). Stores status in a single
 * WP option so PageLimitGuard has something real to depend on now.
 */
final class OptionBackedLicenseService implements LicenseServiceInterface {
    private const OPTION_NAME = 'diviforge_license_status';

    public function isLicensed(): bool {
        return $this->status() === LicenseStatus::ACTIVE;
    }

    public function status(): string {
        $status = get_option(self::OPTION_NAME, LicenseStatus::FREE);

        return LicenseStatus::isValid($status) ? $status : LicenseStatus::FREE;
    }
}
