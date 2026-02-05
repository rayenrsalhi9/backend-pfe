<?php

namespace App\Repositories\Implementation;

use App\Models\Pages;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\PagesRepositoryInterface;


//use Your Model

/**
 * Class ScreenRepository.
 */
class PagesRepository extends BaseRepository implements PagesRepositoryInterface
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
        return Pages::class;
    }

}
