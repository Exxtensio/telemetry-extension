<?php

namespace Exxtensio\TelemetryExtension;

use OpenTelemetry\API\Trace;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

class TelemetryService
{
    protected ?SpanInterface $activeRootSpan = null;
    protected ?ScopeInterface $activeRootScope = null;

    public function __construct(protected Trace\TracerInterface $tracer) {}

    public function traceRoot(string $name, array $attributes = []): void
    {
        $span = $this->tracer->spanBuilder($name)
            ->setSpanKind(Trace\SpanKind::KIND_SERVER)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        $scope = $span->activate();

        $this->activeRootSpan = $span;
        $this->activeRootScope = $scope;
    }

    /** @throws Throwable */
    public function trace(string $name, callable $callback, array $attributes = []): mixed
    {
        $span = $this->tracer->spanBuilder($name)
            ->setSpanKind(Trace\SpanKind::KIND_INTERNAL)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        $scope = $span->activate();

        try {
            $result = $callback($span);
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(Trace\StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->end();
            $scope->detach();
        }

        return $result;
    }

    /**
     * Завершает root trace вручную.
     */
    public function endActiveRoot(): void
    {
        if ($this->activeRootSpan?->isRecording()) {
            $this->activeRootSpan->end();
        }

        $this->activeRootScope?->detach();

        $this->activeRootSpan = null;
        $this->activeRootScope = null;
    }
}
