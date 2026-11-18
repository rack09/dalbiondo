<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\ValidPasswordBytes;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('app:change-owner-password')]
#[Description('Aggiorna la password dell’account del titolare')]
class ChangeOwnerPassword extends Command
{
    public function handle(): int
    {
        $owners = User::query()->where('role', 'owner')->limit(2)->get();

        if ($owners->count() !== 1) {
            $this->error('Deve esistere un solo account titolare.');

            return self::FAILURE;
        }

        $owner = $owners->first();
        $this->info("Stai aggiornando la password di {$owner->email}.");

        $validator = Validator::make([
            'password' => $this->secret('Nuova password (minimo 12 caratteri)', false),
            'password_confirmation' => $this->secret('Conferma la nuova password', false),
        ], [
            'password' => ['bail', 'required', 'string', 'min:12', new ValidPasswordBytes, 'confirmed'],
        ], [
            'password.required' => 'Inserisci la nuova password.',
            'password.min' => 'La password deve contenere almeno 12 caratteri.',
            'password.confirmed' => 'Le nuove password non coincidono.',
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        if (! $owner->changePassword($validator->validated()['password'])) {
            $this->error('La password è stata aggiornata durante l’operazione. Riprova.');

            return self::FAILURE;
        }

        $this->info('Password del titolare aggiornata.');

        return self::SUCCESS;
    }
}
