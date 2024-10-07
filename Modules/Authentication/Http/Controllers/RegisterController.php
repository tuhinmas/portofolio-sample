<?php

namespace Modules\Authentication\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Modules\Authentication\Entities\User;
use Modules\Authentication\Http\Requests\RegisterRequest;
use Modules\DataAcuan\Entities\Position;
use Modules\Personel\Entities\Personel;

class RegisterController extends Controller
{
    public function __construct(User $user, Role $role, Personel $personel, Position $position)
    {
        $this->role = $role;
        $this->user = $user;
        $this->personel = $personel;
        $this->position = $position;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $email = $this->user->where('email', $request->email)->first();
            if ($email) {
                return $this->response('02', 'Email has ben used', $request->email);
            }

            $user = $this->user->firstOrCreate([
                'name' => $request->name,
                'email' => $request->email,
            ], [
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'personel_id' => $request->personel_id,
            ]);

            $role = $this->role($user);
            if ($user) {
                Personel::query()
                    ->where("id", $user->personel_id)
                    ->update([
                        "status" => 1,
                    ]);
            }

            return $this->response('00', 'Account created', $user);
        } catch (\Throwable $th) {
            return $this->response('01', 'Failed to create account', $th->getMessage(), 500);
        }
    }

    public function role($user)
    {
        $personel = $this->personel->findOrFail($user->personel_id);
        $position = $this->position->findOrFail($personel->position_id);
        $role = $this->role->where('name', $position->name)->first();
        $user->assignRole($role->name);
        return $user;
    }

    public function role_list()
    {
        $roles = $this->role->all();

        return response()->json([
            'response_code' => '00',
            'response_message' => 'role list',
            'data' => $roles,
        ]);
    }

    public function personel_list()
    {
        $personels = $this->personel->all();

        return response()->json([
            'response_code' => '00',
            'response_message' => 'personnel list',
            'data' => $personels,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $this->user->findOrFail($id);
        try {

            if (!Hash::check($request->password, $user->password)) {
                $user->password_change_at = now();
            }

            $user->password = bcrypt($request->password);
            $user->save();
            return $this->response("00", "success, user updated", $user);
        } catch (\Throwable $th) {
            return $this->response("01", "failed, user not updated", $th->getMessage(), 500);
        }
    }

    /**
     * response
     *
     * @param [type] $code
     * @param [type] $message
     * @param [type] $data
     * @return void
     */
    public function response($code, $message, $data)
    {
        return response()->json([
            'response_code' => $code,
            'response_message' => $message,
            'data' => $data,
        ]);
    }
}
