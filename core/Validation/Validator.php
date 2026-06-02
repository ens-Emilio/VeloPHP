<?php

declare(strict_types=1);

namespace Core\Validation;

use Core\Exceptions\ValidationException;

class Validator
{
    private array $errors = [];

    public function __construct(
        private readonly array $data,
        private readonly array $rules,
    ) {
    }

    /**
     * Valida os dados e retorna somente os campos validados.
     *
     * @throws ValidationException
     */
    public function validate(): array
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        // Retorna apenas os campos que foram validados
        return array_intersect_key($this->data, $this->rules);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        match ($rule) {
            'required' => $this->validateRequired($field, $value),
            'string' => $this->validateString($field, $value),
            'numeric' => $this->validateNumeric($field, $value),
            'integer' => $this->validateInteger($field, $value),
            'email' => $this->validateEmail($field, $value),
            'min' => $this->validateMin($field, $value, (int) ($params[0] ?? 0)),
            'max' => $this->validateMax($field, $value, (int) ($params[0] ?? 0)),
            'in' => $this->validateIn($field, $value, $params),
            'regex' => $this->validateRegex($field, $value, $params[0] ?? ''),
            'confirmed' => $this->validateConfirmed($field, $value),
            'url' => $this->validateUrl($field, $value),
            'boolean' => $this->validateBoolean($field, $value),
            'array' => $this->validateArray($field, $value),
            'nullable' => null, // Permite null; se for null, não aplica demais regras
            default => null,
        };
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    private function validateRequired(string $field, mixed $value): void
    {
        if ($this->isBlank($value)) {
            $this->addError($field, "O campo {$field} é obrigatório.");
        }
    }

    private function validateString(string $field, mixed $value): void
    {
        if (!$this->isBlank($value) && !is_string($value)) {
            $this->addError($field, "O campo {$field} deve ser uma string.");
        }
    }

    private function validateNumeric(string $field, mixed $value): void
    {
        if (!$this->isBlank($value) && !is_numeric($value)) {
            $this->addError($field, "O campo {$field} deve ser numérico.");
        }
    }

    private function validateInteger(string $field, mixed $value): void
    {
        if (!$this->isBlank($value) && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "O campo {$field} deve ser um inteiro.");
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if (!$this->isBlank($value) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, "O campo {$field} deve ser um e-mail válido.");
        }
    }

    private function validateMin(string $field, mixed $value, int $min): void
    {
        if ($this->isBlank($value)) {
            return;
        }

        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, "O campo {$field} deve ter no mínimo {$min} caracteres.");
        } elseif (is_numeric($value) && $value < $min) {
            $this->addError($field, "O campo {$field} deve ser no mínimo {$min}.");
        } elseif (is_array($value) && count($value) < $min) {
            $this->addError($field, "O campo {$field} deve ter no mínimo {$min} itens.");
        }
    }

    private function validateMax(string $field, mixed $value, int $max): void
    {
        if ($this->isBlank($value)) {
            return;
        }

        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, "O campo {$field} deve ter no máximo {$max} caracteres.");
        } elseif (is_numeric($value) && $value > $max) {
            $this->addError($field, "O campo {$field} deve ser no máximo {$max}.");
        } elseif (is_array($value) && count($value) > $max) {
            $this->addError($field, "O campo {$field} deve ter no máximo {$max} itens.");
        }
    }

    private function validateIn(string $field, mixed $value, array $allowed): void
    {
        if (!$this->isBlank($value) && !in_array((string) $value, $allowed, true)) {
            $list = implode(', ', $allowed);
            $this->addError($field, "O campo {$field} deve ser um dos valores: {$list}.");
        }
    }

    private function validateRegex(string $field, mixed $value, string $pattern): void
    {
        if (!$this->isBlank($value) && !preg_match($pattern, (string) $value)) {
            $this->addError($field, "O campo {$field} possui formato inválido.");
        }
    }

    private function validateConfirmed(string $field, mixed $value): void
    {
        $confirmation = $this->data[$field . '_confirmation'] ?? null;
        if ($value !== $confirmation) {
            $this->addError($field, "A confirmação de {$field} não confere.");
        }
    }

    private function validateUrl(string $field, mixed $value): void
    {
        if (!$this->isBlank($value) && filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->addError($field, "O campo {$field} deve ser uma URL válida.");
        }
    }

    private function validateBoolean(string $field, mixed $value): void
    {
        if (!$this->isBlank($value) && !in_array($value, [true, false, 0, 1, '0', '1'], true)) {
            $this->addError($field, "O campo {$field} deve ser verdadeiro ou falso.");
        }
    }

    private function validateArray(string $field, mixed $value): void
    {
        if (!$this->isBlank($value) && !is_array($value)) {
            $this->addError($field, "O campo {$field} deve ser um array.");
        }
    }
}
