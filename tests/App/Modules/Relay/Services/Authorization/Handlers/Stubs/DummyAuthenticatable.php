<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers\Stubs;

use Illuminate\Contracts\Auth\Authenticatable;

class DummyAuthenticatable implements Authenticatable
{
    private readonly int $id;

    private string $rememberToken;

    public function __construct(
        ?int $id = null,
        ?string $rememberToken = null,
    ) {
        $this->id = $id ?? fake()->randomNumber();
        $this->rememberToken = $rememberToken ?? fake()->sha256();
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return fake()->password();
    }

    public function getRememberToken()
    {
        return $this->rememberToken;
    }

    public function setRememberToken($value)
    {
        $this->rememberToken = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    public function getRecallerCookieValue(): string
    {
        return $this->getAuthIdentifier().'|'.$this->getRememberToken().'|'.$this->getAuthPasswordName();
    }
}
