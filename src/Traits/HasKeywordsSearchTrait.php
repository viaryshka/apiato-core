<?php

namespace Apiato\Core\Traits;

use Apiato\Core\Criterias\KeywordsSearchCriteria;
use Illuminate\Database\Eloquent\Builder;
use Prettus\Repository\Exceptions\RepositoryException;

trait HasKeywordsSearchTrait
{
    protected array $keywordsFields = [];

    protected array $fulltextFields = [];

    /**
     * @throws RepositoryException
     */
    public function addKeywordsSearchCriteria(): static
    {
        $this->pushCriteria(app(KeywordsSearchCriteria::class));

        return $this;
    }

    public function getKeywordsFields(): array
    {
        return $this->keywordsFields;
    }

    public function getFulltextFields(): array
    {
        $result = [];
        foreach ($this->fulltextFields as $key => $value) {
            if (is_int($key)) {
                $result[$value] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function intersectSearchFields(array $fields = []): array
    {
        $availableKeys = $this->getKeywordsFields();
        $validFields = [];
        foreach ($fields as $key => $value) {
            if (in_array($value, $availableKeys)) {
                $validFields[$key] = $value;
            }
        }
        if (! count($validFields)) {
            return $availableKeys;
        }

        return $validFields;
    }

    public function setKeywordsSearchBuilder(Builder $query, $field, $keywords, $or): bool
    {
        return false;
    }
}
