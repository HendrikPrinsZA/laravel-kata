<?php

namespace Larawell\LaravelPlus\Collections;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Larawell\LaravelPlus\Exceptions\SmartCollectionException;

class SmartCollection extends Collection
{
    protected const UNIQUE_FIELDS = [];

    protected const UPSERT_MAX = 500;

    protected function upsertNew(SupportCollection $records): void
    {
        if ($records->isEmpty()) {
            return;
        }

        /** @var Model $firstRecord */
        $firstRecord = $records->first();
        $updateFields = $firstRecord->getFillable();

        if (empty(static::UNIQUE_FIELDS)) {
            throw new SmartCollectionException(sprintf(
                'Required cost static::UNIQUE_FIELDS should be set on %s',
                static::class
            ));
        }

        $records->chunk(self::UPSERT_MAX)->each(
            fn (SupportCollection $chunkedRecords) => $firstRecord::class::upsert(
                $chunkedRecords->map(fn (Model $record) => $record->getAttributes())->toArray(),
                static::UNIQUE_FIELDS,
                $updateFields
            )
        );
    }

    protected function upsertExisting(SupportCollection $records): void
    {
        if ($records->isEmpty()) {
            return;
        }

        /** @var Model $firstRecord */
        $firstRecord = $records->first();
        $updateFields = $firstRecord->getFillable();

        $records->chunk(self::UPSERT_MAX)->each(
            fn (SupportCollection $chunkedRecords) => $firstRecord::class::upsert(
                $chunkedRecords->map(fn (Model $record) => $record->getAttributes())->toArray(),
                ['id'],
                $updateFields
            )
        );

        $this->fresh();
    }

    public function upsert(): bool
    {
        if ($this->isEmpty()) {
            return true;
        }

        // Upsert records by ids
        $records = $this->reduce(function (array $splitRecords, Model $record) {
            $key = is_null($record->id) ? 'new' : 'existing';
            $splitRecords[$key]->push($record);

            return $splitRecords;
        }, [
            'new' => collect(),
            'existing' => collect(),
        ]);

        $this->upsertNew($records['new']);
        $this->upsertExisting($records['existing']);

        return true;
    }

    public function delete(): bool
    {
        if ($this->isEmpty()) {
            return true;
        }

        /** @var Model $firstRecord */
        $firstRecord = $this->first();

        // Fail if we find any records without an id set
        if ($this->first(fn (Model $model) => is_null($model->id))) {
            throw new SmartCollectionException(
                'Found a record without an id, you need to refresh the collection first'
            );
        }

        return $firstRecord::class::whereIn('id', $this->pluck('id'))->delete();
    }
}
