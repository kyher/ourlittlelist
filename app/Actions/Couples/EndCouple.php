<?php

namespace App\Actions\Couples;

use App\Models\Couple;
use Illuminate\Support\Facades\DB;

class EndCouple
{
    /**
     * End a couple. Deleting the row frees both members (users.couple_id is nullOnDelete)
     * and permanently deletes all couple-owned data: every couple-owned table MUST reference
     * couples.id with cascadeOnDelete (FR-016, FR-017).
     */
    public function __invoke(Couple $couple): void
    {
        DB::transaction(fn () => $couple->delete());
    }
}
