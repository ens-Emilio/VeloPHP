<?php

declare(strict_types=1);

namespace Core\Exceptions;

class ValidationException extends \RuntimeException
{
    public function __construct(
        private readonly array $errors,
        string $message = 'Os dados fornecidos são inválidos.',
    ) {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
