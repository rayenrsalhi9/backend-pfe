<?php

namespace App\Http\Controllers\Blogs;

use App\Models\Blogs;
use Ramsey\Uuid\Uuid;
use App\Models\ArticleUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPermissionTrait;
use App\Traits\CacheableTrait;
use App\Models\BlogComments;
use App\Models\BlogReactions;
use App\Models\Tags;
use App\Models\ResponseAuditTrails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BlogsController extends Controller
{
    use HasPermissionTrait;
    use CacheableTrait;

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
        $cacheKey = $this->getCacheKey('blogs', 'list', md5(json_encode($request->all())));
        $ttl = $this->getCacheTtl('blogs');

        $blog = $this->cacheRemember($cacheKey, 'blogs', $ttl, function () use ($request) {
            $user = Auth::user();
            $limit = $request->limit;
            $banner = $request->banner;
            $grouped = $request->grouped;

            $query = Blogs::orderBy('created_at', 'DESC')
                ->with('category', 'creator', 'tags')
                ->withCount(['comments', 'reactions', 'reactionsUp', 'reactionsDown'])
                ->when($limit, function ($query) use ($limit) {
                    return $query->take($limit);
                })
                ->when($banner, function ($query) use ($banner) {
                    return $query->where('banner', boolval($banner));
                });

            if ($request->title) {
                $query->where('title', 'like', '%' . $request->title . '%')
                    ->orWhere('subtitle',  'like', '%' . $request->title . '%');
            }

            if ($request->category) {
                $query->where('category_id', $request->category);
            }

            if ($request->createdAt) {
                $startDate = Carbon::parse($request->createdAt)->setTimezone('UTC');
                $endDate = Carbon::parse($request->createdAt)->setTimezone('UTC')->addDays(1)->addSeconds(-1);

                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            $blog = $query->get();

            return $blog;
        });

        $canDelete = $this->hasPermission('BLOG_DELETE_COMMENT');
        foreach ($blog as $b) {
            $b->setAttribute('canDeleteComments', $canDelete);
        }

        return response()->json($blog, 200);
    }

    function getOne($id)
    {
        $cacheKey = $this->getCacheKey('blogs', 'item', $id);
        $ttl = $this->getCacheTtl('blogs');

        $blog = $this->cacheRemember($cacheKey, 'blogs', $ttl, function () use ($id) {
            return Blogs::where('id', $id)
                ->with('category', 'creator', 'reactions', 'reactionsUp', 'reactionsDown', 'reactions.user', 'comments', 'comments.user', 'tags')
                ->withCount(['comments'])
                ->first();
        });

        if ($blog) {
            $canDelete = $this->hasPermission('BLOG_DELETE_COMMENT');
            $blog->setAttribute('canDeleteComments', $canDelete);
        }

        return response()->json($blog, 200);
    }

    function create(Request $request)
    {

        $user = Auth::user();
        $tags = $request->tags;

        try {

            $blog = new Blogs();
            $blog->title = $request->title;
            $blog->subtitle = $request->subtitle;
            $blog->body = $request->body;
            $blog->picture = isset($request->picture) ? $this->saveFile($request->picture) : null;
            $blog->privacy = $request->private ? 'private' : 'public';
            $blog->created_by = $user->id;
            $blog->banner = $request->banner;
            $blog->category_id = $request->category;
            $blog->expiration = $request->expiration;
            $blog->start_date = $request->startDate;
            $blog->end_date = $request->endDate;
            $blog->save();

            if ($tags && count($tags) > 0) {
                foreach ($tags as $key => $tag) {
                    $blogTag = new Tags();
                    $blogTag->blog_id = $blog->id;
                    $blogTag->metatag = $tag['label'];
                    $blogTag->created_by = $user->id;
                    $blogTag->save();
                }
            }

            $response = $blog->load('category', 'creator', 'tags');

            $this->flushCacheTag('blogs');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function update($id, Request $request)
    {

        $user = Auth::user();
        $tags = $request->tags;

        try {

            $blog = Blogs::where('id', $id)->first();

            $blog->title = $request->title;
            $blog->subtitle = $request->subtitle;
            $blog->body = $request->body;
            $blog->picture = isset($request->picture) ? $this->saveFile($request->picture) : $blog->picture;
            $blog->privacy = $request->private ? 'private' : 'public';
            $blog->created_by = $user->id;
            $blog->banner = $request->banner;
            $blog->category_id = $request->category;
            $blog->expiration = $request->expiration;
            $blog->start_date = $request->startDate;
            $blog->end_date = $request->endDate;
            $blog->save();

            if ($tags && count($tags) > 0) {

                Tags::where('blog_id', $blog->id)->delete();

                foreach ($tags as $key => $tag) {
                    $blogTag = new Tags();
                    $blogTag->blog_id = $blog->id;
                    $blogTag->metatag = $tag['label'];
                    $blogTag->created_by = $user->id;
                    $blogTag->save();
                }
            }

            $response = $blog->load('category', 'creator', 'tags');

            $this->flushCacheTag('blogs');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function delete($id)
    {
        $category = Blogs::where('id', $id)->first();
        $category->delete();

        $this->flushCacheTag('blogs');

        return response()->json('successfully deleted', 200);
    }

    function addComment($id, Request $request)
    {

        $blog = Blogs::where('id', $id)->first();
        $user = Auth::user();

        $comment = new BlogComments();
        $comment->blog_id = $blog->id;
        $comment->user_id = $user->id;
        $comment->comment = $request->comment;
        $comment->save();

        $response = $blog->load('category', 'creator', 'reactions', 'reactionsUp', 'reactionsDown', 'reactions.user', 'comments', 'comments.user');

        $this->flushCacheTag('blogs');

        return response()->json($response, 200);
    }

    function addReaction($id, Request $request)
    {

        $blog = Blogs::where('id', $id)->first();
        $user = Auth::user();

        $blogReaction = BlogReactions::where([
            'blog_id' => $blog->id,
            'user_id' => $user->id
        ])->first();

        if ($blogReaction) {

            if ($blogReaction->type == $request->type) {
                $blogReaction->delete();
            } else {
                $blogReaction->blog_id = $blog->id;
                $blogReaction->type = $request->type;
                $blogReaction->user_id = $user->id;
                $blogReaction->save();
            }
        } else {
            $reaction = new BlogReactions();
            $reaction->blog_id = $blog->id;
            $reaction->user_id = $user->id;
            $reaction->type = $request->type;
            $reaction->save();
        }

        $response = $blog->load('category', 'creator', 'reactions', 'reactionsUp', 'reactionsDown', 'reactions.user', 'comments', 'comments.user');

        $this->flushCacheTag('blogs');

        return response()->json($response, 200);
    }

    function deleteComment($commentId, Request $request)
    {
        $user = Auth::user();

        $request->merge(['commentId' => $commentId]);
        $request->validate([
            'commentId' => 'required|uuid'
        ]);

        try {

            $comment = BlogComments::find($commentId);

            $canDelete = $this->hasPermission('BLOG_DELETE_COMMENT');
            $isOwner = $comment && $comment->user_id === $user->id;

            if (!$canDelete && !$isOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete comments.'
                ], 403);
            }

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            $blogId = $comment->blog_id;
            $commentContent = $comment->comment;

            $comment->delete();

            $this->flushCacheTag('blogs');

            // audit trail
            try {
                $audit = new ResponseAuditTrails();
                $audit->blogId = $blogId;
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
