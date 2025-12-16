<?php

namespace Apiato\Core\Abstracts\Transformers;

use Apiato\Core\Http\Resources\Collection;
use Apiato\Core\Http\Resources\Item;
use Illuminate\Database\Eloquent\Model;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract as FractalTransformer;

abstract class Transformer extends FractalTransformer
{
    protected array $availableCounts = [];

    protected function addCounts(Model $model, array $data): array
    {
        if (empty($this->availableCounts)) {
            return $data;
        }

        $attributes = $model->getAttributes();

        foreach ($this->availableCounts as $relation) {
            $key = "{$relation}_count";

            if (array_key_exists($key, $attributes)) {
                $data[$key] = $model->{$key};
            }
        }

        return $data;
    }

    public static function empty(): callable
    {
        return static fn(): array => [];
    }

    public function nullableItem(
        mixed $data,
        callable|self $transformer,
        string|null $resourceKey = null
    ): Primitive|Item {
        if (is_null($data)) {
            return $this->primitive(null);
        }

        return $this->item($data, $transformer, $resourceKey);
    }

    public function item($data, $transformer, string|null $resourceKey = null): Item
    {
        return new Item($data, $transformer, $resourceKey);
    }

    public function collection($data, $transformer, string|null $resourceKey = null): Collection
    {
        return new Collection($data, $transformer, $resourceKey);
    }
}
