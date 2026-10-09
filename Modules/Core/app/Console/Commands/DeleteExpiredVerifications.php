<?php

namespace Modules\Core\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteExpiredVerifications extends Command
{
    protected $signature = 'verifications:delete-expired';
    protected $description = 'Delete expired verification codes';

    public function handle()
    {
        $deleted = DB::table('verifications')
            ->where('expires_at', '<', Carbon::now())
            ->delete();
        $this->info("Deleted {$deleted} expired verification records.");
        return Command::SUCCESS;
    }
}
