<?php
namespace DiviForge\AI;

if (!defined('ABSPATH')) { exit; }

use DiviForge\AI\Job\AiJob;
use DiviForge\AI\Job\AiJobStatus;
use DiviForge\AI\Provider\AiPrompt;
use DiviForge\AI\Provider\AiProviderRegistry;
use DiviForge\Core\Logger;
use DiviForge\Repository\AiJobRepositoryInterface;

final class AiService {
    private AiJobRepositoryInterface $jobs;
    private AiProviderRegistry $providers;
    private Logger $logger;

    public function __construct(AiJobRepositoryInterface $jobs, AiProviderRegistry $providers, Logger $logger) {
        $this->jobs = $jobs;
        $this->providers = $providers;
        $this->logger = $logger;
    }

    public function submit(string $provider, string $model, string $prompt): AiJob {
        $job = $this->jobs->create(new AiJob($provider, $model, $prompt));
        $this->logger->info('AI job queued', array(
            'job_id' => $job->id(),
            'provider' => $provider,
            'model' => $model,
        ));

        return $job;
    }

    public function run(AiJob $job): AiJob {
        if (!$this->providers->has($job->provider())) {
            $failed = $job->withError(sprintf('AI provider not registered: %s', $job->provider()));
            $this->jobs->update($failed);
            return $failed;
        }

        $running = $job->withStatus(AiJobStatus::RUNNING);
        $this->jobs->update($running);

        try {
            $completion = $this->providers->get($job->provider())->complete(new AiPrompt($job->model(), $job->prompt()));
            $result = $running->withResult($completion->text(), $completion->tokens(), $completion->cost());
        } catch (\Throwable $exception) {
            $result = $running->withError($exception->getMessage());
            $this->logger->error('AI job failed', array(
                'job_id' => $job->id(),
                'message' => $exception->getMessage(),
            ));
        }

        $this->jobs->update($result);

        return $result;
    }
}
