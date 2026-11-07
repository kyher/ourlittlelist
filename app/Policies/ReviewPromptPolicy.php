<?php

namespace App\Policies;

use App\Models\ReviewPrompt;
use App\Models\User;

class ReviewPromptPolicy
{
    /**
     * Only the member being prompted can dismiss the prompt.
     */
    public function dismiss(User $user, ReviewPrompt $prompt): bool
    {
        return $prompt->user_id === $user->id;
    }
}
