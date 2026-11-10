<?php

namespace Workbench\App\Support;

/**
 * The result of creating a demo token, shaped like the Sanctum result.
 */
class NewDemoAccessToken
{
    public function __construct(
        public readonly string $plainTextToken,
        public readonly DemoAccessToken $accessToken,
    ) {}
}
