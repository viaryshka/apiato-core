<?php

namespace Apiato\Core\Criterias;

use Apiato\Core\Abstracts\Criterias\Criteria;
use Prettus\Repository\Contracts\RepositoryInterface as PrettusRepositoryInterface;

class WhereNotInCriteria extends Criteria
{
    public function __construct(
        private string $field,
        private array $notIn,
    ) {
    }

    public function apply($model, PrettusRepositoryInterface $repository)
    {
        return $model->whereNotIn($this->field, $this->notIn);
    }
}
