<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas;

final class SchemaConfig
{
    /**
     * Whether schemas should be serialized as permissive
     * ("additionalProperties": true) instead of strict.
     *
     * Driven by the "nimbus.schemas.additional_properties" config flag so the
     * behavior can be toggled per application (for example, to let the Nimbus
     * console accept request fields beyond the declared validation rules).
     * Falls back to strict schemas when no config repository is bound, which
     * keeps unit tests running outside a container deterministic.
     */
    public static function allowsAdditionalProperties(): bool
    {
        if (! function_exists('app') || ! app()->bound('config')) {
            return false;
        }

        return (bool) app('config')->get('nimbus.schemas.additional_properties', false);
    }
}
