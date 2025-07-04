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
        string               $groupName,
        string               $streamName,
                             $level = Logger::DEBUG,
        bool                 $bubble = true
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
            $groups = $this->client->describeLogGroups(['logGroupNamePrefix' => $this->groupName]);
            $groupExists = collect($groups['logGroups'] ?? [])->pluck('logGroupName')->contains($this->groupName);
            if (!$groupExists) {
                $this->client->createLogGroup([
                    'logGroupName' => $this->groupName,
                ]);
            }

            $streams = $this->client->describeLogStreams(['logGroupName' => $this->groupName, 'logStreamNamePrefix' => $this->streamName]);
            $stream = collect($streams['logStreams'] ?? [])->firstWhere('logStreamName', $this->streamName);
            if (!$stream) {
                $this->client->createLogStream([
                    'logGroupName' => $this->groupName,
                    'logStreamName' => $this->streamName,
                ]);
            }

            $sequenceToken = $stream['uploadSequenceToken'] ?? null;

            $params = [
                'logGroupName' => $this->groupName,
                'logStreamName' => $this->streamName,
                'logEvents' => [
                    [
                        'message' => $record->formatted,
                        'timestamp' => round(microtime(true) * 1000),
                    ],
                ],
            ];

            if ($sequenceToken) $params['sequenceToken'] = $sequenceToken;

            $this->client->putLogEvents($params);
        } catch (\Exception $e) {
            AppException::set('cloudwatch', 'default', $e->getMessage(), 'slack');
        }
    }
}
