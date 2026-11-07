<?php

namespace Workbench\App\Models\Concerns;

use Workbench\App\Support\DemoAccessToken;
use Workbench\App\Support\NewDemoAccessToken;

/**
 * Gives the workbench user a `createToken` method like the Sanctum trait, without the package.
 */
trait HasDemoTokens
{
    /**
     * @param  list<string>  $abilities
     */
    public function createToken(string $name, array $abilities = ['*']): NewDemoAccessToken
    {
        return DemoAccessToken::create($this->getKey(), $abilities);
    }
}
