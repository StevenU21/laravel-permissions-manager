<?php

use Deifhelt\LaravelPermissionsManager\Tests\TestCase;
use Deifhelt\LaravelPermissionsManager\Traits\HasPermissionCheck;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Traits\HasRoles;

uses(TestCase::class);

// Mock classes
class TestUser extends User
{
    use HasRoles;

    protected $guarded = [];
    protected $table = 'users'; 
}

class TestModel extends Model
{
    public $user_id;

    public function __construct($userId = null)
    {
        $this->user_id = $userId;
    }
}

class TestPolicy
{
    use HasPermissionCheck;
}

// Tests
it('grants access to admin via before method', function () {
    $user = Mockery::mock(TestUser::class)->makePartial();
    $user->shouldReceive('hasRole')->with('admin')->andReturn(true);

    $policy = new TestPolicy();

    expect($policy->before($user, 'any_ability'))->toBeTrue();
});

it('does not grant implicit access to non-admin via before method', function () {
    $user = Mockery::mock(TestUser::class)->makePartial();
    $user->shouldReceive('hasRole')->with('admin')->andReturn(false);

    $policy = new TestPolicy();

    expect($policy->before($user, 'any_ability'))->toBeNull();
});

it('allows action when user has permission', function () {
    $user = Mockery::mock(TestUser::class)->makePartial();
    $user->shouldReceive('hasPermissionTo')->with('edit posts')->andReturn(true);

    $policy = new TestPolicy();

    expect($policy->checkPermission($user, 'edit posts'))->toBeTrue();
});

it('throws exception when user lacks permission', function () {
    $user = Mockery::mock(TestUser::class)->makePartial();
    $user->shouldReceive('hasPermissionTo')->with('edit posts')->andReturn(false);

    $policy = new TestPolicy();

    $policy->checkPermission($user, 'edit posts');
})->throws(UnauthorizedException::class);

it('allows owner access when model is provided', function () {
    $user = Mockery::mock(TestUser::class)->makePartial();
    $user->id = 1;

    $user->shouldReceive('hasPermissionTo')->with('edit posts')->andReturn(true);

    $policy = new TestPolicy();
    $model = new TestModel(1);

    expect($policy->checkPermission($user, 'edit posts', $model))->toBeTrue();
});

it('throws exception when user is not owner of model', function () {
    $user = Mockery::mock(TestUser::class)->makePartial();
    $user->id = 1;
    $user->shouldReceive('hasPermissionTo')->with('edit posts')->andReturn(true);

    $policy = new TestPolicy();
    $model = new TestModel(2); // Owned by 2

    $policy->checkPermission($user, 'edit posts', $model);
})->throws(UnauthorizedException::class);

it('admin bypasses ownership check via before method', function () {
    $user = Mockery::mock(TestUser::class)->makePartial();
    $user->id = 1;
    $user->shouldReceive('hasRole')->with('admin')->andReturn(true);

    $policy = new TestPolicy();
    $model = new TestModel(999); // Model owned by someone else

    expect($policy->before($user, 'any_ability'))->toBeTrue();
});
