<?php

namespace Modules\Core\Observers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Core\Services\VerificationService;

class UserObserver
{
    public function __construct(protected VerificationService $verificationService) {}

    public function created(User $user): void
    {

        $this->syncUserToNestJs($user);

        if (!empty($user->provider) || !empty($user->provider_id) || Auth::guard('admin')->check()) {
            return;
        }
        $this->verificationService->sendVerification($user);
    }

    /**
     * Insert the user into the NestJS mirror DB (dual-write). See config/database.php
     * keys `nestjs` connection and `nestjs_sync` (env: NESTJS_DB_*).
     */
    // protected function syncUserToNestJs(User $user): void
    // {
    //     if (! Config::get('database.nestjs_sync.enabled', true)) {
    //         return;
    //     }

    //     $connection = (string) Config::get('database.nestjs_sync.connection', 'nestjs');
    //     $table = (string) Config::get('database.nestjs_sync.table', 'user');

    //     try {
    //         $attrs = $user->getAttributes();
    //         $attrs = Arr::except($attrs, ['created_at', 'updated_at']);
    //         DB::connection($connection)->table($table)->insert($attrs);
    //     } catch (\Throwable $e) {
    //         Log::channel('single')->error('UserObserver: failed to sync user to NestJS connection', [
    //             'user_id' => $user->id,
    //             'connection' => $connection,
    //             'table' => $table,
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }
    protected function syncUserToNestJs(User $user): void
{
    Log::info('SYNC STARTED');

    if (! Config::get('database.nestjs_sync.enabled', true)) {
        Log::warning('SYNC DISABLED');
        return;
    }

    $connection = (string) Config::get('database.nestjs_sync.connection', 'nestjs');
    $table = (string) Config::get('database.nestjs_sync.table', 'user');

    Log::info('SYNC CONFIG', [
        'connection' => $connection,
        'table' => $table,
    ]);

    try {
        // Check DB connection
        $dbName = DB::connection($connection)->getDatabaseName();

        Log::info('CONNECTED TO DB', [
            'database' => $dbName,
        ]);

        $attrs = $user->getAttributes();
        $attrs = Arr::except($attrs, ['created_at', 'updated_at']);

        Log::info('USER ATTRIBUTES', [
            'user_id' => $user->id,
            'attrs_keys' => array_keys($attrs),
        ]);

        $result = DB::connection($connection)
            ->table($table)
            ->insert($attrs);

        Log::info('INSERT RESULT', [
            'success' => $result,
        ]);

    } catch (\Throwable $e) {
        Log::error('SYNC ERROR', [
            'message' => $e->getMessage(),
            'connection' => $connection,
            'table' => $table,
        ]);
    }
}
}
