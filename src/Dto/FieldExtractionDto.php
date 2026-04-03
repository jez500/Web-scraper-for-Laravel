<?php

namespace Jez500\WebScraperForLaravel\Dto;

use Jez500\WebScraperForLaravel\Exceptions\SchemaValidationException;
use Jez500\WebScraperForLaravel\Schema\SchemaValidator;

class FieldExtractionDto
{
    public function __construct(
        public string $type,
        public ?string $value = null,
        public ?string $prepend = null,
        public ?string $append = null,
        public ?MatchDefinitionDto $match = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $match = null;
        if (array_key_exists('match', $data) && $data['match'] !== null) {
            if (! is_array($data['match'])) {
                throw new SchemaValidationException(['field.match must be an object definition.']);
            }

            $match = MatchDefinitionDto::fromArray($data['match']);
        }

        $dto = new self(
            type: (string) ($data['type'] ?? ''),
            value: self::normalizeOptionalString($data, 'value'),
            prepend: self::normalizeOptionalString($data, 'prepend'),
            append: self::normalizeOptionalString($data, 'append'),
            match: $match,
        );

        (new SchemaValidator)->validateField($dto);

        return $dto;
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new SchemaValidationException(['Field definition JSON must decode to an object.']);
        }

        return self::fromArray($decoded);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'type' => $this->type,
        ];

        if ($this->value !== null) {
            $payload['value'] = $this->value;
        }

        if ($this->prepend !== null) {
            $payload['prepend'] = $this->prepend;
        }

        if ($this->append !== null) {
            $payload['append'] = $this->append;
        }

        if ($this->match !== null) {
            $payload['match'] = $this->match->toArray();
        }

        return $payload;
    }

    protected static function normalizeOptionalString(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        if (! is_string($data[$key])) {
            throw new SchemaValidationException(["field.{$key} must be a string or null."]);
        }

        return $data[$key];
    }
}
