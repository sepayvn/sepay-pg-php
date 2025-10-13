<?php

declare(strict_types=1);

namespace SePay\Exceptions;

/**
 * Exception thrown when authentication fails
 *
 * This includes invalid credentials, expired tokens, or signature verification failures
 *
 * @package SePay\Exceptions
 */
class AuthenticationException extends SePayException
{
    //
}
