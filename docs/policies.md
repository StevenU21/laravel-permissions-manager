# Policy Integration & Security Patterns

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

## Full Implementation Example

```php
namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Deifhelt\LaravelPermissionsManager\Traits\HasPermissionCheck;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HasPermissionCheck; // Includes HandlesAuthorization automatically

    /**
     * VIEW ANY checks only permission.
     * No model involved -> No ownership check.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'read posts');
    }

    /**
     * UPDATE checks permission AND ownership.
     * Pass the $post model to trigger the user_id check.
     */
    public function update(User $user, Post $post): bool
    {
        return $this->checkPermission($user, 'update posts', $post);
    }
}
```

## Policy Registration (Laravel 11 & 12)

In Laravel 11 and 12, you need to manually register your policies in the `AppServiceProvider`:

```php
namespace App\Providers;

use App\Models\Post;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
    }
}
```

**Why this is necessary:**

Laravel's auto-discovery of policies works by convention (e.g., `App\Models\Post` → `App\Policies\PostPolicy`). However, if your policies are in a different namespace or you want explicit control, you must register them manually using `Gate::policy()`.

This ensures that when you call `$user->can('update', $post)`, Laravel knows which policy class to use.

## Using Policies in Controllers

To enforce authorization in your controllers, use the `AuthorizesRequests` trait and the `authorize()` method.

### Setup

Add the `AuthorizesRequests` trait to your controller:

```php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    use AuthorizesRequests;
}
```

### Authorization Without Model (Class-Level)

For actions that don't operate on a specific model instance (like listing all resources), pass the model class:

```php
public function index(Request $request)
{
    $this->authorize('viewAny', Product::class);

    $products = Product::all();
    return view('products.index', compact('products'));
}
```

**What happens internally:**

1. Laravel calls `ProductPolicy::viewAny($user)`
2. The policy's `before()` method runs first (admin bypass)
3. If not admin, `viewAny()` executes and calls `checkPermission($user, 'read products')`
4. If unauthorized, throws `UnauthorizedException` (403)

### Authorization With Model (Instance-Level)

For actions on a specific resource, pass the model instance:

```php
public function edit(Product $product)
{
    $this->authorize('update', $product);

    return view('products.edit', compact('product'));
}

public function destroy(Product $product)
{
    $this->authorize('destroy', $product);

    $product->delete();
    return redirect()->route('products.index');
}
```

**What happens internally:**

1. Laravel calls `ProductPolicy::update($user, $product)`
2. The policy's `before()` method runs first (admin bypass)
3. If not admin, `update()` executes and calls `checkPermission($user, 'update products', $product)`
4. Checks permission AND ownership (`$user->id === $product->user_id`)
5. If unauthorized or not owner, throws `UnauthorizedException` (403)

### Alternative: Authorization in Form Requests

Instead of authorizing in the controller, you can move authorization logic to Form Requests for cleaner controllers.

#### Approach 1: Controller Authorization (Traditional)

```php
public function store(ProductRequest $request)
{
    $this->authorize('create', Product::class);

    $product = Product::create($request->validated());
    return redirect()->route('products.index');
}
```

#### Approach 2: Form Request Authorization (Recommended)

Move the authorization to the Form Request's `authorize()` method:

```php
namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('post')) {
            return $this->user()->can('create', Product::class);
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return $this->user()->can('update', $this->route('product'));
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ];
    }
}
```

**Benefits:**

-   **Single Responsibility**: The Form Request handles both validation AND authorization
-   **Cleaner Controllers**: No need for `$this->authorize()` calls
-   **Automatic Handling**: Laravel automatically calls `authorize()` before validation

#### Approach 3: Simplified Form Request (Single Action)

For Form Requests that handle only one action (e.g., only POST):

```php
class CreateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->isMethod('post')
            ? $this->user()->can('create', Sale::class)
            : false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'total' => 'required|numeric|min:0',
        ];
    }
}
```

**When to use each approach:**

-   **Controller Authorization**: Simple cases, quick prototyping
-   **Form Request Authorization**: Production code, complex validation rules
-   **Simplified Form Request**: Single-action requests (create-only, update-only)

### Exception Handling

The `UnauthorizedException` is automatically caught by the exception handler configured in `bootstrap/app.php`:

```php
$exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
    return back()->with('error', 'You do not have permission to perform this action.');
});
```

This provides a consistent user experience across your entire application.
