<?php

namespace App\Http\Middleware;

use App\Services\KkprlApplicantAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureKkprlApplicant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app(KkprlApplicantAuthService::class)->current() === null) {
            return redirect()->route('kkprl.applicant.login');
        }

        return $next($request);
    }
}
