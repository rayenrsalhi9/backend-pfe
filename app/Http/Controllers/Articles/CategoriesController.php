<?php

namespace App\Http\Controllers\Articles;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPermissionTrait;
use App\Models\ArticleCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CategoriesController extends Controller
{
  use HasPermissionTrait;
  function getAll()
  {
    $categories = ArticleCategories::orderBy('created_at','DESC')->get();
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

      $category = new ArticleCategories();
      $category->name = $request->name;
      $category->description = $request->description;
      $category->save();

      return response()->json($category, 200);
    } catch (\Throwable $th) {
      return response($th->getMessage(),500);
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

      $category = ArticleCategories::where('id',$id)->first();
      $category->name = $request->name;
      $category->description = $request->description;
      $category->save();

      return response()->json($category, 200);
    } catch (\Throwable $th) {
      return response($th->getMessage(),500);
    }
  }

  function delete($id)
  {
    if (!$this->hasPermission('ARTICLE_DELETE_CATEGORY')) {
      return response()->json([
        'success' => false,
        'message' => 'You do not have permission to delete this category.'
      ], 403);
    }

    $category = ArticleCategories::where('id',$id)->first();

    if (!$category) {
      return response()->json([
        'success' => false,
        'message' => 'Category not found'
      ], 404);
    }

    $category->delete();

    return response()->json('successfully deleted',200);
  }
}
