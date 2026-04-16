<?php

namespace Apiato\Core\Abstracts\Actions;

use Apiato\Core\Exceptions\InvalidRequestParamsException;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class Action
{
    /**
     * @throws Throwable
     */
    public function transactionalRun(...$arguments)
    {
        return DB::transaction(function () use ($arguments) {
            return static::run(...$arguments);
        });
    }

    /**
     * @throws InvalidRequestParamsException
     */
    public function tryRun(mixed ...$arguments): mixed
    {
        try {
            return static::run(...$arguments);
        } catch (Throwable $exception) {
            throw new InvalidRequestParamsException($exception->getMessage());
        }
    }
}
