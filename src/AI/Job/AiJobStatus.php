<?php
namespace DiviForge\AI\Job;

if (!defined('ABSPATH')) { exit; }

final class AiJobStatus {
    public const QUEUED = 'queued';
    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const ERROR = 'error';

    public static function all(): array {
        return array(self::QUEUED, self::RUNNING, self::COMPLETED, self::ERROR);
    }

    public static function isValid(string $status): bool {
        return in_array($status, self::all(), true);
    }
}
