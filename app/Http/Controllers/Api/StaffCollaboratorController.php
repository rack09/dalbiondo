<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ValidPasswordBytes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffCollaboratorController extends Controller
{
    public function index(): JsonResponse
    {
        $collaborators = $this->collaborators()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'is_active']);

        return response()->json($collaborators)->header('Cache-Control', 'no-store');
    }

    public function update(Request $request, string $collaborator): JsonResponse
    {
        $user = $this->collaborators()->findOrFail($collaborator);
        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => Str::lower($email)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
        ], [
            'name.required' => 'Inserisci il nome del collaboratore.',
            'name.max' => 'Il nome può contenere al massimo 255 caratteri.',
            'email.required' => 'Inserisci l’indirizzo email del collaboratore.',
            'email.email' => 'Inserisci un indirizzo email valido.',
            'email.unique' => 'Questo indirizzo email è già in uso.',
        ]);

        $changes = ['name' => $data['name']];
        if ($user->email !== $data['email']) {
            $changes += [
                'email' => $data['email'],
                'email_verified_at' => null,
                'remember_token' => null,
                'staff_access_version' => DB::raw('staff_access_version + 1'),
            ];
        }

        try {
            $this->collaborators()->whereKey($user->getKey())->update($changes);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => ['Questo indirizzo email è già in uso.']]);
        }

        return $this->collaboratorResponse($this->collaborators()->findOrFail($collaborator));
    }

    public function updatePassword(Request $request, string $collaborator): JsonResponse
    {
        $user = $this->collaborators()->findOrFail($collaborator);
        $data = $request->validate([
            'password' => ['bail', 'required', 'string', 'min:12', new ValidPasswordBytes, 'confirmed'],
        ], [
            'password.required' => 'Inserisci la nuova password.',
            'password.min' => 'La password deve contenere almeno 12 caratteri.',
            'password.confirmed' => 'Le nuove password non coincidono.',
        ]);

        if (! $user->changePassword($data['password'])) {
            return response()->json(['message' => 'L’account è stato aggiornato. Riprova.'], 409);
        }

        return $this->collaboratorResponse($this->collaborators()->findOrFail($collaborator));
    }

    public function updateStatus(Request $request, string $collaborator): JsonResponse
    {
        $user = $this->collaborators()->findOrFail($collaborator);
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $active = (bool) $data['is_active'];

        $this->collaborators()
            ->whereKey($user->getKey())
            ->where('is_active', '!=', $active)
            ->update([
                'is_active' => $active,
                'remember_token' => null,
                'staff_access_version' => DB::raw('staff_access_version + 1'),
            ]);

        return $this->collaboratorResponse($this->collaborators()->findOrFail($collaborator));
    }

    private function collaborators(): Builder
    {
        return User::query()->where('role', 'collaborator');
    }

    private function collaboratorResponse(User $collaborator): JsonResponse
    {
        return response()->json($collaborator->only(['id', 'name', 'email', 'is_active']))
            ->header('Cache-Control', 'no-store');
    }
}
