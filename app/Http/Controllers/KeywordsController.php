<?php

namespace App\Http\Controllers;
use App\Models\Keyword;
use App\Models\KeywordProfile;
use Illuminate\Http\Request;
use Lang;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KeywordsController extends Controller
{
    /**
    * Display a listing of the keywords.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(){
        $keywords = Keyword::all();
        $profiles = Profile::all();
        return view('keywordList', compact('keywords','profiles'))->with('i');
    }

    /**
    * Create and store a newly created keyword
    *
    * @return \Illuminate\Http\Response
    */
    public function create(Request $request){
        try {
            $validator = Validator::make($request->all(),[
            'profile' => 'required|not_in:0',
            'primary' => 'required',
            'result' => 'required',
            ]);
            if($validator->fails()){
                return redirect('/keywords')->with('validation_error', ' ')->withErrors($validator)->withInput();
            }else{
                $keyword = new Keyword;
                $keyword->primary_keyword = $request->input('primary');
                $keyword->secondary_keyword = $request->input('secondary');
                $keyword->resultant_keyword = $request->input('result');
                if ($keyword->save()) {
                    $profileIds = $request->input('profile');
                    $keywordProfiles = [];
                    foreach($profileIds as $profileId) {
                        $keywordProfiles[] = [
                            'keyword_id' => $keyword->id,
                            'profile_id' => $profileId,
                        ];
                    }
                    KeywordProfile::insert($keywordProfiles);
                    return redirect('/keywords')->with('save_success', __('Keyword added successfully.'));
                } else {
                    return redirect('/keywords')->with('save_error', __('There is some problem while saving!'));
                }
            }
        }catch (\Exception $e) {
            return redirect('/keywords')->with('save_error', __('Internal server Error.'));
        }
    } 

    /**
    * Editing the specified keyword.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($id = null){
        $keyword = Keyword::find($id);
        $keywordProfile = DB::table('keyword_profiles')
            ->select('profile_id')
            ->where('keyword_id', '=', $id)
            ->pluck('profile_id');
        return response()->json([
            'status' => 200,
            'detail' => $keyword,
            'keyword_profile' => $keywordProfile
        ]);
    }

    /**
    * Update the specified keyword.
    *
    * @param  \Illuminate\Http\Request $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(Request $request,$id = null){
        try {
            $validator = Validator::make($request->all(),[
                'profile' => 'required|not_in:0',
                'primary' => 'required',
                'result' => 'required',
            ]);
            if($validator->fails()){
                return redirect('/keywords')->with('validation_error', $id)->withErrors($validator)->withInput();
            }else{
                $keywordId  = $request->input('keyword_id');
                $keyword = Keyword::find($keywordId);
                $profileIds = $request->input('profile');
                $keyword->primary_keyword = $request->input('primary');
                $keyword->secondary_keyword = $request->input('secondary');
                $keyword->resultant_keyword = $request->input('result');
                
                if ($keyword->update()) {
                    DB::table('keyword_profiles')->where('keyword_id', $keywordId)->delete();
                    $profileIds = $request->input('profile');
                    $keywordProfiles = [];
                    foreach($profileIds as $profileId) {
                        $keywordProfiles[] = [
                            'keyword_id' => $keyword->id,
                            'profile_id' => $profileId,
                        ];
                    }
                    KeywordProfile::insert($keywordProfiles);
                    return redirect('/keywords')->with('save_success', __('Keyword updated successfully.'));
                } else {
                    return redirect('/keywords')->with('save_error', __('There is some problem while saving!'));
                }
            }
        }catch (\Exception $e) {
            return redirect('/keywords')->with('save_error', __('Internal server Error.'));
        }
    }

    /**
    * Remove the specified keyword.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy(Request $request){
        $keywordId  = $request->input('delete_keyword_id');
        $keyword = Keyword::find($keywordId);
       if ($keyword->delete()) {
             DB::table('keyword_profiles')->where('keyword_id', $keywordId)->delete();
            return redirect('/keywords')->with('save_success', 'Keyword deleted successfully.');
        } else {
            return redirect('/keywords')->with('save_error', 'There is some problem while Deleting!');
        }
    } 
}
