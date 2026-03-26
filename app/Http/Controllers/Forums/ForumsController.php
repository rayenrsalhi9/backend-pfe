<?php

namespace App\Http\Controllers\Forums;

use App\Http\Controllers\Controller;
use App\Models\ForumComments;
use App\Models\ForumReactions;
use App\Models\Forums;
use App\Models\Tags;
use App\Models\ResponseAuditTrails;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ForumsController extends Controller
{
    /**
     * True if authenticated user has role: roles.name = "Super Admin"
     * Pivot table in your DB is: userRoles (NOT user_roles)
     * We still auto-detect columns to be safe.
     */
    private function currentUserIsSuperAdmin(): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        // detect pivot table
        $pivotTable = null;
        $candidates = class_exists(\App\Models\UserRoles::class) 
            ? [(new \App\Models\UserRoles())->getTable(), 'userroles', 'user_roles', 'role_user', 'users_roles']
            : ['userRoles', 'userroles', 'user_roles', 'role_user', 'users_roles'];
        foreach ($candidates as $candidate) {
            if (Schema::hasTable($candidate)) {
                $pivotTable = $candidate;
                break;
            }
        }
        if (!$pivotTable || !Schema::hasTable('roles'))
            return false;

        // detect columns
        $userCol = null;
        foreach (['userId', 'user_id', 'userid', 'userID'] as $c) {
            if (Schema::hasColumn($pivotTable, $c)) {
                $userCol = $c;
                break;
            }
        }

        $roleCol = null;
        foreach (['roleId', 'role_id', 'roleid', 'roleID'] as $c) {
            if (Schema::hasColumn($pivotTable, $c)) {
                $roleCol = $c;
                break;
            }
        }

        if (!$userCol || !$roleCol)
            return false;

        $q = DB::table($pivotTable)
            ->join('roles', 'roles.id', '=', $pivotTable . '.' . $roleCol)
            ->where($pivotTable . '.' . $userCol, '=', $user->id)
            ->whereRaw('LOWER(roles.name) = ?', [strtolower('Super Admin')]);

        // consider active role rows if these columns exist
        if (Schema::hasColumn('roles', 'isDeleted')) {
            $q->where('roles.isDeleted', 0);
        }
        if (Schema::hasColumn('roles', 'deleted_at')) {
            $q->whereNull('roles.deleted_at');
        }

        // consider active pivot rows if these columns exist
        if (Schema::hasColumn($pivotTable, 'isDeleted')) {
            $q->where($pivotTable . '.isDeleted', 0);
        }
        if (Schema::hasColumn($pivotTable, 'deleted_at')) {
            $q->whereNull($pivotTable . '.deleted_at');
        }

        return $q->exists();
    }

    function getAll(Request $request)
    {
        $limit = $request->limit;
        $banner = $request->banner;

        $query = Forums::orderBy('created_at', 'DESC')
            ->with('category', 'creator', 'reactions', 'comments', 'tags')
            ->withCount(['reactions', 'comments'])
            ->when($limit, function ($query) use ($limit) {
                return $query->take($limit);
            })
            ->when($banner, function ($query) use ($banner) {
                return $query->where('banner', boolval($banner));
            });

        if ($request->closed) {
            $query->where('closed', 'like', $request->closed);
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

        // expose capability so UI can show delete button only for super admin
        $canDelete = $this->currentUserIsSuperAdmin();
        foreach ($forums as $f) {
            $f->setAttribute('canDeleteComments', $canDelete);
            $f->setAttribute('can_delete_comments', $canDelete);
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
                'tags'
            )
            ->withCount(['reactions', 'comments'])
            ->first();

        if ($forum) {
            $canDelete = $this->currentUserIsSuperAdmin();
            $forum->setAttribute('canDeleteComments', $canDelete);
            $forum->setAttribute('can_delete_comments', $canDelete);
        }

        return response()->json($forum, 200);
    }

    function create(Request $request)
    {
        $user = Auth::user();
        $tags = $request->tags;

        try {
            $forum = new Forums();
            $forum->title = $request->title;
            $forum->content = $request->input('content');
            $forum->privacy = $request->private ? 'private' : 'public';
            $forum->created_by = $user->id;
            $forum->category_id = $request->category;
            $forum->closed = false;
            $forum->save();

            if ($tags && count($tags) > 0) {
                foreach ($tags as $tag) {
                    $forumTag = new Tags();
                    $forumTag->forum_id = $forum->id;
                    $forumTag->metatag = $tag['label'];
                    $forumTag->created_by = $user->id;
                    $forumTag->save();
                }
            }

            $response = $forum->load('category', 'creator', 'tags');
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
            $forum = Forums::where('id', $id)->first();
            $forum->title = $request->title;
            $forum->content = $request->input('content');
            $forum->privacy = $request->private ? 'private' : 'public';
            $forum->category_id = $request->category;
            $forum->closed = $request->closed;
            $forum->save();

            if ($tags !== null) {
                Tags::where('forum_id', $forum->id)->delete();

                if (count($tags) > 0) {
                    foreach ($tags as $tag) {
                        $forumTag = new Tags();
                        $forumTag->forum_id = $forum->id;
                        $forumTag->metatag = $tag['label'];
                        $forumTag->created_by = $user->id;
                        $forumTag->save();
                    }
                }
            }

            $response = $forum->load('category', 'creator', 'tags');
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
        return response()->json('successfully deleted', 200);
    }

    function addComment($id, Request $request)
    {
        $forum = Forums::where('id', $id)->first();
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

        return response()->json($response, 200);
    }

    function addReaction($id, Request $request)
    {
        $forum = Forums::where('id', $id)->first();
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

        return response()->json($response, 200);
    }

    function deleteComment($commentId, Request $request)
    {
        try {
            $request->merge(['commentId' => $commentId]);
            $request->validate([
                'commentId' => 'required|uuid|exists:forum_comments,id'
            ]);

            // ✅ Only Super Admin can delete comments
            if (!$this->currentUserIsSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Super Admin can delete comments.'
                ], 403);
            }

            $user = Auth::user();
            $comment = ForumComments::findOrFail($commentId);

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

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Comment deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment'
            ], 500);
        }
    }
}
