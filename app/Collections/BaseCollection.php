<?php

namespace App\Collections;

use App\Exceptions\BaseCollectionException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BaseCollection extends Collection
{
    protected const UPSERT_MAX = 500;

    protected const FIELD_UNIQUE = 'collection_unique_attributes';

    public function upsert(): bool
    {
        if ($this->isEmpty()) {
            return true;
        }

        /** @var \Illuminate\Database\Eloquent\Model $first */
        $first = $this->first();
        $model = $first::class;
        $uniqueAttributes = $first?->{self::FIELD_UNIQUE};
        if (is_string($uniqueAttributes)) {
            $uniqueAttributes = [$uniqueAttributes];
        }
        $uniqueAttributes = collect($uniqueAttributes)->unique()->values()->toArray();

        // Required to be safe
        if (empty($uniqueAttributes)) {
            throw new BaseCollectionException(sprintf(
                'Missing required property: `%s::%s`',
                $model,
                self::FIELD_UNIQUE,
            ));
        }

        // Determine which fields to update
        $updateFields = collect(array_keys($first->getAttributes()))
            ->toArray();

        $this->chunk(self::UPSERT_MAX)->each(fn (Collection $rows) => $model::upsert(
            $rows->map(fn (Model $row) => $row->getAttributes(array_merge(
                $updateFields
            )))->toArray(),
            $uniqueAttributes,
            $updateFields
        )
        );

        return true;
    }

    public function delete(): bool
    {
        if ($this->isEmpty()) {
            return true;
        }

        $first = $this->first();

        /** @var Model $model */
        $model = $first::class;

        $ids = $this->pluck('id');
        $model::query()->whereIn('id', $ids)->delete();

        return $model::all()->isEmpty();
    }
}
