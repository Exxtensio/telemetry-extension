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
        $this->app->singleton(TracerProvider::class, function () {
            $serviceName = config('telemetry-extension.default');

            $resource = ResourceInfo::create(Attributes::create([
                ResourceAttributes::SERVICE_NAME => $serviceName,
                ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => config('app.env', 'local'),
            ]));

            $exporter = Registry::spanExporterFactory('otlp')->create();

            return new TracerProvider(
                new SimpleSpanProcessor($exporter),
                null,
                $resource
            );
        });

        $this->app->singleton(
            TelemetryManager::class,
            fn () => new TelemetryManager($this->app->make(TracerProvider::class))
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../config/telemetry-extension.php',
            'telemetry-extension'
        );
    }

    public function boot(Filesystem $filesystem): void
    {
        if (!$filesystem->exists(config_path('telemetry-extension.php')))
            $filesystem->copy(__DIR__ . '/../config/telemetry-extension.php', config_path('telemetry-extension.php'));
    }
}
