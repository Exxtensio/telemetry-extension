<?php

namespace Exxtensio\TelemetryExtension;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Common\Time\SystemClock;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Registry;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SemConv\ResourceAttributes;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TracerProviderInterface::class, function () {

            $resource = ResourceInfo::create(Attributes::create([
                ResourceAttributes::SERVICE_NAME => 'app',
                ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => config('app.env', 'local'),
            ]));

            $exporter = Registry::spanExporterFactory('otlp')->create();

            $clock = new SystemClock();
            $processor = new BatchSpanProcessor($exporter, $clock);
            return new TracerProvider(
                [$processor],
                null,
                $resource
            );
        });

        $this->app->singleton(TelemetryService::class, function ($app) {
            return new TelemetryService($app->make(TracerProviderInterface::class));
        });
    }

    public function boot(): void
    {
        $this->app->terminating(function () {
            $provider = app(TracerProviderInterface::class);
            if (method_exists($provider, 'shutdown')) {
                $provider->shutdown();
            }
        });
    }
}
