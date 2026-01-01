# Basic Usage

You can access translated permissions anywhere in your application using the Facade or the Helper class.

## In PHP (Controllers/Services)

You can use the `Permissions` facade to get a list of all permissions with their translations, perfect for sending to a frontend (Vue, React, JSON APIs).

```php
use Deifhelt\LaravelPermissionsManager\Facades\Permissions;

public function index()
{
    // Returns a collection of permissions with 'name' and 'label'
    $permissions = Permissions::getPermissionsWithLabels();

    // Output structure (Default):
    // [
    //     ['name' => 'create users', 'label' => 'Crear Usuarios'],
    //     ['name' => 'update products', 'label' => 'Editar Productos'],
    // ]

    // Returns a flattened key-value pair array (Ideal for Laravel Form Selects)
    $options = Permissions::getPermissionsWithLabels(flatten: true);

    // Output structure (Flattened):
    // [
    //     'create users' => 'Crear Usuarios',
    //     'update products' => 'Editar Productos',
    // ]

    return response()->json($permissions);
}
```

### Translating Single Strings

If you just need to translate one permission string, use the helper method:

```php
use Deifhelt\LaravelPermissionsManager\PermissionTranslator;

// "Crear Usuarios"
$label = PermissionTranslator::translate('create users');
```

## In Blade Views

If you need to display a permission label in a view:

```blade
@foreach(Permissions::getPermissionsWithLabels() as $permission)
    <div class="form-check">
        <input type="checkbox" name="permissions[]" value="{{ $permission['name'] }}">
        <label>{{ $permission['label'] }}</label>
    </div>
@endforeach
```

Or using the standard translation helper (if you registered the keys manually, but `getPermissionsWithLabels` is recommended for dynamic lists):

```blade
{{-- This works via the native Laravel translator if keys exist --}}
{{ trans('permissions::permissions.actions.create') }}
```
