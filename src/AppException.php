<?php

namespace Exxtensio\TelemetryExtension;

use Illuminate\Support\Facades\Log;

final class AppException
{
    private static function getType($mainClass, $type): ?string
    {
        return match ($mainClass) {
            \Illuminate\Http\Request::class => match ($type) {
                'not_found' => 'Route not found',
            },
            \Exxtensio\TelemetryExtension\AppHandler::class => 'Loki push failed',
            \App\Http\Controllers\ApiEventController::class => match ($type) {
                'connection_failed' => 'Rabbit connection failed',
                'connection_timeout' => 'Rabbit connection timeout',
            },
            \App\Services\UserService::class => match ($type) {
                'deleted' => 'User deleted failed',
                'registered' => 'User registered failed',
                'updated' => 'User updated failed',
                'subscribed' => 'User subscribed failed',
            },
            \App\Console\Commands\RabbitRunCommand::class => match ($type) {
                'collect_handle' => 'Collect command failed',
                'insert_handle' => 'Insert command failed',
            },
            \App\Services\ConsumeRabbitService::class => match ($type) {
                'collect_timeout' => 'AMQP Collect connection timeout',
                'insert_timeout' => 'AMQP Insert connection timeout',
                'collect_consuming_error' => 'AMQP Collect failed while consuming',
                'insert_consuming_error' => 'AMQP Insert failed while consuming',
                'collect_connection' => 'AMQP Collect connection failed',
                'insert_connection' => 'AMQP Insert connection failed',
                'collect_error' => 'AMQP Collect failed',
                'insert_error' => 'AMQP Insert failed',
            },
            \App\Location\Phone::class => match ($type) {
                'get_instance' => 'PhoneNumberUtil getInstance failed'
            },
            default => 'Unknown error'
        };
    }

    static public function set($mainClass, $exceptionClass = null, $action = 'default', $e = null, $channel = 'loki'): void
    {
        $message = self::getType($mainClass, $action);
        if($channel !== 'loki') {
            Log::channel('slack')->error($message, [
                'class' => $mainClass,
                'type' => $exceptionClass,
                'action' => $action,
                'message' => $e ? $e->getMessage() ?? null : null,
                'content' => $e ? $e->getResponse()->getBody()->getContents() ?? null : null
            ]);
        } else {
            Log::error($message, [
                'class' => $mainClass,
                'type' => $exceptionClass,
                'action' => $action,
                'message' => $e ? $e->getMessage() ?? null : null,
                'content' => $e ? $e->getResponse()->getBody()->getContents() ?? null : null
            ]);
        }
    }
}
