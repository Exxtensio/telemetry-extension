<?php

namespace Exxtensio\TelemetryExtension;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

class TelemetryService implements TelemetryInterface
{
    protected TracerProviderInterface $provider;
    protected ?SpanInterface $rootSpan = null;
    protected ?ScopeInterface $rootScope = null;

    public function __construct(TracerProviderInterface $provider) {
        $this->provider = $provider;
    }

    public function startRoot(string $tracerName, string $name, array $attributes = []): void
    {
        if($this->rootSpan) {
            return;
        }

        $tracer = $this->provider->getTracer($tracerName);
        $this->rootSpan = $tracer->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $this->rootSpan->setAttribute($key, $value);
        }

        $this->rootScope = $this->rootSpan->activate();
    }

    public function endRoot(): void
    {
        if($this->rootSpan) {
            $this->rootSpan->end();
            $this->rootSpan = null;
        }

        if ($this->rootScope) {
            $this->rootScope->detach();
            $this->rootScope = null;
        }
    }

    public function startSpan(string $tracerName, string $name, array $attributes = []): array
    {
        $tracer = $this->provider->getTracer($tracerName);
        $span = $tracer->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }
        $scope = $span->activate();
        return [$span, $scope];
    }

    public function endSpan(SpanInterface $span, ScopeInterface $scope): void
    {
        $span->end();
        $scope->detach();
    }

    /**
     * @throws Throwable
     */
    public function withSpan(string $tracerName, string $name, callable $callback, array $attributes = []): mixed
    {
        $tracer = $this->provider->getTracer($tracerName);
        $span = $tracer->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        $scope = $span->activate();

        try {
            $response = $callback($span);
            $span->setStatus(StatusCode::STATUS_OK);
            return $response;
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->end();
            $scope->detach();
        }
    }
}
