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

        $parameters = count($args) === 1 && is_array($args[0]) ? $args[0] : $args;

        foreach ($this->eventListeners[$event] as $callback) {
            app()->call($callback, $parameters);
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
