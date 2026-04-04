<?php

namespace App\Console\Commands;

use App\Models\Resume;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldUploads extends Command
{
    protected $signature = 'cleanup:uploads {--hours=24 : Delete uploads older than this many hours}';
    protected $description = 'Delete uploaded resume files and DB records older than N hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $oldResumes = Resume::where('created_at', '<', $cutoff)->get();
        $count = $oldResumes->count();

        if ($count === 0) {
            $this->info('No old uploads to clean up.');
            return 0;
        }

        foreach ($oldResumes as $resume) {
            // Delete the physical file
            if ($resume->file_path && Storage::disk('public')->exists($resume->file_path)) {
                Storage::disk('public')->delete($resume->file_path);
            }

            // Cascade will handle evaluations if FK constraints are in place
            $resume->delete();
        }

        $this->info("Cleaned up {$count} upload(s) older than {$hours} hours.");
        return 0;
    }
}
