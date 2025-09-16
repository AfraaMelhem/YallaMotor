<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOrSuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('admin')->user();
        $staff = auth('staff')->user();

        if (
            ($admin && $admin->hasRole('super-admin')) ||
            ($staff && $staff->hasRole('admin'))
        ) {
            auth()->setUser($admin ?? $staff);

            return $next($request);
        }else{
            abort(403);
        }

    }
}

