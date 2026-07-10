<?php
namespace DiviForge\Licensing;

if (!defined('ABSPATH')) { exit; }

final class PageLimitGuard {
    private LicenseServiceInterface $license;

    public function __construct(LicenseServiceInterface $license) {
        $this->license = $license;
    }

    public function canCreateAnotherPage(): bool {
        if ($this->license->isLicensed()) {
            return true;
        }

        return $this->countDiviForgePages() < PlanLimits::FREE_PAGE_LIMIT;
    }

    public function remainingFreePages(): int {
        if ($this->license->isLicensed()) {
            return PHP_INT_MAX;
        }

        return max(0, PlanLimits::FREE_PAGE_LIMIT - $this->countDiviForgePages());
    }

    private function countDiviForgePages(): int {
        global $wpdb;

        return (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key IN ('_diviforge_package_imported_at', '_diviforge_version')
             AND p.post_type = 'page'
             AND p.post_status != 'trash'"
        );
    }
}
