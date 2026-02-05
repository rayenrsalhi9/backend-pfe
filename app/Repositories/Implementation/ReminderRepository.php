<?php

namespace App\Repositories\Implementation;

use App\Models\DailyReminders;
use App\Models\FrequencyEnum;
use App\Models\HalfYearlyReminders;
use App\Models\QuarterlyReminders;
use App\Models\Reminders;
use App\Models\ReminderUsers;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\ReminderRepositoryInterface;
use App\Repositories\Exceptions\RepositoryException;
use Illuminate\Support\Facades\DB;

class ReminderRepository extends BaseRepository implements ReminderRepositoryInterface
{

    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return Reminders::class;
    }
    public function getReminders($attributes)
    {
        $query = Reminders::select([
            'reminders.createdDate', 'reminders.startDate', 'reminders.endDate', 'reminders.id', 'reminders.subject', 'reminders.message',
            'reminders.frequency', 'reminders.documentId', 'documents.name as documentName'
        ])->leftjoin('documents', 'reminders.documentId', '=', 'documents.id');

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'subject') {
            $query = $query->orderBy('subject', $direction);
        } else if ($orderBy == 'message') {
            $query = $query->orderBy('message', $direction);
        } else if ($orderBy == 'startDate') {
            $query = $query->orderBy('startDate', $direction);
        } else if ($orderBy == 'endDate') {
            $query = $query->orderBy('endDate', $direction);
        } else if ($orderBy == 'documentName') {
            $query = $query->orderBy('documents.name', $direction);
        }

        if ($attributes->subject) {
            $query = $query->where('subject',  'like', '%' . $attributes->subject . '%');
        }

        if ($attributes->message) {
            $query = $query->where('message', 'like', '%' . $attributes->message . '%');
        }

        if ($attributes->frequency != '') {
            $query = $query->where('frequency', $attributes->frequency);
        }
        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();
        return $results;
    }

    public function getRemindersCount($attributes)
    {
        $query = Reminders::query()
            ->leftjoin('documents', 'reminders.documentId', '=', 'documents.id');


        if ($attributes->subject) {
            $query = $query->where('subject', $attributes->subject);
        }

        if ($attributes->message) {
            $query = $query->where('message', 'like', '%' . $attributes->message . '%');
        }
        if ($attributes->frequency != '') {
            $query = $query->where('frequency',  $attributes->frequency);
        }

        $count = $query->count();
        return $count;
    }

    public function addReminders($request)
    {
        try {
            DB::beginTransaction();

            if ($request['frequency'] == '') {
                $request['frequency'] = FrequencyEnum::OneTime->value;
            }

            if (!$request['isRepeated']) {
                $request['frequency'] = FrequencyEnum::OneTime->value;
            }

            $model = $this->model->newInstance($request);
            $saved = $model->save();

            if ($request['reminderUsers']) {
                $model->reminderUsers()->createMany($request['reminderUsers']);
            } else {
                $userId = Auth::parseToken()->getPayload()->get('userId');
                $model->reminderUsers()->createMany(array(['userId' => $userId, 'reminderId' => $model->id]));
            }

            if (isset($request['dailyReminders'])) {
                $model->dailyReminders()->createMany($request['dailyReminders']);
            }

            if (isset($request['halfYearlyReminders'])) {
                $model->halfYearlyReminders()->createMany($request['halfYearlyReminders']);
            }

            if (isset($request['quarterlyReminders'])) {
                $model->quarterlyReminders()->createMany($request['quarterlyReminders']);
            }

            DB::commit();
            return $saved;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }

    public function updateReminders($request, $id)
    {
        try {
            DB::beginTransaction();
            $model = $this->model->find($id);
            $model->subject = $request->subject;
            $model->documentId = $request->documentId;
            $model->message = $request->message;
            $model->frequency = $request->frequency;
            $model->dayOfWeek = $request->dayOfWeek;
            $model->isRepeated = $request->isRepeated;
            $model->isEmailNotification = $request->isEmailNotification;
            $model->startDate = $request->startDate;
            $model->endDate = $request->endDate;
            $reminderUsers = $request->reminderUsers;
            $dailyReminders = $request->dailyReminders;
            $halfYearlyReminders = $request->halfYearlyReminders;
            $quarterlyReminders = $request->quarterlyReminders;

            $saved = $model->save();
            $this->resetModel();
            $result = $this->parseResult($model);

            $reminderUser = ReminderUsers::where('reminderId', '=', $id)->get('id');
            ReminderUsers::destroy($reminderUser);

            $dailyReminder = DailyReminders::where('reminderId', '=', $id)->get('id');
            DailyReminders::destroy($dailyReminder);

            $halfYearlyReminder = HalfYearlyReminders::where('reminderId', '=', $id)->get('id');
            HalfYearlyReminders::destroy($halfYearlyReminder);

            $quarterlyReminder = QuarterlyReminders::where('reminderId', '=', $id)->get('id');
            QuarterlyReminders::destroy($quarterlyReminder);

            if ($reminderUsers) {
                $model->reminderUsers()->createMany($reminderUsers);
            } else {
                $userId = Auth::parseToken()->getPayload()->get('userId');
                $model->reminderUsers()->createMany(array(['userId' => $userId, 'reminderId' => $model->id]));
            }

            if (isset($dailyReminders)) {
                $model->dailyReminders()->createMany($dailyReminders);
            }

            if (isset($halfYearlyReminders)) {
                $model->halfYearlyReminders()->createMany($halfYearlyReminders);
            }

            if (isset($quarterlyReminders)) {
                $model->quarterlyReminders()->createMany($quarterlyReminders);
            }

            if (!$saved) {
                throw new RepositoryException('Error in saving data.');
            }
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }

    public function findReminder($id)
    {
        $model = $this->model->with('reminderUsers')
            ->with('quarterlyReminders')
            ->with('halfYearlyReminders')
            ->with('dailyReminders')->findOrFail($id);
        $this->resetModel();
        return $this->parseResult($model);
    }

    public function  getReminderForLoginUser($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $query = Reminders::select([
            'reminders.createdDate', 'reminders.startDate', 'reminders.endDate', 'reminders.id', 'reminders.subject', 'reminders.message',
            'reminders.frequency', 'reminders.documentId', 'documents.name as documentName'
        ])
            ->leftJoin('documents', function ($join) {
                $join->on('reminders.documentId', '=', 'documents.id')
                    ->where('documents.isDeleted', '=', false);
            })
            ->orWhereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            });

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'subject') {
            $query = $query->orderBy('subject', $direction);
        } else if ($orderBy == 'message') {
            $query = $query->orderBy('message', $direction);
        } else if ($orderBy == 'startDate') {
            $query = $query->orderBy('startDate', $direction);
        } else if ($orderBy == 'endDate') {
            $query = $query->orderBy('endDate', $direction);
        } else if ($orderBy == 'documentName') {
            $query = $query->orderBy('documents.name', $direction);
        }

        if ($attributes->subject) {
            $query = $query->where('subject',  'like', '%' . $attributes->subject . '%');
        }

        if ($attributes->message) {
            $query = $query->where('message', 'like', '%' . $attributes->message . '%');
        }

        if ($attributes->frequency != '') {
            $query = $query->where('frequency', $attributes->frequency);
        }
        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();
        return $results;
    }

    public function getReminderForLoginUserCount($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $query = Reminders::query()
            ->leftJoin('documents', function ($join) {
                $join->on('reminders.documentId', '=', 'documents.id')
                    ->where('documents.isDeleted', '=', false);
            })
            ->orWhereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            });

        if ($attributes->subject) {
            $query = $query->where('subject', $attributes->subject);
        }

        if ($attributes->message) {
            $query = $query->where('message', 'like', '%' . $attributes->message . '%');
        }
        if ($attributes->frequency != '') {
            $query = $query->where('frequency',  $attributes->frequency);
        }

        $count = $query->count();
        return $count;
    }

    public function deleteReminderCurrentUser($id)
    {

        $userId = Auth::parseToken()->getPayload()->get('userId');
        return  ReminderUsers::where('reminderId', '=', $id)->Where('userId', '=', $userId)->delete();
    }
}
