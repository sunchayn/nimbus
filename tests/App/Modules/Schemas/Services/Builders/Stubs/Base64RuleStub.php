<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services\Builders\Stubs;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Base64RuleStub implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void {}
}
