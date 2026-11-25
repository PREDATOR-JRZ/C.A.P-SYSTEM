<?php

namespace App\Exceptions;

use RuntimeException;

class WorkflowException extends RuntimeException
{
    public static function invalidTransition(string $from, string $to, string $reason = ''): self
    {
        $message = "Cannot transition application from '{$from}' to '{$to}'.";
        if ($reason !== '') {
            $message .= ' ' . $reason;
        }
        return new self($message);
    }
}
