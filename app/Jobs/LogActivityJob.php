<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogActivityJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(
        public readonly string $action,
        public readonly ?int $userId,
        public readonly ?string $subjectType,
        public readonly ?int $subjectId,
        public readonly array $properties = [],
    ) {
    }

    public function handle(): void
    {
        ActivityLog::create([
            'user_id' => $this->userId,
            'action' => $this->action,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'properties' => $this->properties,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
