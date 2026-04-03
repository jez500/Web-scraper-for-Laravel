<?php

namespace Jez500\WebScraperForLaravel\Schema;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Jez500\WebScraperForLaravel\Dto\FieldExtractionDto;
use Jez500\WebScraperForLaravel\Dto\MatchDefinitionDto;
use Jez500\WebScraperForLaravel\Dto\ScrapeSchemaDto;
use Jez500\WebScraperForLaravel\WebScraperInterface;

class SchemaCompiler
{
    public function __construct(
        protected WebScraperInterface $scraper,
        protected SchemaValidator $validator = new SchemaValidator,
    ) {}

    public function compile(FieldExtractionDto|ScrapeSchemaDto|array|string $schema): Collection
    {
        $schema = $this->normalizeSchema($schema);
        $this->validator->validateSchema($schema);

        $result = [];

        foreach ($schema->fields as $name => $definition) {
            $result[$name] = $this->resolveField($definition);
        }

        return collect($result);
    }

    protected function normalizeSchema(FieldExtractionDto|ScrapeSchemaDto|array|string $schema): ScrapeSchemaDto
    {
        if ($schema instanceof FieldExtractionDto) {
            return new ScrapeSchemaDto([
                'field' => $schema,
            ]);
        }

        if ($schema instanceof ScrapeSchemaDto) {
            return $schema;
        }

        if (is_string($schema)) {
            return ScrapeSchemaDto::fromJson($schema);
        }

        return ScrapeSchemaDto::fromArray($schema);
    }

    protected function resolveField(FieldExtractionDto $field): mixed
    {
        $value = $this->extract($field);

        if ($field->match !== null) {
            $value = $this->resolveMatch($value, $field->match);
        }

        return $this->applyTransforms($value, $field);
    }

    protected function extract(FieldExtractionDto $field): mixed
    {
        return match ($field->type) {
            'css' => $this->normalizeCollection($this->scraper->getSelector(...self::parseCssSelector((string) $field->value))),
            'json' => $this->normalizeCollection($this->scraper->getJson((string) $field->value)),
            'regex' => $this->normalizeCollection($this->scraper->getRegex((string) $field->value)),
            'schema_org' => $this->normalizeCollection($this->scraper->getSchemaOrg()),
            'xpath' => $this->normalizeCollection($this->scraper->getXpath((string) $field->value)),
            default => null,
        };
    }

    /**
     * Parse a CSS selector value into arguments for getSelector().
     *
     * Supports two shorthand conventions:
     * - Pipe delimiter: "selector|attribute" extracts the named attribute (e.g. "meta[property=og:title]|content")
     * - HTML prefix: "!selector" returns raw innerHTML instead of text (e.g. "!.rich-content")
     *
     * @return array{0: string, 1?: string, 2?: array<int, string>}
     */
    public static function parseCssSelector(string $selector): array
    {
        if (str_starts_with($selector, '!')) {
            $actualSelector = substr($selector, 1);
            return [$actualSelector, 'html'];
        }

        if (! str_contains($selector, '|')) {
            return [$selector];
        }

        $parts = explode('|', $selector);
        $attr = array_pop($parts);
        $selectorPart = implode('|', $parts);

        if ($selectorPart === '') {
            throw new \InvalidArgumentException('CSS selector cannot be empty before pipe delimiter "|" in: ' . $selector);
        }

        if ($attr === '') {
            throw new \InvalidArgumentException('Attribute name cannot be empty after pipe delimiter "|" in: ' . $selector);
        }

        return [$selectorPart, 'attr', [$attr]];
    }

    protected function resolveMatch(mixed $value, MatchDefinitionDto $match): mixed
    {
        $key = $this->normalizeMatchKey($value);

        $normalizedCases = [];
        foreach ($match->cases as $caseKey => $caseDefinition) {
            $normalizedCases[$this->normalizeMatchKey($caseKey)] = $caseDefinition;
        }

        if ($key !== null && array_key_exists($key, $normalizedCases)) {
            return $this->resolveField($normalizedCases[$key]);
        }

        if ($match->default !== null) {
            return $this->resolveField($match->default);
        }

        return $value;
    }

    protected function applyTransforms(mixed $value, FieldExtractionDto $field): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->applyTransforms($item, $field), $value);
        }

        if ($value === null) {
            return null;
        }

        if ($field->prepend === null && $field->append === null) {
            return $value;
        }

        $value = (string) $value;

        if ($field->prepend !== null) {
            $value = $field->prepend.$value;
        }

        if ($field->append !== null) {
            $value .= $field->append;
        }

        return $value;
    }

    protected function normalizeCollection(Collection $collection): mixed
    {
        $values = $collection->values()->all();

        if ($values === []) {
            return null;
        }

        if (count($values) === 1) {
            return $values[0];
        }

        return $values;
    }

    protected function normalizeMatchKey(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = Arr::first($value);
        }

        if ($value === null) {
            return null;
        }

        return Str::of((string) $value)->trim()->lower()->toString();
    }
}