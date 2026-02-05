<?php

namespace App\Repositories\Implementation;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Exceptions\RepositoryException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var \App\Innoclapps\Models\Model
     */
    protected $model;

    /**
     * @var \Closure|null
     */
    protected $scopeQuery = null;

    /**
     * Indicates whether the events are booted
     *
     * @var boolean
     */
    protected static $eventsBooted = false;

    /**
     * The repositories class names that are booted
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected static $dispatcher;

    /**
     * @var array
     */
    protected static $fieldSearchable = [];

    /**
     * Create new BaseRepository instance.
     */
    public function __construct()
    {
        $this->makeModel();
    }



    /**
     * Boot the repository
     */
    public static function boot()
    {
        //
    }

    /**
     * @throws \App\Innoclapps\Repository\Exceptions\RepositoryException
     */
    public function resetModel()
    {
        $this->makeModel();
    }

    /**
     * Set the repository searchable fields
     *
     * @param array $fields
     *
     * @return static
     */
    public function setSearchableFields(array $fields)
    {
        static::$fieldSearchable = $fields;

        return $this;
    }

    /**
     * Get the repository searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        return static::$fieldSearchable;
    }

    /**
     * Get model instance
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        if ($this->model instanceof Builder) {
            return $this->model->getModel();
        }

        return $this->model;
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    abstract public static function model();

    /**
     * Get the application
     *
     * @return \Illuminate\Container\Container;
     */
    protected function app()
    {
        return app();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \App\Innoclapps\Repository\Exceptions\RepositoryException
     */
    public function makeModel()
    {
        $model = $this->app()->make($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException(sprintf(
                'Class %s must be an instance of %s',
                $this->model(),
                Model::class
            ));
        }

        return $this->model = $model;
    }

    /**
     * Provide columns that should be selected
     *
     * @param array|string $columns
     *
     * @return static
     */
    public function columns($columns)
    {
        $this->model = $this->model->select($columns);

        return $this;
    }

    /**
     * Count results of repository
     *
     * @param array $where
     * @param string $columns
     *
     * @return int
     */
    public function count(array $where = [], $columns = '*')
    {
        // $this->applyCriteria();
        $this->applyScope();

        if ($where) {
            $this->applyConditions($where);
        }

        $result = $this->model->count($columns);

        $this->resetModel();
        $this->resetScope();

        return $result;
    }

    /**
     * Add scoped query
     *
     * @param \Closure $scope
     *
     * @return static
     */
    public function scopeQuery(Closure $scope)
    {
        $this->scopeQuery = $scope;

        return $this;
    }

    /**
     * Retrieve data array for populate field select

     * @param string $column
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function pluck($column, $key = null)
    {
        // $this->applyCriteria();

        return $this->model->pluck($column, $key);
    }

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all($columns = ['*'])
    {
        // $this->applyCriteria();
        $this->applyScope();

        if ($this->model instanceof Builder) {
            $results = $this->model->get($columns);
        } else {
            $results = $this->model->all($columns);
        }

        $this->resetModel();
        $this->resetScope();


        return $this->parseResult($results);
    }

    /**
     * Alias of All method
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function get($columns = ['*'])
    {
        return $this->all($columns);
    }

    /**
     * Retrieve first data of repository
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        // $this->applyCriteria();
        $this->applyScope();

        $results = $this->model->first($columns);

        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * Retrieve first data of repository, or return new Entity
     *
     * @param array $attributes
     * @param array $values
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrNew(array $attributes = [], array $values = [])
    {
        $this->makeModel();

        // $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->firstOrNew($attributes, $values);

        $this->resetModel();

        return $this->parseResult($model);
    }

    /**
     * Retrieve first data of repository, or create new Entity
     *
     * @param array $attributes
     * @param array $values
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $attributes = [], array $values = [])
    {
        $this->makeModel();

        // $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->firstOrCreate($attributes, $values);

        $this->resetModel();

        return $this->parseResult($model);
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes = [], array $values = [])
    {
        $this->makeModel();

        // $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->updateOrCreate($attributes, $values);

        $this->resetModel();

        return $this->parseResult($model);
    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param int|null $limit
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($limit = null, $columns = ['*'], $method = 'paginate')
    {
        // $this->applyCriteria();
        $this->applyScope();

        $limit = is_null($limit) ? 15 : $limit;

        $results = $this->model->{$method}($limit, $columns);

        $results->appends(app('request')->query());

        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param int $value
     *
     * @return static
     */
    public function limit($limit)
    {
        $this->model = $this->model->limit($limit);

        return $this;
    }

    /**
     * Retrieve all data of repository, simple paginated
     *
     * @param int|null $limit
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function simplePaginate($limit = null, $columns = ['*'])
    {
        return $this->paginate($limit, $columns, 'simplePaginate');
    }

    /**
     * Find data by id
     *
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function find($id)
    {
        // $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->findOrFail($id);

        $this->resetModel();

        return $this->parseResult($model);
    }

    /**
     * Find multiple data by their primary keys.
     *
     * @param array $ids
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findMany($ids, $columns = ['*'])
    {
        // $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->findMany($ids, $columns);

        $this->resetModel();

        return $this->parseResult($model);
    }

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByField($field, $value = null, $columns = ['*'])
    {
        // $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->where($field, '=', $value)->get($columns);

        $this->resetModel();

        return $this->parseResult($model);
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        // $this->applyCriteria();
        $this->applyScope();

        $this->applyConditions($where);

        $model = $this->model->get($columns);

        $this->resetModel();

        return $this->parseResult($model);
    }

    /**
     * Find data by multiple values in one field
     *
     * @param string $field
     * @param array $values
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhereIn($field, array $values, $columns = ['*'])
    {
        // $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->whereIn($field, $values)->get($columns);

        $this->resetModel();

        return $this->parseResult($model);
    }

    /**
     * Find data by excluding multiple values in one field
     *
     * @param string $field
     * @param array $values
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhereNotIn($field, array $values, $columns = ['*'])
    {
        // $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->whereNotIn($field, $values)->get($columns);

        $this->resetModel();

        return $this->parseResult($model);
    }

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
    public function findWhereBetween($column, array $values, $columns = ['*'], $boolean = 'and', $not = false)
    {
        // $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->whereBetween($column, $values, $boolean, $not)->get($columns);

        $this->resetModel();

        return $this->parseResult($model);
    }

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes)
    {
        $model = $this->model->newInstance($attributes);
        $model->save();
        $this->resetModel();
        $result = $this->parseResult($model);
        return $result;
    }


    /**
     * Update a entity in repository by id
     *
     * @param array $attributes
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update(array $attributes, $id)
    {
        $this->makeModel();
        $this->applyScope();

        $model = $this->model->findOrFail($id);
        $model->fill($attributes);
        $saved = $model->save();

        if (!$saved) {
            throw new RepositoryException('Error in saving data.');
        }
        $model->save();
        $this->resetModel();

        $result = $this->parseResult($model);

        return $result;
    }



    /**
     * Delete a entity in repository by id
     *
     * @param mixed $id
     *
     * @return boolean|array
     */
    public function delete($id)
    {

        $this->makeModel();
        $this->applyScope();

        $model = $this->model->findOrFail($id);

        $userId = Auth::parseToken()->getPayload()->get('userId');

        $model->isDeleted = true;
        $model->deletedBy = $userId;
        $model->deleted_at = Carbon::now();

        $res = $model->save();
        return   $res;
    }

    /**
     * Perform delete on the given model
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return boolean
     */
    protected function performDeleteOnModel($model)
    {
        return $model->delete();
    }

    /**
     * Perform delete on the given model with events
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return boolean
     */
    protected function performDeleteOnModelWithEvents($model)
    {

        $result = $this->performDeleteOnModel($model);
        return $model;
    }

    /**
     * Delete multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return bool
     */
    public function deleteWhere(array $where)
    {
        $this->applyScope();

        $this->applyConditions($where);

        $deleted = $this->model->delete();

        $this->resetModel();

        return $deleted;
    }

    /**
     * Update multiple entities
     *
     * @param array $attributes
     * @param array $where
     *
     * @return bool
     */
    public function massUpdate(array $attributes, array $where = [])
    {
        $this->makeModel();

        // $this->applyCriteria();
        $this->applyScope();

        if ($where) {
            $this->applyConditions($where);
        }

        $result = $this->model->update($attributes);

        $this->resetModel();

        return $result;
    }

    /**
     * Order collection by a given column
     *
     * @param string $column
     * @param string $direction
     *
     * @return static
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->model = $this->model->orderBy($column, $direction);

        return $this;
    }

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
    public function sync($id, $relation, $attributes, $detaching = true)
    {
        return $this->find($id)->{$relation}()->sync($attributes, $detaching);
    }

    /**
     * SyncWithoutDetaching
     *
     * @param int $id
     * @param string $relation
     * @param array $attributes
     *
     * @return array
     */
    public function syncWithoutDetaching($id, $relation, $attributes)
    {
        return $this->sync($id, $relation, $attributes, false);
    }

    /**
     * Attach
     *
     * @param int $id
     * @param string $relation
     * @param mixed $ids
     *
     * @return void
     */
    public function attach($id, $relation, $ids)
    {
        return $this->find($id)->{$relation}()->attach($ids);
    }

    /**
     * Detach
     *
     * @param int $id
     * @param string $relation
     * @param mixed $ids
     *
     * @return int
     */
    public function detach($id, $relation, $ids)
    {
        return $this->find($id)->{$relation}()->detach($ids);
    }

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
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
    {
        $this->model = $this->model->has($relation, $operator, $count, $boolean, $callback);

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param string $relation
     * @param string $boolean
     * @param \Closure|null $callback
     *
     * @return static
     */
    public function doesntHave($relation, $boolean = 'and', \Closure $callback = null)
    {
        $this->model = $this->model->doesntHave($relation, $boolean, $callback);

        return $this;
    }

    /**
     * Load relations
     *
     * @param array|string $relations
     *
     * @return static
     */
    public function with($relations)
    {
        $this->model = $this->model->with($relations);

        return $this;
    }

    /**
     * Add subselect queries to count the relations.
     *
     * @param string|array $relations
     *
     * @return static
     */
    public function withCount($relations)
    {
        $this->model = $this->model->withCount($relations);

        return $this;
    }

    /**
     * Add subselect queries to include the sum of the relation's column.
     *
     * @param string|array $relation
     * @param string $column
     *
     * @return static
     */
    public function withSum($relation, $column)
    {
        $this->model = $this->model->withSum($relation, $column);

        return $this;
    }

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
    public function whereHas($relation, \Closure $callback = null, $operator = '>=', $count = 1)
    {
        $this->model = $this->model->whereHas($relation, $callback, $operator, $count);

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param string $relation
     * @param \Closure|null $callback
     *
     * @return static
     */
    public function whereDoesntHave($relation, \Closure $callback = null)
    {
        $this->model = $this->model->whereDoesntHave($relation, $callback);

        return $this;
    }

    /**
     * Group results by column
     *
     * @param string $by
     *
     * @return static
     */
    public function groupBy($by)
    {
        $this->model = $this->model->groupBy($by);

        return $this;
    }

    /**
     * Get the repository scope query
     *
     * @return \Closure|null
     */
    public function getScope()
    {
        return $this->scopeQuery;
    }

    /**
     * Reset the query scope
     *
     * @param \Closure|null $with
     *
     * @return static
     */
    public function resetScope($with = null)
    {
        $this->scopeQuery = $with;

        return $this;
    }

    /**
     * Apply scope in current Query
     *
     * @return static
     */
    protected function applyScope()
    {
        if (isset($this->scopeQuery) && is_callable($this->scopeQuery)) {
            $callback    = $this->scopeQuery;
            $this->model = $callback($this->model);
        }

        return $this;
    }

    /**
     * Applies the given where conditions to the model.
     *
     * @param array $where
     * @return void
     */
    protected function applyConditions(array $where)
    {
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->model                   = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, '=', $value);
            }
        }
    }

    /**
     * Create array of models by the given value
     *
     * @param mixed $id
     *
     * @return array
     */
    protected function createModelsArray($value)
    {
        if ($value instanceof Model) {
            $models = [$value];
        } elseif (is_numeric($value) || is_array($value)) {
            $models = new Collection($value);
        } elseif ($value instanceof Collection) {
            $models = $value;
        } else {
            // Eloquent Collection
            $models = $value->all();
        }

        // We will check if the collection has mixes of Id's and models
        if ($models instanceof Collection) {
            $possibleModelIds = $models->filter(function ($model) {
                return is_numeric($model);
            });

            return $models->whereInstanceOf(Model::class)
                ->when($possibleModelIds->isNotEmpty(), function ($collection) use ($possibleModelIds) {
                    return $collection->merge(
                        $this->findMany($possibleModelIds->all())->all()
                    )->all();
                });
        }

        return $models;
    }

    /**
     * Wrapper result data
     *
     * @param mixed $result
     *
     * @return mixed
     */
    public function parseResult($result)
    {
        return $result;
    }

    // /**
    //  * Register an updating model event with the dispatcher.
    //  *
    //  * @param \Closure|string $callback
    //  * @return void
    //  */
    // public static function updating($callback)
    // {
    //     static::registerModelEvent('updating', $callback);
    // }

    // /**
    //  * Register an updated model event with the dispatcher.
    //  *
    //  * @param \Closure|string $callback
    //  * @return void
    //  */
    // public static function updated($callback)
    // {
    //     static::registerModelEvent('updated', $callback);
    // }

    // /**
    //  * Register a creating model event with the dispatcher.
    //  *
    //  * @param \Closure|string $callback
    //  * @return void
    //  */
    // public static function creating($callback)
    // {
    //     static::registerModelEvent('creating', $callback);
    // }

    // /**
    //  * Register a created model event with the dispatcher.
    //  *
    //  * @param \Closure|string $callback
    //  * @return void
    //  */
    // public static function created($callback)
    // {
    //     static::registerModelEvent('created', $callback);
    // }

    // /**
    //  * Register a deleting model event with the dispatcher.
    //  *
    //  * @param \Closure|string $callback
    //  * @return void
    //  */
    // public static function deleting($callback)
    // {
    //     static::registerModelEvent('deleting', $callback);
    // }

    // /**
    //  * Register a deleted model event with the dispatcher.
    //  *
    //  * @param \Closure|string $callback
    //  * @return void
    //  */
    // public static function deleted($callback)
    // {
    //     static::registerModelEvent('deleted', $callback);
    // }
}
