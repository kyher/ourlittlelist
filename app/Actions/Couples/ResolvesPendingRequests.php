<?php

namespace App\Actions\Couples;

use App\Enums\CoupleRequestStatus;
use App\Exceptions\CoupleActionException;
use App\Models\CoupleRequest;
use Illuminate\Support\Facades\DB;

trait ResolvesPendingRequests
{
    /**
     * Move a request from pending to a final status, refusing if it is no longer pending.
     *
     * @throws CoupleActionException
     */
    protected function resolvePending(CoupleRequest $request, CoupleRequestStatus $status): void
    {
        DB::transaction(function () use ($request, $status) {
            $locked = CoupleRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($locked->status !== CoupleRequestStatus::Pending) {
                throw new CoupleActionException(__('This request is no longer available.'));
            }

            $locked->resolve($status);
        });

        $request->refresh();
    }
}
