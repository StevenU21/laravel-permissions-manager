# Policy Setup

Integrate the `HasPermissionCheck` trait to your policies to get instant security features.

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

In Laravel 11 and 12, manual registration might be required depending on your auto-discovery configuration. We recommend being explicit in `AppServiceProvider`:

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

This ensures that when you call `$user->can('update', $post)`, Laravel knows exactly which policy class to use.
