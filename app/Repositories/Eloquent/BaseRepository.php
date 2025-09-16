<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    use Filterable;

    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * List records with filters, relations, sorting, and pagination.
     */
    public function list(array $request = []): Builder
    {
        $query = $this->model->newQuery();
        $query = $this->applyFilters($query, $request['filters'] ?? []);
        $query = $this->applySearch($query, $request['search'] ?? null);
        $query = $this->applySorting($query, $request['sort'] ?? []);
        return $query;
    }

    public function show($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->show($id);
        $record->update($data);
        return $record;
    }

    public function delete(int|string $id): bool
    {
//        dd($this->findById($id)->getRelations());
        $record = $this->show($id);
//        dd(get_class($record), $record->guard_name);
        return $record->delete();
    }

    public function getPaginatedList(array $data = [], int $perPage = 15): mixed
    {
        $query = $this->list($data);
        return $query->paginate($perPage);
    }
}
