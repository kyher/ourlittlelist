<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\password;

class CreateAdmin extends Command
{
    use PasswordValidationRules;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {email : The email address the admin will log in with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin account, prompting for its password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        $emailErrors = Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Admin::class)],
        ])->errors();

        if ($emailErrors->isNotEmpty()) {
            $this->showErrors($emailErrors);

            return self::FAILURE;
        }

        if (! $this->canPrompt()) {
            $this->components->error('This command must be run interactively so a password can be entered.');

            return self::FAILURE;
        }

        $password = $this->promptForPassword();

        try {
            Admin::create(['email' => $email, 'password' => $password]);
        } catch (UniqueConstraintViolationException) {
            $this->components->error(__('validation.unique', ['attribute' => 'email']));

            return self::FAILURE;
        }

        $this->components->info("Admin account created for {$email}.");

        return self::SUCCESS;
    }

    /**
     * Whether a person is able to answer the password prompts.
     */
    private function canPrompt(): bool
    {
        if (! $this->input->isInteractive()) {
            return false;
        }

        return $this->laravel->runningUnitTests() || stream_isatty(STDIN);
    }

    /**
     * Ask for the password and its confirmation until they pass the regular password rules.
     */
    private function promptForPassword(): string
    {
        while (true) {
            $password = password('Password');
            $confirmation = password('Confirm password');

            $errors = Validator::make(
                ['password' => $password, 'password_confirmation' => $confirmation],
                ['password' => $this->passwordRules()],
            )->errors();

            if ($errors->isEmpty()) {
                return $password;
            }

            $this->showErrors($errors);
        }
    }

    /**
     * Show each validation message to the operator.
     */
    private function showErrors(MessageBag $errors): void
    {
        foreach ($errors->all() as $message) {
            $this->components->error($message);
        }
    }
}
