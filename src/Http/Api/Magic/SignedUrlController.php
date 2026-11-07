<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Magic;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Modules\Magic\Actions\SignRouteUrlAction;
use Sunchayn\Nimbus\Modules\Magic\Exceptions\UnsignableUrlException;

class SignedUrlController
{
    public function __construct(
        private readonly SignRouteUrlAction $signRouteUrlAction,
    ) {}

    public function __invoke(SignedUrlRequest $signedUrlRequest): JsonResponse
    {
        try {
            $url = $this->signRouteUrlAction->execute(
                method: (string) $signedUrlRequest->validated('method'),
                url: (string) $signedUrlRequest->validated('url'),
            );
        } catch (UnsignableUrlException $unsignableUrlException) {
            return response()->json(['message' => $unsignableUrlException->getMessage()], 422);
        }

        return response()->json(['url' => $url]);
    }
}
