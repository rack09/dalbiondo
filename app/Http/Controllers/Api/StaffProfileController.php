<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ValidPasswordBytes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Inserisci il tuo nome.',
            'name.max' => 'Il nome può contenere al massimo 255 caratteri.',
        ]);

        /** @var User $user */
        $user = $request->user('web');
        $user->name = $data['name'];
        $user->save();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['bail', 'required', 'string', new ValidPasswordBytes, 'current_password:web'],
            'password' => ['bail', 'required', 'string', 'min:12', new ValidPasswordBytes, 'confirmed'],
        ], [
            'current_password.required' => 'Inserisci la password attuale.',
            'current_password.current_password' => 'La password attuale non è corretta.',
            'password.required' => 'Inserisci la nuova password.',
            'password.min' => 'La password deve contenere almeno 12 caratteri.',
            'password.confirmed' => 'Le nuove password non coincidono.',
        ]);

        /** @var User $user */
        $user = $request->user('web');

        if (! $user->changePassword($data['password'])) {
            throw ValidationException::withMessages([
                'current_password' => ['La password è stata aggiornata. Accedi di nuovo e riprova.'],
            ]);
        }

        $request->session()->regenerate(true);
        $request->session()->put(User::SESSION_ACCESS_VERSION, $user->staff_access_version);

        return response()->json(['message' => 'Password aggiornata.']);
    }
}
