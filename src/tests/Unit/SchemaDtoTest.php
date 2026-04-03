<?php

namespace Jez500\WebScraperForLaravel\tests\Unit;

use Jez500\WebScraperForLaravel\Dto\FieldExtractionDto;
use Jez500\WebScraperForLaravel\Dto\ScrapeSchemaDto;
use Jez500\WebScraperForLaravel\Exceptions\SchemaValidationException;
use Orchestra\Testbench\TestCase;

class SchemaDtoTest extends TestCase
{
    public function test_can_hydrate_schema_from_array_and_json(): void
    {
        $payload = [
            'fields' => [
                'title' => [
                    'type' => 'css',
                    'value' => 'title',
                    'append' => '!',
                ],
                'availability' => [
                    'type' => 'css',
                    'value' => '.availability',
                    'match' => [
                        'default' => [
                            'type' => 'css',
                            'value' => '.availability-default',
                        ],
                        'cases' => [
                            'in stock' => [
                                'type' => 'css',
                                'value' => '.availability-label',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $schema = ScrapeSchemaDto::fromArray($payload);
        $this->assertSame('css', $schema->fields['title']->type);
        $this->assertSame('title', $schema->fields['title']->value);
        $this->assertSame('!', $schema->fields['title']->append);
        $this->assertSame('in stock', array_key_first($schema->fields['availability']->match->cases));
        $this->assertSame('title', ScrapeSchemaDto::fromJson(json_encode($payload))->fields['title']->value);
    }

    public function test_invalid_extractor_type_throws_validation_exception(): void
    {
        $this->expectException(SchemaValidationException::class);
        $this->expectExceptionMessage('field.type must be one of');

        FieldExtractionDto::fromArray([
            'type' => 'bogus',
            'value' => 'title',
        ]);
    }

    public function test_invalid_regex_pattern_throws_validation_exception(): void
    {
        $this->expectException(SchemaValidationException::class);
        $this->expectExceptionMessage('field.value must be a valid regular expression');

        FieldExtractionDto::fromArray([
            'type' => 'regex',
            'value' => '/(/',
        ]);
    }

    public function test_schema_requires_fields_to_be_objects(): void
    {
        $this->expectException(SchemaValidationException::class);
        $this->expectExceptionMessage('fields.title must be an object definition');

        ScrapeSchemaDto::fromArray([
            'fields' => [
                'title' => 'Example Domain',
            ],
        ]);
    }

    public function test_invalid_match_definition_throws_validation_exception(): void
    {
        $this->expectException(SchemaValidationException::class);
        $this->expectExceptionMessage('field.match must be an object definition');

        FieldExtractionDto::fromArray([
            'type' => 'css',
            'value' => 'title',
            'match' => 'in stock',
        ]);
    }

    public function test_invalid_match_default_throws_validation_exception(): void
    {
        $this->expectException(SchemaValidationException::class);
        $this->expectExceptionMessage('match.default must be an object definition');

        FieldExtractionDto::fromArray([
            'type' => 'css',
            'value' => 'title',
            'match' => [
                'default' => 'fallback',
            ],
        ]);
    }

    public function test_invalid_prepend_values_throw_validation_exception(): void
    {
        $this->expectException(SchemaValidationException::class);
        $this->expectExceptionMessage('field.prepend must be a string or null');

        FieldExtractionDto::fromArray([
            'type' => 'css',
            'value' => 'title',
            'prepend' => ['prefix'],
            'append' => ['suffix'],
        ]);
    }

    public function test_invalid_append_values_throw_validation_exception(): void
    {
        $this->expectException(SchemaValidationException::class);
        $this->expectExceptionMessage('field.append must be a string or null');

        FieldExtractionDto::fromArray([
            'type' => 'css',
            'value' => 'title',
            'prepend' => 'prefix',
            'append' => ['suffix'],
        ]);
    }

    public function test_invalid_match_cases_throw_validation_exception(): void
    {
        $this->expectException(SchemaValidationException::class);
        $this->expectExceptionMessage('match.cases must be an object definition');

        FieldExtractionDto::fromArray([
            'type' => 'css',
            'value' => 'title',
            'match' => [
                'cases' => 'in stock',
            ],
        ]);
    }
}
