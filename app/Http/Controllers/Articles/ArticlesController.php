<?php

namespace App\Http\Controllers\Articles;

use Ramsey\Uuid\Uuid;
use App\Models\Articles;
use App\Models\ArticleUsers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ArticlesController extends Controller
{

  private function saveFile($image_64)
  {

    try {
      $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];

      $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

      $image = str_replace($replace, '', $image_64);

      $image = str_replace(' ', '+', $image);

      $destinationPath = public_path() . '/images//';

      $imageName = Uuid::uuid4() . '.' . $extension;

      file_put_contents($destinationPath . $imageName,  base64_decode($image));
      return 'images/' . $imageName;
    } catch (\Exception $e) {
      return '';
    }
  }

  function getAll(Request $request)
  {

    
    $limit = $request->limit;
    $query = Articles::orderBy('created_at', 'DESC')
      ->with('category',  'creator')
      ->when($limit, function ($query) use ($limit) {
        return $query->take($limit);
      })
      // ->where(function ($query) use ($user) {
      //   $query->where('privacy', 'public')
      //     ->orWhere(function ($query) use ($user) {
      //       $query->where('privacy', 'private')
      //         ->whereHas('users', function ($query) use ($user) {
      //           $query->where('user_id', $user->id);
      //         });
      //     });
      // })
      ;

    if ($request->name) {
      $query->where('title', 'like', '%' . $request->name . '%')
        ->orWhere('short_text',  'like', '%' . $request->name . '%');
    }

    if ($request->articleCategoryId) {
      $query->where('article_category_id', $request->articleCategoryId);
    }

    if ($request->createdAt) {
      $startDate = Carbon::parse($request->createdAt)->setTimezone('UTC');
      $endDate = Carbon::parse($request->createdAt)->setTimezone('UTC')->addDays(1)->addSeconds(-1);

      $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    $articles = $query->get();
    return response()->json($articles, 200);
  }

  function create(Request $request)
  {

    $user = Auth::user();

    try {

      $article = new Articles();
      $article->title = $request->title;
      $article->short_text = $request->description;
      $article->long_text = $request->body;
      $article->picture = isset($request->picture) ? $this->saveFile($request->picture) : null;
      $article->privacy = $request->private ? 'private' : 'public';
      $article->created_by = $user->id;
      $article->article_category_id = $request->category;
      $article->save();

      if ($request->private) {
        foreach ($request->users as $key => $user) {
          $articleUser = new ArticleUsers();
          $articleUser->article_id = $article->id;
          $articleUser->user_id = $user;
          $articleUser->save();
        }
      }

      $response = $article->load('category', 'users', 'users.user', 'creator');

      return response()->json($response, 200);
    } catch (\Throwable $th) {
      return response($th->getMessage(), 500);
    }
  }

  function getOne($id)
  {
    $articles = Articles::where('id', $id)->with('category', 'users', 'users.user', 'creator')->first();
    return response()->json($articles, 200);
  }

  function update($id, Request $request)
  {

    $user = Auth::user();

    try {

      $article = Articles::where('id', $id)->first();
      $article->title = $request->title;
      $article->short_text = $request->description;
      $article->long_text = $request->body;
      $article->privacy = $request->private ? 'private' : 'public';
      $article->picture = isset($request->picture) ? $this->saveFile($request->picture) : $article->picture;
      $article->created_by = $user->id;
      $article->article_category_id = $request->category;
      $article->save();

      if ($request->private) {

        ArticleUsers::where('article_id', $id)->delete();

        foreach ($request->users as $key => $user) {
          $articleUser = new ArticleUsers();
          $articleUser->article_id = $article->id;
          $articleUser->user_id = $user;
          $articleUser->save();
        }
      }

      $response = $article->load('category', 'users', 'users.user', 'creator');

      return response()->json($response, 200);
    } catch (\Throwable $th) {
      return response($th->getMessage(), 500);
    }
  }

  function delete($id)
  {
    $category = Articles::where('id', $id)->first();
    $category->delete();

    return response()->json('successfully deleted', 200);
  }
}
