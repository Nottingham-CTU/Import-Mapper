<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Represents the result of a validation operation
 */
final readonly class ValidationResult
{
    public bool $isValid;
    public array $errors;

    /**
     * @param ImportError[] $errors Array of ImportError objects
     */
    public function __construct(array $errors = [])
    {
        $this->isValid = empty($errors);
        $this->errors = $errors;
    }

    /**
     * Create a successful validation result
     */
    public static function success(): self
    {
        return new self([]);
    }

    /**
     * Create a failed validation result
     */
    public static function failure(array $errors): self
    {
        return new self($errors);
    }
}
