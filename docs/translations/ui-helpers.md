# UI Helper Methods

The package assists in formatting permissions for your frontend, whether you need grouped checkboxes (for forms) or flat lists (for tables).

## Grouped Permissions (Checkboxes)

Ideal for "Create/Edit Role" forms. It groups permissions by their resource (e.g. "Users", "Products") and marks selected ones as checked.

### Controller Implementation

```php
use Deifhelt\LaravelPermissionsManager\Facades\Permissions;
use App\Models\Role;

public function create()
{
    // 1. Get all available permissions
    $permissions = Permission::all();

    // 2. Build groups
    // Automatically groups by resource, converts labels, and checks selected IDs from old input
    $permissionGroups = Permissions::buildPermissionGroups(
        $permissions,
        null, // Translations (fetched automatically if null)
        old('permissions', []) // Selected IDs used to mark checkboxes as 'checked'
    );

    return view('admin.roles.create', compact('permissionGroups'));
}

public function edit(Role $role)
{
    $permissions = Permission::all();

    // Get existing role permissions
    $rolePermissions = $role->permissions->pluck('id')->toArray();

    // Use old input if validation failed, otherwise use role permissions
    $selectedIds = old('permissions', $rolePermissions);

    $permissionGroups = Permissions::buildPermissionGroups(
        $permissions,
        null,
        $selectedIds
    );

    return view('admin.roles.edit', compact('role', 'permissionGroups'));
}
```

### Blade View Implementation

The `permissionGroups` array has this structure:

```php
[
    [
        'key' => 'users',
        'title' => 'Users', // Capitalized title
        'permissions' => [
            [
                'id' => 1,
                'name' => 'create users',
                'label' => 'Create Users', // Translated
                'checked' => true // or false
            ],
            // ...
        ]
    ],
    // ...
]
```

Use it in your view to generate the form:

```blade
<div class="row">
    @foreach($permissionGroups as $group)
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header font-weight-bold">
                    {{ $group['title'] }}
                </div>
                <div class="card-body">
                    @foreach($group['permissions'] as $permission)
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permission['id'] }}"
                                   id="perm-{{ $permission['id'] }}"
                                   {{ $permission['checked'] ? 'checked' : '' }}>

                            <label class="form-check-label" for="perm-{{ $permission['id'] }}">
                                {{ $permission['label'] }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
```

---

## Permission Items (List/Table)

Ideal for displaying a read-only list of permissions for a Role, for example in a `show` view.

### Controller Implementation

```php
public function show(Role $role)
{
    // Returns a flat list with translated 'label' and 'labelSearch' keys
    // Perfect for passing to a Vue component or a simple table
    $permissionItems = Permissions::buildPermissionItems($role->permissions);

    return view('admin.roles.show', compact('role', 'permissionItems'));
}
```

### Blade Implementation

```blade
<table class="table table-striped">
    <thead>
        <tr>
            <th>Permission Name</th>
            <th>Label (Translated)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($permissionItems as $item)
            <tr>
                <td><code>{{ $item['name'] }}</code></td>
                <td>{{ $item['label'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
```

Or pass it to a Vue/React component:

```blade
<permissions-list :items="{{ json_encode($permissionItems) }}"></permissions-list>
```
