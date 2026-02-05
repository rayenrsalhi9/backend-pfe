<?php

namespace App\Http\Controllers;

use App\Models\Articles;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicArticlesController extends Controller
{
    // public function getAllPublic(Request $request)
    
    // {
      
    //     $limit = $request->limit;
    //     $query = Articles::orderBy('created_at', 'DESC')
    //         ->with('category', 'creator')
    //         ->where('privacy', 'public') // Seulement les articles publics
    //         ->when($limit, function ($query) use ($limit) {
    //             return $query->take($limit);
    //         });

    //     if ($request->name) {
    //         $query->where('title', 'like', '%' . $request->name . '%')
    //               ->orWhere('short_text', 'like', '%' . $request->name . '%');
    //     }

    //     if ($request->articleCategoryId) {
    //         $query->where('article_category_id', $request->articleCategoryId);
    //     }

    //     if ($request->createdAt) {
    //         $startDate = Carbon::parse($request->createdAt)->setTimezone('UTC');
    //         $endDate = Carbon::parse($request->createdAt)->setTimezone('UTC')->addDays(1)->addSeconds(-1);

    //         $query->whereBetween('created_at', [$startDate, $endDate]);
    //     }

    //     $articles = $query->get();
    //     return response()->json($articles, 200);
    // }
}
