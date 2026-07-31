<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Ensure the authenticated user has one of the given roles.
     *
     * Guests are redirected to the login page. Authenticated users whose role
     * does not match any of the allowed roles receive a 403 response. Both the
     * role value ('user', 'organizer', 'admin') and the enum name ('User',
     * 'Organizer', 'Admin') are accepted, e.g. `role:admin,organizer`.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles  Allowed role values or names.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $userRole = $user->role;

        // The role attribute is cast to the string-backed UserRole enum, so we
        // compare both the value ('admin') and the name ('Admin') to keep the
        // middleware tolerant of either spelling in route definitions.
        $roleValue = $userRole->value;
        $roleName = $userRole->name;

        foreach ($roles as $allowed) {
            if ($roleValue === $allowed || $roleName === $allowed) {
                return $next($request);
            }
        }

        abort(403);
    }
}
