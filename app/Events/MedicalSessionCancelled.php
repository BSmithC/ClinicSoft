<?php

namespace App\Events;

use App\Models\MedicalSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedicalSessionCancelled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly MedicalSession $session,
        public readonly ?string $reason = null,
    ) {
    }
}
