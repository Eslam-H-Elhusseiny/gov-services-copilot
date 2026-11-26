<?php

namespace App\Application\Copilot\Llm;

use App\Domain\Copilot\Llm\LlmProviderException;

final class StructuredOutputValidator
{
    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $data
     */
    public static function validate(?string $json, array $schema): array
    {
        if ($json === null || $json === '') {
            throw new LlmProviderException('application', 'Structured output is missing.');
        }

        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new LlmProviderException('application', 'Structured output is not valid JSON.');
        }

        foreach ($schema['required'] ?? [] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new LlmProviderException('application', sprintf('Structured output is missing required key [%s].', $key));
            }
        }

        foreach ($data as $key => $value) {
            $type = $schema['properties'][$key]['type'] ?? null;

            if ($type !== null && ! self::matchesType($value, $type)) {
                throw new LlmProviderException('application', sprintf('Structured output key [%s] has an invalid type.', $key));
            }
        }

        return $data;
    }

    private static function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array', 'object' => is_array($value),
            'null' => $value === null,
            default => true,
        };
    }
}
