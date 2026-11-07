<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors;

/**
 * Processes rules that describe the shape of a value, such as its format, pattern, or accepted file types.
 */
class FormatRuleProcessor
{
    private const UNICODE_LETTERS = '\p{L}\p{M}';

    /**
     * PHP date formats whose output is also a valid JSON Schema date or date-time.
     */
    private const DATE_FORMAT_SCHEMA_FORMATS = [
        'Y-m-d' => 'date',
        'c' => 'date-time',
        'Y-m-d\TH:i:sP' => 'date-time',
        'Y-m-d\TH:i:s.v\Z' => 'date-time',
    ];

    /**
     * @param  string[]  $params
     * @return array<string, mixed>
     */
    public static function process(string $name, array $params): array
    {
        return match ($name) {
            'url', 'active_url' => ['format' => 'uri'],
            'ipv4' => ['format' => 'ipv4'],
            'ipv6' => ['format' => 'ipv6'],
            'ip' => ['hints' => ['IPv4 or IPv6 address']],
            'date_format' => self::dateFormat($params),
            'regex' => self::regex($params[0] ?? ''),
            'alpha' => ['pattern' => self::characterClass($params, '')],
            'alpha_num' => ['pattern' => self::characterClass($params, '\p{N}')],
            'alpha_dash' => ['pattern' => self::characterClass($params, '\p{N}_-')],
            'digits' => self::digits((int) ($params[0] ?? 0), (int) ($params[0] ?? 0)),
            'digits_between' => self::digits((int) ($params[0] ?? 0), (int) ($params[1] ?? 0)),
            'distinct' => ['uniqueItems' => true],
            'between' => ['minimum' => $params[0] ?? null, 'maximum' => $params[1] ?? null],
            'mimes', 'extensions' => ['hints' => ['accepted types '.implode(', ', $params)]],
            'mimetypes' => ['hints' => ['accepted mime types '.implode(', ', $params)]],
            'dimensions' => ['format' => 'binary', 'hints' => ['image dimensions '.implode(', ', $params)]],
            'image' => ['hints' => ['image file']],
            default => [],
        };
    }

    /**
     * @param  string[]  $params
     * @return array{format?: string, hints: string[], dateFormat: string}
     */
    private static function dateFormat(array $params): array
    {
        $format = $params[0] ?? '';

        $schema = [
            'hints' => ['date format '.$format],
            'dateFormat' => $format,
        ];

        // Any other format would fail a JSON Schema format check, so only the dateFormat describes it.
        if (isset(self::DATE_FORMAT_SCHEMA_FORMATS[$format])) {
            $schema['format'] = self::DATE_FORMAT_SCHEMA_FORMATS[$format];
        }

        return $schema;
    }

    /**
     * Keeps the pattern only when ECMAScript reads it the same way as PCRE.
     *
     * @return array{pattern?: string, hints?: string[]}
     */
    private static function regex(string $expression): array
    {
        $delimiter = $expression[0] ?? '';
        $end = strrpos($expression, $delimiter);

        $isPlainDelimiter = $delimiter !== '' && ! ctype_alnum($delimiter) && ! in_array($delimiter, ['\\', '(', '[', '{', '<'], true);

        if (! $isPlainDelimiter || $end === false || $end === 0) {
            return ['hints' => ['matches the regex '.$expression]];
        }

        $pattern = substr($expression, 1, $end - 1);
        $flags = substr($expression, $end + 1);

        $usesPcreOnlySyntax = preg_match('/\\\\[AzZhRGK]|\(\?[^:=!<]|\[\[:|[+*?}]\+/', $pattern) === 1;

        if (($flags !== '' && $flags !== 'u') || $usesPcreOnlySyntax) {
            return ['hints' => ['matches the regex '.$expression]];
        }

        return ['pattern' => $pattern];
    }

    /**
     * Laravel reads letters as Unicode unless the ascii option is given.
     *
     * @param  string[]  $params
     */
    private static function characterClass(array $params, string $extra): string
    {
        $letters = in_array('ascii', $params, true)
            ? 'A-Za-z'
            : self::UNICODE_LETTERS;

        // The ASCII form needs plain digits instead of the Unicode number class.
        if (in_array('ascii', $params, true)) {
            $extra = str_replace('\p{N}', '0-9', $extra);
        }

        return sprintf('^[%s%s]+$', $letters, $extra);
    }

    /**
     * @return array{digits: array{int, int}}
     */
    private static function digits(int $min, int $max): array
    {
        return ['digits' => [$min, $max]];
    }
}
