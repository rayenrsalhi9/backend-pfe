<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentTokens;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentTokenRepositoryInterface;

//use Your Model

/**
 * Class UserRepository.
 */
class DocumentTokenRepository extends BaseRepository implements DocumentTokenRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * Class constructor
     *
 
     * @param Model $model
     */


    public static function model()
    {
        return DocumentTokens::class;
    }

    public function getDocumentToken($id)
    {
        $token = Uuid::uuid4();
        $model = DocumentTokens::find($id);
        if ($model == null) {
            $model = DocumentTokens::create([
                'documentId' => $id,
                'createdDate' => Carbon::now(),
                'token' => $token,
            ]);
            $model->save();
        } else {
            $token =  $model->token;
        }

        return $token->ToString();
    }

    public function deleteDocumentToken($token)
    {
        $model = DocumentTokens::where('token', '=', $token);

        if ($model != null) {
            $model->delete();
        }
        return true;
    }

    public function getDocumentPathByToken($id, $request)
    {
        $model = DocumentTokens::where('token', '=', $request->input('token'))
            ->where('documentId', '=', $id);
        if (is_null($model)) {
            return false;
        } else {
            return true;
        }
    }
}
