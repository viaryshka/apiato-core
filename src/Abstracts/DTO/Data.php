<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\DTO;

use Spatie\LaravelData\Data as LaravelData;
use Spatie\LaravelData\Optional;

abstract class Data extends LaravelData
{
    /**
     * Convert the object to an array with specified keys
     */
    public function toArrayOnly(array $keys): array
    {
        $array = [];
        foreach ($keys as $key) {
            if ($this->contains($key)) {
                $array[$key] = $this->{$key};
            }
        }

        return $array;
    }

    /**
     * Check if the key exists in the object and not null
     */
    public function isset(string $key): bool
    {
        return property_exists($this, $key) && ! $this->{$key} instanceof Optional && ! is_null($this->{$key});
    }

    /**
     * Check if the key exists in the object (with null)
     */
    public function contains(string $key): bool
    {
        return property_exists($this, $key) && ! $this->{$key} instanceof Optional;
    }

    public function get(string $key, $default = null): mixed
    {
        if ($this->contains($key)) {
            return $this->{$key} ?? $default;
        }

        return $default;
    }

    public function getArray(string $key, $default = []): array
    {
        if ($this->isset($key) && is_array($this->{$key}) && ! empty($this->{$key})) {
            return $this->{$key};
        }

        return $default;
    }
}
