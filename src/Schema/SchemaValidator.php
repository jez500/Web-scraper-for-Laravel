<?php

namespace Jez500\WebScraperForLaravel\Schema;

use Jez500\WebScraperForLaravel\Dto\FieldExtractionDto;
use Jez500\WebScraperForLaravel\Dto\MatchDefinitionDto;
use Jez500\WebScraperForLaravel\Dto\ScrapeSchemaDto;
use Jez500\WebScraperForLaravel\Exceptions\SchemaValidationException;

class SchemaValidator
{
    /**
     * @var array<int, string>
     */
    protected array $supportedTypes = [
        'css',
        'json',
        'regex',
        'schema_org',
        'xpath',
    ];

    public function validateSchema(ScrapeSchemaDto $schema): ScrapeSchemaDto
    {
        $errors = [];

        foreach ($schema->fields as $name => $definition) {
            try {
                $this->validateField($definition, "fields.{$name}");
            } catch (SchemaValidationException $exception) {
                array_push($errors, ...$exception->errors());
            }
        }

        if ($errors !== []) {
            throw new SchemaValidationException($errors);
        }

        return $schema;
    }

    public function validateField(FieldExtractionDto $field, string $path = 'field'): FieldExtractionDto
    {
        $errors = [];

        if (! in_array($field->type, $this->supportedTypes, true)) {
            $errors[] = "{$path}.type must be one of: ".implode(', ', $this->supportedTypes).'.';
        }

        if (in_array($field->type, ['css', 'json', 'regex', 'xpath'], true) && blank($field->value)) {
            $errors[] = "{$path}.value is required for {$field->type} extraction.";
        }

        if ($field->type === 'regex' && ! blank($field->value)) {
            $this->validateRegex((string) $field->value, "{$path}.value", $errors);
        }

        if ($field->match !== null) {
            try {
                $this->validateMatchDefinition($field->match, "{$path}.match");
            } catch (SchemaValidationException $exception) {
                array_push($errors, ...$exception->errors());
            }
        }

        if ($errors !== []) {
            throw new SchemaValidationException($errors);
        }

        return $field;
    }

    public function validateMatchDefinition(MatchDefinitionDto $match, string $path = 'match'): MatchDefinitionDto
    {
        $errors = [];

        if ($match->default !== null) {
            try {
                $this->validateField($match->default, "{$path}.default");
            } catch (SchemaValidationException $exception) {
                array_push($errors, ...$exception->errors());
            }
        }

        foreach ($match->cases as $name => $definition) {
            try {
                $this->validateField($definition, "{$path}.cases.{$name}");
            } catch (SchemaValidationException $exception) {
                array_push($errors, ...$exception->errors());
            }
        }

        if ($errors !== []) {
            throw new SchemaValidationException($errors);
        }

        return $match;
    }

    /**
     * @param  array<int, string>  $errors
     */
    protected function validateRegex(string $regex, string $path, array &$errors): void
    {
        $result = @preg_match($regex, '');

        if ($result === false) {
            $errors[] = "{$path} must be a valid regular expression.";
        }
    }
}
