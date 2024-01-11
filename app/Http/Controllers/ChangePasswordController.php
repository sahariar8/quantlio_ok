<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Hash;

class ChangePasswordController extends Controller
{
    public function index(Request $request)
    {
        return view('changePassword');
    }
 
    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'current_password' => 'required|string',
            'new_password' => 'required|confirmed|min:8|string'
        ]);
       
        $auth = Auth::user();
        // The passwords matches
        if (!Hash::check($request->get('current_password'), $auth->password)) 
        {
            return redirect('/changePassword')->with('save_error', 'Current Password is Invalid');
        }
 
        // Current password and new password same
        if (strcmp($request->get('current_password'), $request->new_password) == 0) 
        {
            return redirect('/changePassword')->with('save_error', 'New Password cannot be same as your current password.');
        }

        $user =  User::find($auth->id);
        $user->password =  Hash::make($request->new_password);
        $user->save();
        return redirect('/changePassword')->with('save_success', 'Password changed successfully.');
    }
}
