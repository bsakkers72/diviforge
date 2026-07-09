<?php
namespace DiviForge\Repository;

if (!defined('ABSPATH')) { exit; }

use DiviForge\AI\Job\AiJob;

interface AiJobRepositoryInterface {
    public function create(AiJob $job): AiJob;

    public function update(AiJob $job): void;

    public function find(int $id): ?AiJob;

    /** @return AiJob[] */
    public function all(int $limit = 50): array;
}
