# Using Policies

Once your policies are set up, you can use them in Controllers or Form Requests.

## In Controllers

Use the `authorize()` method from the `AuthorizesRequests` trait (standard in Laravel Controllers).

### Class-Level (No Model)

For actions like listing or creating resources:

```php
public function index(Request $request)
{
    // Checks "viewAny" ability
    $this->authorize('viewAny', Product::class);

    $products = Product::all();
    return view('products.index', compact('products'));
}
```

### Instance-Level (With Model)

For actions on a specific resource (Edit, Update, Delete):

```php
public function edit(Product $product)
{
    // Checks "update" ability AND ownership (if configured)
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

## In Form Requests (Recommended)

Move authorization logic to Form Requests for cleaner controllers.

### Standard Approach

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

### Simplified Action-Specific Requests

If you create separate requests for Store and Update (e.g. `StoreProductRequest`), it's even cleaner:

```php
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }
// ...
}
```

## Exception Handling

The `UnauthorizedException` thrown by the package is automatically caught by Laravel. You can customize the response in `bootstrap/app.php` if needed:

```php
$exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
    if ($request->expectsJson()) {
        return response()->json(['message' => 'You do not have permission.'], 403);
    }
    return back()->with('error', 'You do not have permission to perform this action.');
});
```
