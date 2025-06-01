<?php

namespace Exxtensio\TelemetryExtension;

use Illuminate\Support\Facades\Log;

final class AppException
{
    private static function getType($mainClass, $exceptionClass): ?string
    {
        return match ($mainClass) {
            \Illuminate\Http\Request::class => match ($exceptionClass) {
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class => 'Route not found',
                default => 'Unknown error'
            },
            default => 'Unknown error'
        };
    }

    static public function set($mainClass, $exceptionClass, $e, $channel = 'loki'): void
    {
        $message = self::getType($mainClass, $exceptionClass);
        if($channel !== 'loki') {
            Log::channel('slack')->error($message, [
                'class' => $mainClass,
                'type' => $exceptionClass,
                'message' => $e->getMessage() ?? null,
                'content' => $e->getResponse()->getBody()->getContents() ?? null
            ]);
        } else {
            Log::error($message, [
                'class' => $mainClass,
                'type' => $exceptionClass,
                'message' => $e->getMessage() ?? null,
                'content' => $e->getResponse()->getBody()->getContents() ?? null
            ]);
        }
    }
}
