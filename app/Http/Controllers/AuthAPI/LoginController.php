<?php

namespace App\Http\Controllers\AuthAPI;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->middleware('auth')->except('login');
        $this->user = $user;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $user = $this->user->query()
            ->where('email', $request->login)
            ->orWhere('username', $request->login)
            ->first();

        if ($user == null) {
            return response()->json([
                'response_code' => '01',
                'response_msg' => 'user not found!',
            ]);
        }

        if (!$token = auth()->attempt($request->only($this->username_or_email($request), 'password'))) {
            return response()->json([
                'response_code' => '01',
                'response_message' => 'login failed',
            ], 401);
        }
        return $this->respondWithToken($token, $user);
    }

    public function username_or_email($request)
    {
        $input_type = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $request->merge([
            $input_type => $request->login,
        ]);

        return $input_type;
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $logout = auth()->logout();
        return response()->json([
            'response_code' => '00',
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $user)
    {
        $user->getPermissionsViaRoles();
        $data = [
            'token' => $token,
            'user' => $user,
        ];
        
        $link = null;
        if(!$user->roles->isEmpty()){
            $link = $user->roles[0]->name;
        }
        else{
            $link = 'login';
        }
        return response()->json([
            'response_code' => "00",
            'response_msg' => 'Login berhasil',
            'data' => $data,
            'link' => $link
        ]);
    }
}
