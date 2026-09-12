<?php

namespace App\Http\Middleware;

use App\Services\MemberServices;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->user()->email;
        if (!MemberServices::is_active($email)){
            return response()->json([
                'status-code' => 403,
                'message' => 'member is de active'
            ]);
        }
        return $next($request);
    }
}
