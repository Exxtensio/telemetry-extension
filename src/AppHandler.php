<?php

namespace Exxtensio\TelemetryExtension;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Handler\AbstractProcessingHandler;

class AppHandler extends AbstractProcessingHandler
{
    protected Client $client;

    protected string $url;
    protected string $service;

    public function __construct($level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->client = new Client;
        $this->url = rtrim(config('logging.channels.loki.url'), '/');
        $this->service = env('APP_SERVICE', 'default');
    }

    protected function write(LogRecord $record): void
    {
        try {
            $timestamp = Carbon::now()->timestamp * 1000000000;

            $logMessage = array_merge(
                ['title' => $record->message],
                $record->context
            );

            $logEntry = [
                'streams' => [
                    [
                        'stream' => [
                            'service' => $this->service,
                            'level' => strtolower($record->level->getName()),
                        ],
                        'values' => [
                            [(string)$timestamp, json_encode($logMessage)],
                        ],
                    ],
                ],
            ];

            $this->client->post("$this->url/loki/api/v1/push", [
                'json' => $logEntry,
                'headers' => ['Content-Type' => 'application/json'],
            ]);

        } catch (RequestException $e) {
            AppException::set(self::class, 'default', $e->getMessage(), 'slack');
        } catch (GuzzleException $e) {
            AppException::set(self::class, 'default', $e->getMessage(), 'slack');
        }
    }
}
