<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-owner')]
#[Description('Crea il primo account del titolare')]
class CreateOwner extends Command implements Isolatable
{
    protected $isolated = true;

    public function handle(): int
    {
        if (User::query()->where('role', 'owner')->exists()) {
            $this->error('Esiste già un account titolare.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->ask('Nome del titolare'),
            'email' => $this->ask('Email del titolare'),
            'password' => $this->secret('Password (minimo 12 caratteri)', false),
            'password_confirmation' => $this->secret('Conferma la password', false),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        if (strlen($data['password']) > 72) {
            $this->error('La password deve occupare al massimo 72 byte.');

            return self::FAILURE;
        }

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = 'owner';
        $user->save();

        $this->info('Account del titolare creato.');

        return self::SUCCESS;
    }
}