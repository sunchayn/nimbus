<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers\Stubs;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\PendingRequest;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Contracts\SpecialAuthenticationInjectorContract;

class DummySpecialAuthenticationInjector implements SpecialAuthenticationInjectorContract
{
    public function attach(PendingRequest $pendingRequest, Authenticatable $authenticatable): PendingRequest {}
}
