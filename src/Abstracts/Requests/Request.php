<?php

namespace Apiato\Core\Abstracts\Requests;

use Apiato\Core\Abstracts\Enums\BasePermissionEnum;
use Apiato\Core\Abstracts\Models\UserModel as User;
use Illuminate\Foundation\Http\FormRequest as LaravelRequest;
use Illuminate\Support\Facades\Config;

abstract class Request extends LaravelRequest
{
    /**
     * Roles and/or Permissions that has access to this request.
     *
     * @example ['permissions' => 'create-users', 'roles' => 'admin|manager']
     * @example ['permissions' => null, 'roles' => 'admin']
     * @example ['permissions' => ['create-users'], 'roles' => null]
     *
     * @var array<string, string|array<string>|null>
     */
    protected array $access = [
        'permissions' => null,
        'roles' => null,
    ];

    /**
     * check if a user has permission to perform an action.
     * User can set multiple permissions (separated with "|") and if the user has
     * any of the permissions, he will be authorized to proceed with this action.
     */
    public function hasAccess(User|null $user = null): bool
    {
        // if not in parameters, take from the request object {$this}
        $user = $user ?: $this->user();

        if ($user) {
            $autoAccessRoles = Config::get('apiato.requests.allow-roles-to-access-all-routes');
            // there are some roles defined that will automatically grant access
            if (! empty($autoAccessRoles)) {
                $hasAutoAccessByRole = $user->hasAnyRole($autoAccessRoles);
                if ($hasAutoAccessByRole) {
                    return true;
                }
            }
        }

        // check if the user has any role / permission to access the route
        $hasAccess = array_merge(
            $this->hasAnyPermissionAccess($user),
            $this->hasAnyRoleAccess($user),
        );

        // allow access if user has access to any of the defined roles or permissions.
        return empty($hasAccess) || in_array(true, $hasAccess, true);
    }

    protected function hasAnyPermissionAccess($user): array
    {
        if (! array_key_exists('permissions', $this->access) || ! $this->access['permissions']) {
            return [];
        }
        if (is_array($this->access['permissions'])) {
            $permissions = $this->access['permissions'];
        } elseif (is_string($this->access['permissions'])) {
            $permissions = explode('|', $this->access['permissions']);
        } elseif ($this->access['permissions'] instanceof BasePermissionEnum) {
            $permissions = [$this->access['permissions']->value];
        } else {
            return [];
        }
        return array_map(static function ($permission) use ($user) {
            return $user->hasPermissionTo($permission);
        }, $permissions);
    }

    protected function hasAnyRoleAccess($user): array
    {
        if (! array_key_exists('roles', $this->access) || ! $this->access['roles']) {
            return [];
        }
        if (is_array($this->access['roles'])) {
            $roles = $this->access['roles'];
        } elseif (is_string($this->access['roles'])) {
            $roles = explode('|', $this->access['roles']);
        } elseif ($this->access['roles'] instanceof BasePermissionEnum) {
            $roles = [$this->access['roles']->value];
        } else {
            return [];
        }

        return array_map(static function ($role) use ($user) {
            return $user->hasRole($role);
        }, $roles);
    }

    /**
     * Used from the `authorize` function if the Request class.
     * To call functions and compare their bool responses to determine
     * if the user can proceed with the request or not.
     */
    protected function check(array $functions): bool
    {
        $orIndicator = '|';
        $returns = [];

        // iterate all functions in the array
        foreach ($functions as $function) {
            // in case the value doesn't contain a separator (single function per key)
            if (! strpos($function, $orIndicator)) {
                // simply call the single function and store the response.
                $returns[] = $this->{$function}();
            } else {
                // in case the value contains a separator (multiple functions per key)
                $orReturns = [];

                // iterate over each function in the key
                foreach (explode($orIndicator, $function) as $orFunction) {
                    // dynamically call each function
                    $orReturns[] = $this->{$orFunction}();
                }

                // if in_array returned `true` means at least one function returned `true` thus return `true` to allow access.
                // if in_array returned `false` means no function returned `true` thus return `false` to prevent access.
                // return single boolean for all the functions found inside the same key.
                $returns[] = in_array(true, $orReturns, true);
            }
        }

        // if in_array returned `true` means a function returned `false` thus return `false` to prevent access.
        // if in_array returned `false` means all functions returned `true` thus return `true` to allow access.
        // return the final boolean
        return ! in_array(false, $returns, true);
    }

    protected function checkPolicy(): bool
    {
        return true;
    }

    public function authorize(): bool
    {
        return $this->check([
            'hasAccess',
            'checkPolicy',
        ]);
    }
}
