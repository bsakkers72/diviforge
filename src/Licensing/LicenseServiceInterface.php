<?php
namespace DiviForge\Licensing;

if (!defined('ABSPATH')) { exit; }

interface LicenseServiceInterface {
    public function isLicensed(): bool;

    public function status(): string;
}
