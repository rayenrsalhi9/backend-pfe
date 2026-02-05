<?php

namespace App\Repositories\Implementation;

use App\Models\Categories;
use App\Models\Documents;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\CategoryRepositoryInterface;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
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
        return Categories::class;
    }

    public function deleteCategory($id)
    {
        $document = Documents::where('categoryId', '=', $id)->first();

        if (!is_null($document)) {
            return false;
        } else {
            $this->delete($id);
            return true;
        }
    }
}
