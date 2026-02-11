<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\LoginAudit;
use App\Mail\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repositories\Contracts\UserRepositoryInterface;

class AuthController extends Controller
{

    private $userRepository;
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $token = Auth::claims([])->attempt($credentials);
        $remoteIP = $this->getIp();

        $model = LoginAudit::create([
            'userName' => $request['email'],
            'loginTime' => Carbon::now(),
            'remoteIP' => $remoteIP,
            'status' => $token ? 'Success' : 'Error',
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude']
        ]);

        $model->save();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();

        $userActive = Users::find($user ->id);
        $userActive->isConnected = true;
        $userActive->save();

        $userClaimsFromRole =  DB::table('userRoles')
            ->select('roleClaims.claimType')
            ->leftJoin('roles', 'roles.id', '=', 'userRoles.roleId')
            ->leftJoin('roleClaims', 'roleClaims.roleId', '=', 'roles.id')
            ->where('userRoles.userId', '=', $user->id)
            ->get()
            ->toArray();

        $userIndividualClaims = DB::table('userClaims')
            ->select('claimType')
            ->where('userClaims.userId', '=', $user->id)
            ->get()
            ->toArray();

        $allClaimsObjArray = array_merge($userClaimsFromRole, $userIndividualClaims);

        $userClaims = array_map(function ($value) {
            return $value->claimType;
        }, $allClaimsObjArray);

        $user->claims = $userClaims;

        $token = Auth::claims(array('claims' => $userClaims, 'email' => $user->email, 'userId' => $user->id))->attempt($credentials);

        return response()->json([
            'status' => 'success',
            'claims' => $userClaims,
            'user' => [
                'id' => $user->id,
                'firstName' =>  $user->firstName,
                'lastName' => $user->lastName,
                'email' => $user->email,
                'userName' => $user->userName,
                'avatar' => $user->avatar,
                'phoneNumber' => $user->phoneNumber
            ],
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function logout(Request $request) {

        $userActive = Users::find($request->user);
        $userActive->isConnected = false;
        $userActive->save();
    }

    public function refresh()
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $token = Auth::getToken();
        $user = $this->userRepository->findUser($userId);

        $userClaimsFromRole =  DB::table('userRoles')
            ->select('roleClaims.claimType')
            ->leftJoin('roles', 'roles.id', '=', 'userRoles.roleId')
            ->leftJoin('roleClaims', 'roleClaims.roleId', '=', 'roles.id')
            ->where('userRoles.userId', '=', $user->id)
            ->get()
            ->toArray();

        $userIndividualClaims = DB::table('userClaims')
            ->select('claimType')
            ->where('userClaims.userId', '=', $user->id)
            ->get()
            ->toArray();

        $allClaimsObjArray = array_merge($userClaimsFromRole, $userIndividualClaims);

        $userClaims = array_map(function ($value) {
            return $value->claimType;
        }, $allClaimsObjArray);

        $user->claims = $userClaims;

        $token = Auth::claims(array('claims' => $userClaims, 'email' => $user->email, 'userId' => $user->id))->refresh($token);

        return response()->json([
            'status' => 'success',
            'claims' => $userClaims,
            'user' => [
                'id' => $user->id,
                'firstName' =>  $user->firstName,
                'lastName' => $user->lastName,
                'email' => $user->email,
                'userName' => $user->userName,
                'phoneNumber' => $user->phoneNumber
            ],
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function testToken()
    {
        $token = Auth::parseToken();
        return $token->getPayload()->get('Peter');
    }

    public function getIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return request()->ip(); // it will return the server IP if the client IP is not found using this method.
    }

    function subscribe(Request $request)
    {

        $user = Users::where('email', $request->email)->first();

        if (!$user) {

            $user = new Users();
            $user->firstName = $request->firstName;
            $user->lastName = $request->lastName;
            $user->email = $request->email;
            $user->userName = $request->email;
            // Generate a secure random password instead of hardcoded '123456'
            $user->password = Hash::make(bin2hex(random_bytes(16)));
            $user->save();
        }

        $credentials = $request->only('email', 'password');
        $token = Auth::claims(array('claims' => 'guest', 'email' => $user->email, 'userId' => $user->id))->attempt($credentials);

        return response([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'firstName' =>  $user->firstName,
                'lastName' => $user->lastName,
                'email' => $user->email,
                'userName' => $user->userName,
            ],
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ], 200);
    }

    function forgot(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 422);
        }

        $verify = User::where('email', $request->all()['email'])->exists();

        if ($verify) {
            $verify2 =  DB::table('password_resets')->where([
                ['email', $request->all()['email']]
            ]);

            if ($verify2->exists()) {
                $verify2->delete();
            }

            $token = random_int(100000, 999999);
            $password_reset = DB::table('password_resets')->insert([
                'email' => $request->all()['email'],
                'token' =>  $token,
                'created_at' => Carbon::now()
            ]);

            if ($password_reset) {
                Mail::to($request->all()['email'])->send(new ResetPassword($token));

                return response()->json([
                    'status' => 'error',
                    'message' => 'Please check your email for a 6 digit pin',
                ], 200);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'This email does not exist',
            ], 400);
        }
    }

    public function verifyPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'token' => ['required'],
        ]);

        if ($validator->fails()) {
            return new JsonResponse(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $check = DB::table('password_resets')->where([
            ['email', $request->email],
            ['token', $request->token],
        ]);

        if ($check->exists()) {

            $difference = Carbon::now()->diffInSeconds($check->first()->created_at);
            if ($difference > 3600) {
                return new JsonResponse(['status' => 'error', 'message' => "Token Expired"], 400);
            }

            DB::table('password_resets')->where([
                ['email', $request->email],
                ['token', $request->token],
            ])->delete();

            $token = Hash::make($request->token . ':' . $request->email);

            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' =>  $token,
                'created_at' => Carbon::now()
            ]);

            return new JsonResponse(
                [
                    'status' => 'success',
                    'message' => "You can now reset your password",
                    'token' => $token
                ],
                200
            );
        } else {
            return new JsonResponse(
                [
                    'status' => 'error',
                    'message' => "Invalid token"
                ],
                401
            );
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return new JsonResponse(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $check = DB::table('password_resets')->where([
            ['email', $request->email],
            ['token', $request->token],
        ]);

        if (! $check->exists()) return new JsonResponse(['status' => 'error', 'message' => "Token Expired"], 400);

        $check->delete();

        $user = Users::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        return new JsonResponse(
            [
                'status' => 'success',
                'message' => "Your password has been reset"
            ],
            200
        );
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
            'username' => ['required', 'string', 'max:255', 'unique:users,userName'],
            'firstName' => ['nullable', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        // Create new user
        $user = new Users();
        $user->firstName = $request->firstName ?: '';
        $user->lastName = $request->lastName ?: '';
        $user->email = $request->email;
        $user->userName = $request->username;
        $user->password = Hash::make($request->password);
        $user->save();

        // Get user claims (empty for new users)
        $userClaims = [];

        // Generate token with claims
        $token = Auth::claims([
            'claims' => $userClaims, 
            'email' => $user->email, 
            'userId' => $user->id
        ])->login($user);

        return response()->json([
            'status' => 'success',
            'claims' => $userClaims,
            'user' => [
                'id' => $user->id,
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'email' => $user->email,
                'userName' => $user->userName,
                'phoneNumber' => $user->phoneNumber
            ],
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

}
