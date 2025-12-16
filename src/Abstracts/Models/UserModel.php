<?php

namespace Apiato\Core\Abstracts\Models;

use Apiato\Core\Http\Resources\HasResourceKey;
use Apiato\Core\Scopes\KeywordsSearchScopes;
use Apiato\Core\Traits\CanGetFillableStatically;
use Apiato\Core\Traits\CanGetTableNameStatically;
use Apiato\Core\Traits\FactoryLocatorTrait;
use Apiato\Core\Http\Resources\ResourceKeyAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class UserModel extends Authenticatable implements ResourceKeyAware
{
    use CanGetFillableStatically;
    use CanGetTableNameStatically;
    use FactoryLocatorTrait, HasFactory {
        FactoryLocatorTrait::newFactory insteadof HasFactory;
    }
    use HasResourceKey;
    use KeywordsSearchScopes;
}
