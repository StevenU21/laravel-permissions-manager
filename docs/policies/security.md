# Security Pattern: The Triad

The `HasPermissionCheck` trait is designed to implement critical security best practices: **Deny by Default**, **Admin Override**, and **Ownership Assurance**.

## The Security Triad

This trait implements three checks in a specific order. Understanding this flow is crucial for auditing your security.

### 1. The Admin Bypass (The `before` Gate)

Laravel Policies support a `before` method that runs **before** any other check.

```php
public function before($user, $ability) { ... }
```

**Why it's implemented this way:**

-   **Performance**: We don't want to query the database for 50 permissions if we verify the user is an Admin in the first step.
-   **Fail-Safe**: It guarantees that an Admin can literally do anything. Even if you forget to assign a specific permission to the admin role, this method overrides the missing assignment.
-   **Configurable**: The checked role is defined in `config('permissions.super_admin_role')`. It defaults to `'admin'` but can be changed to any role name or an array of roles.
-   **Logic Skip**: If `before()` returns `true`, Laravel **skips** the `checkPermission()` call entirely. This is why Admins bypass ownership checks—the code that enforces ownership is never even reached.

### 2. The Permission Check (The Gatekeeper)

If the user is NOT an admin, we verify they have the "Key" to enter the room.

```php
if (! $user->hasPermissionTo($permission)) {
    throw new UnauthorizedException(403);
}
```

**Why throw Exception vs Return False?**

-   **Explicit Failure**: We prefer throwing a specific `403` exception rather than just returning false. This allows the global Exception Handler (configured in `bootstrap/app.php`) to catch it and render a consistent "You do not have permission" response across your entire app.

### 3. The Ownership Check (The Data Protector)

Possessing the "Update Key" (Permission) shouldn't authorize you to update "Everyone's House" (Data).

```php
if ($model && $user->id !== $model->user_id) {
    throw new UnauthorizedException(403);
}
```

**Mechanics:**

-   This check only triggers if you pass a `$model` instance.
-   It rigidly compares the authenticated `$user->id` with the model's `$model->user_id`.

> **Constraint**: This requires your database table to have a `user_id` foreign key. If your app uses `creator_id` or `author_id`, you should override this trait or map the accessor in your model.
