<?php
namespace DiviForge\Core;

if (!defined('ABSPATH')) { exit; }

final class Container {
    /** @var array<string, callable|object|mixed> */
    private array $bindings = array();

    /** @var array<string, object|mixed> */
    private array $instances = array();

    public function set(string $id, $resolver): void {
        $this->bindings[$id] = $resolver;
    }

    public function has(string $id): bool {
        return array_key_exists($id, $this->bindings) || array_key_exists($id, $this->instances);
    }

    public function get(string $id) {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!array_key_exists($id, $this->bindings)) {
            throw new \RuntimeException(sprintf('DiviForge service not registered: %s', $id));
        }

        $resolver = $this->bindings[$id];
        $instance = is_callable($resolver) ? $resolver($this) : $resolver;
        $this->instances[$id] = $instance;

        return $instance;
    }
}
