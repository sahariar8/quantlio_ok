<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    public function index(){
        $data = User::all();
        $roles = Role::all();
        return view('users',compact('roles', 'data'))->with('i');
    }

    public function login() {
        if (Auth::user()) {
            return redirect('/trackOrders');
        }
        return view('login');
    }

    public function create(Request $request) {
        $validator = Validator::make($request->all(),[
            'username' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'roles' => 'required'
        ]);
        if($validator->fails()){
            return redirect('/users')->with('validation_error', ' ')->withErrors($validator)->withInput();
        }else{
            $user = new User;
            $user->username = $request->input('username');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            $user->assignRole($request->input('roles'));
            if ($user->save()) {
                return redirect('/users')->with('save_success', 'User details added successfully.');
            } else {
                return redirect('/users')->with('save_error', 'There is some problem while saving!');
            }
        }
    }
    
    public function destroy(Request $request) {
        $user = User::find($request->delete_user_id);
        if ($user->delete()) {
            return redirect('/users')->with('save_success', 'User Deleted successfully.');
        } else {
            return redirect('/users')->with('save_error', 'There is some problem while Deleting!');
        }
    }

    public function edit($id){
        
        // $user = DB::table('users')->where('id', $id)->first();
        // dd($user);
        $user = User::find($id);
        $userRole = $user->roles->pluck('name','id')->all();
        return response()->json([
            'status' => 200,
            'detail' => $user,
            'userRole' => $userRole,
        ]);
    }
    public function update(Request $request,$id){
        $validator = Validator::make($request->all(),[
            'username' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'roles' => 'required'
        ]);
        if($validator->fails()){
            return redirect('/users')->with('validation_error', $id)->withErrors($validator)->withInput();
        }else{
            $userId  = $request->input('user_id');
            $user = User::find($userId);
            $user->username = $request->username;
            $user->email = $request->email;
            DB::table('model_has_roles')->where('model_id',$userId)->delete();
            $user->assignRole($request->input('roles'));
            if ($user->update()) {
                return redirect('/users')->with('save_success', 'User Updated successfully.');
            } else {
                return redirect('/users')->with('save_error', 'There is some problem while saving!');
            }
        }
    }
}
