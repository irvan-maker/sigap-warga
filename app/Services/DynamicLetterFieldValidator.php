<?php

namespace App\Services;

use App\Enums\LetterFieldType;
use App\Models\LetterFieldDefinition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class DynamicLetterFieldValidator
{
    private const MAX_TEXT_LENGTH = 1000;

    private const MAX_TEXTAREA_LENGTH = 10000;

    private const MAX_ABSOLUTE_NUMBER = 1_000_000_000_000_000;

    /**
     * Only structural metadata named `required`, `min`, and `max` is interpreted.
     * Every other database value is treated as inert metadata.
     *
     * @param  Collection<int, LetterFieldDefinition>  $definitions
     * @param  array<string, mixed>  $submitted
     * @return array<int, array{definition: LetterFieldDefinition, value: mixed}>
     */
    public function validate(Collection $definitions, array $submitted): array
    {
        $knownKeys = $definitions->pluck('key')->all();
        $unknownKeys = array_values(array_diff(array_keys($submitted), $knownKeys));

        if ($unknownKeys !== []) {
            throw ValidationException::withMessages([
                'fields' => 'Formulir memuat field yang tidak dikenal. Muat ulang halaman dan coba lagi.',
            ]);
        }

        $rules = [];
        foreach ($definitions as $definition) {
            $rules['fields.'.$definition->key] = $this->rulesFor($definition);
        }

        $validated = Validator::make(['fields' => $submitted], $rules, [], $this->labels($definitions))->validate();
        $values = $validated['fields'] ?? [];

        return $definitions->map(fn (LetterFieldDefinition $definition): array => [
            'definition' => $definition,
            'value' => $this->normalize($definition->field_type, $values[$definition->key] ?? null),
        ])->all();
    }

    /** @return array<int, mixed> */
    private function rulesFor(LetterFieldDefinition $definition): array
    {
        $metadata = is_array($definition->validation) ? $definition->validation : [];
        $required = $definition->is_required || ($metadata['required'] ?? false) === true;
        $rules = [$required ? 'required' : 'nullable'];

        return match ($definition->field_type) {
            LetterFieldType::TEXT => [...$rules, 'string', ...$this->lengthRules($metadata, self::MAX_TEXT_LENGTH)],
            LetterFieldType::TEXTAREA => [...$rules, 'string', ...$this->lengthRules($metadata, self::MAX_TEXTAREA_LENGTH)],
            LetterFieldType::DATE => [...$rules, 'date_format:Y-m-d'],
            LetterFieldType::NUMBER => [...$rules, 'numeric', ...$this->numberRules($metadata)],
            LetterFieldType::SELECT => [...$rules, 'string', Rule::in($this->selectOptions($definition))],
            LetterFieldType::BOOLEAN => [...$rules, 'boolean'],
        };
    }

    /** @return array<int, string> */
    private function lengthRules(array $metadata, int $hardMaximum): array
    {
        $rules = [];
        $minimum = $this->boundedInteger($metadata['min'] ?? null, 0, $hardMaximum);
        $maximum = $this->boundedInteger($metadata['max'] ?? null, 1, $hardMaximum) ?? $hardMaximum;

        if ($minimum !== null && $minimum <= $maximum) {
            $rules[] = 'min:'.$minimum;
        }
        $rules[] = 'max:'.$maximum;

        return $rules;
    }

    /** @return array<int, string> */
    private function numberRules(array $metadata): array
    {
        $rules = [];
        $minimum = $this->boundedNumber($metadata['min'] ?? null) ?? -self::MAX_ABSOLUTE_NUMBER;
        $maximum = $this->boundedNumber($metadata['max'] ?? null) ?? self::MAX_ABSOLUTE_NUMBER;

        if ($minimum <= $maximum) {
            $rules[] = 'min:'.$minimum;
            $rules[] = 'max:'.$maximum;
        } else {
            $rules[] = 'min:'.-self::MAX_ABSOLUTE_NUMBER;
            $rules[] = 'max:'.self::MAX_ABSOLUTE_NUMBER;
        }

        return $rules;
    }

    /** @return array<int, string> */
    private function selectOptions(LetterFieldDefinition $definition): array
    {
        $options = $definition->configuration['options'] ?? [];

        return is_array($options)
            ? array_values(array_filter($options, fn (mixed $option): bool => is_string($option)))
            : [];
    }

    private function normalize(LetterFieldType $type, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            LetterFieldType::TEXT, LetterFieldType::TEXTAREA, LetterFieldType::DATE, LetterFieldType::SELECT => trim((string) $value),
            LetterFieldType::NUMBER => preg_match('/^[+-]?\d+$/', (string) $value) === 1
                ? (int) $value
                : (float) $value,
            LetterFieldType::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        };
    }

    private function boundedInteger(mixed $value, int $minimum, int $maximum): ?int
    {
        if (! is_int($value) && ! (is_string($value) && preg_match('/^\d+$/', $value) === 1)) {
            return null;
        }

        $value = (int) $value;

        return $value >= $minimum && $value <= $maximum ? $value : null;
    }

    private function boundedNumber(mixed $value): int|float|null
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = $value + 0;

        return is_finite((float) $value) && abs((float) $value) <= self::MAX_ABSOLUTE_NUMBER
            ? $value
            : null;
    }

    /** @return array<string, string> */
    private function labels(Collection $definitions): array
    {
        return $definitions->mapWithKeys(fn (LetterFieldDefinition $definition): array => [
            'fields.'.$definition->key => $definition->label,
        ])->all();
    }
}
