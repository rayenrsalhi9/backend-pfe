<?php

namespace App\Http\Controllers\Forums;

use App\Http\Controllers\Controller;
use App\Models\ForumComments;
use App\Models\ForumReactions;
use App\Models\Forums;
use App\Models\Tags;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                $forumReaction->delete();
            } else {
                $forumReaction->forum_id = $forum->id;
                $forumReaction->type = $request->type;
                $forumReaction->user_id = $user->id;
                $forumReaction->save();
            }
        } else {
            $reaction = new ForumReactions();
            $reaction->forum_id = $forum->id;
            $reaction->user_id = $user->id;
            $reaction->type = $request->type;
            $reaction->save();
        }

        $response = $forum->load('category', 'creator',  'reactions', 'reactionsUp', 'reactionsDown', 'reactionsHeart', 'reactions.user', 'comments', 'comments.user');

        return response()->json($response, 200);
    }
}
