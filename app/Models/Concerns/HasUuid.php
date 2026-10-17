<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Gives a model a public-facing UUID without touching its primary key.
 *
 * The integer `id` stays exactly where it is and every foreign key keeps
 * pointing at it -- this is only about what shows up in a URL. Sequential ids
 * let anyone walk the catalogue (/admin/users/1, /2, /3 ...) and read the size
 * of the business off the last one; a uuid says nothing.
 *
 * Models that already have a human-readable key of their own (Course, which
 * routes on its slug) can use the trait for the column and simply override
 * getRouteKeyName().
 */
trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        // creating, not created: the value has to be in the INSERT, because the
        // column is NOT NULL and unique.
        static::creating(function ($model) {
            if (blank($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Route model binding resolves on the uuid, so {user} in a route means the
     * uuid and route('...', $user) emits it. Anything that passes $user->id by
     * hand will 404 -- pass the model itself.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
