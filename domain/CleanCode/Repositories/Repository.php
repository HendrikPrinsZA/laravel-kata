<?php

namespace Domain\CleanCode\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class Repository
{
    public function __construct(protected Model $model)
    {
        // ...
    }

    public function query(): Builder
    {
        return $this->model->query();
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): Model
    {
        return $this->model->find($id);
    }

    public function findMany(array $ids): Collection
    {
        return $this->query()->findMany($ids);
    }

    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function make(array $data): Model
    {
        return $this->model->make($data);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(int|string $id): int
    {
        return $this->model->destroy($id);
    }
}
