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

class UserController extends Controller
{
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

        $request['password'] = Hash::make($request->password);
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
        $model = $this->userRepository->find($id);
        $model->firstName = $request->firstName;
        $model->lastName = $request->lastName;
        $model->phoneNumber = $request->phoneNumber;
        $model->userName = $request->userName;
        $model->email = $request->email;
        $model->avatar = $request->avatar;

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
            'password' => 'required'
        ]);

        $user = Users::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        return  response()->json(($user), 204);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'oldPassword' => 'required',
            'newPassword' => 'required',
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

       if ($employes->isEmpty()) {
           return response()->json(['message' => 'Aucun employé trouvé'], 404);
       }

       return response()->json($employes);
   }

   
    public function getAllPublic(Request $request)
    
    {
      
        $limit = $request->limit;
        $query = Articles::orderBy('created_at', 'DESC')
            ->with('category', 'creator')
            ->where('privacy', 'public') // Seulement les articles publics
            ->when($limit, function ($query) use ($limit) {
                return $query->take($limit);
            });

        if ($request->name) {
            $query->where('title', 'like', '%' . $request->name . '%')
                  ->orWhere('short_text', 'like', '%' . $request->name . '%');
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
