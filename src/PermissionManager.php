<?php

namespace Deifhelt\LaravelPermissionsManager;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PermissionManager
{
    private Collection $permissions;

    private Collection $specialPermissions;

    private Collection $rolesDefinition;

    private const DEFAULT_ACTIONS = ['read', 'create', 'update', 'destroy'];

    public function __construct(array $permissions = [], array $specialPermissions = [])
    {
        $this->permissions = collect($permissions);
        $this->specialPermissions = collect($specialPermissions);
        $this->rolesDefinition = collect();
    }

    public static function make(array $permissions = [], array $special = []): self
    {
        return new self($permissions, $special);
    }

    /**
     * Define the roles and their associated permissions.
     */
    public function withRoles(array $rolesDefinition): self
    {
        $this->rolesDefinition = collect($rolesDefinition);

        return $this;
    }

    /**
     * Generates the flat list of all calculated permissions.
     */
    public function all(): array
    {
        return $this->buildPermissions()
            ->flatMap(fn (Collection $actions) => $actions)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Maps roles with their respective permission names.
     */
    public function getRolesWithPermissions(): array
    {
        $allPermissions = collect($this->all());
        $catalog = $allPermissions->flip();

        return $this->rolesDefinition
            ->map(fn ($definition) => $this->compileRolePermissions($definition, $catalog, $allPermissions)->all())
            ->toArray();
    }

    protected function buildPermissions(): Collection
    {
        return $this->permissions->mapWithKeys(function ($value, $key) {
            $resource = is_numeric($key) ? (string) $value : (string) $key;
            $actions = is_numeric($key) ? collect(self::DEFAULT_ACTIONS) : collect(Arr::wrap($value));

            $basePermissions = $actions->map(fn ($action) => sprintf('%s %s', $action, $resource));
            $specials = collect($this->specialPermissions->get($resource, []))->filter();

            return [
                $resource => $basePermissions->merge($specials)->unique()->values(),
            ];
        });
    }

    protected function compileRolePermissions($definition, Collection $catalog, Collection $allPermissions): Collection
    {
        // Handle wildcard (*) for super-admin or similar roles that have all permissions
        if ($definition === '*' || (is_array($definition) && reset($definition) === '*')) {
            return $allPermissions;
        }

        $permissions = collect();
        $normalized = collect(Arr::wrap($definition));

        foreach ($normalized as $resource => $items) {
            // Implicit resource handling or direct permission names
            if (is_int($resource)) {
                if ($catalog->has($items)) {
                    $permissions->push($items);
                }

                continue;
            }

            // Split items by space, comma or pipe if it's a string
            $actions = is_array($items) ? collect($items) : collect(preg_split('/[\s,|]+/', trim((string) $items)));

            $actions->map(fn ($item) => Str::contains($item, ' ') ? $item : sprintf('%s %s', $item, $resource))
                ->filter(fn ($p) => $catalog->has($p))
                ->each(fn ($p) => $permissions->push($p));
        }

        return $permissions->unique()->values();
    }
}
