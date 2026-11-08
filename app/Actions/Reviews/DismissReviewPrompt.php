<?php

namespace App\Actions\Reviews;

use App\Models\ReviewPrompt;

class DismissReviewPrompt
{
    /**
     * Hide a prompt for good. The item can still be reviewed from its page (FR-016).
     */
    public function __invoke(ReviewPrompt $prompt): void
    {
        $prompt->forceFill(['dismissed_at' => now()])->save();
    }
}
