<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs;

use Illuminate\Http\Request;

/**
 * Stub controller used by strategy unit tests to exercise real reflection.
 */
class StrategyControllerStub
{
    public function withFormRequest(FormRequestStub $request): void {}

    public function withExceptionRequest(FormRequestWithExceptionStub $request): void {}

    public function withBaseRequest(Request $request): void {}

    public function withSpatieData(SpatieDataObjectStub $data): void {}

    public function withUntypedParam($param): void {}

    public function withUnionParam(FormRequestStub|Request $request): void {}

    public function withRulesMissingParam(FormRequestWithoutRulesStub $request): void {}

    public function withInlineValidation(Request $request): void
    {
        $request->validate(['title' => 'required|string']);
    }

    public function withNoParams(): void {}
}
