<?php

namespace App\Repositories\Implementation;

use App\Models\Actions;
use App\Models\Pages;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\ActionsRepositoryInterface;


//use Your Model

/**
 * Class ActionsRepository.
 */
class ActionsRepository extends BaseRepository implements ActionsRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return Actions::class;
    }

    public function createAction(array $attribute)
    {
        $page = Pages::find($attribute['pageId']);

        $attribute['code'] =  str_replace(' ', '_', $page->name) . '_' . str_replace(' ', '_', $attribute['name']);
        $attribute['code'] = strtoupper($attribute['code']);
        $model = $this->model->newInstance($attribute);
        $model->save();
        $this->resetModel();
        $result = $this->parseResult($model);
        return $result;
    }
}
