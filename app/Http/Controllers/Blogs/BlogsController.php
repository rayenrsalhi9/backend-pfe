<?php

namespace App\Http\Controllers\Blogs;

use App\Models\Blogs;
use Ramsey\Uuid\Uuid;
use App\Models\ArticleUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Models\BlogComments;
use App\Models\BlogReactions;
use App\Models\Tags;
use Illuminate\Support\Facades\Auth;

class BlogsController extends Controller
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
        $user = Auth::user();
        $limit = $request->limit;
        $banner = $request->banner;
        $grouped = $request->grouped;

        $query = Blogs::orderBy('created_at', 'DESC')
            ->with('category', 'creator', 'tags')
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
        return response()->json($blog, 200);
    }

    function getOne($id)
    {
        $blog = Blogs::where('id', $id)->with('category', 'creator', 'reactions', 'reactionsUp', 'reactionsDown', 'reactions.user', 'comments', 'comments.user', 'tags')->first();
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

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function delete($id)
    {
        $category = Blogs::where('id', $id)->first();
        $category->delete();

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

        return response()->json($response, 200);
    }
}
