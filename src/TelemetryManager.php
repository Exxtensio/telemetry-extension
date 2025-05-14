<?php

namespace Exxtensio\TelemetryExtension;

use OpenTelemetry\SDK\Trace\TracerProvider;

class TelemetryManager
{
    protected array $services = [];

    public function __construct(protected TracerProvider $provider) {}

    public function get(string $name): TelemetryService
    {
        return $this->services[$name] ??= new TelemetryService(
            $this->provider->getTracer($name)
        );
    }
}
