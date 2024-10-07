<?php

namespace App\Http\Controllers\AuthAPI;

use App\Models\Role;
use Modules\Authentication\Entities\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;
use App\Http\Requests\Auth\RegisterRequest;

class RegisterController extends Controller
{
    public function __construct(User $user, Role $role, Personel $personel, Position $position){
        $this->middleware(['auth','can:crud personel']);
        $this->role = $role;
        $this->user = $user;
        $this->personel = $personel;
        $this->position = $position;
    }

    public function register(RegisterRequest $request){
        try {
            $email = $this->user->where('email', $request->email)->first();
            if($email){
                return $this->response('02','Email has ben used', $request->email);
            }

            $user = $this->user->firstOrCreate([
                'name' => $request->name,
                'email' => $request->email,
            ],[
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'personel_id' => $request->personel_id,
            ]);
    
            $role = $this->role($user);

            return $this->response('00','Account created', $user);
        } catch (\Throwable $th) {
            return $this->response('01','Failed to create account', $th->getMessage());
        }
    }

    public function role($user){
        $personel = $this->personel->findOrFail($user->personel_id);
        $position = $this->position->findOrFail($personel->position_id);
        // dd($position);
        $role = $this->role->where('name', $position->name)->first();
        $user->assignRole($role->name);
        return $user;
    }

    public function role_list(){
        $roles = $this->role->all();
        
        return response()->json([
            'response_code' => '00',
            'response_message' => 'role list',
            'data' => $roles
        ]);
    }

    public function personel_list(){
        $personels = $this->personel->all();

        return response()->json([
            'response_code' => '00',
            'response_message' => 'personnel list',
            'data' => $personels
        ]);
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
