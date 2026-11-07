<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions;

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Encryption\Encrypter;

/** @final */
class EncryptValueAction
{
    public function __construct(private readonly Encrypter $encrypter) {}

    /**
     * Encrypts a value, and adds the cookie prefix when a cookie name is given.
     */
    public function execute(string $value, ?string $cookieName = null): string
    {
        if ($cookieName !== null && $cookieName !== '') {
            $value = CookieValuePrefix::create($cookieName, $this->encrypter->getKey()).$value;
        }

        return $this->encrypter->encryptString($value);
    }
}
