<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if ($user?->role === 'collaborator' && (
            ! $user->is_active ||
            $request->session()->get(User::SESSION_ACCESS_VERSION, 0) !== $user->staff_access_version
        )) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['message' => 'Sessione scaduta. Accedi di nuovo.'], 401);
        }

        return $next($request);
    }
}
