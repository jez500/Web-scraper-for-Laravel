<?php

namespace Jez500\WebScraperForLaravel\Exceptions;

use InvalidArgumentException;

class SchemaValidationException extends InvalidArgumentException
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(
        protected array $errors,
        string $message = 'Invalid scrape schema'
    ) {
        parent::__construct($message."\n".implode("\n", $errors));
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
