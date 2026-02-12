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

class ForumsController extends Controller
{

    function getAll(Request $request)
    {
        $user = Auth::user();
        $limit = $request->limit;
        $banner = $request->banner;

        $query = Forums::orderBy('created_at', 'DESC')
            ->with('category', 'creator','reactions','comments', 'tags')
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

        $blog = $query->get();
        return response()->json($blog, 200);
    }

    function getOne($id)
    {
        $blog = Forums::where('id', $id)->with('category', 'creator',  'reactions', 'reactionsUp', 'reactionsDown', 'reactionsHeart', 'reactions.user', 'comments', 'comments.user', 'tags')->first();
        return response()->json($blog, 200);
    }

    function create(Request $request)
    {

        $user = Auth::user();
        $tags = $request->tags;

        try {

            $forum = new Forums();
            $forum->title = $request->title;
            $forum->content = $request->content;
            $forum->privacy = $request->private ? 'private' : 'public';
            $forum->created_by = $user->id;
            $forum->category_id = $request->category;
            $forum->closed = false;
            $forum->save();

            if($tags && count($tags) > 0) {

                foreach ($tags as $key => $tag) {
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
            $forum->content = $request->content;
            $forum->privacy = $request->private ? 'private' : 'public';
            $forum->created_by = $user->id;
            $forum->category_id = $request->category;
            $forum->closed = $request->closed;
            $forum->save();

            if($tags && count($tags) > 0) {

                Tags::where('forum_id',$forum->id)->delete();

                foreach ($tags as $key => $tag) {
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

    function delete($id)
    {
        $category = Forums::where('id', $id)->first();
        $category->delete();

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

        // Create audit trail entry (non-blocking after comment creation successful)
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

        $response = $forum->load('category', 'creator',  'reactions', 'reactionsUp', 'reactionsDown', 'reactionsHeart', 'reactions.user', 'comments', 'comments.user');

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
                // Delete reaction
                $forumReaction->delete();
                
                // Create audit trail entry
                $audit = new ResponseAuditTrails();
                $audit->forumId = $forum->id;
                $audit->responseId = $forumReaction->id;
                $audit->responseType = 'reaction';
                $audit->operationName = 'Deleted';
                $audit->responseContent = $request->type;
                $audit->ipAddress = $request->ip();
                $audit->userAgent = $request->userAgent();
                $audit->save();
            } else {
                // Store previous type for audit
                $previousType = $forumReaction->type;
                
                $forumReaction->forum_id = $forum->id;
                $forumReaction->type = $request->type;
                $forumReaction->user_id = $user->id;
                $forumReaction->save();
                
                // Create audit trail entry
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
            }
        } else {
            $reaction = new ForumReactions();
            $reaction->forum_id = $forum->id;
            $reaction->user_id = $user->id;
            $reaction->type = $request->type;
            $reaction->save();
            
            // Create audit trail entry
            $audit = new ResponseAuditTrails();
            $audit->forumId = $forum->id;
            $audit->responseId = $reaction->id;
            $audit->responseType = 'reaction';
            $audit->operationName = 'Created';
            $audit->responseContent = $request->type;
            $audit->ipAddress = $request->ip();
            $audit->userAgent = $request->userAgent();
            $audit->save();
        }

        $response = $forum->load('category', 'creator',  'reactions', 'reactionsUp', 'reactionsDown', 'reactionsHeart', 'reactions.user', 'comments', 'comments.user');

        return response()->json($response, 200);
    }

    function deleteComment($commentId, Request $request)
    {
        try {
            // Validate comment ID
            $request->merge(['commentId' => $commentId]);
            $request->validate([
                'commentId' => 'required|uuid|exists:forum_comments,id'
            ]);

            $user = Auth::user();
            $comment = ForumComments::findOrFail($commentId);
            
            // Check permissions
            $canDelete = false;
            
            // User can delete their own comments
            if ($comment->user_id === $user->id) {
                $canDelete = true;
            }
            
            // Admin can delete any comment
            if ($user->hasClaim('FORUM_DELETE_COMMENT')) {
                $canDelete = true;
            }
            
            if (!$canDelete) {
                return response()->json([
                    'success' => false,
                    'message' => 'You don\'t have permission to delete this comment'
                ], 403);
            }

            // Get forum ID before deletion for audit trail
            $forumId = $comment->forum_id;
            $commentContent = $comment->comment;
            
            // Delete the comment
            $comment->delete();
            
            // Delete the comment
            $comment->delete();
            
            // Create audit trail entry (non-blocking after deletion successful)
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
            throw $e; // Let Laravel handle validation errors (422 response)
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
