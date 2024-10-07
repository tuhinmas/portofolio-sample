<?php

namespace App\Http\Controllers\authAPI;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function send_email(Request $request)
    {
        $credentials = request()->validate(['email' => 'required|email']);

        Password::sendResetLink($credentials);

        $status = Password::sendResetLink(
            $request->only('email')
        );
        if ($status) {
            return response()->json([
                "response_code" => '00',
                'response_message' => 'If email is exist in system we will sent reset link to your email',
            ]);
        }
    }

    public function password_update(Request $request)
    {
        $credentials = request()->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);

        $reset_password_status = Password::reset($credentials, function ($user, $password) {
            $user->password = bcrypt($password);
            $user->save();
        });

        if ($reset_password_status == Password::INVALID_TOKEN) {
            return response()->json([
                "response_code" => '01',
                "msg" => "Invalid token provided"
            ]);
        }

        return response()->json([
            "response_code" => '00',
            'response_message' => "Password has been successfully changed",
        ]);
    }
}
