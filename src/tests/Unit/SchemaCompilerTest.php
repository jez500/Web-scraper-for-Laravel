<?php

namespace Jez500\WebScraperForLaravel\tests\Unit;

use Jez500\WebScraperForLaravel\Dto\ScrapeSchemaDto;

class SchemaCompilerTest extends WebScraperTest
{
    public function test_can_compile_simple_field_definitions(): void
    {
        $schema = ScrapeSchemaDto::fromArray([
            'fields' => [
                'title' => [
                    'type' => 'css',
                    'value' => 'title',
                ],
                'heading' => [
                    'type' => 'xpath',
                    'value' => '//h1',
                ],
                'title_regex' => [
                    'type' => 'regex',
                    'value' => '~<title>(.*)</title>~',
                ],
            ],
        ]);

        $result = $this->getScraper()
            ->from('https://example.com/')
            ->get()
            ->fromDto($schema);

        $this->assertSame('Example Domain', $result->get('title'));
        $this->assertSame('Example Domain', $result->get('heading'));
        $this->assertSame('Example Domain', $result->get('title_regex'));
    }

    public function test_can_compile_schema_org(): void
    {
        $scraper = $this->getScraper();
        $scraper->setBody('<html><head><script type="application/ld+json">{"name":"Example Domain","@type":"WebPage"}</script></head><body></body></html>');

        $result = $scraper->fromDto([
            'fields' => [
                'structured' => [
                    'type' => 'schema_org',
                ],
            ],
        ]);

        $this->assertSame('Example Domain', $result->get('structured')['name']);
        $this->assertSame('WebPage', $result->get('structured')['@type']);
    }

    public function test_can_resolve_match_definitions(): void
    {
        $scraper = $this->getScraper();
        $scraper->setBody('
            <html>
                <body>
                    <div class="availability">in stock</div>
                    <div class="availability-label">Available now</div>
                    <div class="availability-default">Unavailable</div>
                </body>
            </html>
        ');

        $schema = ScrapeSchemaDto::fromArray([
            'fields' => [
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
        ]);

        $result = $scraper->fromDto($schema);

        $this->assertSame('Available now', $result->get('availability'));
    }

    public function test_from_dto_accepts_json_payloads(): void
    {
        $schema = json_encode([
            'fields' => [
                'title' => [
                    'type' => 'css',
                    'value' => 'title',
                ],
            ],
        ]);

        $result = $this->getScraper()
            ->from('https://example.com/')
            ->get()
            ->fromDto($schema);

        $this->assertSame('Example Domain', $result->get('title'));
    }

    public function test_from_dto_accepts_legacy_array_payloads(): void
    {
        $result = $this->getScraper()
            ->from('https://example.com/')
            ->get()
            ->fromDto([
                'fields' => [
                    'title' => [
                        'type' => 'css',
                        'value' => 'title',
                    ],
                    'heading' => [
                        'type' => 'xpath',
                        'value' => '//h1',
                    ],
                ],
            ]);

        $this->assertSame('Example Domain', $result->get('title'));
        $this->assertSame('Example Domain', $result->get('heading'));
    }
}
