<?php

namespace Jez500\WebScraperForLaravel\Dto;

use Jez500\WebScraperForLaravel\Exceptions\SchemaValidationException;
use Jez500\WebScraperForLaravel\Schema\SchemaValidator;

class MatchDefinitionDto
{
    /**
     * @param  array<string, FieldExtractionDto>  $cases
     */
    public function __construct(
        public ?FieldExtractionDto $default = null,
        public array $cases = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $default = null;
        if (array_key_exists('default', $data) && $data['default'] !== null) {
            if (! is_array($data['default'])) {
                throw new SchemaValidationException(['match.default must be an object definition.']);
            }

            $default = FieldExtractionDto::fromArray($data['default']);
        }

        $cases = [];
        $rawCases = $data['cases'] ?? [];
        if (! is_array($rawCases)) {
            throw new SchemaValidationException(['match.cases must be an object definition.']);
        }

        foreach ($rawCases as $match => $definition) {
            if (! is_array($definition)) {
                throw new SchemaValidationException([
                    "match.cases.{$match} must be an object definition.",
                ]);
            }

            $cases[(string) $match] = FieldExtractionDto::fromArray($definition);
        }

        $dto = new self($default, $cases);

        (new SchemaValidator)->validateMatchDefinition($dto);

        return $dto;
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new SchemaValidationException(['Match definition JSON must decode to an object.']);
        }

        return self::fromArray($decoded);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->default !== null) {
            $payload['default'] = $this->default->toArray();
        }

        if ($this->cases !== []) {
            $cases = [];

            foreach ($this->cases as $match => $definition) {
                $cases[$match] = $definition->toArray();
            }

            $payload['cases'] = $cases;
        }

        return $payload;
    }
}
