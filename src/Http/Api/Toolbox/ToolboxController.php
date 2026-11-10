<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Toolbox;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\CreateSignedUrlAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\DecodeJwtAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\DecryptValueAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\EncryptValueAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\FindSanctumTokenAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\HashValueAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\ReadSessionAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\VerifySignedUrlAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\DataTransferObjects\CreateSignedUrlData;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;

/**
 * Serves the small Laravel tools of the toolbox page.
 * Each tool takes one form and returns one result.
 */
class ToolboxController
{
    public function decrypt(Request $request, DecryptValueAction $action): JsonResponse
    {
        $input = $request->validate(['value' => ['required', 'string'], 'cookie_name' => ['nullable', 'string']]);

        return $this->respond(fn (): array => $action->execute($input['value'], $input['cookie_name'] ?? null));
    }

    public function encrypt(Request $request, EncryptValueAction $action): JsonResponse
    {
        $input = $request->validate(['value' => ['required', 'string'], 'cookie_name' => ['nullable', 'string']]);

        return $this->respond(fn (): array => ['value' => $action->execute($input['value'], $input['cookie_name'] ?? null)]);
    }

    public function verifySignedUrl(Request $request, VerifySignedUrlAction $action): JsonResponse
    {
        $input = $request->validate(['url' => ['required', 'string']]);

        return $this->respond(fn (): array => $action->execute($input['url']));
    }

    public function createSignedUrl(Request $request, CreateSignedUrlAction $action): JsonResponse
    {
        $input = $request->validate([
            'route' => ['required', 'string'],
            'parameters' => ['nullable', 'array'],
            'expires_in_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->respond(fn (): array => ['url' => $action->execute(new CreateSignedUrlData(
            route: $input['route'],
            parameters: $input['parameters'] ?? [],
            expiresInMinutes: isset($input['expires_in_minutes']) ? (int) $input['expires_in_minutes'] : null,
        ))]);
    }

    public function hash(Request $request, HashValueAction $action): JsonResponse
    {
        $input = $request->validate(['value' => ['required', 'string'], 'hash' => ['nullable', 'string']]);

        return $this->respond(fn (): array => $action->execute($input['value'], $input['hash'] ?? null));
    }

    public function token(Request $request, DecodeJwtAction $jwtAction, FindSanctumTokenAction $sanctumAction): JsonResponse
    {
        $input = $request->validate(['type' => ['required', 'in:jwt,sanctum'], 'token' => ['required', 'string']]);

        return $this->respond(
            fn (): array => $input['type'] === 'jwt'
                ? $jwtAction->execute($input['token'])
                : $sanctumAction->execute($input['token']),
        );
    }

    public function session(Request $request, ReadSessionAction $action): JsonResponse
    {
        $input = $request->validate(['value' => ['required', 'string']]);

        return $this->respond(fn (): array => ['session' => $action->execute($input['value'])]);
    }

    /**
     * @param  Closure(): array<string, mixed>  $tool
     */
    private function respond(Closure $tool): JsonResponse
    {
        try {
            return response()->json($tool());
        } catch (InvalidToolInputException $invalidToolInputException) {
            return response()->json(['message' => $invalidToolInputException->getMessage()], 422);
        }
    }
}
