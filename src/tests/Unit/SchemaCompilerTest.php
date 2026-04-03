<?php

namespace Jez500\WebScraperForLaravel\tests\Unit;

use Jez500\WebScraperForLaravel\Dto\FieldExtractionDto;
use Jez500\WebScraperForLaravel\Dto\ScrapeSchemaDto;
use Jez500\WebScraperForLaravel\Schema\SchemaCompiler;

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

    public function test_from_dto_accepts_field_extraction_dto_payloads(): void
    {
        $field = FieldExtractionDto::fromArray([
            'type' => 'css',
            'value' => 'title',
        ]);

        $result = $this->getScraper()
            ->from('https://example.com/')
            ->get()
            ->fromDto($field);

        $this->assertSame('Example Domain', $result->get('field'));
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

    public function test_css_selector_pipe_delimiter_extracts_attribute(): void
    {
        $scraper = $this->getScraper();
        $scraper->setBody('<html><body><a href="https://example.com" class="link">Click</a></body></html>');

        $result = $scraper->fromDto([
            'fields' => [
                'url' => [
                    'type' => 'css',
                    'value' => 'a.link|href',
                ],
            ],
        ]);

        $this->assertSame('https://example.com', $result->get('url'));
    }

    public function test_css_selector_pipe_delimiter_extracts_meta_content(): void
    {
        $scraper = $this->getScraper();
        $scraper->setBody('<html><head><meta property="og:title" content="My Page Title"></head><body></body></html>');

        $result = $scraper->fromDto([
            'fields' => [
                'title' => [
                    'type' => 'css',
                    'value' => 'meta[property=og:title]|content',
                ],
            ],
        ]);

        $this->assertSame('My Page Title', $result->get('title'));
    }

    public function test_css_selector_html_prefix_returns_inner_html(): void
    {
        $scraper = $this->getScraper();
        $scraper->setBody('<html><body><div class="rich"><strong>Bold</strong> text</div></body></html>');

        $result = $scraper->fromDto([
            'fields' => [
                'content' => [
                    'type' => 'css',
                    'value' => '!.rich',
                ],
            ],
        ]);

        $this->assertSame('<strong>Bold</strong> text', $result->get('content'));
    }

    public function test_css_selector_without_special_chars_returns_text(): void
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
                ],
            ]);

        $this->assertSame('Example Domain', $result->get('title'));
    }

    public function test_parse_css_selector_unit(): void
    {
        $this->assertSame(
            ['title'],
            SchemaCompiler::parseCssSelector('title'),
            'Plain selector returns single-element array'
        );

        $this->assertSame(
            ['meta[property=og:title]', 'attr', ['content']],
            SchemaCompiler::parseCssSelector('meta[property=og:title]|content'),
            'Pipe delimiter splits selector and attribute'
        );

        $this->assertSame(
            ['.rich-content', 'html'],
            SchemaCompiler::parseCssSelector('!.rich-content'),
            'Exclamation prefix returns html mode'
        );

        $this->assertSame(
            ['', 'html'],
            SchemaCompiler::parseCssSelector('!'),
            'Bare exclamation returns empty selector with html mode'
        );
    }
}
