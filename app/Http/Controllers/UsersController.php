<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use Auth;
use App\Notifications\SignupActivate;

class UsersController extends Controller {

    public function login() {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('appToken')->accessToken;
            //After successfull authentication, notice how I return json parameters
            return response()->json([
                        'success' => true,
                        'token' => $success,
                        'user' => $user
            ]);
        } else {
            //if authentication is unsuccessfull, notice how I return json parameters
            return response()->json([
                        'success' => false,
                        'message' => 'Invalid Email or Password',
                            ], 401);
        }
    }

    /**
     * Register api.
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    
                   // 'phone' => 'required|unique:users|regex:/(0)[0-9]{10}/',
                    'email' => 'required|email|unique:users',
                    'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                        'success' => false,
                        'message' => $validator->errors(),
                            ], 401);
        }
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $input['activation_token']= str_random(60);
     
        $user = User::create($input);
       
        $success['token'] = $user->createToken('appToken')->accessToken;
        //return $success['token'] ;
                $user->notify(new SignupActivate($user));

        return response()->json([
                    'success' => true,
                    'token' => $success,
                    'user' => $user
        ]);
    }

    public function logout(Request $res) {
        if (Auth::user()) {
            $user = Auth::user()->token();
            $user->revoke();

            return response()->json([
                        'success' => true,
                        'message' => 'Logout successfully'
            ]);
        } else {
            return response()->json([
                        'success' => false,
                        'message' => 'Unable to Logout'
            ]);
        }
    }

    public function signupActivate($token)
{
    $user = User::where('activation_token', $token)->first();
    if (!$user) {
        return response()->json([
            'message' => 'This activation token is invalid.'
        ], 404);
    }
    $user->active = true;
    $user->activation_token = '';
    $user->save();
    return $user;
}

}
