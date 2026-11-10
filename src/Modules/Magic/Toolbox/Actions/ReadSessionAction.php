<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Session\SessionManager;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;

/** @final */
class ReadSessionAction
{
    public function __construct(
        private readonly SessionManager $sessionManager,
        private readonly Repository $repository,
        private readonly DecryptValueAction $decryptValueAction,
        private readonly Encrypter $encrypter,
    ) {}

    /**
     * Reads a stored session by its ID, or by the value of its encrypted cookie.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidToolInputException
     */
    public function execute(string $cookieValue): array
    {
        $id = trim($cookieValue);

        if (preg_match('/^[A-Za-z0-9]{40}$/', $id) !== 1) {
            $id = $this->decryptValueAction->execute($id, (string) $this->repository->get('session.cookie'))['value'];
        }

        $payload = $this->sessionManager->driver()->getHandler()->read($id);

        if ($payload === '') {
            throw InvalidToolInputException::becauseSessionIsUnknown();
        }

        // Objects are never instantiated, so a stored payload cannot run host code.
        $attributes = $this->decode($payload);

        if (! is_array($attributes)) {
            throw InvalidToolInputException::becauseSessionIsUnknown();
        }

        /** @var array<string, mixed> $attributes */
        $attributes = json_decode((string) json_encode($attributes, JSON_PARTIAL_OUTPUT_ON_ERROR), true) ?? [];

        return $attributes;
    }

    private function decode(string $payload): mixed
    {
        if ($this->repository->get('session.encrypt')) {
            try {
                $payload = $this->encrypter->decryptString($payload);
            } catch (DecryptException) {
                return null;
            }
        }

        return $this->repository->get('session.serialization') === 'json'
            ? json_decode($payload, true)
            : @unserialize($payload, ['allowed_classes' => false]);
    }
}
