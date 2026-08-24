<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes every /api request ask for JSON, whether or not the caller remembered to.
 *
 * The controllers behind these routes are shared with the browser and decide
 * what to return by reading Accept (Controller::wantsData), so a request without
 * the header would be handed a Blade view — a login page, most confusingly, once
 * the token expired. Setting it here means that cannot happen, and it also makes
 * the API testable with a bare curl.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->expectsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
