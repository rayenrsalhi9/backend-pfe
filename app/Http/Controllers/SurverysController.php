<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Surveys;
use Illuminate\Http\Request;
use App\Models\SurveyAnswers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SurverysController extends Controller
{
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

        $survey = $query->get();
        return response()->json($survey, 200);
    }

    function getLast()
    {
        $survey = Surveys::where('closed', false)->orderBy('created_at', 'DESC')->latest()->first();
        return response()->json($survey, 200);
    }

    function getOne($id)
    {
        $survey = Surveys::where('id', $id)->with('creator')->first();

        return response()->json($survey, 200);
    }

    function statistics($id) {

        try {
            $groupedSurveyAnswers = SurveyAnswers::where('survey_id',$id)->select([
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

        $answerExist = SurveyAnswers::where(['survey_id' => $survey->id, 'user_id' => $user->id])->first();
        if ($answerExist) return response()->json([
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
            $survey->created_by = $user->id;
            $survey->start_date = $request->startDate;
            $survey->end_date = $request->endDate;
            $survey->save();

            $response = $survey->load('creator');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function delete($id)
    {
        $survey = Surveys::where('id', $id)->first();
        $survey->delete();

        return response()->json('successfully deleted', 200);
    }
}
