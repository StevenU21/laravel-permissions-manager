# Defining Permissions

The core of this package is the `config/permissions.php` file. The `permissions` and `special_permissions` arrays are processed by the internal compiler to generate the final list of permissions.

## 1. Standard Resources (Auto-CRUD)

When you define a value without a string key (numeric index), the system treats it as a **Standard Resource**.

```php
'permissions' => [
    'products', // Numeric key 0 => value 'products'
    'invoices',
]
```

**What happens internally:**

1. The compiler detects a numeric key.
2. It assigns the **default CRUD actions**: `read`, `create`, `update`, `destroy`.
3. It generates the permission strings:
    - `read products`, `create products`, `update products`, `destroy products`
    - `read invoices`, `create invoices`, `update invoices`, `destroy invoices`

## 2. Custom Resources (Manual Definition)

When you define a key-value pair, the system disables auto-CRUD generation and expects you to explicitly list the allowed actions.

```php
'permissions' => [
    'settings' => ['update', 'view'], // String key 'settings' => array of actions
]
```

**Result:**

-   `update settings`
-   `view settings`

> **Use Case**: This is useful for singleton resources or features where "creating" or "deleting" doesn't make sense (e.g., Global Settings, server status).

## 3. Special Permissions (Extensions)

The `special_permissions` array is for actions that do not follow the standard patterns or are "extra" actions on top of a standard resource.

```php
'special_permissions' => [
    'users' => ['ban', 'impersonate'],
    'system' => ['maintenance-mode'],
]
```

**Merging Logic:**
If you have `users` in your main `permissions` array (Standard CRUD) AND `users` in `special_permissions`, the manager **merges** them.

Resulting permissions for `users`:

-   `read users`, `create users`, `update users`, `destroy users` (From Standard)
-   `ban users`, `impersonate users` (From Special)
