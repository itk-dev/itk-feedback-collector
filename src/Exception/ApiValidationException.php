<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

class ApiValidationException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = Response::HTTP_BAD_REQUEST,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
