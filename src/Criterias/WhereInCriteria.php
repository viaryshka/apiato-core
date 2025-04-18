<?php

namespace Apiato\Core\Criterias;

use Apiato\Core\Abstracts\Criterias\Criteria;
use Prettus\Repository\Contracts\RepositoryInterface as PrettusRepositoryInterface;


class WhereInCriteria extends Criteria
{
    public function __construct(
        private string $field,
        private array $in,
    ) {}

    public function apply($model, PrettusRepositoryInterface $repository)
    {
        return $model->whereIn($this->field, $this->in);
    }
}
