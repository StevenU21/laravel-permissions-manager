# Configuration Deep Dive

The configuration file permissions.php is the single source of truth for your application's authorization logic. Understanding how it works will allow you to leverage the full power of the internal compiler.

## 1. Super Admin Configuration (Bypass)

The `super_admin_role` configuration key defines roles that bypass **all** permission checks. These users are effectively "root" users.

```php
// config/permissions.php

// Single role
'super_admin_role' => 'admin',

// Multiple roles
'super_admin_role' => ['admin', 'root', 'super-admin'],
```

> **Note**: These roles do not need to be defined in the `roles` array below.
>
> **Important behaviors:**
>
> 1.  **Auto-Creation**: The system **automatically creates** these roles in the database when you run `permissions:sync`.
> 2.  **Global Bypass**: The package registers a `Gate::before` callback. This means:
>     -   `@can('any_permission')` in Blade **WORKS**.
>     -   `$user->can('any_permission')` **WORKS**.
>     -   Database permissions are **NOT required** for these users. The system approves them instantly in memory.

---

## 2. Resource Definitions & CRUD Generation

The `permissions` array is processed by the `PermissionManager::buildPermissions()` method. It intelligently distinguishes between standard resources and custom definitions.

### Standard Resources (Numeric Keys)

When you define a value without a string key (numeric index), the system treats it as a **Standard Resource**.

```php
'permissions' => [
    'products', // Numeric key 0 => value 'products'
]
```

**What happens internally:**

1. The compiler detects a numeric key.
2. It assigns the **default CRUD actions**: `read`, `create`, `update`, `destroy`.
3. It generates the permission strings:
    - `read products`
    - `create products`
    - `update products`
    - `destroy products`

### Custom Resources (String Keys)

When you define a key-value pair, the system disables auto-CRUD generation and expects you to explicitly list the allowed actions.

```php
'permissions' => [
    'settings' => ['update', 'view'], // String key 'settings' => array of actions
]
```

**What happens internally:**

1. The compiler detects a string key (`settings`).
2. It assumes you are overriding the defaults.
3. It iterates over the array values.
4. It generates:
    - `update settings`
    - `view settings`

> **Note**: This is useful for singleton resources or features where "creating" or "deleting" doesn't make sense (e.g., a Dashboard or Global Settings).

---

## 3. Special Permissions

The `special_permissions` array is for actions that do not follow the `verb noun` pattern strictly or are "extra" actions on top of a standard resource.

```php
'special_permissions' => [
    'users' => ['ban', 'impersonate'],
]
```

**Mergin Logic:**
If you have `users` in your main `permissions` array (Standard CRUD) AND `users` in `special_permissions`, the manager **merges** them.

Resulting permissions for `users`:

-   `read users`
-   `create users`
-   `update users`
-   `destroy users`
-   `ban users`
-   `impersonate users`

---

## 4. Role Composition Strategy

Role definition is where the `PermissionManager` shines. It supports distinct strategies for defining what a role can do.

### Strategy A: The Wildcard (`*`)

While the `super_admin_role` config is the preferred way to handle global admins, you can still assign a wildcard `*` to any role in the `roles` array. This assigns **every known permission** to that role in the database.

```php
'roles' => [
    'developer' => '*',
]
```

**Difference vs Super Admin:**

-   **Super Admin (`super_admin_role`)**: Bypasses checks in code (faster, safer fallback).
-   **Wildcard (`*`)**: Actually inserts database records for every permission.

### Strategy B: Resource-Based Assignment (Smart Mapping)

You can assign an entire resource's permissions to a role simply by naming the resource.

```php
'roles' => [
    'manager' => [
        'products', // Grant ALL permissions related to 'products'
    ]
]
```

**Internal Logic:**

1. The compiler checks if `products` is a known resource in your definitions.
2. If yes, it looks up all permissions associated with `products` (CRUD + Special).
3. It assigns all effectively: `read products`, `create products`, `update products`, `destroy products`...

### Strategy C: Granular Action Assignment

You can be specific about which actions on a resource are allowed.

```php
'roles' => [
    'editor' => [
        'posts' => ['read', 'update'], // Only read and update, NO delete
    ]
]
```

**Auto-Prefixing:**
Notice you didn't write `read posts`. You wrote `['read', 'update']` under the `'posts'` key. The compiler automatically prefixes these actions with the resource name:

-   `read posts`
-   `update posts`

### Strategy D: Explicit Permission Strings

For complete control or one-off permissions, you can just list the raw permission string.

```php
'roles' => [
    'analyst' => [
        'export-csv reports', // Explicit full string
    ]
]
```

---

## Summary of Processing Power

When you run `permissions:sync`, the `PermissionManager`:

1. **Compiles** sources into a flat list of unique permissions.
2. **Normalizes** definitions.
3. **Resolves** Role strategies (Wildcard -> Resource -> Granular -> Explicit).
4. **Passes** the optimized data structure to the Syncer for database insertion.
