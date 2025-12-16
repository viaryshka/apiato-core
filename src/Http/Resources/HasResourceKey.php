<?php

namespace Apiato\Core\Http\Resources;

trait HasResourceKey
{
    public function getResourceKey(): string
    {
        return class_basename($this);
    }
}
