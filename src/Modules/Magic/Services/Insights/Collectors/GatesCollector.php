<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\GatesResult;

/**
 * @phpstan-import-type Gate from GatesResult
 */
class GatesCollector implements InsightCollectorContract
{
    /** @var list<Gate> */
    private array $gates = [];

    public function __construct(private readonly GateContract $gate) {}

    public function start(): void
    {
        $this->gate->after(function (mixed $user, string $ability, mixed $result, array $arguments): null {
            $this->record($user, $ability, $result, $arguments);

            return null;
        });
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function record(mixed $user, string $ability, mixed $result, array $arguments): void
    {
        $this->gates[] = [
            'ability' => $ability,
            'result' => is_bool($result) ? $result : null,
            'arguments' => array_values(array_map(fn (mixed $argument): string => is_object($argument) ? $argument::class : get_debug_type($argument), $arguments)),
            'user' => $user instanceof Authenticatable ? (string) $user->getAuthIdentifier() : null,
        ];
    }

    public function collect(): GatesResult
    {
        return new GatesResult($this->gates);
    }
}
