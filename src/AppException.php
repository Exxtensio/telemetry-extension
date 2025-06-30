<?php

namespace Exxtensio\TelemetryExtension;

use Illuminate\Support\Facades\Log;

final class AppException
{
    private static function getType($type, $action): ?string
    {
        return match ($type) {
            'route' => match ($action) {
                'not_found' => 'Route not found',
                'api_not_found' => 'API Route not found',
                'method_not_allowed' => 'Method not allowed',
                default => 'Unknown error'
            },
            'cloudwatch' => match ($action) {
                'default' => 'Cloudwatch push failed',
                default => 'Unknown error'
            },
            'api-controller' => match ($action) {
                'connection_failed' => 'SQS connection failed',
                'connection_timeout' => 'SQS connection timeout',
                default => 'Unknown error'
            },
            'user-service' => match ($action) {
                'deleted' => 'User deleted failed',
                'registered' => 'User registered failed',
                'updated' => 'User updated failed',
                'webhookUpdated' => 'User webhook updated failed',
                'subscribed' => 'User subscribed failed',
                default => 'Unknown error'
            },
            'queue-loop' => match ($action) {
                'collect_handle' => 'Collect command failed',
                'insert_handle' => 'Insert command failed',
                default => 'Unknown error'
            },
            'sqs-service' => match ($action) {
                'sqs_receive_error' => 'SQS failed while trying to receive a message',
                'sqs_error' => 'SQS failed while processing message',
                default => 'Unknown error'
            },
            'telemetry' => match ($action) {
                'default' => 'Telemetry withSpan failed',
                default => 'Unknown error'
            },
            'exchangerate' => match ($action) {
                'default' => 'Exchangerate failed',
                default => 'Unknown error'
            },
            'phone' => match ($action) {
                'get_instance' => 'PhoneNumberUtil getInstance failed',
                default => 'Unknown error'
            },
            default => 'Unknown error'
        };
    }

    static public function set($type = 'default', $action = 'default', $message = null, $channel = 'cloudwatch'): void
    {
        $title = self::getType($type, $action);

        if($channel !== 'cloudwatch') {
            Log::channel('slack')->error($title, [
                'type' => $type,
                'action' => $action,
                'message' => $message
            ]);
        } else {
            Log::error($title, [
                'type' => $type,
                'action' => $action,
                'message' => $message
            ]);
        }
    }
}
