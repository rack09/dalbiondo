<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffInvitationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'replace' => ['sometimes', 'required', 'boolean'],
        ]);

        $email = Str::lower($data['email']);
        $replace = (bool) ($data['replace'] ?? false);

        $result = DB::transaction(function () use ($email, $replace): array {
            $invitation = StaffInvitation::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first() ?? new StaffInvitation();

            if (User::query()->where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['Questo indirizzo email è già registrato.'],
                ]);
            }

            if (
                ! $replace &&
                $invitation->exists &&
                $invitation->accepted_at === null &&
                $invitation->expires_at->isFuture()
            ) {
                return [
                    'email' => $invitation->email,
                    'invitation_url' => null,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                ];
            }

            $token = bin2hex(random_bytes(32));
            $expiresAt = now()->addHours(48);

            $invitation->email = $email;
            $invitation->token_hash = hash('sha256', $token);
            $invitation->expires_at = $expiresAt->toDateTimeString();
            $invitation->accepted_at = null;
            $invitation->save();

            return [
                'email' => $email,
                'invitation_url' => url('/attiva-invito').'#'.$token,
                'expires_at' => $expiresAt->toIso8601String(),
            ];
        }, 3);

        $status = $result['invitation_url'] === null ? 200 : 201;

        return response()->json($result, $status)
            ->header('Cache-Control', 'no-store');
    }
}