<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Contracts\ActionsRepositoryInterface;

class ActionsController extends Controller
{
    private $actionsRepository;

    public function __construct(ActionsRepositoryInterface $actionsRepository)
    {
        $this->actionsRepository = $actionsRepository;
    }

    public function index()
    {
        return response($this->actionsRepository->orderBy('order')->all(), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'order' => 'required',
            'pageId'  => 'required',
            'modifiedBy' => 'required'
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return  response($this->actionsRepository->createAction($request->all()), 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        return  response($this->actionsRepository->update($request->all(), $id), 204);
    }

    //  /**
    //  * @param int $id
    //  * @return JsonResponse
    //  */

    public function destroy($id)
    {
        return response($this->actionsRepository->delete($id), 204);
    }
}
