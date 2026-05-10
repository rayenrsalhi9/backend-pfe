<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use App\Models\Employe;
use App\Models\Articles;
use Carbon\Carbon;

class UserController extends Controller
{
    use \App\Http\Controllers\Traits\HasPermissionTrait;
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        return response()->json($this->userRepository->all());
    }

    public function dropdown()
    {
        return response()->json($this->userRepository->getUsersForDropdown());
    }

    public function getUsersWithClaim(Request $request)
    {
        if (!$this->hasPermission('USER_VIEW_USERS')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view users.'
            ], 403);
        }

        $claimType = $request->query('claim', 'CHAT_VIEW_CHATS');
        return response()->json($this->userRepository->getUsersWithClaim($claimType));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'       => ['required', 'email', 'unique:' . (new Users())->getTable()],
            'firstName' =>   ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 409);
        }

        if ($request->password) {
            $validator = Validator::make($request->all(), [
                'password' => ['string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>~\-_=+\[\];\'\\\\\/])(?=.*[0-9]).{8,}$/'],
            ]);
            if ($validator->fails()) {
                return response()->json($validator->messages(), 409);
            }
            $request['password'] = Hash::make($request->password);
        }
        return  response()->json($this->userRepository->createUser($request->all()), 201);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->userRepository->findUser($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->except(['password']);
        $model = Users::findOrFail($id);
        $model->firstName = $request->firstName;
        $model->lastName = $request->lastName;
        $model->phoneNumber = $request->phoneNumber;
        $model->userName = $request->userName;
        $model->email = $request->email;
        $model->avatar = $request->avatar;
        $model->direction = $request->direction;

        return  response()->json($this->userRepository->updateUser($model, $id, $request['roleIds']), 200);
    }

    public function destroy($id)
    {
        $user = Users::findOrFail($id);
        $user->isDeleted = 1;
        $user->save();
        return response([], 204);
    }

    public function updateUserProfile(Request $request)
    {
        return  response()->json($this->userRepository->updateUserProfile($request), 200);
    }

    public function submitResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>~\-_=+\[\];\'\\\\\/])(?=.*[0-9]).{8,}$/']
        ]);

        $user = Users::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        return  response()->json(($user), 204);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'oldPassword' => 'required',
            'newPassword' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>~\-_=+\[\];\'\\\\\/])(?=.*[0-9]).{8,}$/'],
        ]);

        if (!(Hash::check($request->get('oldPassword'), Auth::user()->password))) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Old Password does not match!',
            ], 422);
        }

        Users::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->newPassword)
        ]);

        return response()->json([], 200);
    }
   // Méthode pour récupérer la liste des employés
    public function getEmployes()
    {
        $employes = Employe::all();

        return response()->json($employes);
    }

   
    public function getAllPublic(Request $request)
    {
        $user = Auth::user();
        $limit = $request->limit;
        $query = Articles::orderBy('created_at', 'DESC')
            ->with('category', 'creator')
            ->where(function ($query) use ($user) {
                $query->where('privacy', 'public');
                if ($user) {
                    $query->orWhere(function ($query) use ($user) {
                        $query->where('privacy', 'private')
                            ->where(function ($query) use ($user) {
                                $query->whereHas('users', function ($query) use ($user) {
                                    $query->where('user_id', $user->id);
                                });
                                $query->orWhere('created_by', $user->id);
                            });
                    });
                }
            })
            ->when($limit, function ($query) use ($limit) {
                return $query->take($limit);
            });

        if ($request->name) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->name . '%')
                      ->orWhere('short_text', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->articleCategoryId) {
            $query->where('article_category_id', $request->articleCategoryId);
        }

        if ($request->createdAt) {
            $startDate = Carbon::parse($request->createdAt)->setTimezone('UTC');
            $endDate = Carbon::parse($request->createdAt)->setTimezone('UTC')->addDays(1)->addSeconds(-1);

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $articles = $query->get();
        return response()->json($articles, 200);
    }
}
