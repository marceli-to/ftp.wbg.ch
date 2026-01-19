<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredFiles extends Command
{
    protected $signature = 'files:cleanup';

    protected $description = 'Delete expired files from storage and database';

    public function handle()
    {
        $expiredFiles = File::expired()->get();

        if ($expiredFiles->isEmpty()) {
            $this->info('No expired files found.');
            return 0;
        }

        $count = 0;

        foreach ($expiredFiles as $file) {
            Storage::disk('local')->delete($file->storage_path);
            $file->delete();
            $count++;
            $this->line("Deleted: {$file->original_name}");
        }

        $this->info("Successfully deleted {$count} expired file(s).");

        return 0;
    }
}
