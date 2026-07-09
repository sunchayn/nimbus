<?php

namespace Sunchayn\Nimbus\Modules\Collections\DataTransferObjects;

/**
 * A single shareable environment collection.
 *
 * Mirrors the frontend `EnvironmentCollection` shape ({ id, name, variables,
 * requests }) plus a backend-derived `order` used to keep collections stable
 * across reloads. Variables and requests are kept as pass-through arrays so the
 * store never has to track the frontend's exact shapes: `requests` entries are
 * shareable-request payloads (method, endpoint, headers, body, ...).
 */
readonly class CollectionData
{
    /**
     * @param  array<int, array<string, mixed>>  $variables
     * @param  array<int, array<string, mixed>>  $requests
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $variables,
        public array $requests = [],
        public int $order = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, int $order = 0): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            variables: self::normalizedVariables($data['variables'] ?? []),
            requests: array_values($data['requests'] ?? []),
            order: (int) ($data['order'] ?? $order),
        );
    }

    /**
     * Coerce variable keys and values to strings. The frontend dereferences
     * `key.trim()` and interpolates `value`, but Laravel's
     * ConvertEmptyStringsToNull middleware turns empty strings into null on
     * sync — so a persisted blank variable would crash the UI on reload.
     *
     * @param  array<int|string, mixed>  $variables
     * @return array<int, array<string, mixed>>
     */
    private static function normalizedVariables(array $variables): array
    {
        $normalized = [];

        foreach ($variables as $variable) {
            if (! is_array($variable)) {
                continue;
            }

            $variable['key'] = (string) ($variable['key'] ?? '');
            $variable['value'] = (string) ($variable['value'] ?? '');

            $normalized[] = $variable;
        }

        return $normalized;
    }

    /**
     * The on-disk representation. `order` is persisted so collection ordering
     * survives a reload even though files have no inherent order.
     *
     * @return array{id: string, name: string, order: int, variables: array<int, array<string, mixed>>, requests: array<int, array<string, mixed>>}
     */
    public function toStorageArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'order' => $this->order,
            'variables' => $this->variables,
            'requests' => $this->requests,
        ];
    }

    /**
     * The shape sent to the frontend. `order` is a backend concern and is omitted.
     *
     * @return array{id: string, name: string, variables: array<int, array<string, mixed>>, requests: array<int, array<string, mixed>>}
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'variables' => $this->variables,
            'requests' => $this->requests,
        ];
    }
}
