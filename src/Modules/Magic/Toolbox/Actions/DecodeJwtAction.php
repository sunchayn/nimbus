<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions;

use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;

/** @final */
class DecodeJwtAction
{
    /**
     * Decodes the header and the payload without verifying the signature.
     *
     * @return array{header: array<string, mixed>, payload: array<string, mixed>}
     *
     * @throws InvalidToolInputException
     */
    public function execute(string $token): array
    {
        $parts = explode('.', trim($token));

        if (count($parts) !== 3) {
            throw InvalidToolInputException::becauseTokenIsNotAJwt();
        }

        $header = $this->decodePart($parts[0]);
        $payload = $this->decodePart($parts[1]);

        return ['header' => $header, 'payload' => $payload];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidToolInputException
     */
    private function decodePart(string $part): array
    {
        $decoded = base64_decode(strtr($part, '-_', '+/'), true);
        $json = $decoded === false ? null : json_decode($decoded, true);

        if (! is_array($json)) {
            throw InvalidToolInputException::becauseTokenIsNotAJwt();
        }

        return $json;
    }
}
