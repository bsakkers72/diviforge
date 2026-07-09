<?php
namespace DiviForge\AI\Job;

if (!defined('ABSPATH')) { exit; }

final class AiJob {
    private ?int $id;
    private string $provider;
    private string $model;
    private string $prompt;
    private string $response;
    private string $status;
    private int $tokens;
    private float $cost;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(
        string $provider,
        string $model,
        string $prompt,
        string $response = '',
        string $status = AiJobStatus::QUEUED,
        int $tokens = 0,
        float $cost = 0.0,
        ?int $id = null,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->provider = $provider;
        $this->model = $model;
        $this->prompt = $prompt;
        $this->response = $response;
        $this->status = $status;
        $this->tokens = $tokens;
        $this->cost = $cost;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function id(): ?int { return $this->id; }
    public function provider(): string { return $this->provider; }
    public function model(): string { return $this->model; }
    public function prompt(): string { return $this->prompt; }
    public function response(): string { return $this->response; }
    public function status(): string { return $this->status; }
    public function tokens(): int { return $this->tokens; }
    public function cost(): float { return $this->cost; }
    public function createdAt(): string { return $this->createdAt; }
    public function updatedAt(): string { return $this->updatedAt; }

    public function withId(int $id): self {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function withStatus(string $status): self {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withResult(string $response, int $tokens, float $cost): self {
        $clone = clone $this;
        $clone->response = $response;
        $clone->tokens = $tokens;
        $clone->cost = $cost;
        $clone->status = AiJobStatus::COMPLETED;
        return $clone;
    }

    public function withError(string $message): self {
        $clone = clone $this;
        $clone->response = $message;
        $clone->status = AiJobStatus::ERROR;
        return $clone;
    }
}
