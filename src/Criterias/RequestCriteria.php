<?php

namespace Apiato\Core\Criterias;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Prettus\Repository\Contracts\RepositoryInterface;
use Prettus\Repository\Criteria\RequestCriteria as ParentRequestCriteria;

class RequestCriteria extends ParentRequestCriteria
{
    /**
     * Apply criteria in query repository
     *
     * @param  Builder|Model  $model
     *
     * @throws Exception
     */
    public function apply($model, RepositoryInterface $repository): mixed
    {
        $fieldsSearchable = $repository->getFieldsSearchable();
        $search = $this->request->get(config('repository.criteria.params.search', 'search'), null);
        $searchFields = $this->request->get(config('repository.criteria.params.searchFields', 'searchFields'), null);
        $orderBy = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'), null);
        $sortedBy = $this->request->get(config('repository.criteria.params.sortedBy', 'sortedBy'), 'asc');
        $with = $this->request->get(config('repository.criteria.params.with', 'with'), null);
        $withCount = $this->request->get(config('repository.criteria.params.withCount', 'withCount'), null);
        $searchJoin = $this->request->get(config('repository.criteria.params.searchJoin', 'searchJoin'), 'and');
        $sortedBy = ! empty($sortedBy) ? $sortedBy : 'asc';
        if ($search && is_array($fieldsSearchable) && count($fieldsSearchable)) {

            $searchFields = is_array($searchFields) || is_null($searchFields) ? $searchFields : explode(';',
                $searchFields);
            $isFirstField = true;
            $searchData = $this->parserSearchData($search);
            $fields = $this->parserFieldsSearch($fieldsSearchable, $searchFields, array_keys($searchData));
            $search = $this->parserSearchValue($search);
            $modelForceAndWhere = strtolower($searchJoin) === 'and';
            $model = $model->where(function ($query) use (
                $fields,
                $search,
                $searchData,
                $isFirstField,
                $modelForceAndWhere
            ) {
                /** @var Builder $query */
                foreach ($fields as $field => $condition) {

                    if (is_numeric($field)) {
                        $field = $condition;
                        $condition = '=';
                    }

                    $value = null;

                    $condition = trim(strtolower($condition));

                    if (isset($searchData[$field])) {
                        $value = ($condition == 'like' || $condition == 'ilike') ? "%{$searchData[$field]}%" : $searchData[$field];
                    } else {
                        if (! is_null($search) && ! in_array($condition, ['in', 'between'])) {
                            $value = ($condition == 'like' || $condition == 'ilike') ? "%{$search}%" : $search;
                        }
                    }

                    $relation = null;
                    if (stripos($field, '.')) {
                        $explode = explode('.', $field);
                        $field = array_pop($explode);
                        $relation = implode('.', $explode);
                    }
                    if ($condition === 'in') {
                        $value = explode(',', $value);
                        if (trim($value[0]) === '' || $field == $value[0]) {
                            $value = null;
                        }
                    }
                    if ($condition === 'between') {
                        $value = explode(',', $value);
                        if (count($value) < 2) {
                            $value = null;
                        }
                    }
                    if ($condition === 'in_null') {
                        $value = '';
                    }
                    if ($condition === 'in_not_null') {
                        $value = '';
                    }
                    $modelTableName = $query->getModel()->getTable();
                    $isScope = Str::startsWith($condition, 'scope-');
                    if ($isFirstField || $modelForceAndWhere) {
                        if (! is_null($value)) {
                            if (! is_null($relation)) {
                                $query->whereHas($relation,
                                    function ($query) use ($field, $condition, $value, $isScope) {
                                        if ($condition === 'in') {
                                            $query->whereIn($field, $value);
                                        } elseif ($condition === 'between') {
                                            $query->whereBetween($field, $value);
                                        } elseif ($condition === 'is_null') {
                                            $query->whereNull($field);
                                        } elseif ($condition === 'is_not_null') {
                                            $query->whereNotNull($field);
                                        } elseif ($isScope) {
                                            $scopeName = explode('-', $condition);
                                            if (count($scopeName) == 2) {
                                                $query->{$scopeName[1]}($value);
                                            }
                                        } else {
                                            $query->where($field, $condition, $value);
                                        }
                                    });
                            } else {
                                if ($condition === 'in') {
                                    $query->whereIn($modelTableName.'.'.$field, $value);
                                } elseif ($condition === 'between') {
                                    $query->whereBetween($modelTableName.'.'.$field, $value);
                                } elseif ($condition === 'is_null') {
                                    $query->whereNull($modelTableName.'.'.$field);
                                } elseif ($condition === 'is_not_null') {
                                    $query->whereNotNull($modelTableName.'.'.$field);
                                } elseif ($isScope) {
                                    $scopeName = explode('-', $condition);
                                    if (count($scopeName) == 2) {
                                        $query->{$scopeName[1]}($value);
                                    }
                                } else {
                                    $query->where($modelTableName.'.'.$field, $condition, $value);
                                }
                            }
                            $isFirstField = false;
                        }
                    } else {
                        if (! is_null($value)) {
                            if (! is_null($relation)) {
                                $query->orWhereHas($relation,
                                    function ($query) use ($field, $condition, $value, $isScope) {
                                        if ($condition === 'in') {
                                            $query->whereIn($field, $value);
                                        } elseif ($condition === 'between') {
                                            $query->whereBetween($field, $value);
                                        } elseif ($condition === 'is_null') {
                                            $query->whereNull($field);
                                        } elseif ($condition === 'is_not_null') {
                                            $query->whereNotNull($field);
                                        } elseif ($isScope) {
                                            $scopeName = explode('-', $condition);
                                            if (count($scopeName) == 2) {
                                                $query->{$scopeName[1]}($value);
                                            }
                                        } else {
                                            $query->where($field, $condition, $value);
                                        }
                                    });
                            } else {
                                if ($condition === 'in') {
                                    $query->orWhereIn($modelTableName.'.'.$field, $value);
                                } elseif ($condition === 'between') {
                                    $query->whereBetween($modelTableName.'.'.$field, $value);
                                } elseif ($condition === 'is_null') {
                                    $query->whereNull($modelTableName.'.'.$field, 'or');
                                } elseif ($condition === 'is_not_null') {
                                    $query->whereNull($modelTableName.'.'.$field, 'or', true);
                                } elseif ($isScope) {
                                    $scopeName = explode('-', $condition);
                                    if (count($scopeName) == 2) {
                                        $query->orWhere->{$scopeName[1]}($value);
                                    }
                                } else {
                                    $query->orWhere($modelTableName.'.'.$field, $condition, $value);
                                }
                            }
                        }
                    }
                }
            });
        }

        if (isset($orderBy) && ! empty($orderBy)) {
            $orderBySplit = explode(';', $orderBy);
            if (count($orderBySplit) > 1) {
                $sortedBySplit = explode(';', $sortedBy);
                foreach ($orderBySplit as $orderBySplitItemKey => $orderBySplitItem) {
                    $sortedBy = isset($sortedBySplit[$orderBySplitItemKey]) ? $sortedBySplit[$orderBySplitItemKey] : $sortedBySplit[0];
                    $isIdValueOrder = preg_match('/idValue\|(.+)/', $orderBySplitItem);
                    if ($isIdValueOrder) {
                        $model = $this->orderByIdValue($model, $orderBySplitItem, $sortedBy);
                    } else {
                        $model = $this->parserFieldsOrderBy($model, $orderBySplitItem, $sortedBy);
                    }
                }
            } else {
                $isIdValueOrder = preg_match('/idValue\|(.+)/', $orderBySplit[0]);
                if ($isIdValueOrder) {
                    $model = $this->orderByIdValue($model, $orderBySplit[0], $sortedBy);
                } else {
                    $model = $this->parserFieldsOrderBy($model, $orderBySplit[0], $sortedBy);
                }
            }
        }

        if ($with) {
            $with = explode(';', $with);
            $model = $model->with($with);
        }

        if ($withCount) {
            $withCount = explode(';', $withCount);
            $model = $model->withCount($withCount);
        }

        return $model;
    }

