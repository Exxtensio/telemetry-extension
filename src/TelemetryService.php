<?php

namespace Exxtensio\TelemetryExtension;

use OpenTelemetry\API\Trace\TracerInterface;
use Throwable;

class TelemetryService
{
    public function __construct(protected TracerInterface $tracer) {}

    /**
     * @throws Throwable
     */
    public function trace(string $name, callable $callback, array $attributes = []): mixed
    {
        $span = $this->tracer->spanBuilder($name)->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        try {
            return $callback();
        } catch (Throwable $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $span->end();
        }
    }
}
