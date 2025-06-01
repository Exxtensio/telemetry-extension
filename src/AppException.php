<?php

namespace Exxtensio\TelemetryExtension;

use Illuminate\Support\Facades\Log;

final class AppException
{
    private static function getType($mainClass, $action): ?string
    {
        return match ($mainClass) {
            \Illuminate\Http\Request::class => match ($action) {
                'not_found' => 'Route not found',
                default => 'Unknown error'
            },
            \Exxtensio\TelemetryExtension\AppHandler::class => match ($action) {
                'default' => 'Loki push failed',
                default => 'Unknown error'
            },
            \App\Http\Controllers\ApiEventController::class => match ($action) {
                'connection_failed' => 'Rabbit connection failed',
                'connection_timeout' => 'Rabbit connection timeout',
                default => 'Unknown error'
            },
            \App\Services\UserService::class => match ($action) {
                'deleted' => 'User deleted failed',
                'registered' => 'User registered failed',
                'updated' => 'User updated failed',
                'subscribed' => 'User subscribed failed',
                default => 'Unknown error'
            },
            \App\Console\Commands\RabbitRunCommand::class => match ($action) {
                'collect_handle' => 'Collect command failed',
                'insert_handle' => 'Insert command failed',
                default => 'Unknown error'
            },
            \App\Services\ConsumeRabbitService::class => match ($action) {
                'collect_timeout' => 'AMQP Collect connection timeout',
                'collect_consuming_error' => 'AMQP Collect failed while consuming',
                'collect_connection' => 'AMQP Collect connection failed',
                'collect_error' => 'AMQP Collect failed',
                'insert_timeout' => 'AMQP Insert connection timeout',
                'insert_consuming_error' => 'AMQP Insert failed while consuming',
                'insert_connection' => 'AMQP Insert connection failed',
                'insert_error' => 'AMQP Insert failed',
                default => 'Unknown error'
            },
            \Exxtensio\TelemetryExtension\TelemetryService::class => match ($action) {
                'default' => 'Telemetry withSpan failed',
                default => 'Unknown error'
            },
            \App\Location\Phone::class => match ($action) {
                'get_instance' => 'PhoneNumberUtil getInstance failed',
                default => 'Unknown error'
            },
            default => 'Unknown error'
        };
    }

    static public function set($mainClass, $action = 'default', $message = null, $channel = 'loki'): void
    {
        $title = self::getType($mainClass, $action);

        if($channel !== 'loki') {
            Log::channel('slack')->error($title, [
                'class' => $mainClass,
                'action' => $action,
                'message' => $message
            ]);
        } else {
            Log::error($title, [
                'class' => $mainClass,
                'action' => $action,
                'message' => $message
            ]);
        }
    }
}
