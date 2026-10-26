<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Rebuilds the public demo from scratch so visitors always find the same
 * starting point. Refuses to run unless demo mode is enabled, because it
 * drops every table.
 */
#[Signature('demo:reset')]
#[Description('Wipe the database and uploaded files, then seed the demo condominium again')]
class ResetDemo extends Command
{
    /**
     * Directories on the uploads disk that only hold demo data.
     */
    private const UPLOAD_DIRECTORIES = ['payments', 'expenses'];

    public function handle(): int
    {
        if (! config('app.demo')) {
            $this->error('Demo mode is off (APP_DEMO=false). Refusing to wipe the database.');

            return self::FAILURE;
        }

        foreach (self::UPLOAD_DIRECTORIES as $directory) {
            Storage::disk('uploads')->deleteDirectory($directory);
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->info('Demo data reset.');

        return self::SUCCESS;
    }
}
