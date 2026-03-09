<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect(route('auth.login'));
        }

        $allowedRoles = explode('|', $roles);

        $user->loadMissing('roles');

        if (! $user->roles->whereIn('code', $allowedRoles)->isNotEmpty()) {
            abort(403, 'Bạn không có quyền truy cập chức năng này');
        }

        return $next($request);
    }
}
