<?php

namespace App\Http\Controllers\Forums;

use Illuminate\Http\Request;
use App\Models\ForumCategories;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ForumCategoriesController extends Controller
{
    function getAll()
    {
        $categories = ForumCategories::orderBy('created_at', 'DESC')->get();
        return response()->json($categories, 200);
    }

    function create(Request $request)
    {
        try {

            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'name' => "required",
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 409);
            }

            $category = new ForumCategories();
            $category->name = $request->name;
            $category->description = $request->description;
            $category->save();

            return response()->json($category, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function getOne()
    {
    }

    function update($id, Request $request)
    {
        try {


            $validator = Validator::make($request->all(), [
                'name' => "required",
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 409);
            }

            $category = ForumCategories::where('id', $id)->first();
            $category->name = $request->name;
            $category->description = $request->description;
            $category->save();

            return response()->json($category, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function delete($id)
    {
        $category = ForumCategories::where('id', $id)->first();
        $category->delete();

        return response()->json('successfully deleted', 200);
    }
}
