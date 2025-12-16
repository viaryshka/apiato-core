<?php

namespace Apiato\Core\Abstracts\Transformers;

use Apiato\Http\Resources\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract as FractalTransformer;

abstract class Transformer extends FractalTransformer
{
    protected array $availableCounts = [];

    public function transform(mixed $data): array
    {
        $counts = [];
        foreach ($this->availableCounts as $count) {
            if (! is_null($data->$count)) {
                $counts[$count] = $data->$count;
            }
        }

        if (method_exists($this, 'mapFields')) {
            return [...$this->mapFields($data), ...$counts];
        }

        return $data;
    }

    public function nullableItem($data, $transformer, $resourceKey = null): Primitive|Item
    {
        if (is_null($data)) {
            return $this->primitive(null);
        }

        return $this->item($data, $transformer, $resourceKey);
    }

    public static function empty(): callable
    {
        return static fn(): array => [];
    }

    public function item($data, $transformer, string|null $resourceKey = null): \Apiato\Http\Resources\Item
    {
        return new Item($data, $transformer, $resourceKey);
    }

    public function collection($data, $transformer, string|null $resourceKey = null): Collection
    {
        return new Collection($data, $transformer, $resourceKey);
    }
}
