<?php

namespace App\Actions\Couples;

use App\Enums\CoupleRequestStatus;
use App\Models\CoupleRequest;

class CancelCoupleRequest
{
    use ResolvesPendingRequests;

    /**
     * The sender withdraws a pending request (FR-008).
     */
    public function __invoke(CoupleRequest $request): void
    {
        $this->resolvePending($request, CoupleRequestStatus::Cancelled);
    }
}
