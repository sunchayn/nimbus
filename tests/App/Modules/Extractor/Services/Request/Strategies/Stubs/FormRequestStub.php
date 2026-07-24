<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs;

use Illuminate\Http\Request;

class FormRequestStub extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email',
        ];
    }
}

class FormRequestWithoutRulesStub extends Request {}
