<?php

namespace App\Modules\Account\Console;

use App\Modules\Account\Data\NewAdminData;
use App\Modules\Account\Services\UserService;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

final class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {--name= : Full name}
        {--phone= : Mobile number, e.g. 01712345678}
        {--email= : Email address (optional)}
        {--password= : Password (min 8 characters)}';

    protected $description = 'Create an active admin user for the admin panel';

    public function handle(UserService $userService): int
    {
        $input = [
            'name' => $this->option('name') ?? text('Full name', required: true),
            'phone' => $this->option('phone') ?? text('Mobile number', placeholder: '01712345678', required: true),
            'email' => $this->option('email') ?? text('Email address (optional)'),
            'password' => $this->option('password') ?? password('Password (min 8 characters)', required: true),
        ];

        $input['phone'] = PhoneNumber::normalize($input['phone']) ?? $input['phone'];
        $input['email'] = $input['email'] === '' ? null : Str::lower($input['email']);

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:'.PhoneNumber::PATTERN, 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
        ], ['phone.regex' => 'The phone must be a valid Bangladeshi mobile number (01XXXXXXXXX).']);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $admin = $userService->createAdmin(new NewAdminData(
            name: $input['name'],
            phone: $input['phone'],
            email: $input['email'],
            password: $input['password'],
        ));

        $this->components->info("Admin [{$admin->name}] created. Sign in at ".route('admin.login').' with '.$admin->phone.'.');

        return self::SUCCESS;
    }
}
