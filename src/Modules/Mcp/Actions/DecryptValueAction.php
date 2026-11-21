<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;

/** @final */
class DecryptValueAction
{
    public const KINDS = ['cookie', 'payload'];

    public function __construct(private readonly Encrypter $encrypter) {}

    /**
     * @return array{kind: string, value: mixed}
     *
     * @throws McpToolException
     */
    public function execute(string $value, string $kind = 'cookie', ?string $name = null): array
    {
        $this->assertKnownKind(kind: $kind);

        $raw = $this->decrypt(value: $value);

        $decrypted = match ($kind) {
            'cookie' => $this->readCookieValue(raw: $raw, name: $name),
            default => $this->readPayloadValue(raw: $raw),
        };

        return [
            'kind' => $kind,
            'value' => $decrypted,
        ];
    }

    /**
     * @throws McpToolException
     */
    private function assertKnownKind(string $kind): void
    {
        if (in_array($kind, self::KINDS, true)) {
            return;
        }

        $knownKinds = implode(', ', self::KINDS);

        throw McpToolException::becauseInputIsInvalid(
            message: "Unknown kind `{$kind}`.",
            hint: "Use one of {$knownKinds}.",
        );
    }

    /**
     * @throws McpToolException
     */
    private function decrypt(string $value): string
    {
        try {
            $raw = $this->encrypter->decrypt($value, false);
        } catch (DecryptException) {
            throw McpToolException::becauseDecryptionFailed();
        }

        if (! is_string($raw)) {
            throw McpToolException::becauseDecryptionFailed();
        }

        return $raw;
    }

    private function readCookieValue(string $raw, ?string $name): string
    {
        if ($name === null) {
            return $raw;
        }

        $prefix = CookieValuePrefix::create($name, $this->encrypter->getKey());

        return str_starts_with($raw, $prefix) ? substr($raw, strlen($prefix)) : $raw;
    }

    /**
     * Payloads are serialized by `encrypt()`, so classes are never rebuilt from them.
     */
    private function readPayloadValue(string $raw): mixed
    {
        $value = @unserialize($raw, ['allowed_classes' => false]);

        return $value === false && $raw !== serialize(false) ? $raw : $value;
    }
}
