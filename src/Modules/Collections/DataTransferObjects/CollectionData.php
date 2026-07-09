<?php

namespace Sunchayn\Nimbus\Modules\Collections\DataTransferObjects;

/**
 * A single shareable environment collection.
 *
 * Mirrors the frontend `EnvironmentCollection` shape ({ id, name, variables })
 * plus a backend-derived `order` used to keep collections stable across reloads.
 * Variables are kept as a pass-through array so the store never has to track the
 * frontend's variable shape.
 */
readonly class CollectionData
{
    /**
     * @param  array<int, array<string, mixed>>  $variables
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $variables,
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
            variables: array_values($data['variables'] ?? []),
            order: (int) ($data['order'] ?? $order),
        );
    }

    /**
     * The on-disk representation. `order` is persisted so collection ordering
     * survives a reload even though files have no inherent order.
     *
     * @return array{id: string, name: string, order: int, variables: array<int, array<string, mixed>>}
     */
    public function toStorageArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'order' => $this->order,
            'variables' => $this->variables,
        ];
    }

    /**
     * The shape sent to the frontend. `order` is a backend concern and is omitted.
     *
     * @return array{id: string, name: string, variables: array<int, array<string, mixed>>}
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'variables' => $this->variables,
        ];
    }
}
