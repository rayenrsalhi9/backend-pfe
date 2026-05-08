<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Surveys;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use Illuminate\Http\Request;
use App\Models\SurveyAnswers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Traits\HasPermissionTrait;
use App\Traits\CacheableTrait;

class SurverysController extends Controller
{
    use HasPermissionTrait;
    use CacheableTrait;
    function getAll(Request $request)
    {
        $viewer = Auth::id() ?? 'guest';
        $cacheKey = $this->getCacheKey('surveys', 'list', md5(json_encode($request->all())), $viewer);
        $ttl = $this->getCacheTtl('surveys');

        $surveys = $this->cacheRemember($cacheKey, 'surveys', $ttl, function () use ($request) {
            $user = Auth::user();
            $limit = $request->limit;
            $privacy = $request->privacy;
            $type = $request->type;

            $query = Surveys::orderBy('created_at', 'DESC')
                ->with('creator', 'answers')
                ->when($privacy, function ($query) use ($privacy) {
                    return $query->where('privacy', $privacy);
                })
                ->where(function ($q) use ($user) {
                    $q->where('privacy', '!=', 'private')
                        ->orWhere(function ($q2) use ($user) {
                            if ($user) {
                                $q2->where('privacy', 'private')
                                    ->whereJsonContains('users', $user->id);
                            } else {
                                $q2->whereRaw('1 = 0');
                            }
                        });
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

            $query->when($limit, function ($query) use ($limit) {
                return $query->take($limit);
            });

            return $query->get();
        });

        return response()->json($surveys, 200);
    }

    function getAllForDashboard(Request $request)
    {
        $user = Auth::user();
        $limit = $request->limit;
        $type = $request->type;
        $privacy = $request->privacy;

        $query = Surveys::orderBy('created_at', 'DESC')
            ->with('creator', 'answers')
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

        if ($limit) {
            $query->take($limit);
        }

        $surveys = $query->get();

        return response()->json($surveys, 200);
    }

    function getLast()
    {
        $viewer = Auth::id() ?? 'guest';
        $cacheKey = $this->getCacheKey('surveys', 'latest', $viewer);
        $ttl = $this->getCacheTtl('surveys');

        $survey = $this->cacheRemember($cacheKey, 'surveys', $ttl, function () {
            $user = Auth::user();

            $query = Surveys::where('closed', false)
                ->when($user, function ($q) use ($user) {
                    $q->whereDoesntHave('answers', function ($subQ) use ($user) {
                        $subQ->where('user_id', $user->id);
                    });
                })
                ->where(function ($q) use ($user) {
                    if ($user) {
                        $q->where('privacy', 'public')
                            ->orWhere(function ($q2) use ($user) {
                                $q2->where('privacy', 'private')
                                    ->whereJsonContains('users', $user->id);
                            });
                    } else {
                        $q->where('privacy', 'public');
                    }
                })
                ->latest();

            return $query->first();
        });

        return response()->json($survey, 200);
    }

    function getOne($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $survey = Surveys::where('id', $id)
            ->with('creator')
            ->withCount('answers')
            ->first();

        if (!$survey) {
            return response()->json(['message' => 'Survey not found'], 404);
        }

        $isPublic = $survey->privacy !== 'private';
        $isCreator = $survey->created_by === $user->id;
        $isAllowedUser = in_array($user->id, $survey->users ?? []);

        if (!$isPublic && !$isCreator && !$isAllowedUser) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $survey->toArray();
        $data['answersCount'] = (int) $survey->answers_count;

        return response()->json($data, 200);
    }

    function statistics($id)
    {
        $survey = Surveys::where('id', $id)->first();

        if (!$survey) {
            return response()->json(['message' => 'Survey not found'], 404);
        }

        $user = Auth::user();
        $isCreator = $survey->created_by === $user->id;
        $hasPermission = $this->hasPermission('SURVEY_VIEW_STATISTICS');

        if (!$isCreator && !$hasPermission) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $groupedSurveyAnswers = SurveyAnswers::where('survey_id', $id)->select([
                DB::raw("MONTH(survey_answers.created_at) as month"),
                'surveys.type',
                'survey_answers.answer',
                DB::raw('COUNT(survey_answers.id) as count')
            ])
                ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
                ->groupBy('month', 'surveys.type', 'survey_answers.answer')
                ->get();

            return response()->json($groupedSurveyAnswers, 200);
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }

    function create(StoreSurveyRequest $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $isPrivate = $request->privacy === 'private';

            $users = [];
            if ($request->has('users')) {
                $inputUsers = $request->users;
                if (is_string($inputUsers)) {
                    $inputUsers = json_decode($inputUsers, true) ?? [];
                }
                if (!is_array($inputUsers)) {
                    $inputUsers = [];
                }
                $users = array_unique($inputUsers);
                $users = array_values(array_filter($users));
            }
            if ($user && !in_array($user->id, $users)) {
                $users[] = $user->id;
            }
            $users = array_values($users);

            $survey = new Surveys();
            $survey->title = $request->title;
            $survey->type = $request->type;
            $survey->privacy = $isPrivate ? 'private' : 'public';
            $survey->blog = $request->boolean('blog', true);
            $survey->forum = $request->boolean('forum', true);
            $survey->created_by = $user->id;
            $survey->start_date = $request->startDate;
            $survey->end_date = $request->endDate;
            $survey->users = $isPrivate ? $users : null;
            $survey->save();

            $this->flushCacheTag('surveys');

            $response = $survey->load('creator');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the survey.'
            ], 500);
        }
    }

    function answer($id, Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $survey = Surveys::where('id', $id)->first();

        if (!$survey) {
            return response()->json(['message' => 'Survey not found'], 404);
        }

        if ($survey->privacy === 'private') {
            $isCreator = $survey->created_by === $user->id;
            $allowedUsers = is_array($survey->users) ? $survey->users : json_decode($survey->users ?? '[]', true);
            if (!$isCreator && !in_array($user->id, $allowedUsers)) {
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

        $this->flushCacheTag('surveys');

        $response = $survey->load('answers', 'creator', 'answers.user');

        return response()->json($response, 200);
    }

    function update($id, UpdateSurveyRequest $request)
    {
        $user = Auth::user();

        $survey = Surveys::where('id', $id)->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Survey not found'
            ], 404);
        }

        $isOwner = $user && $survey->created_by === $user->id;
        $hasPermission = $this->hasPermission('SURVEY_EDIT_SURVEY');

        if (!$isOwner && !$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this survey.'
            ], 403);
        }

        try {
            if ($request->filled('title')) {
                $survey->title = $request->title;
            }
            if ($request->filled('type')) {
                $survey->type = $request->type;
            }

            if ($request->has('privacy')) {
                $isPrivate = $request->privacy === 'private';
                $survey->privacy = $isPrivate ? 'private' : 'public';

                if ($isPrivate) {
                    $users = $request->has('users') ? $request->users : $survey->users;
                    if (is_string($users)) {
                        $users = json_decode($users, true) ?? [];
                    }
                    if (!is_array($users)) {
                        $users = [];
                    }
                    $users = array_unique($users);
                    $users = array_filter($users);

                    if (!in_array($survey->created_by, $users)) {
                        $users[] = $survey->created_by;
                    }

                    $survey->users = array_values($users);
                } else {
                    $survey->users = [];
                }
            }

            if ($request->has('blog')) {
                $survey->blog = $request->boolean('blog', $survey->blog);
            }
            if ($request->has('forum')) {
                $survey->forum = $request->boolean('forum', $survey->forum);
            }
            if ($request->filled('startDate')) {
                $survey->start_date = $request->startDate;
            }
            if ($request->filled('endDate')) {
                $survey->end_date = $request->endDate;
            }

            $survey->save();

            $this->flushCacheTag('surveys');

            $response = $survey->load('creator');

            return response()->json($response, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the survey.'
            ], 500);
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

        $this->flushCacheTag('surveys');

        return response()->json('successfully deleted', 200);
    }
}
