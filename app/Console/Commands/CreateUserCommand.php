<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a User';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user['name'] = $this->ask('Enter user name');
        $user['email'] = $this->ask('Enter email address');
        $user['password'] = $this->secret('Enter password');

        $roleName = $this->choice('Select role for user', ['admin', 'editor'], 1);
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            $this->error('Role does not exist');

            return -1;
        }

        $validator = Validator::make($user, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Password::defaults()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return -1;
        }

        DB::transaction(function () use ($user, $role) {
            $newUser = User::create($user);
            $newUser->roles()->attach($role->id);
        });

        $this->info("User {$user['email']} successfully created");

        return 0;
    }
}
