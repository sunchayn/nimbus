<?php

namespace Sunchayn\Nimbus\Modules\Relay\ValueObjects;

use Illuminate\Support\Str;

class ResponseCookieValueObject
{
    protected ?string $decryptedValue;

    public function __construct(
        public string $key,
        protected string $rawValue,
        protected string $prefix,
        bool $decrypt = true,
    ) {
        $this->decryptedValue = $decrypt ? $this->computeDecryptedValue() : null;
    }

    /**
     * @return array{
     *     key: string,
     *     value: array{
     *         raw: string,
     *         decrypted: string|null,
     *     },
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => [
                'raw' => $this->rawValue,
                'decrypted' => $this->decryptedValue,
            ],
        ];
    }

    private function computeDecryptedValue(): ?string
    {
        return rescue(
            fn () => Str::replaceStart($this->prefix, '', decrypt($this->rawValue, false)),
            report: false,
        );
    }
}
