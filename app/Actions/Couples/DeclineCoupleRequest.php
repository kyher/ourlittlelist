<?php

namespace App\Actions\Couples;

use App\Enums\CoupleRequestStatus;
use App\Models\CoupleRequest;

class DeclineCoupleRequest
{
    use ResolvesPendingRequests;

    /**
     * The receiver rejects a pending request. No couple is created (FR-014).
     */
    public function __invoke(CoupleRequest $request): void
    {
        $this->resolvePending($request, CoupleRequestStatus::Declined);
    }
}
