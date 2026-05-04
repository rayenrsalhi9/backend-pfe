<?php

namespace App\Http\Controllers\Forums;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPermissionTrait;
use App\Traits\CacheableTrait;
use App\Models\ForumComments;
use App\Models\ForumReactions;
use App\Models\Forums;
use App\Models\Tags;
use App\Models\ForumUsers;
use App\Models\ResponseAuditTrails;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ForumsController extends Controller
{
    use HasPermissionTrait;
    use CacheableTrait;

    function getAll(Request $request)
    {
        $cacheKey = $this->getCacheKey('forums', 'list', md5(json_encode($request->all())));
        $ttl = $this->getCacheTtl('forums');

        $forums = $this->cacheRemember($cacheKey, 'forums', $ttl, function () use ($request) {
            $limit = $request->limit;
            $banner = $request->banner;
            $user = Auth::user();

            $query = Forums::orderBy('created_at', 'DESC')
                ->with(['creator' => function ($q) {
                    $q->select('users.id', 'users.firstName', 'users.lastName', 'users.userName', 'users.avatar', 'users.isDeleted')->withoutGlobalScope('isDeleted');
                }])
                ->with('category', 'allowedUsers')
                ->withCount(['reactions', 'comments'])
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

            if ($request->has('banner')) {
                $parsedBanner = filter_var($request->banner, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($parsedBanner !== null) {
                    $query->where('banner', $parsedBanner);
                }
            }

            if ($request->has('closed')) {
                $bool = filter_var($request->closed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bool !== null) {
                    $query->where('closed', $bool);
                }
            }

            if ($request->title) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            if ($request->category) {
                $query->where('category_id', $request->category);
            }

            if ($request->createdAt) {
                $startDate = Carbon::parse($request->createdAt)->setTimezone('UTC');
                $endDate = Carbon::parse($request->createdAt)->setTimezone('UTC')->addDays(1)->addSeconds(-1);
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            $forums = $query->get();

            return $forums;
        });

        $canDelete = $this->hasPermission('FORUM_DELETE_COMMENT');
        foreach ($forums as $f) {
            $f->setAttribute('canDeleteComments', $canDelete);
        }

        return response()->json($forums, 200);
    }

    function getAllForDashboard(Request $request)
    {
        $user = Auth::user();
        $limit = $request->limit;

        $query = Forums::orderBy('created_at', 'DESC')
            ->with(['creator' => function ($q) {
                $q->select('users.id', 'users.firstName', 'users.lastName', 'users.userName', 'users.avatar', 'users.isDeleted')->withoutGlobalScope('isDeleted');
            }])
            ->with('category', 'allowedUsers')
            ->withCount(['reactions', 'comments']);

        if ($request->has('banner')) {
            $parsedBanner = filter_var($request->banner, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($parsedBanner !== null) {
                $query->where('banner', $parsedBanner);
            }
        }

        if ($request->has('closed')) {
            $bool = filter_var($request->closed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool !== null) {
                $query->where('closed', $bool);
            }
        }

        if ($request->title) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->createdAt) {
            $startDate = Carbon::parse($request->createdAt)->setTimezone('UTC');
            $endDate = Carbon::parse($request->createdAt)->setTimezone('UTC')->addDays(1)->addSeconds(-1);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($limit) {
            $query->take($limit);
        }

        $forums = $query->get();

        $canDelete = $this->hasPermission('FORUM_DELETE_COMMENT');
        foreach ($forums as $f) {
            $f->setAttribute('canDeleteComments', $canDelete);
        }

        return response()->json($forums, 200);
    }

    function getOne($id)
    {
        $forum = Forums::where('id', $id)
            ->with(
                'category',
                'creator',
                'reactions',
                'reactionsUp',
                'reactionsDown',
                'reactionsHeart',
                'reactions.user',
                'comments',
                'comments.user',
                'tags',
                'allowedUsers.user'
            )
            ->withCount(['reactions', 'comments'])
            ->first();

        if (!$forum) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = Auth::user();

        // Privacy check for private forums
        if ($forum->privacy === 'private') {
            if (!$user) {
                return response()->json(['message' => 'Not found'], 404);
            }
            $isAllowed = $forum->allowedUsers()->where('user_id', $user->id)->exists();
            $isCreator = $forum->created_by === $user->id;
            if (!$isAllowed && !$isCreator) {
                return response()->json(['message' => 'Not found'], 404);
            }
        }

        $canDelete = $this->hasPermission('FORUM_DELETE_COMMENT');
        $forum->setAttribute('canDeleteComments', $canDelete);

        return response()->json($forum, 200);
    }

    function create(Request $request)
    {
        $user = Auth::user();
        $rawTags = $request->tags;
        $tags = is_array($rawTags) ? $rawTags : [];

        try {
            $forum = new Forums();
            $forum->title = $request->title;
            $forum->content = $request->input('content');
            $forum->privacy = $request->private ? 'private' : 'public';
            $forum->created_by = $user->id;
            $forum->category_id = $request->category;
            $forum->closed = false;
            $forum->save();

            if ($request->private && is_array($request->users)) {
                foreach ($request->users as $userId) {
                    $forumUser = new ForumUsers();
                    $forumUser->forum_id = $forum->id;
                    $forumUser->user_id = $userId;
                    $forumUser->save();
                }
            }

            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    if (!isset($tag['label']) || !is_string($tag['label'])) {
                        continue;
                    }
                    $forumTag = new Tags();
                    $forumTag->forum_id = $forum->id;
                    $forumTag->metatag = $tag['label'];
                    $forumTag->created_by = $user->id;
                    $forumTag->save();
                }
            }

            $response = $forum->load('category', 'creator', 'tags');

            $this->flushCacheTag('forums');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function update($id, Request $request)
    {
        $user = Auth::user();
        $rawTags = $request->tags;
        $tags = (is_array($rawTags)) ? $rawTags : null;

        try {
            $forum = Forums::where('id', $id)->first();
            if (!$forum) {
                return response()->json(['message' => 'Not found'], 404);
            }
            $forum->title = $request->title;
            $forum->content = $request->input('content');
            $forum->privacy = $request->private ? 'private' : 'public';
            $forum->category_id = $request->category;
            $forum->closed = $request->closed;
            $forum->save();

            if ($request->private && is_array($request->users)) {
                ForumUsers::where('forum_id', $forum->id)->delete();
                foreach ($request->users as $userId) {
                    $forumUser = new ForumUsers();
                    $forumUser->forum_id = $forum->id;
                    $forumUser->user_id = $userId;
                    $forumUser->save();
                }
            }

            if ($tags !== null) {
                Tags::where('forum_id', $forum->id)->delete();

                if (!empty($tags)) {
                    foreach ($tags as $tag) {
                        if (!isset($tag['label']) || !is_string($tag['label'])) {
                            continue;
                        }
                        $forumTag = new Tags();
                        $forumTag->forum_id = $forum->id;
                        $forumTag->metatag = $tag['label'];
                        $forumTag->created_by = $user->id;
                        $forumTag->save();
                    }
                }
            }

            $response = $forum->load('category', 'creator', 'tags');

            $this->flushCacheTag('forums');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function delete($id)
    {
        $forum = Forums::find($id);
        if (!$forum) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->authorize('delete', $forum);
        
        $forum->delete();

        $this->flushCacheTag('forums');

        return response()->json('successfully deleted', 200);
    }

    function addComment($id, Request $request)
    {
        $forum = Forums::where('id', $id)->first();
        if (!$forum) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $user = Auth::user();

        $comment = new ForumComments();
        $comment->forum_id = $forum->id;
        $comment->user_id = $user->id;
        $comment->comment = $request->comment;
        $comment->save();

        // audit trail (non-blocking)
        try {
            $audit = new ResponseAuditTrails();
            $audit->forumId = $forum->id;
            $audit->responseId = $comment->id;
            $audit->responseType = 'comment';
            $audit->operationName = 'Created';
            $audit->responseContent = $request->comment;
            $audit->ipAddress = $request->ip();
            $audit->userAgent = $request->userAgent();
            $audit->createdBy = $user->id;
            $audit->save();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }

        $response = $forum->load(
            'category',
            'creator',
            'reactions',
            'reactionsUp',
            'reactionsDown',
            'reactionsHeart',
            'reactions.user',
            'comments',
            'comments.user'
        );

        $this->flushCacheTag('forums');

        return response()->json($response, 200);
    }

    function addReaction($id, Request $request)
    {
        $forum = Forums::where('id', $id)->first();
        if (!$forum) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $user = Auth::user();

        $forumReaction = ForumReactions::where([
            'forum_id' => $forum->id,
            'user_id' => $user->id
        ])->first();

        if ($forumReaction) {
            if ($forumReaction->type == $request->type) {
                $forumReaction->delete();

                try {
                    $audit = new ResponseAuditTrails();
                    $audit->forumId = $forum->id;
                    $audit->responseId = $forumReaction->id;
                    $audit->responseType = 'reaction';
                    $audit->operationName = 'Deleted';
                    $audit->responseContent = $request->type;
                    $audit->ipAddress = $request->ip();
                    $audit->userAgent = $request->userAgent();
                    $audit->createdBy = $user->id;
                    $audit->save();
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }
            } else {
                $previousType = $forumReaction->type;

                $forumReaction->type = $request->type;
                $forumReaction->save();

                try {
                    $audit = new ResponseAuditTrails();
                    $audit->forumId = $forum->id;
                    $audit->responseId = $forumReaction->id;
                    $audit->responseType = 'reaction';
                    $audit->operationName = 'Updated';
                    $audit->responseContent = $request->type;
                    $audit->previousContent = $previousType;
                    $audit->ipAddress = $request->ip();
                    $audit->userAgent = $request->userAgent();
                    $audit->createdBy = $user->id;
                    $audit->save();
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }
            }
        } else {
            $reaction = new ForumReactions();
            $reaction->forum_id = $forum->id;
            $reaction->user_id = $user->id;
            $reaction->type = $request->type;
            $reaction->save();

            try {
                $audit = new ResponseAuditTrails();
                $audit->forumId = $forum->id;
                $audit->responseId = $reaction->id;
                $audit->responseType = 'reaction';
                $audit->operationName = 'Created';
                $audit->responseContent = $request->type;
                $audit->ipAddress = $request->ip();
                $audit->userAgent = $request->userAgent();
                $audit->createdBy = $user->id;
                $audit->save();
            } catch (\Throwable $th) {
                Log::error($th->getMessage());
            }
        }

        $response = $forum->load(
            'category',
            'creator',
            'reactions',
            'reactionsUp',
            'reactionsDown',
            'reactionsHeart',
            'reactions.user',
            'comments',
            'comments.user'
        );

        $this->flushCacheTag('forums');

        return response()->json($response, 200);
    }

    function deleteComment($commentId, Request $request)
    {
        $user = Auth::user();

        try {
            $request->merge(['commentId' => $commentId]);
            $request->validate([
                'commentId' => 'required|uuid'
            ]);

            $comment = ForumComments::find($commentId);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            $canDelete = $this->hasPermission('FORUM_DELETE_COMMENT');
            $isOwner = $comment->user_id === $user->id;

            if (!$canDelete && !$isOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete comments.'
                ], 403);
            }

            $forumId = $comment->forum_id;
            $commentContent = $comment->comment;

            $comment->delete();

            // audit trail (non-blocking)
            try {
                $audit = new ResponseAuditTrails();
                $audit->forumId = $forumId;
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

            $this->flushCacheTag('forums');

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Comment deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment'
            ], 500);
        }
    }
}
