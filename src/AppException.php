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
                'insert_consume_message_error' => 'AMQP Insert failed while processing message',
                'insert_consume_nack_error' => 'AMQP Insert failed to nack message',
                'insert_connection_dropped' => 'AMQP Insert connection or channel dropped unexpectedly',
                'insert_wait_error' => 'AMQP Insert failed while waiting for message',
                'insert_error' => 'AMQP Insert failed with unexpected error during main loop',
                'collect_consume_message_error' => 'AMQP Collect failed while processing message',
                'collect_consume_nack_error' => 'AMQP Collect failed to nack message',
                'collect_connection_dropped' => 'AMQP Collect connection or channel dropped unexpectedly',
                'collect_wait_error' => 'AMQP Collect failed while waiting for message',
                'collect_error' => 'AMQP Collect failed with unexpected error during main loop',
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
