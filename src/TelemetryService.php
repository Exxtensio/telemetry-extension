<?php

namespace Exxtensio\TelemetryExtension;

use OpenTelemetry\API\Trace;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

class TelemetryService implements TelemetryInterface
{
    protected bool $enabled = true;
    protected Trace\TracerProviderInterface $provider;
    protected ?Trace\SpanInterface $rootSpan = null;
    protected ?ScopeInterface $rootScope = null;

    public function __construct(Trace\TracerProviderInterface $provider)
    {
        $this->provider = $provider;
        $this->enabled = filter_var(env('TELEMETRY_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function startRoot(string $tracerName, string $name, array $attributes = []): void
    {
        if (!$this->enabled || $this->rootSpan) return;

        $tracer = $this->provider->getTracer($tracerName);
        $this->rootSpan = $tracer->spanBuilder($name)->startSpan();

        foreach ($attributes as $key => $value) {
            $this->rootSpan->setAttribute($key, $value);
        }

        $this->rootScope = $this->rootSpan->activate();
    }

    public function endRoot(): void
    {
        if (!$this->enabled) return;

        if ($this->rootSpan) {
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
    public function withSpan(string $tracerName, string $name, callable $callback, array $trace = [], array $attributes = []): mixed
    {
        if (!$this->enabled) {
            try {
                return $callback(null);
            } catch (Throwable $e) {
                AppException::set('telemetry', 'default', $e->getMessage());
                throw $e;
            }
        }

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
            return $response;
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(Trace\StatusCode::STATUS_ERROR, $e->getMessage());
            AppException::set('telemetry', 'default', $e->getMessage());
            throw $e;
        } finally {
            $span->end();
            $scope->detach();
        }
    }

    public function getContext($context): array
    {
        if (!$this->enabled) {
            return [];
        }

        $carrier = [];
        TraceContextPropagator::getInstance()->inject($carrier, null, $context);
        return $carrier;
    }
}
