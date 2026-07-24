<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs;

use Illuminate\Http\Request;

class FormRequestWithDifferentRulesStub extends Request
{
    public function rules(): array
    {
        return [
            'title' => 'required',
            'content' => 'nullable|string',
        ];
    }
}
