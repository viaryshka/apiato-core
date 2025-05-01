<?php

namespace Apiato\Core\Abstracts\Repositories;

use Apiato\Core\Traits\CanEagerLoadTrait;
use Apiato\Core\Traits\HasAvailableSortsTrait;
use Apiato\Core\Traits\HasKeywordsSearchTrait;
use Apiato\Core\Traits\HasRequestCriteriaTrait;
use Illuminate\Support\Facades\Request;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Events\RepositoryEntityDeleted;
use Prettus\Repository\Events\RepositoryEntityDeleting;

class Repository extends BaseRepository
{
    use CanEagerLoadTrait;
    use HasAvailableSortsTrait;
    use HasKeywordsSearchTrait;
    use HasRequestCriteriaTrait;

    protected int $maxPaginationLimit = 0;

    protected ?bool $allowDisablePagination = null;

    public function boot(): void
    {
        parent::boot();

        $this->eagerLoadRequestedRelations();
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
        return $limit ?? Request::get('limit');
    }

    public function wantsToSkipPagination(mixed $limit): bool
    {
        return $limit == '0';
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
}
