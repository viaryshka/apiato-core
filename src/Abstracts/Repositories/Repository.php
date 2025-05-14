<?php

namespace Apiato\Core\Abstracts\Repositories;

use Apiato\Core\Http\RequestRelation;
use Apiato\Core\Traits\HasAvailableSortsTrait;
use Apiato\Core\Traits\HasKeywordsSearchTrait;
use Apiato\Core\Traits\HasRequestCriteriaTrait;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Events\RepositoryEntityDeleted;
use Prettus\Repository\Events\RepositoryEntityDeleting;
use RuntimeException;

/**
 * @template TModel of Model
 */
class Repository extends BaseRepository
{
    use HasAvailableSortsTrait;
    use HasKeywordsSearchTrait;
    use HasRequestCriteriaTrait;

    protected int $maxPaginationLimit = 0;

    protected bool|null $allowDisablePagination = null;

    /** @var Closure[] */
    protected array $scopes = [];

    public function boot(): void
    {
        parent::boot();

        if ($this->shouldEagerLoadIncludes()) {
            $this->eagerLoadRequestedIncludes(app(RequestRelation::class));
        }
    }

    public function shouldEagerLoadIncludes(): bool
    {
        return true;
    }

    public function eagerLoadRequestedIncludes(RequestRelation $requestRelation): void
    {
        $this->scope(function (Builder|Model $model) use ($requestRelation): Builder|Model {
            if ($requestRelation->requestingIncludes()) {
                if ($model instanceof Model) {
                    return $model->with($requestRelation->getValidRelationsFor($model));
                }

                return $model->with($requestRelation->getValidRelationsFor($model->getModel()));
            }

            return $model;
        });
    }

    public function model(): string
    {
        $className = $this->getClassName(); // e.g. UserRepository
        $modelName = $this->getModelName($className); // e.g. User

        return $this->getModelNamespace($modelName);
    }

    public function getClassName(): string
    {
        $fullName = static::class;

        return substr($fullName, strrpos($fullName, '\\') + 1);
    }

    public function getModelName(string $className): string|array
    {
        return str_replace('Repository', '', $className);
    }

    public function getModelNamespace(array|string $modelName): string
    {
        return 'App\\Containers\\'.$this->getCurrentSection().'\\'.$this->getCurrentContainer().'\\Models\\'.$modelName;
    }

    public function getCurrentSection(): string
    {
        return explode('\\', static::class)[2];
    }

    public function getCurrentContainer(): string
    {
        return explode('\\', static::class)[3];
    }

    public function paginate($limit = null, $columns = ['*'], $method = 'paginate'): mixed
    {
        $limit = $this->setPaginationLimit($limit);

        if ($this->wantsToSkipPagination($limit) && $this->canSkipPagination()) {
            return $this->all($columns);
        }

        if ($this->exceedsMaxPaginationLimit($limit)) {
            $limit = $this->maxPaginationLimit;
        }

        return parent::paginate($limit, $columns, $method);
    }

    public function setPaginationLimit($limit): mixed
    {
        // the priority is for the function parameter, if not available then take
        // it from the request if available and if not keep it null.
        return $limit ?? request()?->input('limit');
    }

    public function wantsToSkipPagination(mixed $limit): bool
    {
        return '0' === $limit || 0 === $limit;
    }

    public function canSkipPagination(): mixed
    {
        // check local (per repository) rule
        if (! is_null($this->allowDisablePagination)) {
            return $this->allowDisablePagination;
        }

        return config('repository.pagination.skip');
    }

    public function exceedsMaxPaginationLimit(mixed $limit): bool
    {
        return $this->maxPaginationLimit > 0 && $limit > $this->maxPaginationLimit;
    }

    public function max(string $column)
    {
        return $this->model->max($column);
    }

    public function min(string $column)
    {
        return $this->model->min($column);
    }

    public function forceDelete($id)
    {
        $this->applyScope();

        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $model = $this->find($id);
        $originalModel = clone $model;

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        event(new RepositoryEntityDeleting($this, $model));

        $deleted = $model->forceDelete();

        event(new RepositoryEntityDeleted($this, $originalModel));

        return $deleted;
    }

    public function scope(Closure $scope): static
    {
        $this->scopes[] = $scope;

        return $this;
    }

    public function resetScope(): static
    {
        parent::resetScope();
        $this->resetScopes();

        return $this;
    }

    public function resetScopes(): static
    {
        $this->scopes = [];

        return $this;
    }

    protected function applyScope(): static
    {
        parent::applyScope();
        $this->applyScopes();

        return $this;
    }

    protected function applyScopes(): static
    {
        foreach ($this->scopes as $scope) {
            if (! is_callable($scope)) {
                throw new RuntimeException('Query scope is not callable');
            }
            $this->model = $scope($this->model);
        }

        return $this;
    }
}