    protected function orderByIdValue($model, $orderBy, $sortedBy)
    {
        $split = explode('|', $orderBy);
        if (count($split) > 1 && $split[0] === 'idValue') {
            $table = $model->getModel()->getTable();
            $id = (int) $split[1];
            $model = $model->orderByRaw("CASE WHEN $table.id = {$id} THEN 0 ELSE 1 END $sortedBy");
        }

        return $model;
    }

    protected function parserFieldsOrderBy($model, $orderBy, $sortedBy)
    {
        // Check if this is a scope-based ordering
        $isScope = Str::startsWith($orderBy, 'scope-');
        if ($isScope) {
            $scopeName = explode('-', $orderBy, 2);
            if (count($scopeName) == 2) {
                $model = $model->{$scopeName[1]}($sortedBy);
            }
            return $model;
        }

        $split = explode('|', $orderBy);
        if (count($split) == 2) {
            /*
             * ex.
             * products|description -> join products on current_table.product_id = products.id order by description
             *
             * products:custom_id|products.description -> join products on current_table.custom_id = products.id order
             * by products.description (in case both tables have same column name)
             */
            $table = $model->getModel()->getTable();
            $sortTable = $split[0];
            $sortColumn = $split[1];

            $split = explode(':', $sortTable);
            $localKey = '.id';
            if (count($split) > 1) {
                $sortTable = $split[0];

                $commaExp = explode(',', $split[1]);
                $keyName = $table.'.'.$split[1];
                if (count($commaExp) > 1) {
                    $keyName = $table.'.'.$commaExp[0];
                    $localKey = '.'.$commaExp[1];
                }
            } else {
                /*
                 * If you do not define which column to use as a joining column on current table, it will
                 * use a singular of a join table appended with _id
                 *
                 * ex.
                 * products -> product_id
                 */
                $prefix = Str::singular($sortTable);
                $keyName = $table.'.'.$prefix.'_id';
            }

            $model = $model
                ->leftJoin($sortTable, $keyName, '=', $sortTable.$localKey)
                ->orderBy($sortColumn, $sortedBy)
                ->addSelect($table.'.*');
        } elseif (count($split) == 3) {
            $table = $model->getModel()->getTable();
            $middleTable = $split[0];
            $sortTable = $split[1];
            $sortColumn = $split[2];

            $split = explode(':', $middleTable);
            $middleLocalKey = '.id';
            if (count($split) > 1) {
                $middleTable = $split[0];

                $commaExp = explode(',', $split[1]);
                $middleKeyName = $table.'.'.$split[1];
                if (count($commaExp) > 1) {
                    $middleKeyName = $table.'.'.$commaExp[0];
                    $middleLocalKey = '.'.$commaExp[1];
                }
            } else {
                $prefix = Str::singular($middleTable);
                $middleKeyName = $table.'.'.$prefix.'_id';
            }
            $aliasesMiddleTable = $middleTable.'_m';
            $aliasesSortedTable = $sortTable.'_s';

            $split = explode(':', $sortTable);
            $localKey = '.id';
            if (count($split) > 1) {
                $sortTable = $split[0];

                $commaExp = explode(',', $split[1]);
                $keyName = $aliasesMiddleTable.'.'.$split[1];
                if (count($commaExp) > 1) {
                    $keyName = $aliasesMiddleTable.'.'.$commaExp[0];
                    $localKey = '.'.$commaExp[1];
                }
            } else {
                $prefix = Str::singular($sortTable);
                $keyName = $aliasesMiddleTable.'.'.$prefix.'_id';
            }

            $model = $model
                ->leftJoin("$middleTable AS $aliasesMiddleTable", $middleKeyName, '=',
                    $aliasesMiddleTable.$middleLocalKey)
                ->leftJoin("$sortTable AS $aliasesSortedTable", $keyName, '=', $aliasesSortedTable.$localKey)
                ->orderBy("$aliasesSortedTable.$sortColumn", $sortedBy)
                ->addSelect($table.'.*');
        } else {
            $model = $model->orderBy($orderBy, $sortedBy);
        }

        return $model;
    }

