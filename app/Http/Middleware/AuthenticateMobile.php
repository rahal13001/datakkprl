<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;

class AuthenticateMobile extends Authenticate
{
    protected function unauthenticated($request, array $guards): never
    {
        throw new AuthenticationException('Unauthenticated.', $guards);
    }
}
