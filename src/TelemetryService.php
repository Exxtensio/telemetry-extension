<?php

namespace Exxtensio\TelemetryExtension;

use OpenTelemetry\API\Trace;
use Throwable;

class TelemetryService
{
    public function __construct(protected Trace\TracerInterface $tracer) {}

    /**
     * @throws Throwable
     */
    public function trace(string $name, callable $callback, array $attributes = [], bool $autoFinish = true): mixed
    {
        $span = $this->tracer->spanBuilder($name)
            ->setSpanKind(Trace\SpanKind::KIND_SERVER)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        $scope = $span->activate();

        try {
            return $callback($span);
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(Trace\StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            if ($autoFinish) {
                $span->end();
                $scope->detach();
            }
        }
    }

    public function traceRoot(string $name, array $attributes = []): array
    {
        $span = $this->tracer->spanBuilder($name)
            ->setSpanKind(Trace\SpanKind::KIND_SERVER)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        $scope = $span->activate();

        return [$span, $scope];
    }
}
