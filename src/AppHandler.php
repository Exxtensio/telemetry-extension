<?php

namespace Exxtensio\TelemetryExtension;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Monolog\Logger;

class AppHandler extends AbstractProcessingHandler
{
    protected $client;
    protected $groupName;
    protected $streamName;

    public function __construct(
        CloudWatchLogsClient $client,
        string $groupName,
        string $streamName,
        $level = Logger::DEBUG,
        bool $bubble = true
    )
    {
        parent::__construct($level, $bubble);
        $this->client = $client;
        $this->groupName = $groupName;
        $this->streamName = $streamName;
    }

    protected function write(LogRecord $record): void
    {
        try {
            $this->client->putLogEvents([
                'logGroupName' => $this->groupName,
                'logStreamName' => $this->streamName,
                'logEvents' => [
                    [
                        'message' => $record->formatted,
                        'timestamp' => round(microtime(true) * 1000),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            AppException::set('cloudwatch', 'default', $e->getMessage(), 'slack');
        }
    }
}
