<?php
// src/Core/Validator.php
namespace App\Core;

class Validator {
    private array $errors = [];
    private array $validated = [];

    public function validate(array $data, array $rules): self {
        foreach ($rules as $field => $ruleString) {
            $rulesArr = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($rulesArr as $rule) {
                [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || trim((string)$value) === '') {
                            $this->addError($field, "$field is required");
                        }
                        break;

                    case 'string':
                        if ($value !== null && !is_string($value)) {
                            $this->addError($field, "$field must be a string");
                        }
                        break;

                    case 'email':
                        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, "$field must be a valid email");
                        }
                        break;

                    case 'min':
                        if ($value !== null && strlen((string)$value) < (int)$param) {
                            $this->addError($field, "$field must be at least $param characters");
                        }
                        break;

                    case 'max':
                        if ($value !== null && strlen((string)$value) > (int)$param) {
                            $this->addError($field, "$field must be at most $param characters");
                        }
                        break;

                    case 'boolean':
                        if ($value !== null && !is_bool($value) && !in_array($value, ['true', 'false'])) {
                            $this->addError($field, "$field must be a boolean");
                        }
                        break;

                    case 'numeric':
                        if ($value !== null && !is_numeric($value)) {
                            $this->addError($field, "$field must be numeric");
                        }
                        break;

                    case 'regex':
                        if ($value !== null && !preg_match($param, (string)$value)) {
                            $this->addError($field, "$field format is invalid");
                        }
                        break;
                }
            }

            // If no error on this field, keep it as validated data
            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }

        return $this;
    }

    private function addError(string $field, string $message): void {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function validated(): array {
        return $this->validated;
    }
}