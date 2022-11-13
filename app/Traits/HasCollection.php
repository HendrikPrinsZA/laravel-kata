<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;

trait HasCollection
{
    /**
     * Create a new Eloquent Collection instance.
     */
    public function newCollection(array $models = []): Collection
    {
        $className = class_basename($this);
        $domainNamespace = str_replace(sprintf('\Models\%s', $className), '', static::class);
        $collectionClass = sprintf("%s\Collections\%sCollection", $domainNamespace, $className);

        return new $collectionClass($models);
    }
}
