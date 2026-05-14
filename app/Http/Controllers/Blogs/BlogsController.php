<?php

namespace App\Http\Controllers\Blogs;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPermissionTrait;
use App\Models\BlogComments;
use App\Models\BlogReactions;
use App\Models\Blogs;
use App\Models\BlogUsers;
use App\Models\ResponseAuditTrails;
use App\Models\Tags;
use App\Traits\CacheableTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class BlogsController extends Controller
{
    use CacheableTrait;
    use HasPermissionTrait;

    private function saveFile($image_64)
    {

        try {
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $image = str_replace($replace, '', $image_64);

            $image = str_replace(' ', '+', $image);

            $destinationPath = public_path().'/images//';

            if (! is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $imageName = Uuid::uuid4().'.'.$extension;

            $decoded = base64_decode($image, true);

            if ($decoded === false) {
                throw new \Exception('Base64 decode failed');
            }

            $bytes = file_put_contents($destinationPath.$imageName, $decoded);

            if ($bytes === false) {
                throw new \Exception('Failed to write file');
            }

            return 'images/'.$imageName;
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getAll(Request $request)
    {
        $viewer = Auth::id() ?? 'guest';
        $cacheKey = $this->getCacheKey('blogs', 'list', $this->normalizeRequestParams($request->all()), $viewer);
        $ttl = $this->getCacheTtl('blogs');

        $blog = $this->cacheRemember($cacheKey, 'blogs', $ttl, function () use ($request) {
            $user = Auth::user();
            $limit = $request->limit;
            $banner = $request->banner;
            $grouped = $request->grouped;

            $query = Blogs::orderBy('created_at', 'DESC')
                ->with('category', 'creator', 'tags', 'allowedUsers')
                ->withCount(['comments', 'reactions', 'reactionsUp', 'reactionsDown'])
                ->where(function ($query) use ($user) {
                    $query->where('privacy', 'public');
                    if ($user) {
                        $query->orWhere(function ($query) use ($user) {
                            $query->where('privacy', 'private')
                                ->where(function ($query) use ($user) {
                                    $query->whereHas('allowedUsers', function ($query) use ($user) {
                                        $query->where('user_id', $user->id);
                                    });
                                    $query->orWhere('created_by', $user->id);
                                });
                        });
                    }
                })
                ->when($limit, function ($query) use ($limit) {
                    return $query->take($limit);
                });

            // Banner filtering removed

            if ($request->title) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->title.'%')
                        ->orWhere('subtitle', 'like', '%'.$request->title.'%');
                });
            }

            if ($request->category) {
                $query->where('category_id', $request->category);
            }

            if ($request->createdAt) {
                try {
                    $startDate = Carbon::parse($request->createdAt)->setTimezone('UTC');
                    $endDate = Carbon::parse($request->createdAt)->setTimezone('UTC')->addDays(1)->addSeconds(-1);
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                } catch (\Exception $e) {
                }
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

    public function getAllForDashboard(Request $request)
    {
        $limit = $request->limit;

        $query = Blogs::orderBy('created_at', 'DESC')
            ->with('category', 'creator', 'tags', 'allowedUsers')
            ->withCount(['comments', 'reactions', 'reactionsUp', 'reactionsDown']);

        if ($request->title) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->title.'%')
                    ->orWhere('subtitle', 'like', '%'.$request->title.'%');
            });
        }

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->createdAt) {
            try {
                $startDate = Carbon::parse($request->createdAt)->setTimezone('UTC');
                $endDate = Carbon::parse($request->createdAt)->setTimezone('UTC')->addDays(1)->addSeconds(-1);
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } catch (\Exception $e) {
            }
        }

        if ($limit) {
            $query->take($limit);
        }

        $blogs = $query->get();

        $canDelete = $this->hasPermission('BLOG_DELETE_COMMENT');
        foreach ($blogs as $b) {
            $b->setAttribute('canDeleteComments', $canDelete);
        }

        return response()->json($blogs, 200);
    }

    public function getOne($id)
    {
        $cacheKey = $this->getCacheKey('blogs', 'item', $id);
        $ttl = $this->getCacheTtl('blogs');

        $blog = $this->cacheRemember($cacheKey, 'blogs', $ttl, function () use ($id) {
            return Blogs::where('id', $id)
                ->with('category', 'creator', 'reactions', 'reactionsUp', 'reactionsDown', 'reactions.user', 'comments', 'comments.user', 'tags', 'allowedUsers.user')
                ->withCount(['comments'])
                ->first();
        });

        if (! $blog) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = Auth::user();

        // Privacy check for private blogs
        if ($blog->privacy === 'private') {
            if (! $user) {
                return response()->json(['message' => 'Not found'], 404);
            }
            $isAllowed = $blog->allowedUsers()->where('user_id', $user->id)->exists();
            $isCreator = $blog->created_by === $user->id;
            if (! $isAllowed && ! $isCreator) {
                return response()->json(['message' => 'Not found'], 404);
            }
        }

        if ($blog) {
            $canDelete = $this->hasPermission('BLOG_DELETE_COMMENT');
            $blog->setAttribute('canDeleteComments', $canDelete);
        }

        return response()->json($blog, 200);
    }

    public function create(Request $request)
    {

        $user = Auth::user();
        $tags = $request->tags;
        $picturePath = null;

        try {

            DB::transaction(function () use ($request, $user, $tags, &$picturePath, &$response) {
                $blog = new Blogs;
                $blog->title = $request->title;
                $blog->subtitle = $request->subtitle;
                $blog->body = $request->body;

                if ($request->filled('picture')) {
                    $picturePath = $this->saveFile($request->picture);
                    if (empty($picturePath)) {
                        throw new \Exception('Failed to save picture');
                    }
                    $blog->picture = $picturePath;
                } else {
                    $blog->picture = '';
                }

                $isPrivate = $request->boolean('private');
                $blog->privacy = $isPrivate ? 'private' : 'public';
                $blog->created_by = $user->id;
                $blog->category_id = $request->category;
                $blog->save();

                if ($isPrivate) {
                    $users = is_array($request->users) ? $request->users : [];
                    if (! in_array($user->id, $users)) {
                        $users[] = $user->id;
                    }
                    $users = array_values(array_unique($users));
                    foreach ($users as $userId) {
                        $blogUser = new BlogUsers;
                        $blogUser->blog_id = $blog->id;
                        $blogUser->user_id = $userId;
                        $blogUser->save();
                    }
                }

                if ($tags && count($tags) > 0) {
                    foreach ($tags as $tag) {
                        $label = is_string($tag) ? $tag : (isset($tag['label']) && is_string($tag['label']) && $tag['label'] !== '' ? $tag['label'] : null);
                        if (! $label) {
                            continue;
                        }
                        $blogTag = new Tags;
                        $blogTag->blog_id = $blog->id;
                        $blogTag->metatag = $label;
                        $blogTag->created_by = $user->id;
                        $blogTag->save();
                    }
                }

                $response = $blog->load('category', 'creator', 'tags', 'allowedUsers');
            });

            $this->flushCacheTag('blogs');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            if ($picturePath && file_exists(public_path($picturePath))) {
                unlink(public_path($picturePath));
            }

            return response('Failed to create blog', 500);
        }
    }

    public function update($id, Request $request)
    {

        $user = Auth::user();
        $tags = $request->tags;
        $picturePath = null;
        $oldPicture = null;

        $blog = Blogs::where('id', $id)->first();
        if (! $blog) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->authorize('update', $blog);

        try {

            DB::transaction(function () use ($blog, $id, $request, $user, $tags, &$picturePath, &$oldPicture) {
                $blog->title = $request->title;
                $blog->subtitle = $request->subtitle;
                $blog->body = $request->body;

                if ($request->filled('picture')) {
                    $oldPicture = $blog->picture;
                    $picturePath = $this->saveFile($request->picture);
                    if (empty($picturePath)) {
                        throw new \Exception('Failed to save picture');
                    }
                    $blog->picture = $picturePath;
                }

                $blog->category_id = $request->category;
                $blog->save();

                if ($request->has('private')) {
                    $isPrivate = $request->boolean('private');
                    $blog->privacy = $isPrivate ? 'private' : 'public';
                    $blog->save();

                    BlogUsers::where('blog_id', $id)->delete();

                    if ($isPrivate) {
                        $users = is_array($request->users) ? $request->users : [];
                        if (! in_array($user->id, $users)) {
                            $users[] = $user->id;
                        }
                        $users = array_values(array_unique($users));
                        foreach ($users as $userId) {
                            $blogUser = new BlogUsers;
                            $blogUser->blog_id = $blog->id;
                            $blogUser->user_id = $userId;
                            $blogUser->save();
                        }
                    }
                }

                if ($request->has('tags')) {
                    Tags::where('blog_id', $blog->id)->delete();
                    if ($tags && count($tags) > 0) {
                        foreach ($tags as $tag) {
                            $label = is_string($tag) ? $tag : (isset($tag['label']) && is_string($tag['label']) && $tag['label'] !== '' ? $tag['label'] : null);
                            if (! $label) {
                                continue;
                            }
                            $blogTag = new Tags;
                            $blogTag->blog_id = $blog->id;
                            $blogTag->metatag = $label;
                            $blogTag->created_by = $user->id;
                            $blogTag->save();
                        }
                    }
                }
            });

            if ($oldPicture && file_exists(public_path($oldPicture))) {
                unlink(public_path($oldPicture));
            }

            $response = $blog->load('category', 'creator', 'tags', 'allowedUsers');

            $this->flushCacheTag('blogs');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            if ($picturePath && file_exists(public_path($picturePath))) {
                unlink(public_path($picturePath));
            }

            return response('Update failed', 500);
        }
    }

    public function delete($id)
    {
        $category = Blogs::where('id', $id)->first();
        if (! $category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->authorize('delete', $category);
        $category->delete();

        $this->flushCacheTag('blogs');

        return response()->json('successfully deleted', 200);
    }

    public function addComment($id, Request $request)
    {

        $blog = Blogs::where('id', $id)->first();
        if (! $blog) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $user = Auth::user();

        if ($blog->privacy === 'private') {
            if (! $user) {
                return response()->json(['message' => 'Not found'], 404);
            }
            $isAllowed = $blog->allowedUsers()->where('user_id', $user->id)->exists();
            $isCreator = $blog->created_by === $user->id;
            if (! $isAllowed && ! $isCreator) {
                return response()->json(['message' => 'Not found'], 404);
            }
        }

        $comment = new BlogComments;
        $comment->blog_id = $blog->id;
        $comment->user_id = $user->id;
        $comment->comment = $request->comment;
        $comment->save();

        $response = $blog->load('category', 'creator', 'reactions', 'reactionsUp', 'reactionsDown', 'reactions.user', 'comments', 'comments.user');

        $this->flushCacheTag('blogs');

        return response()->json($response, 200);
    }

    public function addReaction($id, Request $request)
    {

        $blog = Blogs::where('id', $id)->first();
        if (! $blog) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $user = Auth::user();

        if ($blog->privacy === 'private') {
            if (! $user) {
                return response()->json(['message' => 'Not found'], 404);
            }
            $isAllowed = $blog->allowedUsers()->where('user_id', $user->id)->exists();
            $isCreator = $blog->created_by === $user->id;
            if (! $isAllowed && ! $isCreator) {
                return response()->json(['message' => 'Not found'], 404);
            }
        }

        $blogReaction = BlogReactions::where([
            'blog_id' => $blog->id,
            'user_id' => $user->id,
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
            $reaction = new BlogReactions;
            $reaction->blog_id = $blog->id;
            $reaction->user_id = $user->id;
            $reaction->type = $request->type;
            $reaction->save();
        }

        $response = $blog->load('category', 'creator', 'reactions', 'reactionsUp', 'reactionsDown', 'reactions.user', 'comments', 'comments.user');

        $this->flushCacheTag('blogs');

        return response()->json($response, 200);
    }

    public function deleteComment($commentId, Request $request)
    {
        $user = Auth::user();

        $request->merge(['commentId' => $commentId]);
        $request->validate([
            'commentId' => 'required|uuid',
        ]);

        try {

            $comment = BlogComments::find($commentId);

            if (! $comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                ], 404);
            }

            $canDelete = $this->hasPermission('BLOG_DELETE_COMMENT');
            $isOwner = $comment->user_id === $user->id;

            if (! $canDelete && ! $isOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete comments.',
                ], 403);
            }

            $blogId = $comment->blog_id;
            $commentContent = $comment->comment;

            $comment->delete();

            $this->flushCacheTag('blogs');

            // audit trail
            try {
                $audit = new ResponseAuditTrails;
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
                Log::error('Audit trail creation failed: '.$th->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Comment deletion failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
            ], 500);
        }
    }
}
