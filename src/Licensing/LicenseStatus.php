<?php
namespace DiviForge\Licensing;

if (!defined('ABSPATH')) { exit; }

final class LicenseStatus {
    public const FREE = 'free';
    public const ACTIVE = 'active';

    public static function all(): array {
        return array(self::FREE, self::ACTIVE);
    }

    public static function isValid(string $status): bool {
        return in_array($status, self::all(), true);
    }
}
