<?php

namespace Sunchayn\Nimbus\Http\Api\Relay;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Sunchayn\Nimbus\Modules\Relay\Actions\RequestRelayAction;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;

/**
 * Relays the Execution of HTTP requests with authorization and returns structured response data.
 *
 *  A relay endpoint is needed to access HTTP Only cookies,
 *  and deal with Laravel specific details for response/request life-cycle.
 */
class NimbusRelayController
{
    public function __invoke(
        NimbusRelayRequest $nimbusRelayRequest,
        RequestRelayAction $requestRelayAction,
    ): JsonResource|JsonResponse {
        try {
            $relayedRequestResponseData = $requestRelayAction
                ->execute(
                    RequestRelayData::fromRelayApiRequest($nimbusRelayRequest),
                );
        } catch (ConnectionException $connectionException) {
            // The trace holds server paths, so it is only shown while debugging outside production.
            if (config('app.debug') && ! app()->isProduction()) {
                throw $connectionException;
            }

            return response()->json(['message' => $connectionException->getMessage()], 500);
        }

        return RelayResponseResource::make($relayedRequestResponseData);
    }
}
