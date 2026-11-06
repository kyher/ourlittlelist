<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, Unique|ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'username' => $this->usernameRules($userId),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the validation rules used to validate usernames.
     *
     * Input must already be normalised with normaliseUsername().
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function usernameRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:30',
            'regex:/^[a-z0-9][a-z0-9_.]*$/',
            Rule::notIn(config('usernames.reserved', [])),
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the custom validation messages for usernames.
     *
     * @return array<string, string>
     */
    protected function usernameMessages(): array
    {
        return [
            'username.regex' => __('Usernames may only contain letters, numbers, underscores and full stops, and must start with a letter or number.'),
            'username.not_in' => __("That username isn't available."),
            'username.unique' => __('That username is taken.'),
        ];
    }

    /**
     * Normalise a username for validation and storage.
     */
    protected function normaliseUsername(?string $value): string
    {
        return Str::lower(trim((string) $value));
    }
}
