<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new webinterface user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function askValid($question, $field, $rules)
    {
        do
        {
            $value = $this->ask($question);
            $validator = Validator::make([$field => $value], [$field => $rules]);

            if ($validator->fails())
            {
                $this->error($validator->errors()->first($field));
            }
        } while ($validator->fails());
    
        return $value;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->askValid('E-mail address of user', 'email', ['required', 'string', 'email', 'max:255', 'unique:users']);
        $name  = $this->askValid('Full name of user', 'name', ['required', 'string', 'max:255']);        
        
        while (true)
        {
            $pass1 = $this->secret('Password');
            if (!$pass1)
            {
                $this->error("Password cannot be empty");
                continue;
            }

            $pass2 = $this->secret('Repeat password');
            if ($pass1 == $pass2)
            {
                break;
            }
            else
            {
                $this->error('Passwords do not match');
            }
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($pass1),
        ]);

        echo "User created\n";

        return 0;
    }
}
