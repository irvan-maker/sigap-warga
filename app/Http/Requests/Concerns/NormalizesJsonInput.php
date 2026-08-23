<?php

namespace App\Http\Requests\Concerns;

trait NormalizesJsonInput
{
    protected function normalizeJsonFields(array $fields): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if ($value === null || $value === '') {
                $normalized[$field] = null;

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $normalized[$field] = $decoded;
            }
        }

        $this->merge($normalized);
    }
}
