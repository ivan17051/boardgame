<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Hanya admin yang dapat mengakses data master.',
                ], 403);
            }

            return redirect()
                ->route('rental.index')
                ->with('error', 'Akses ditolak. Hanya admin yang dapat mengakses data master.');
        }

        return $next($request);
    }
}
