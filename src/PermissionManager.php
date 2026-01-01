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
     * @param bool $flatten If true, returns ['permission_name' => 'Label']. If false, returns [['name' => '...', 'label' => '...']].
     * @return array
     */
    public function getPermissionsWithLabels(bool $flatten = false): array
    {
        $collection = collect($this->all())
            ->map(fn($permission) => [
                'name' => $permission,
                'label' => PermissionTranslator::translate($permission),
            ]);

        if ($flatten) {
            return $collection->mapWithKeys(fn($item) => [$item['name'] => $item['label']])->all();
        }

        return $collection->all();
    }

    /**
     * Translate a single permission string.
     * Proxy for PermissionTranslator.
     */
    public function translate(string $permission): string
    {
        return PermissionTranslator::translate($permission);
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

    /**
     * Build a grouped structure of permissions for UI (e.g. grouped checkboxes).
     *
     * @param iterable $permissions
     * @param array|null $translatedPermissions
     * @param array $selectedPermissionIds
     * @return array
     */
    public function buildPermissionGroups(iterable $permissions, ?array $translatedPermissions = null, array $selectedPermissionIds = []): array
    {
        $permissionsCollection = collect($permissions);

        if (is_null($translatedPermissions)) {
            $translatedPermissions = $this->getPermissionsWithLabels(flatten: true);
        }

        // Ensure IDs are integers and valid
        $selectedIds = collect($selectedPermissionIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values()
            ->all();

        $grouped = $permissionsCollection
            ->groupBy(fn($perm) => $this->getGroupName($perm->name ?? $perm)) // Handle objects or strings
            ->sortKeys();

        return $grouped
            ->map(function ($permsGroup, $group) use ($translatedPermissions, $selectedIds) {
                // Formatting group title: users -> Users, inventory_movements -> Inventory Movements
                $title = Str::of($group)->replace(['_', '-'], ' ')->title()->toString();

                $items = $permsGroup
                    ->sortBy(fn($p) => $p->name ?? $p)
                    ->map(function ($permission) use ($translatedPermissions, $selectedIds) {
                        $name = $permission->name ?? $permission;
                        $id = $permission->id ?? null;
                        $label = $translatedPermissions[$name] ?? $name;

                        return [
                            'id' => $id,
                            'name' => (string) $name,
                            'label' => (string) $label,
                            'labelSearch' => mb_strtolower((string) $label, 'UTF-8'),
                            'checked' => !is_null($id) && in_array((int) $id, $selectedIds, true),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'key' => $group,
                    'title' => $title,
                    'permissions' => $items,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Build a flat list of permission items for UI (e.g. data tables).
     *
     * @param iterable $permissions
     * @param array|null $translatedPermissions
     * @return array
     */
    public function buildPermissionItems(iterable $permissions, ?array $translatedPermissions = null): array
    {
        $permissionsCollection = collect($permissions);

        if (is_null($translatedPermissions)) {
            $translatedPermissions = $this->getPermissionsWithLabels(flatten: true);
        }

        return $permissionsCollection
            ->sortBy(fn($p) => $p->name ?? $p)
            ->map(function ($permission) use ($translatedPermissions) {
                $name = $permission->name ?? $permission;
                $id = $permission->id ?? null;
                $label = $translatedPermissions[$name] ?? $name;

                return [
                    'id' => $id,
                    'name' => (string) $name,
                    'label' => (string) $label,
                    'labelSearch' => mb_strtolower((string) $label, 'UTF-8'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Extract the group name (Resource) from a permission name.
     */
    protected function getGroupName(string $permissionName): string
    {
        // Dot notation: users.create -> users
        if (Str::contains($permissionName, '.')) {
            return Str::before($permissionName, '.');
        }

        // Space notation: create users -> users
        if (Str::contains($permissionName, ' ')) {
            return Str::after($permissionName, ' ');
        }

        return 'other';
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
