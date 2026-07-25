<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser;

use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ObjectPropertyDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParsedArrayResultDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParsedClosureResultDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParsedObjectResultDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParsedValueDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParseResultDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\Enums\DumpValueTypeEnum;

class VarDumpParser
{
    private const PATTERN_LARAVEL_COMMENT = '/<span[^>]*style=["\'][^"\']*color:\s*#A0A0A0[^"\']*["\'][^>]*>\s*\/\/\s*(.+?)<\/span>/s';

    /**
     * Parse raw HTML output containing one or more Symfony dumps
     */
    public function parse(string $html): ParseResultDto
    {
        $html = $this->cleanHtml($html);
        $dumpSections = $this->extractDumpSections($html);

        if ($dumpSections === []) {
            return ParseResultDto::empty();
        }

        $dumps = [];
        foreach ($dumpSections as $dumpSection) {
            $cleanHtml = $this->removeCommentFromDump($dumpSection);
            $dumps[] = $this->parseValue(trim($cleanHtml));
        }

        return new ParseResultDto(
            source: $this->extractComment($dumpSections[0]),
            dumps: $dumps,
        );
    }

    /**
     * Clean HTML by removing unnecessary elements
     */
    private function cleanHtml(string $html): string
    {
        // Remove style and script tags
        $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

        // Remove ellipsis elements for simpler parsing
        return (string) preg_replace('/<([a-z]+)\s+[^>]*class=(["\'])(?:[^"\'>]*\s)?sf-dump-ellipsis[^"\'>]*\2[^>]*>.*?<\/\1>/si', '', $html);
    }

    /**
     * Extract individual dump sections from HTML
     *
     * @return array<array-key, string>
     */
    private function extractDumpSections(string $html): array
    {
        preg_match_all('/<pre\b[^>]*>\K.*?(?=<\/pre>)/s', $html, $matches);

        return $matches[0];
    }

