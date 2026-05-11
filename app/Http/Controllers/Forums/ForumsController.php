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
use Illuminate\Support\Facades\DB;

class ForumsController extends Controller
{
    use HasPermissionTrait;
    use CacheableTrait;

    function getAll(Request $request)
    {
        $viewer = Auth::id() ?? 'guest';
        $cacheKey = $this->getCacheKey('forums', 'list', $this->normalizeRequestParams($request->all()), $viewer);
        $ttl = $this->getCacheTtl('forums');

        $forums = $this->cacheRemember($cacheKey, 'forums', $ttl, function () use ($request) {
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
                });

            $this->applyForumFilters($query, $request);

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
        $query = Forums::orderBy('created_at', 'DESC')
            ->with(['creator' => function ($q) {
                $q->select('users.id', 'users.firstName', 'users.lastName', 'users.userName', 'users.avatar', 'users.isDeleted')->withoutGlobalScope('isDeleted');
            }])
            ->with('category', 'allowedUsers')
            ->withCount(['reactions', 'comments']);

        $this->applyForumFilters($query, $request);

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

        if (!$this->userCanAccessForum($forum, Auth::user())) {
            return response()->json(['message' => 'Not found'], 404);
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
            DB::transaction(function () use ($request, $user, $tags, &$response) {
                $forum = new Forums();
                $forum->title = $request->title;
                $forum->content = $request->input('content');
                $isPrivate = $request->boolean('private');
                $forum->privacy = $isPrivate ? 'private' : 'public';
                $forum->created_by = $user->id;
                $forum->category_id = $request->category;
                $forum->closed = false;
                $forum->save();

                if ($isPrivate) {
                    $users = is_array($request->users) ? $request->users : [];
                    if (!in_array($user->id, $users)) {
                        $users[] = $user->id;
                    }
                    foreach ($users as $userId) {
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

                $response = $forum->load('category', 'creator', 'tags', 'allowedUsers');
            });

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

            DB::transaction(function () use ($forum, $id, $request, $user, $tags, &$response) {
                $forum->title = $request->title;
                $forum->content = $request->input('content');
                $forum->category_id = $request->category;
                $forum->closed = $request->closed;
                $forum->save();

                if ($request->has('private')) {
                    $isPrivate = $request->boolean('private');
                    $forum->privacy = $isPrivate ? 'private' : 'public';
                    $forum->save();

                    ForumUsers::where('forum_id', $forum->id)->delete();

                    if ($isPrivate) {
                        $users = is_array($request->users) ? $request->users : [];
                        if (!in_array($user->id, $users)) {
                            $users[] = $user->id;
                        }
                        foreach ($users as $userId) {
                            $forumUser = new ForumUsers();
                            $forumUser->forum_id = $forum->id;
                            $forumUser->user_id = $userId;
                            $forumUser->save();
                        }
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

                $response = $forum->load('category', 'creator', 'tags', 'allowedUsers');
            });

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

        if (!$this->userCanAccessForum($forum, Auth::user())) {
            return response()->json(['message' => 'Not found'], 404);
        }

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

        if (!$this->userCanAccessForum($forum, Auth::user())) {
            return response()->json(['message' => 'Not found'], 404);
        }

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
    private function applyForumFilters($query, Request $request)
    {
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

        if ($request->limit) {
            $query->take($request->limit);
        }

        return $query;
    }

    private function userCanAccessForum(Forums $forum, $user): bool
    {
        if ($forum->privacy !== 'private') {
            return true;
        }

        if (!$user) {
            return false;
        }

        $isAllowed = $forum->allowedUsers()->where('user_id', $user->id)->exists();
        $isCreator = $forum->created_by === $user->id;

        return $isAllowed || $isCreator;
    }
}
