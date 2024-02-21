<?php

namespace O21\LaravelWallet\Concerns;

trait Eventable
{
    protected array $eventListeners = [];

    protected function on(string $event, callable $callback): self
    {
        $this->eventListeners[$event][] = $callback;

        return $this;
    }

    protected function fire(string $event, ...$args): void
    {
        if (! isset($this->eventListeners[$event])) {
            return;
        }

        foreach ($this->eventListeners[$event] as $callback) {
            $callback(...$args);
        }
    }

    protected function hasListeners(string $event): bool
    {
        return isset($this->eventListeners[$event]);
    }

    protected function off(string $event): void
    {
        unset($this->eventListeners[$event]);
    }
}
