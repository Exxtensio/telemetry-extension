<?php

namespace Exxtensio\TelemetryExtension;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Monolog\Logger;

class CloudWatchLoggerFactory
{
    public function __invoke(array $config)
    {
        $client = new CloudWatchLogsClient([
            'region' => config('services.ses.region', 'us-east-1'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ]);

        $handler = new AppHandler(
            $client,
            $config['log_group'],
            $config['log_stream'],
            $config['level'] ?? Logger::DEBUG
        );
        return new Logger('cloudwatch', [$handler]);
    }
}
