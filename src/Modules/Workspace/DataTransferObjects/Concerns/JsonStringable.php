<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\Concerns;

/**
 * Turns a DTO into the text of a workspace file.
 */
trait JsonStringable
{
    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    public function toJson(): string
    {
        $json = (string) json_encode(
            $this->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        // PHP indents with four spaces and the files use two, which keeps diffs small.
        // A string never holds a raw new line, so only the indentation is matched.
        $json = (string) preg_replace_callback(
            '/^ +/m',
            fn (array $match): string => str_repeat(' ', intdiv(strlen($match[0]), 2)),
            $json,
        );

        return $json."\n";
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
