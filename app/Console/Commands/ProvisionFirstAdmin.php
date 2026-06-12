<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProvisionFirstAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Passwords must stay interactive and must never be accepted as arguments.
     *
     * @var string
     */
    protected $signature = 'admin:provision-first';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision the first admin user through an interactive CLI-only flow.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (User::where('is_admin', true)->exists()) {
            $this->warn('An admin user already exists. First admin provisioning was skipped.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Admin name'));
        $email = Str::lower(trim((string) $this->ask('Admin email')));

        $identityValidator = Validator::make([
            'name' => $name,
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($identityValidator->fails()) {
            return $this->failWithValidationErrors($identityValidator);
        }

        $password = (string) $this->secret('Admin password');
        $passwordConfirmation = (string) $this->secret('Confirm admin password');

        $passwordValidator = Validator::make([
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ]);

        if ($passwordValidator->fails()) {
            return $this->failWithValidationErrors($passwordValidator);
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->is_admin = true;
        $user->role = User::ROLE_SUPER_ADMIN;
        $user->is_active = true;
        $user->save();

        $this->info('First admin user created successfully.');

        return self::SUCCESS;
    }

    private function failWithValidationErrors($validator): int
    {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }

        return self::FAILURE;
    }
}
