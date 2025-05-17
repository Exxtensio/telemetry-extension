<?php

namespace Exxtensio\TelemetryExtension;

use OpenTelemetry\API\Trace;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

class TelemetryService implements TelemetryInterface
{
    protected Trace\TracerProviderInterface $provider;
    protected ?Trace\SpanInterface $rootSpan = null;
    protected ?ScopeInterface $rootScope = null;

    public function __construct(Trace\TracerProviderInterface $provider) {
        $this->provider = $provider;
    }

    public function startRoot(string $tracerName, string $name, array $attributes = []): void
    {
        if($this->rootSpan) {
            return;
        }

        $tracer = $this->provider->getTracer($tracerName);
        $this->rootSpan = $tracer->spanBuilder($name)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $this->rootSpan->setAttribute($key, $value);
        }

        $this->rootScope = $this->rootSpan->activate();
    }

    public function endRoot(): void
    {
        if($this->rootSpan) {
            $this->rootSpan->setStatus(Trace\StatusCode::STATUS_OK);
            $this->rootSpan->end();
            $this->rootSpan = null;
        }

        if ($this->rootScope) {
            $this->rootScope->detach();
            $this->rootScope = null;
        }
    }

    /**
     * @throws Throwable
     */
    public function withSpan(string $tracerName, string $name, callable $callback, array $trace = [], array $attributes = [], $msg = false): mixed
    {
        $tracer = $this->provider->getTracer($tracerName);
        $context = TraceContextPropagator::getInstance()->extract($trace, null, Context::getCurrent());

        $span = $tracer->spanBuilder($name)
            ->setParent($context)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        $scope = $span->activate();

        try {
            $response = $callback($span);
            $span->setStatus(Trace\StatusCode::STATUS_OK);
            if($msg) $msg->ack();
            return $response;
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(Trace\StatusCode::STATUS_ERROR, $e->getMessage());
            if($msg) $msg->nack(true);
            throw $e;
        } finally {
            $span->end();
            $scope->detach();
        }
    }

    public function getContext($context): array
    {
        $carrier = [];
        TraceContextPropagator::getInstance()->inject($carrier, null, $context);
        return $carrier;
    }
}
