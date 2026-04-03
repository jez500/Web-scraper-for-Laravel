<?php

namespace Jez500\WebScraperForLaravel\Dto;

use Jez500\WebScraperForLaravel\Exceptions\SchemaValidationException;
use Jez500\WebScraperForLaravel\Schema\SchemaValidator;

class ScrapeSchemaDto
{
    /**
     * @param  array<string, FieldExtractionDto>  $fields
     */
    public function __construct(
        public array $fields = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $fields = $data['fields'] ?? $data;

        if (! is_array($fields)) {
            throw new SchemaValidationException(['Schema fields must be an array or object.']);
        }

        $parsed = [];

        foreach ($fields as $name => $definition) {
            if (! is_array($definition)) {
                throw new SchemaValidationException([
                    "fields.{$name} must be an object definition.",
                ]);
            }

            $parsed[(string) $name] = FieldExtractionDto::fromArray($definition);
        }

        $dto = new self($parsed);

        (new SchemaValidator)->validateSchema($dto);

        return $dto;
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new SchemaValidationException(['Schema JSON must decode to an object.']);
        }

        return self::fromArray($decoded);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $fields = [];

        foreach ($this->fields as $name => $definition) {
            $fields[$name] = $definition->toArray();
        }

        return [
            'fields' => $fields,
        ];
    }
}
