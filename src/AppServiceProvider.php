<?php

namespace Exxtensio\TelemetryExtension;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Registry;
use OpenTelemetry\SemConv\ResourceAttributes;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/telemetry-extension.php',
            'telemetry'
        );

        $resource = ResourceInfo::create(Attributes::create([
            ResourceAttributes::SERVICE_NAME => config('telemetry.name'),
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => config('app.env', 'local'),
        ]));

        $exporter = Registry::spanExporterFactory('otlp')->create();

        $provider = new TracerProvider(
            new SimpleSpanProcessor($exporter),
            null,
            $resource
        );

        $this->app->instance('otel.tracer_provider', $provider);
        $this->app->instance('otel.tracer', $provider->getTracer(config('telemetry.name')));

        $this->app->singleton(TelemetryService::class, function ($app) {
            return new TelemetryService($app->make('otel.tracer'));
        });
    }

    public function boot(Filesystem $filesystem): void
    {
        if (!$filesystem->exists(config_path('telemetry-extension.php')))
            $filesystem->copy(__DIR__ . '/../config/telemetry-extension.php', config_path('telemetry-extension.php'));
    }
}