    protected function parserSearchData($search): array
    {
        $searchData = [];

        if (stripos($search, ':')) {
            $fields = explode(';', $search);
            foreach ($fields as $row) {
                try {
                    [$field, $value] = explode(':', $row, 2);
                    $searchData[$field] = $value;
                } catch (\Exception) {
                }
            }
        }

        return $searchData;
    }

    /**
     * @throws Exception
     */
    protected function parserFieldsSearch(
        array $fields = [],
        ?array $searchFields = null,
        ?array $dataKeys = null
    ): array {
        if (! is_null($searchFields) && count($searchFields)) {
            $acceptedConditions = config('repository.criteria.acceptedConditions', [
                '=',
                'like',
            ]);
            $originalFields = $fields;
            $fields = [];

            foreach ($searchFields as $index => $field) {

                $field_parts = explode(':', $field);
                $temporaryIndex = array_search($field_parts[0], $originalFields);

                if (count($field_parts) == 2) {
                    if (in_array($field_parts[1], $acceptedConditions)) {
                        unset($originalFields[$temporaryIndex]);
                        $field = $field_parts[0];
                        $condition = $field_parts[1];
                        if (! Str::startsWith($originalFields[$field], 'scope-')) {
                            $originalFields[$field] = $condition;
                        }
                        $searchFields[$index] = $field;
                    }
                }
            }

            if (! is_null($dataKeys) && count($dataKeys)) {
                $searchFields = array_unique(array_merge($dataKeys, $searchFields));
            }

            foreach ($originalFields as $field => $condition) {
                if (is_numeric($field)) {
                    $field = $condition;
                    $condition = '=';
                }
                if (in_array($field, $searchFields)) {
                    $fields[$field] = $condition;
                }
            }

            if (count($fields) == 0) {
                throw new \Exception(trans('repository::criteria.fields_not_accepted',
                    ['field' => implode(',', $searchFields)]));
            }

        }

        return $fields;
    }

}
