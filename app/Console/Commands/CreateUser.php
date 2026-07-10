<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('app:create-user')]
#[Description('Create a new user account interactively')]
class CreateUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating a new user account.');
        $this->newLine();

        $name = $this->askValid(
            'Name',
            fn (string $v) => Validator::make(['name' => $v], ['name' => ['required', 'string', 'max:255']])
        );

        $email = $this->askValid(
            'Email address',
            fn (string $v) => Validator::make(['email' => $v], [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            ])
        );

        $password = $this->secret('Password (hidden)');

        $validation = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::default()]]
        );

        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info('User created successfully.');
        $this->table(
            ['ID', 'Name', 'Email'],
            [[$user->id, $user->name, $user->email]]
        );

        return self::SUCCESS;
    }

    /**
     * Ask a question and re-ask on validation failure.
     *
     * @param  callable(\Illuminate\Validation\Validator): bool  $makeValidator
     */
    private function askValid(string $label, \Closure $makeValidator): string
    {
        while (true) {
            $value = $this->ask($label);
            $validator = $makeValidator($value ?? '');

            if (! $validator->fails()) {
                return $value;
            }

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
        }
    }
}
