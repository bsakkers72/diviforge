<?php
namespace DiviForge\Repository;

if (!defined('ABSPATH')) { exit; }

use DiviForge\AI\Job\AiJob;
use DiviForge\AI\Job\AiJobStatus;
use DiviForge\Infrastructure\Database\AiJobsTable;

final class AiJobRepository implements AiJobRepositoryInterface {
    public function create(AiJob $job): AiJob {
        global $wpdb;

        $now = current_time('mysql');
        $wpdb->insert(AiJobsTable::tableName(), array(
            'provider' => $job->provider(),
            'model' => $job->model(),
            'prompt' => $job->prompt(),
            'response' => $job->response(),
            'status' => $job->status(),
            'tokens' => $job->tokens(),
            'cost' => $job->cost(),
            'created_at' => $now,
            'updated_at' => $now,
        ));

        return $job->withId((int) $wpdb->insert_id);
    }

    public function update(AiJob $job): void {
        global $wpdb;

        if ($job->id() === null) {
            throw new \RuntimeException('Cannot update an AiJob without an id.');
        }

        $wpdb->update(
            AiJobsTable::tableName(),
            array(
                'provider' => $job->provider(),
                'model' => $job->model(),
                'prompt' => $job->prompt(),
                'response' => $job->response(),
                'status' => $job->status(),
                'tokens' => $job->tokens(),
                'cost' => $job->cost(),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $job->id())
        );
    }

    public function find(int $id): ?AiJob {
        global $wpdb;

        $table = AiJobsTable::tableName();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);

        return $row ? $this->hydrate($row) : null;
    }

    public function all(int $limit = 50): array {
        global $wpdb;

        $table = AiJobsTable::tableName();
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit), ARRAY_A);

        return array_map(array($this, 'hydrate'), $rows ?: array());
    }

    private function hydrate(array $row): AiJob {
        $status = AiJobStatus::isValid((string) $row['status']) ? (string) $row['status'] : AiJobStatus::QUEUED;

        return new AiJob(
            (string) $row['provider'],
            (string) $row['model'],
            (string) $row['prompt'],
            (string) $row['response'],
            $status,
            (int) $row['tokens'],
            (float) $row['cost'],
            (int) $row['id'],
            (string) $row['created_at'],
            (string) $row['updated_at']
        );
    }
}
