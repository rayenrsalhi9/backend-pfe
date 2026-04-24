<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Surveys;
use Illuminate\Http\Request;
use App\Models\SurveyAnswers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Traits\HasPermissionTrait;

class SurverysController extends Controller
{
    use HasPermissionTrait;
    function getAll(Request $request)
    {
        $user = Auth::user();
        $limit = $request->limit;
        $privacy = $request->privacy;
        $type = $request->type;

        $query = Surveys::orderBy('created_at', 'DESC')
            ->with('creator', 'answers')
            ->when($limit, function ($query) use ($limit) {
                return $query->take($limit);
            })
            ->when($privacy, function ($query) use ($privacy) {
                return $query->where('privacy', $privacy);
            });

        if ($request->title) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($type) {
            $query->where('type', $request->type);
        }

        if ($request->createdAt) {
            $startDate = Carbon::parse($request->createdAt)->setTimezone('UTC');
            $endDate = Carbon::parse($request->createdAt)->setTimezone('UTC')->addDays(1)->addSeconds(-1);

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $surveys = $query->get()->filter(function ($survey) use ($user) {
            if (!$survey) return false;
            if ($survey->privacy !== 'private') return true;
            if (!$user) return false;
            $allowedUsers = is_array($survey->users) ? $survey->users : json_decode($survey->users ?? '[]', true);
            return in_array($user->id, $allowedUsers);
        })->values();

        return response()->json($surveys, 200);
    }

    function getLast()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(null, 200);
        }

        $query = Surveys::where('closed', false)->latest()
            ->whereDoesntHave('answers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        $survey = $query->first();

        if ($survey && $survey->privacy === 'private') {
            $allowedUsers = is_array($survey->users) ? $survey->users : json_decode($survey->users ?? '[]', true);
            if (!in_array($user->id, $allowedUsers)) {
                return response()->json(null, 200);
            }
        }

        return response()->json($survey, 200);
    }

    function getOne($id)
    {
        $survey = Surveys::where('id', $id)->with('creator')->first();
        
        if ($survey && $survey->users) {
            $survey->users = is_array($survey->users) ? $survey->users : json_decode($survey->users, true);
        }

        return response()->json($survey, 200);
    }

    function statistics($id)
    {

        try {
            $groupedSurveyAnswers = SurveyAnswers::where('survey_id', $id)->select([
                DB::raw("MONTH(surveys.created_at) as month"),  // Group by month
                'surveys.type',  // Group by survey type
                'survey_answers.answer',  // Group by answer
                DB::raw('COUNT(survey_answers.id) as count')  // Count the number of answers
            ])
                ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')  // Join surveys with survey answers
                ->groupBy('month', 'surveys.type', 'survey_answers.answer')  // Group by month, type, and answer
                ->get();

            return response()->json($groupedSurveyAnswers, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function create(Request $request)
    {

        $user = Auth::user();

        try {

            $survey = new Surveys();
            $survey->title = $request->title;
            $survey->type = $request->type;
            $survey->privacy = $request->private ? 'private' : 'public';
            $survey->blog = $request->blog;
            $survey->forum = $request->forum;
            $survey->created_by = $user->id;
            $survey->start_date = $request->startDate;
            $survey->end_date = $request->endDate;
            $survey->save();

            if ($request->private && $request->users) {
                $users = is_array($request->users) ? $request->users : json_decode($request->users, true);
                if (!in_array($user->id, $users)) {
                    $users[] = $user->id;
                }
                $survey->users = $users;
                $survey->save();
            } elseif ($request->private) {
                $survey->users = [$user->id];
                $survey->save();
            }

            $response = $survey->load('creator');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function answer($id, Request $request)
    {

        $survey = Surveys::where('id', $id)->first();
        $user = Auth::user();
        
        if (!$survey) {
            return response()->json(['message' => 'Survey not found'], 404);
        }
        
        if ($survey->privacy === 'private') {
            $allowedUsers = is_array($survey->users) ? $survey->users : json_decode($survey->users ?? '[]', true);
            if (!in_array($user->id, $allowedUsers)) {
                return response()->json(['message' => 'You are not authorized to answer this survey'], 403);
            }
        }

        $answerExist = SurveyAnswers::where(['survey_id' => $survey->id, 'user_id' => $user->id])->first();
        if ($answerExist)
            return response()->json([
                'message' => 'Survey already answered !',
            ], 409);

        $answer = new SurveyAnswers();
        $answer->survey_id = $survey->id;
        $answer->user_id = $user->id;
        $answer->answer = $request->answer;
        $answer->save();

        $response = $survey->load('answers', 'creator', 'answers.user');

        return response()->json($response, 200);
    }

    function update($id, Request $request)
    {

        $user = Auth::user();

        try {

            $survey = Surveys::where('id', $id)->first();
            $survey->title = $request->title;
            $survey->type = $request->type;
            $survey->privacy = $request->private ? 'private' : 'public';
            $survey->blog = $request->blog;
            $survey->forum = $request->forum;
            $survey->start_date = $request->startDate;
            $survey->end_date = $request->endDate;
            $survey->save();

            if ($request->private && $request->users) {
                $users = is_array($request->users) ? $request->users : json_decode($request->users, true);
                $creatorId = $survey->created_by;
                if ($creatorId && !in_array($creatorId, $users)) {
                    $users[] = $creatorId;
                }
                $survey->users = $users;
            } else {
                $survey->users = null;
            }
            $survey->save();

            $response = $survey->load('creator');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function delete($id)
    {
        if (!$this->hasPermission('SURVEY_DELETE_SURVEY')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete surveys.'
            ], 403);
        }

        $survey = Surveys::where('id', $id)->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Survey not found'
            ], 404);
        }

        $survey->delete();

        return response()->json('successfully deleted', 200);
    }
}
