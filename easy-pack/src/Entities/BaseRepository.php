<?php

namespace EasyPack\Entities;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseRepository
{
    protected Model $model;
    protected Builder $query;
    protected array $with = [];
    protected array $scopes = [];

    public function __construct()
    {
        $this->model = app($this->getModelClass());
        $this->resetQuery();
    }

    /**
     * Get the model class name.
     */
    abstract protected function getModelClass(): string;

    /**
     * Reset the query builder.
     */
    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
    }

    /**
     * Get a fresh query builder.
     */
    public function fresh(): static
    {
        $this->resetQuery();
        return $this;
    }

    /**
     * Get all records.
     */
    public function all(): Collection
    {
        return $this->applyRelations()->get();
    }

    /**
     * Find a record by ID.
     */
    public function find(int|string $id): ?Model
    {
        return $this->applyRelations()->find($id);
    }

    /**
     * Find a record by ID or fail.
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->applyRelations()->findOrFail($id);
    }

    /**
     * Find records by a field value.
     */
    public function findBy(string $field, mixed $value): Collection
    {
        return $this->applyRelations()->where($field, $value)->get();
    }

    /**
     * Find first record by a field value.
     */
    public function findFirstBy(string $field, mixed $value): ?Model
    {
        return $this->applyRelations()->where($field, $value)->first();
    }

    /**
     * Get first record or fail.
     */
    public function firstOrFail(): Model
    {
        return $this->applyRelations()->firstOrFail();
    }

    /**
     * Create a new record.
     */
    public function create(array $attributes): Model
    {
        return $this->model->create($attributes);
    }

    /**
     * Update a record by ID.
     */
    public function update(int|string $id, array $attributes): Model
    {
        $model = $this->findOrFail($id);
        $model->update($attributes);
        return $model->fresh();
    }

    /**
     * Delete a record by ID.
     */
    public function delete(int|string $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    /**
     * Paginate records.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->applyRelations()->paginate($perPage, $columns);
    }

    /**
     * Get paginated results with search.
     */
    public function paginateWithSearch(
        ?string $search = null,
        int $perPage = 15,
        ?string $sortBy = null,
        string $sortDirection = 'asc'
    ): LengthAwarePaginator {
        $query = $this->applyRelations();

        if ($search) {
            $query = $this->applySearch($query, $search);
        }

        if ($sortBy) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query->paginate($perPage);
    }

    /**
     * Apply search to query.
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        $searchable = $this->getSearchableFields();

        if (empty($searchable)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $searchable) {
            foreach ($searchable as $field) {
                $q->orWhere($field, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Get searchable fields from model or override in repository.
     */
    protected function getSearchableFields(): array
    {
        if (property_exists($this->model, 'searchable')) {
            return $this->model->searchable;
        }
        return [];
    }

    /**
     * Set relations to eager load.
     */
    public function with(array|string $relations): static
    {
        $this->with = is_array($relations) ? $relations : func_get_args();
        return $this;
    }

    /**
     * Apply eager loading relations.
     */
    protected function applyRelations(): Builder
    {
        $query = $this->query;

        if (!empty($this->with)) {
            $query = $query->with($this->with);
        }

        return $query;
    }

    /**
     * Add a where clause.
     */
    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Add a where in clause.
     */
    public function whereIn(string $column, array $values): static
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    /**
     * Add an order by clause.
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Get count of records.
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Check if any records exist.
     */
    public function exists(): bool
    {
        return $this->query->exists();
    }

    /**
     * First or create a record.
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->firstOrCreate($attributes, $values);
    }

    /**
     * Update or create a record.
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Bulk insert records.
     */
    public function insert(array $records): bool
    {
        return $this->model->insert($records);
    }

    /**
     * Get the model instance.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Get the query builder.
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Apply a callback to the query.
     */
    public function query(callable $callback): static
    {
        $callback($this->query);
        return $this;
    }

    /**
     * Get validation rules for create.
     */
    public function getCreateRules(): array
    {
        if (method_exists($this->model, 'getCreateRules')) {
            return $this->model->getCreateRules();
        }
        return [];
    }

    /**
     * Get validation rules for update.
     */
    public function getUpdateRules(int|string|null $id = null): array
    {
        if (method_exists($this->model, 'getUpdateRules')) {
            return $this->model->getUpdateRules($id);
        }
        return [];
    }
}
