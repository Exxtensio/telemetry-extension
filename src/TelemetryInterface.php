<?php

namespace Exxtensio\TelemetryExtension;

interface TelemetryInterface
{
    public function startRoot(string $tracerName, string $name, array $attributes = []): void;
    public function endRoot(): void;
    public function withSpan(string $tracerName, string $name, callable $callback, array $trace = [], array $attributes = []): mixed;
}
