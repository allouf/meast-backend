<?php

namespace App\Utilities;

use Illuminate\Support\Facades\Log;
use Throwable;

class LogHelper
{
    public static function error(Throwable $e, $data = null): void
    {
        Log::error(self::formatException($e), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'data' => $data,
        ]);
    }
    public static function info(string $message, $data = null): void
    {
        Log::info($message, [
            'data' => $data,
        ]);
    }


    private static function formatException(Throwable $e): string
    {
        return $e->getMessage();
    }
}
