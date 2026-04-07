<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (!$user || !$user->is_active) {
            abort(403, 'Unauthorized');
        }

        if (in_array('super_admin', $roles) && !$user->isSuperAdmin()) {
            abort(403, 'Super admin access required');
        }

        if (in_array('admin', $roles) && !in_array($user->role->value, ['admin', 'super_admin'])) {
            abort(403, 'Admin access required');
        }

        return $next($request);
    }
}
