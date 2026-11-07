<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Encryption\Encrypter;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;

/** @final */
class DecryptValueAction
{
    public function __construct(private readonly Encrypter $encrypter) {}

    /**
     * Decrypts a value, a cookie, or a session ID.
     * Pass the cookie name to check the cookie prefix.
     *
     * @return array{value: string, prefix_valid: bool|null}
     *
     * @throws InvalidToolInputException
     */
    public function execute(string $value, ?string $cookieName = null): array
    {
        try {
            $plain = $this->encrypter->decryptString(urldecode(trim($value)));
        } catch (DecryptException) {
            throw InvalidToolInputException::becauseValueCannotBeDecrypted();
        }

        // Values encrypted with serialization hold a PHP string, such as `s:3:"abc";`.
        if (preg_match('/^s:\d+:"(.*)";$/s', $plain, $matches) === 1) {
            $plain = $matches[1];
        }

        if ($cookieName !== null && $cookieName !== '') {
            $prefix = CookieValuePrefix::create($cookieName, $this->encrypter->getKey());

            $valid = str_starts_with($plain, $prefix);

            return ['value' => $valid ? CookieValuePrefix::remove($plain) : $plain, 'prefix_valid' => $valid];
        }

        if (preg_match('/^[a-f0-9]{40,64}\|/', $plain) === 1) {
            return ['value' => CookieValuePrefix::remove($plain), 'prefix_valid' => null];
        }

        return ['value' => $plain, 'prefix_valid' => null];
    }
}
