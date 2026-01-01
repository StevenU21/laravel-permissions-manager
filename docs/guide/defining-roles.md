# Defining Roles

Role definition in `config/permissions.php` allows for flexible strategies to define what a role can do.

## 1. Super Admin (Global Bypass)

The `super_admin_role` configuration key defines roles that bypass **all** permission checks. These users are effectively "root" users.

```php
'super_admin_role' => ['admin', 'root'],
```

**Important behaviors:**

1.  **Auto-Creation**: The system **automatically creates** these roles in the database.
2.  **Global Bypass**: It registers a `Gate::before` callback. This means checks like `@can('anything')` return true instantly without database lookups.

## 2. Role Composition Strategies

For regular roles (like Managers, Editors), you can mix and match strategies.

### Strategy A: The Wildcard (`*`)

You can assign a wildcard `*` to any role in the `roles` array. This assigns **every known permission** to that role in the database.

```php
'roles' => [
    'developer' => '*',
]
```

_Difference vs Super Admin: Wildcard roles actually have database records for every permission. Super Admins bypass checks in memory._

### Strategy B: Resource-Based Assignment (Smart Mapping)

You can assign an entire resource's permissions to a role simply by naming the resource.

```php
'roles' => [
    'manager' => [
        'products', // Grant ALL permissions related to 'products' (CRUD + Special)
    ]
]
```

**Result:** The 'manager' role gets `read products`, `create products`, `update products`, `destroy products`, and any special permissions for products.

### Strategy C: Granular Action Assignment

You can be specific about which actions on a resource are allowed.

```php
'roles' => [
    'editor' => [
        'posts' => ['read', 'update'], // Only read and update, NO delete
    ]
]
```

**Result:** `read posts`, `update posts`.

### Strategy D: Explicit Permission Strings

For complete control or one-off permissions, you can just list the raw permission string.

```php
'roles' => [
    'analyst' => [
        'export-csv reports', // Explicit full string
    ]
]
```
