<?php

namespace Exxtensio\TelemetryExtension;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ScopeInterface;

interface TelemetryInterface
{
    public function startRoot(string $tracerName, string $name, array $attributes = []): void;
    public function endRoot(): void;
    public function startSpan(string $tracerName, string $name, array $attributes = []): array;
    public function endSpan(SpanInterface $span, ScopeInterface $scope): void;
    public function withSpan(string $tracerName, string $name, callable $callback, array $attributes = []): mixed;
}
