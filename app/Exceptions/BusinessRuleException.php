<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Base for expected business-rule refusals: the request was well-formed, the
 * user was allowed to make it, but the domain says no.
 *
 * These carry a 422 and a message written for the end user, plus a stable
 * errorCode the jQuery layer can branch on without parsing prose.
 */
class BusinessRuleException extends HttpException
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        public readonly string $errorCode = 'BUSINESS_RULE',
        public readonly array $payload = [],
        int $statusCode = 422,
    ) {
        parent::__construct($statusCode, $message);
    }
}
