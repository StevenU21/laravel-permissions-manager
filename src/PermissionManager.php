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
            ->flatMap(fn(Collection $actions) => $actions)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get all permissions with their translated labels.
     * 
     * @return array<int, array{name: string, label: string}>
     */
    public function getPermissionsWithLabels(): array
    {
        return collect($this->all())
            ->map(fn($permission) => [
                'name' => $permission,
                'label' => PermissionTranslator::translate($permission),
            ])
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
            ->map(fn($definition) => $this->compileRolePermissions($definition, $catalog, $allPermissions, $this->buildPermissions())->all())
            ->toArray();
    }

    protected function buildPermissions(): Collection
    {
        $base = $this->permissions->mapWithKeys(function ($value, $key) {
            $resource = is_numeric($key) ? (string) $value : (string) $key;
            $actions = is_numeric($key) ? collect(self::DEFAULT_ACTIONS) : collect(Arr::wrap($value));

            return [$resource => $actions->map(fn($action) => sprintf('%s %s', $action, $resource))];
        });

        $special = $this->specialPermissions->mapWithKeys(function ($value, $key) {
            $actions = collect($value);
            return [$key => $actions->map(fn($action) => Str::contains($action, ' ') ? $action : sprintf('%s %s', $action, $key))];
        });

        return $base->keys()->merge($special->keys())->unique()
            ->mapWithKeys(function ($resource) use ($base, $special) {
                $b = $base->get($resource, collect());
                $s = $special->get($resource, collect());

                return [
                    $resource => $b->merge($s)->unique()->values(),
                ];
            });
    }

    protected function compileRolePermissions($definition, Collection $catalog, Collection $allPermissions, Collection $structuredPermissions): Collection
    {
        if ($definition === '*' || (is_array($definition) && reset($definition) === '*')) {
            return $allPermissions;
        }

        $permissions = collect();
        $normalized = collect(Arr::wrap($definition));

        foreach ($normalized as $resource => $items) {
            if (is_int($resource)) {
                // Check if $items (the value) is a resource name in our structured permissions
                if ($structuredPermissions->has($items)) {
                    $structuredPermissions->get($items)->each(fn($p) => $permissions->push($p));
                    continue;
                }

                if ($catalog->has($items)) {
                    $permissions->push($items);
                }

                continue;
            }

            $actions = is_array($items) ? collect($items) : collect(preg_split('/[\s,|]+/', trim((string) $items)));

            $actions->map(fn($item) => Str::contains($item, ' ') ? $item : sprintf('%s %s', $item, $resource))
                ->filter(fn($p) => $catalog->has($p))
                ->each(fn($p) => $permissions->push($p));
        }

        return $permissions->unique()->values();
    }
}
