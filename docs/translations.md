# Permission Translations

The package includes a powerful translation engine that automatically converts technical permission names (e.g., `create users`) into human-readable labels (e.g., "Create Users" or "Crear Usuarios").

## How it Works

The translation system uses a **Composite Strategy**:

1.  **Exact Match**: Checks if the full permission string exists in the translations (e.g., special permissions like `assign permissions`).
2.  **Decomposition**: If no exact match is found, it splits the permission into **Action** + **Resource**.
    -   `create users` -> Action: `create` + Resource: `users`
    -   `read inventory_movements` -> Action: `read` + Resource: `inventory_movements`
3.  **Fallback**: If a resource isn't explicitly defined, it attempts to translate the resource name token by token.

This means you **don't** need to translate every single combination. Just translate your Actions and your Resources, and the system handles the rest.

## Configuration

### Publishing Translations

To customize the translations, publish the language files to your application's `lang` directory:

```bash
php artisan vendor:publish --tag=permissions-translations
```

This will create:

-   `lang/vendor/permissions/en/permissions.php`
-   `lang/vendor/permissions/es/permissions.php` (if available)

### Defining Translations

Open the published file (e.g., `lang/vendor/permissions/es/permissions.php`) and define your terms.

```php
return [
    // 1. Special Overrides (Highest Priority)
    'special' => [
        'assign permissions' => 'Asignar permisos',
        'mark all as read' => 'Marcar todo como leído',
    ],

    // 2. Actions (Verbs)
    'actions' => [
        'create' => 'Crear',
        'read' => 'Ver',
        'update' => 'Editar',
        'destroy' => 'Eliminar',
        'export' => 'Exportar',
    ],

    // 3. Resources (Nouns)
    'resources' => [
        'users' => 'Usuarios',
        'products' => 'Productos',
        'inventory_movements' => 'Movimientos de inventario',
    ],
];
```

With just the configuration above, the system can automatically translate:

-   `create products` -> "Crear Productos"
-   `export inventory_movements` -> "Exportar Movimientos de inventario"
-   `destroy users` -> "Eliminar Usuarios"

## Usage

### In PHP (Controllers/Services)

You can use the `Permissions` facade to get a list of all permissions with their translations, perfect for sending to a frontend (Vue, React, etc.).

```php
use Deifhelt\LaravelPermissionsManager\Facades\Permissions;

public function index()
{
    // Returns a collection of permissions with 'name' and 'label'
    $permissions = Permissions::getPermissionsWithLabels();

    // Output structure:
    // [
    //     ['name' => 'create users', 'label' => 'Crear Usuarios'],
    //     ['name' => 'update products', 'label' => 'Editar Productos'],
    // ]

    return response()->json($permissions);
}
```

Or translate a specific string using the helper class:

```php
use Deifhelt\LaravelPermissionsManager\PermissionTranslator;

$label = PermissionTranslator::translate('create users'); // "Crear Usuarios"
```

### In Blade Views

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

## Localization & Multi-language Support

The translation system **natively integrates with Laravel's Localization**. It automatically respects your application's locale configuration.

### How it determines the language?

1.  **Default Config**: It uses the `locale` defined in your application's `config/app.php`.
2.  **Runtime Support**: If you change the locale dynamically (e.g., using `App::setLocale('fr')` in a Middleware), the permission labels will **automatically** be returned in that language.
3.  **Fallback**: If a translation is missing in the current language, it falls back to your configured `fallback_locale`.

You **do not need** any extra configuration in this package to enable multi-language support. Just ensure you have the corresponding translation files in `lang/vendor/permissions/{locale}/`.

## Adding New Languages

1.  Create a new folder in `lang/vendor/permissions/` (e.g., `fr` for French).
2.  Copy the `permissions.php` file from another language.
3.  Translate the actions and resources.
