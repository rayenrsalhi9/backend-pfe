<?php

namespace App\Http\Controllers\Articles;

use Ramsey\Uuid\Uuid;
use App\Models\Articles;
use App\Models\ArticleUsers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPermissionTrait;
use App\Models\ArticleComments;
use App\Models\ResponseAuditTrails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ArticlesController extends Controller
{
  use HasPermissionTrait;

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

    $user = Auth::user();
    $limit = $request->limit;
    $query = Articles::orderBy('created_at', 'DESC')
      ->with('category',  'creator')
      ->withCount(['comments'])
      ->when($limit, function ($query) use ($limit) {
        return $query->take($limit);
      })
      ->where(function ($query) use ($user) {
        $query->where('privacy', 'public');
        if ($user) {
          $query->orWhere(function ($query) use ($user) {
            $query->where('privacy', 'private')
              ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
              });
          });
        }
      });

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

    $canDelete = $this->hasPermission('ARTICLE_DELETE_COMMENT');
    foreach ($articles as $a) {
      $a->setAttribute('canDeleteComments', $canDelete);
      $a->setAttribute('can_delete_comments', $canDelete);
    }

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
    $user = Auth::user();
    $article = Articles::where('id', $id)
      ->with('category', 'users', 'users.user', 'creator', 'comments', 'comments.user')
      ->withCount(['comments'])
      ->first();

    if (!$article) {
      return response()->json(['success' => false, 'message' => 'Article not found'], 404);
    }

    if ($article->privacy === 'private') {
      $isAssigned = $article->users()->where('user_id', $user->id)->exists();
      $isCreator = $article->created_by === $user->id;

      if (!$isAssigned && !$isCreator) {
        return response()->json(['success' => false, 'message' => 'Article not found'], 404);
      }
    }

    $canDelete = $this->hasPermission('ARTICLE_DELETE_COMMENT');
    $article->setAttribute('canDeleteComments', $canDelete);
    $article->setAttribute('can_delete_comments', $canDelete);

    return response()->json($article, 200);
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

  function addComment($id, Request $request)
  {
    $article = Articles::findOrFail($id);
    $user = Auth::user();

    $request->validate([
      'comment' => 'required|string|max:1000'
    ]);

    $comment = new ArticleComments();
    $comment->article_id = $article->id;
    $comment->user_id = $user->id;
    $comment->comment = $request->comment;
    $comment->save();

    // audit trail
    try {
      $audit = new ResponseAuditTrails();
      $audit->articleId = $article->id;
      $audit->responseId = $comment->id;
      $audit->responseType = 'comment';
      $audit->operationName = 'Created';
      $audit->responseContent = $request->comment;
      $audit->ipAddress = $request->ip();
      $audit->userAgent = $request->userAgent();
      $audit->save();
    } catch (\Throwable $th) {
      Log::error($th->getMessage());
    }

    $response = $article->load('category', 'creator', 'comments', 'comments.user');

    return response()->json($response, 200);
  }

  function deleteComment($commentId, Request $request)
  {
    $request->merge(['commentId' => $commentId]);
    $request->validate([
      'commentId' => 'required|uuid|exists:article_comments,id'
    ]);

    try {
      if (!$this->hasPermission('ARTICLE_DELETE_COMMENT')) {
        return response()->json([
          'success' => false,
          'message' => 'You do not have permission to delete comments.'
        ], 403);
      }

      $user = Auth::user();
      $comment = ArticleComments::find($commentId);

      if (!$comment) {
        return response()->json([
          'success' => false,
          'message' => 'Comment not found'
        ], 404);
      }

      $articleId = $comment->article_id;
      $commentContent = $comment->comment;

      $comment->delete();

      // audit trail
      try {
        $audit = new ResponseAuditTrails();
        $audit->articleId = $articleId;
        $audit->responseId = $commentId;
        $audit->responseType = 'comment';
        $audit->operationName = 'Deleted';
        $audit->responseContent = $commentContent;
        $audit->ipAddress = $request->ip();
        $audit->userAgent = $request->userAgent();
        $audit->createdBy = $user->id;
        $audit->save();
      } catch (\Throwable $th) {
        Log::error('Audit trail creation failed: ' . $th->getMessage());
      }

      return response()->json([
        'success' => true,
        'message' => 'Comment deleted successfully'
      ], 200);
    } catch (\Exception $e) {
      Log::error('Comment deletion failed: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to delete comment'
      ], 500);
    }
  }
}
