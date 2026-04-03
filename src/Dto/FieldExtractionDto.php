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
        $dto = new self(
            type: (string) ($data['type'] ?? ''),
            value: array_key_exists('value', $data) && $data['value'] !== null ? (string) $data['value'] : null,
            prepend: array_key_exists('prepend', $data) && $data['prepend'] !== null ? (string) $data['prepend'] : null,
            append: array_key_exists('append', $data) && $data['append'] !== null ? (string) $data['append'] : null,
            match: isset($data['match']) ? MatchDefinitionDto::fromArray($data['match']) : null,
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
}