    /**
     * Extract Laravel file/line comment from dump output
     */
    private function extractComment(string $html): ?string
    {
        if (preg_match(self::PATTERN_LARAVEL_COMMENT, $html, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    /**
     * Remove Laravel comment from dump HTML
     */
    private function removeCommentFromDump(string $html): string
    {
        return (string) preg_replace(self::PATTERN_LARAVEL_COMMENT, '', $html);
    }

    /**
     * Detect the root type of a dump value
     */
    private function detectRootType(string $html): DumpValueTypeEnum
    {
        if ($html === '""') {
            return DumpValueTypeEnum::String;
        }

        if ($html === '[]') {
            return DumpValueTypeEnum::Array;
        }

        // Array with size notation: array:3 [
        if (preg_match('/^<span\b[^>]*class="?sf-dump-note[^>]*>\s*array:\d+/s', $html)) {
            return DumpValueTypeEnum::Array;
        }

        // Closure (must check before object to avoid confusion with named objects)
        if (preg_match('/^<span class="?sf-dump-note[^>]*>Closure\([^)]*\)<\/span>/s', $html)) {
            return DumpValueTypeEnum::Closure;
        }

        // Named Objects (e.g. : ClassName {), or Closures.
        if (preg_match('/^<span class="?sf-dump-note[^>]*>[^<]*<\/span>\s*\{/s', $html, $matches)) {
            // If there are parenthesis in the matched portion (the beginning of the html) then it is a Closure.
            // e.g. <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Foundation\Application::environment(...$environments)"></span> {
            // e.g. <span class=sf-dump-note>Illuminate\Foundation\Application::environment(...$environments)</span> {
            return preg_match('/^.+\([^)]*\)/s', $matches[0])
                ? DumpValueTypeEnum::Closure
                : DumpValueTypeEnum::Object;
        }

        // Runtime object or object reference anchor: {<a... or <a class=sf-dump-ref...
        if (preg_match('/^\{?<a class=sf-dump-ref/s', $html) || preg_match('/^&amp;\d+$/s', $html)) {
            return DumpValueTypeEnum::Object;
        }

        // String value
        if (preg_match('/^"<span\b[^>]*class=sf-dump-str\b/s', $html)) {
            return DumpValueTypeEnum::String;
        }

        // Uninitialized property (must be before const check).
        if (preg_match('/^<span [^>]* title="Uninitialized property">/s', $html)) {
            return DumpValueTypeEnum::Uninitialized;
        }

        // Boolean or null constants
        if (preg_match('/^<span\b[^>]*class=sf-dump-const\b/s', $html)) {
            return DumpValueTypeEnum::Constant;
        }

        // Numeric value
        if (preg_match('/^<span\b[^>]*class=sf-dump-num\b/s', $html)) {
            return DumpValueTypeEnum::Number;
        }

        return DumpValueTypeEnum::Unknown;
    }

    /**
     * Parse a value based on its detected type
     */
    private function parseValue(string $html): ParsedValueDto
    {
        $dumpValueTypeEnum = $this->detectRootType($html);

        $value = match ($dumpValueTypeEnum) {
            DumpValueTypeEnum::Object => $this->parseObject($html),
            DumpValueTypeEnum::Array => $this->parseArray($html),
            DumpValueTypeEnum::String => $this->parseString($html),
            DumpValueTypeEnum::Constant => $this->parseConst($html),
            DumpValueTypeEnum::Number => $this->parseNumber($html),
            DumpValueTypeEnum::Closure => $this->parseClosure($html),
            DumpValueTypeEnum::Uninitialized => $this->parseUninitialized($html),
            DumpValueTypeEnum::Unknown => null,
        };

        return new ParsedValueDto(type: $dumpValueTypeEnum, value: $value);
    }

    /**
     * Parse object structure and properties
     */
    private function parseObject(string $html): ParsedObjectResultDto
    {
        $className = $this->extractObjectClassName($html);

        // Extract content between { and }
        if (! preg_match('/\{<a class=sf-dump-ref[^>]*>[^<]+<\/a><samp[^>]+>(.+)<\/samp>}\n?$/s', $html, $match)) {
            // Sometimes items are not listed when a certain depth is reached.
            // e.g. Symfony\Component\Routing\CompiledRoute {#369 …8}
            return new ParsedObjectResultDto(className: $className, properties: []);
        }

        $content = trim($match[1], "\n");

        // Normalize indentation to simplify parsing
        $content = $this->normalizeIndentation($content);

        $properties = [];

        // Match properties with visibility markers: +/-/# "propertyName": value
        $pattern = '/[#\+\-]"?<span class=sf-dump-(public|protected|private)[^>]*>([^<]+)<\/span>"?:\s(.*?)(?=(?:\n[#\+\-](?:<|"<)|\Z))/s';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $visibility = $match[1];
            $propertyName = trim($match[2]);
            $propertyValueHtml = trim($match[3]);

            $properties[$propertyName] = new ObjectPropertyDto(
                visibility: $visibility,
                value: $this->parseValue($propertyValueHtml),
            );
        }

        return new ParsedObjectResultDto(className: $className, properties: $properties);
    }

    /**
     * Parse array structure and items
     */
    private function parseArray(string $html): ParsedArrayResultDto
    {
        if ($html === '[]') {
            return new ParsedArrayResultDto(items: [], numericallyIndexed: true);
        }

        // Extract content within <samp> tags
        if (! preg_match('/^<span\b[^>]*class=sf-dump-note[^>]*>\s*array:\d+\s*<\/span>\s*\[\s*<samp\b[^>]*>(.*?)<\/samp>]$/s', $html, $match)) {
            // Sometimes items are not listed when a certain depth is reached.
            // e.g. +methods: array:2 [ …2]
            return new ParsedArrayResultDto(items: [], numericallyIndexed: true);
        }

        $content = trim($match[1], "\n");

        // Normalize indentation
        $content = $this->normalizeIndentation($content);

        $items = [];
        $isNumerical = true;

        // Match key => value pairs (works for both indexed and associative)
        $pattern = '/"?<span class=sf-dump-(key|index)[^>]*>([^<]+)<\/span>"?\s=>\s(.*?)(?=(?:\n"?<span class=sf-dump-(?:key|index))|\Z)/s';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $keyType = $match[1]; // 'key' or 'index'
            $key = $match[2];
            $valueHtml = trim($match[3]);

            // If any key is not an index, the array is not numerically indexed
            if ($keyType === 'key') {
                $isNumerical = false;
            }

            $items[$key] = $this->parseValue($valueHtml);
        }

        return new ParsedArrayResultDto(items: $items, numericallyIndexed: $isNumerical);
    }

    /**
     * Parse string primitive value
     */
    private function parseString(string $html): string
    {
        if ($html === '""') {
            return '';
        }

        if (preg_match('/<span class=sf-dump-str[^>]*>([^<]*)<\/span>/', $html, $match)) {
            return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
        }

        return '';
    }

    /**
     * Parse constant value (true, false, null)
     */
    private function parseConst(string $html): ?bool
    {
        if (preg_match('/<span class=sf-dump-const[^>]*>([^<]+)<\/span>/', $html, $match)) {
            return match (strtolower(trim($match[1]))) {
                'true' => true,
                'false' => false,
                default => null,
            };
        }

        return null;
    }

    /**
     * Parse numeric value (int or float)
     */
    private function parseNumber(string $html): int|float
    {
        if (preg_match('/<span class=sf-dump-num[^>]*>([^<]+)<\/span>/', $html, $match)) {
            $value = $match[1];

            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return 0;
    }

    /**
     * Parse closure information
     */
    private function parseClosure(string $html): ParsedClosureResultDto
    {
        $signature = 'CLosure()';
        $className = null;
        $thisReference = null;

        // Extract signature from between span tags.
        if (preg_match('/^<span[^>]*>([^<]+)/', $html, $match)) {
            $signature = trim($match[1]);
        }
        // Extract signature from the title
        elseif (preg_match('/^<span[^>]*title="([^"\n]+)/', $html, $match)) {
            $signature = trim($match[1]);
        }

        // Extract class context
        if (preg_match('/<span [^>]*class=sf-dump-meta\s*>class<\/span>:\s*"?<span [^>]*title="([^"\n]+)/', $html, $match)) {
            $className = trim($match[1]);
        }

        // Extract this reference
        if (preg_match('/<span [^>]*class=sf-dump-meta\s*>this<\/span>:\s*"?<span [^>]*title="([^"\n]+)/', $html, $match)) {
            $thisReference = trim($match[1]);
        }

        return new ParsedClosureResultDto(
            signature: $signature,
            className: $className,
            thisReference: $thisReference,
        );
    }

    /**
     * Parse uninitialized property annotation
     */
    private function parseUninitialized(string $html): string
    {
        if (preg_match('/^<span[^>]+>([^<]+)<\/span>$/', $html, $match)) {
            return $match[1];
        }

        return 'undefined';
    }

    /**
     * Extract class name from the object dump
     */
    private function extractObjectClassName(string $html): string
    {
        // Extract header portion before opening brace {
        $header = str_contains($html, '{') ? explode('{', $html, 2)[0] : $html;

        // Check the title attribute in the span or link before {
        $title = $this->extractClassNameFromTitle($header);

        if ($title !== null) {
            return $title;
        }

        // Check for class name in sf-dump-note span
        if (preg_match('/<span class="?[^">]*sf-dump-note[^>]*>(.*?)<\/span>/s', $header, $match)) {
            $noteText = html_entity_decode(strip_tags(trim($match[1])), ENT_QUOTES | ENT_HTML5);

            if ($noteText !== '') {
                return $noteText;
            }
        }

        // Check for reference tag like &amp;1 or <a class=sf-dump-ref ...>&amp;1</a>
        if (preg_match('/&amp;(\d+)/', $header, $match)) {
            return '&'.$match[1].' (reference)';
        }

        // Default fallback for objects without explicit class name
        return '<runtime object>';
    }

    /**
     * Extract and sanitize class name from title attributes in the HTML header
     */
    private function extractClassNameFromTitle(string $header): ?string
    {
        if (! preg_match_all('/<(?:span|a)\b[^>]*title="([^"]+)"[^>]*>/is', $header, $matches, PREG_SET_ORDER)) {
            return null;
        }

        foreach ($matches as $match) {
            $fullTag = $match[0];
            $titleVal = $match[1];

            // Ignore property visibility spans
            if (preg_match('/class="?[^">]*sf-dump-(?:public|protected|private)\b/i', $fullTag)) {
                continue;
            }

            $cleanTitle = (string) preg_replace(
                ['/\s+/', '/\s+@\d+$/'],
                [' ', ''],
                html_entity_decode(trim($titleVal), ENT_QUOTES | ENT_HTML5),
            );

            if ($cleanTitle !== '' && ! preg_match('/\b\d+\s+occurrences?\b/i', $cleanTitle)) {
                return $cleanTitle;
            }
        }

        return null;
    }

    /**
     * Normalize indentation by removing common leading whitespace
     */
    private function normalizeIndentation(string $content): string
    {
        // Find the indentation of the first line
        if (preg_match('/^(\s+)/s', $content, $match)) {
            $indent = $match[1];

            // Remove this indentation from all lines
            return (string) preg_replace("/\n{$indent}/", "\n", ltrim($content));
        }

        return ltrim($content);
    }
}
