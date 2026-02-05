<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Contracts\PagesRepositoryInterface;

class PagesController extends Controller
{
    private $pagesRepository;

    public function __construct(PagesRepositoryInterface $pagesRepository)
    {
        $this->pagesRepository = $pagesRepository;
    }

    public function index()
    {
        return response($this->pagesRepository->orderBy('order')->all(), 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Name' => 'required',
            'Order' => 'required',
            'ModifiedBy' => 'required'
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return  response($this->pagesRepository->create($request->all()), 201);
    }

    public function update(Request $request, $id)
    {
        return  response($this->pagesRepository->update($request->all(), $id), 204);
    }

    public function destroy($id)
    {
        return response($this->pagesRepository->delete($id), 204);
    }
}
