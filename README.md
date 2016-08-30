# Laravel Custom Relation

A custom relation for when stock relations aren't enough.

## Use this if...

* None of the stock Relations fit the bill. (BelongsToManyThrough, etc)

## Installation

The recommended way to install is with [composer](http://getcomposer.org/):

```shell
composer require johnnyfreeman/laravel-custom-relation
```

## Example

Let's say we have 3 models:

- `User`
- `Role`
- `Permission`

Let's also say `User` has a many-to-many relation with `Role`, and `Role` has a many-to-many relation with `Permission`. 

So their models might look something like this. (I kept them brief on purpose.)

```php
class User
{
    public function roles() {
        return $this->belongsToMany(Role::class);
    }
}
```
```php
class Role
{
    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function permissions() {
        return $this->belongsToMany(Permission::class);
    }
}
```
```php
class Permission
{
    public function roles() {
        return $this->belongsToMany(Role::class);
    }
}
```

**What if you wanted to get all the `Permission`s for a `User`, or all the `User`s with a particular `Permission`?** There no stock Relation in Laravel to descibe this. What we need is a `BelongsToManyThrough` but no such thing exists in stock Laravel.

## Solution

First, make sure your models are using the `HasCustomRelations` trait. Then, define custom relations like this.

```php
use LaravelCustomRelation/HasCustomRelations;

class User
{
    use HasCustomRelations;

    /**
     * Get the related permissions
     *
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
    public function permissions()
    {
        return $this->custom(
            Permission::class,

            // add constraints
            function ($relation) {
                $relation->getQuery()
                    // join the pivot table for permission and roles
                    ->join('permission_role', 'permission_role.permission_id', '=', 'permissions.id')
                    // join the pivot table for users and roles
                    ->join('role_user', 'role_user.role_id', '=', 'permission_role.role_id')
                    // for this user
                    ->where('role_user.user_id', $this->id);
            },

            // add eager constraints
            function ($relation, $models) {
                $relation->getQuery()->whereIn('role_user.user_id', $relation->getKeys($models));
            }
        );
    }
}
```

```php
use LaravelCustomRelation/HasCustomRelations;

class Permission
{
    use HasCustomRelations;

    /**
     * Get the related users
     *
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
    public function users()
    {
        return $this->custom(
            User::class,

            // constraints
            function ($relation) {
                $relation->getQuery()
                    // join the pivot table for users and roles
                    ->join('role_user', 'role_user.user_id', '=', 'users.id')
                    // join the pivot table for permission and roles
                    ->join('permission_role', 'permission_role.role_id', '=', 'role_user.role_id')
                    // for this permission
                    ->where('permission_role.permission_id', $this->id);
            },

            // eager constraints
            function ($relation, $models) {
                $relation->getQuery()->whereIn('permission_role.permission_id', $relation->getKeys($models));
            }
        );
    }
}
```

You could now do all the normal stuff for relations without having to query in-between relations first.