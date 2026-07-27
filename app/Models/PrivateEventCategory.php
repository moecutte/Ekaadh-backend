<?php

namespace App\Models;

/**
 * @deprecated Use Category::activeOptionsForPrivate() / private children of the Private root.
 * Kept as a thin compatibility shim for any leftover references.
 */
class PrivateEventCategory extends Category
{
    protected $table = 'categories';

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('private_children', function ($query) {
            $privateId = Category::privateRoot()?->id;
            if ($privateId) {
                $query->where('parent_id', $privateId);
            } else {
                $query->whereRaw('0 = 1');
            }
        });
    }

    /**
     * @return list<array{id:int,name:string,slug:string,requires_couple_names:bool}>
     */
    public static function activeOptions(): array
    {
        return Category::activeOptionsForPrivate();
    }
}
