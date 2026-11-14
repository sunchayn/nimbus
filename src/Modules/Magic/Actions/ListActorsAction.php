<?php

namespace Sunchayn\Nimbus\Modules\Magic\Actions;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\ActorData;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\ListActorsData;
use Throwable;

/** @final */
class ListActorsAction
{
    public const LIMIT = 20;

    private const LABEL_COLUMNS = ['name', 'email', 'username'];

    private const SEARCH_COLUMNS = ['name', 'email'];

    public function __construct(private readonly Factory $factory) {}

    /**
     * Lists every configured guard.
     *
     * @return list<string>
     */
    public function guards(): array
    {
        return array_map(strval(...), array_keys((array) config('auth.guards', [])));
    }

    /**
     * Lists the guards whose user model can create personal access tokens.
     *
     * @return list<string>
     */
    public function tokenGuards(): array
    {
        return array_values(array_filter($this->guards(), $this->canCreateTokens(...)));
    }

    /**
     * Lists the users of the guard's provider. Only Eloquent providers are supported.
     *
     * @return array<int, ActorData>
     */
    public function execute(ListActorsData $data): array
    {
        $guard = $this->factory->guard($data->guard);

        $provider = method_exists($guard, 'getProvider') ? $guard->getProvider() : null;

        if (! $provider instanceof EloquentUserProvider) {
            return [];
        }

        $model = $provider->createModel();

        $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());

        $labelColumns = array_values(array_intersect(self::LABEL_COLUMNS, $columns));

        $builder = $model->newQuery()->select([$model->getKeyName(), ...$labelColumns]);

        if ($data->search !== '') {
            $this->applySearch($builder, $model, $columns, $data->search);
        }

        return $builder
            ->orderBy($model->getKeyName())
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Model $user): ActorData => new ActorData(
                id: (string) $user->getKey(),
                label: $this->labelOf($user, $labelColumns),
                model: $user::class,
            ))
            ->values()
            ->all();
    }

    /**
     * A typed ID matches the key. Other text matches the name or email columns that exist.
     *
     * @param  Builder<covariant Model>  $query
     * @param  list<string>  $columns
     */
    private function applySearch(Builder $query, Model $model, array $columns, string $search): void
    {
        $like = '%'.addcslashes($search, '%_\\').'%';

        $query->getQuery()->where(function (QueryBuilder $group) use ($model, $columns, $search, $like): void {
            // A numeric key column rejects non numeric text on some databases, so it is only compared with digits.
            if ($model->getKeyType() !== 'int' || ctype_digit($search)) {
                $group->orWhere($model->getKeyName(), $search);
            }

            foreach (array_intersect(self::SEARCH_COLUMNS, $columns) as $column) {
                $group->orWhere($column, 'like', $like);
            }
        });
    }

    private function canCreateTokens(string $name): bool
    {
        try {
            $guard = $this->factory->guard($name);
        } catch (Throwable) {
            return false;
        }

        $provider = method_exists($guard, 'getProvider') ? $guard->getProvider() : null;

        return $provider instanceof EloquentUserProvider && method_exists($provider->getModel(), 'createToken');
    }

    /**
     * @param  list<string>  $labelColumns
     */
    private function labelOf(Model $user, array $labelColumns): string
    {
        foreach ($labelColumns as $labelColumn) {
            $value = $user->getAttribute($labelColumn);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return (string) $user->getKey();
    }
}
