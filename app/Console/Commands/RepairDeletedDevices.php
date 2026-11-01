<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairDeletedDevices extends Command
{
    protected $signature = 'devices:repair-deleted {--apply : Reject deleted records and revoke their sessions}';

    protected $description = 'Repair legacy deleted devices without changing subscriptions or credentials';

    public function handle(): int
    {
        $count = Device::where('isdeleted', true)->count();
        $this->info("Deleted records: {$count}");
        if (! $this->option('apply')) {
            $this->info('Preview only. Use --apply to reject deleted records and revoke all related sessions.');

            return self::SUCCESS;
        }
        Device::where('isdeleted', true)->chunkById(100, function ($devices): void {
            foreach ($devices as $device) {
                DB::transaction(function () use ($device): void {
                    $current = Device::whereKey($device->id)->lockForUpdate()->first();
                    if (! $current || ! $current->isdeleted) {
                        return;
                    }
                    $current->moveToDeleted();
                    // Also covers already-rejected records with stale tokens.
                    $current->revokeAllTokens();
                });
            }
        });
        $this->info('Repair complete: deleted records are rejected and their related sessions are revoked.');

        return self::SUCCESS;
    }
}
