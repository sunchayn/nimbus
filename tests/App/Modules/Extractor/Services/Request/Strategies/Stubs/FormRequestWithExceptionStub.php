<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs;

use Illuminate\Http\Request;

class FormRequestWithExceptionStub extends Request
{
    public function rules(): array
    {
        throw new \RuntimeException('Cannot access request context');

        return [
            'name' => 'required',
        ];
    }
}
