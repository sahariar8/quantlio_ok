<?php

namespace App\Http\Controllers;
use App\Models\Profile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfilesController extends Controller
{
    public function index(){
        $data = Profile::all();
        return view('profileList', ['profiles' => $data])->with('i');
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(),[
            'profileName' => 'required|unique:profiles,name,',
        ]);
        if($validator->fails()){
            // return redirect('/profiles')->with('validation_error')->withErrors($validator)->withInput();
            return redirect('/profiles')->with('validation_error', ' ')->withErrors($validator)->withInput();
        } else {
            $profile = new Profile;
            $profile->name = $request->input('profileName');
            if ($profile->save()) {
                return redirect('/profiles')->with('save_success', 'Profile added successfully.');
            } else {
                return redirect('/profiles')->with('save_error', 'There is some problem while save!');
            }
        }
    } 

    public function edit($id){
        $profile = Profile::find($id);
        return response()->json([
            'status' => 200,
            'profile' => $profile,
        ]);
    }

    public function update(Request $request,$id){
        $validator = Validator::make($request->all(),[
            'profileName' => 'required|unique:profiles,name,'.$id,
        ]);
        if($validator->fails()){
            return redirect('/profiles')->with('validation_error', $id)->withErrors($validator)->withInput();
        } else {
            $profileId  = $request->input('profile_id');
            $profile = Profile::find($profileId);
            $profile->name = $request->input('profileName');
            if ($profile->update()) {
                return redirect('/profiles')->with('save_success', 'Profile Updated successfully.');
            } else {
                return redirect('/profiles')->with('save_error', 'There is some problem while save!');
            }

            return redirect('/profiles')->with('status', 'Profile updated successfully!');
        }
    } 

    public function destroy(Request $request){
        try {
            $profileId  = $request->input('delete_test_id');
            $profile = Profile::find($profileId);
            if ($profile->delete()) {
                return redirect('/profiles')->with('save_success', 'Profile Deleted successfully.');
            } else {
                return redirect('/profiles')->with('save_error', 'There is some problem while Delete!');
            }
        } catch (\Exception $e) {
            return redirect('/profiles')->with('save_error', 'There is some problem while Delete! Profile being used in keyword section.');
        }
        
    } 
}
