<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StaffRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'regex:/\A[0-9a-f]{64}\z/'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ], [
            'token.required' => 'Il link di invito è incompleto.',
            'token.regex' => 'Il link di invito non è valido.',
            'name.required' => 'Inserisci il tuo nome.',
            'name.max' => 'Il nome può contenere al massimo 255 caratteri.',
            'password.required' => 'Inserisci una password.',
            'password.min' => 'La password deve contenere almeno 12 caratteri.',
            'password.confirmed' => 'Le password non coincidono.',
        ]);

        if (strlen($data['password']) > 72 || str_contains($data['password'], "\0")) {
            throw ValidationException::withMessages([
                'password' => ['Scegli una password più corta e con caratteri validi.'],
            ]);
        }

        DB::transaction(function () use ($data): void {
            $invitation = StaffInvitation::query()
                ->where('token_hash', hash('sha256', $data['token']))
                ->lockForUpdate()
                ->first();

            if (
                ! $invitation ||
                $invitation->accepted_at !== null ||
                ! $invitation->expires_at->isFuture() ||
                User::query()->where('email', $invitation->email)->exists()
            ) {
                throw ValidationException::withMessages([
                    'token' => ['Invito non valido, scaduto o già utilizzato.'],
                ]);
            }

            $user = new User();
            $user->name = $data['name'];
            $user->email = $invitation->email;
            $user->password = Hash::make($data['password']);
            $user->role = 'collaborator';
            $user->save();

            $invitation->accepted_at = now();
            $invitation->save();
        }, 3);

        return response()->json(['message' => 'Account attivato.'], 201);
    }
}