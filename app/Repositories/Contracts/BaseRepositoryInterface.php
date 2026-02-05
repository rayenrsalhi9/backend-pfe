<?php

namespace App\Repositories\Contracts;



interface BaseRepositoryInterface
{
 /**
     * Provide columns that should be selected
     *
     * @param array|string $columns
     *
     * @return static
     */
    public function columns($columns);

    /**
     * Retrieve data array for populate field select

     * @param string $column
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function pluck($column, $key = null);

    /**
     * Count results of repository
     *
     * @param array $where
     * @param string $columns
     *
     * @return int
     */
    public function count(array $where = [], $columns = '*');

    /**
     * Sync relations
     *
     * @param int $id
     * @param string $relation
     * @param array $attributes
     * @param bool $detaching
     *
     * @return array
     */
    public function sync($id, $relation, $attributes, $detaching = true);

    /**
     * SyncWithoutDetaching
     *
     * @param int $id
     * @param string $relation
     * @param array $attributes
     *
     * @return array
     */
    public function syncWithoutDetaching($id, $relation, $attributes);

    /**
     * Attach
     *
     * @param int $id
     * @param string $relation
     * @param mixed $ids
     *
     * @return void
     */
    public function attach($id, $relation, $ids);

    /**
     * Detach
     *
     * @param int $id
     * @param string $relation
     * @param mixed $ids
     *
     * @return int
     */
    public function detach($id, $relation, $ids);

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all($columns = ['*']);

    /**
     * Retrieve all data of repository, paginated
     *
     * @param int|null $limit
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($limit = null, $columns = ['*']);

    /**
     * Set the "limit" value of the query.
     *
     * @param int $value
     *
     * @return static
     */
    public function limit($limit);

    /**
     * Retrieve all data of repository, simple paginated
     *
     * @param int|null $limit
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function simplePaginate($limit = null, $columns = ['*']);

    /**
     * Find data by id
     *
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function find($id);

    /**
     * Find multiple data by their primary keys.
     *
     * @param array $ids
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findMany($ids, $columns = ['*']);

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByField($field, $value, $columns = ['*']);

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhere(array $where, $columns = ['*']);

    /**
     * Find data by multiple values in one field
     *
     * @param string $field
     * @param array $values
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhereIn($field, array $values, $columns = ['*']);

    /**
     * Find data by excluding multiple values in one field
     *
     * @param string $field
     * @param array $values
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhereNotIn($field, array $values, $columns = ['*']);

    /**
     * Find data using whereBetween query
     *
     * @param string $column
     * @param array $values
     * @param array $columns
     * @param string $boolean
     * @param boolean $not
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhereBetween($column, array $values, $columns = ['*'], $boolean = 'and', $not = false);

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes);

    /**
     * Update a entity in repository by id
     *
     * @param array $attributes
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update(array $attributes, $id);

    /**
     * Delete a entity in repository by id
     *
     * @param mixed $id
     *
     * @return boolean|array
     */
    public function delete($id);

    /**
     * Order collection by a given column
     *
     * @param string $column
     * @param string $direction
     *
     * @return static
     */
    public function orderBy($column, $direction = 'asc');

    /**
     * Load relations
     *
     * @param array|string $relations
     *
     * @return static
     */
    public function with($relations);

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param string $relation
     * @param \Closure|null $callback
     * @param string $operator
     * @param int $count
     *
     * @return static
     */
    public function whereHas($relation, \Closure $callback = null, $operator = '>=', $count = 1);

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param string $relation
     * @param \Closure|null $callback
     *
     * @return static
     */
    public function whereDoesntHave($relation, \Closure $callback = null);

    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param string|\Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param string $operator
     * @param int $count
     * @param string $boolean
     * @param \Closure|null $callback
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', \Closure $callback = null);

    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param string $relation
     * @param string $boolean
     * @param \Closure|null $callback
     *
     * @return static
     */
    public function doesntHave($relation, $boolean = 'and', \Closure $callback = null);

    /**
     * Add subselect queries to count the relations.
     *
     * @param string|array $relations
     *
     * @return static
     */
    public function withCount($relations);

    /**
     * Add subselect queries to include the sum of the relation's column.
     *
     * @param string|array $relation
     * @param string $column
     *
     * @return static
     */
    public function withSum($relation, $column);

    /**
     * Add scoped query
     *
     * @param \Closure $scope
     *
     * @return static
     */
    public function scopeQuery(\Closure $scope);

    /**
     * Reset the query scope
     *
     * @return static
     */
    public function resetScope();

    /**
     * Get the repository searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable() : array;

    /**
     * Retrieve first data of repository, or return new Entity
     *
     * @param array $attributes
     * @param array $values
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrNew(array $attributes = [], array $values = []);

    /**
     * Retrieve first data of repository, or create new Entity
     *
     * @param array $attributes
     * @param array $values
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $attributes = [], array $values = []);

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes = [], array $values = []);

    /**
     * Group results by column
     *
     * @param string $by
     *
     * @return static
     */
    public function groupBy($by);

    /**
     * Update multiple entities
     *
     * @param array $attributes
     * @param array $where
     *
     * @return bool
     */
    public function massUpdate(array $attributes, array $where = []);

    /**
     * Delete multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return bool
     */
    public function deleteWhere(array $where);
}
