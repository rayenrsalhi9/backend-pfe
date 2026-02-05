<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentMetaDatas;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentMetaDataRepositoryInterface;

//use Your Model

/**
 * Class DocumentMetaDataRepository.
 */
class DocumentMetaDataRepository extends BaseRepository implements DocumentMetaDataRepositoryInterface
{

    /**
     * BaseRepository constructor..
     *
     *
     * @param Model $model
     */


    public static function model()
    {
        return DocumentMetaDatas::class;
    }

    public function addMetadata($metadata)
    {
        $this->model->push($metadata);
    }

    public function getDocumentMetadatas($id)
    {
        $result = DocumentMetaDatas::where('documentId', $id)->get();
        return $result;
    }
}
