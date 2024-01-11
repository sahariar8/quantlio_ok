<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\LabLocation;
use Illuminate\Support\Facades\Validator;
use Log;

class LabLocationsController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:insert-labLocation', ['only' => ['create']]);
        $this->middleware('permission:edit-labLocation', ['only' => ['edit','update']]);
        $this->middleware('permission:delete-labLocation', ['only' => ['destroy']]);
    }
    public function index(){
        $locations = LabLocation::all();
        return view('labLocations', ['locations' => $locations])->with('i');
    }
    public function create(Request $request){
        $validator = Validator::make($request->all(),[
            'locationName' => 'required',
            'printableLocationName' => 'required',
            'address' => 'required',
            'director' => 'required',
            'clia' => 'required',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'logo_img' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);
        if($validator->fails()){
            return redirect('/labLocations')->with('validation_error', ' ')->withErrors($validator)->withInput();
        }else{
            $location = new LabLocation;
            $location->location = $request->input('locationName');
            $location->printable_location = $request->input('printableLocationName');
            $location->address = $request->input('address');
            $location->director = $request->input('director');
            $location->CLIA = $request->input('clia');
            $location->phone = $request->input('phone');
            $location->fax = $request->input('fax');
            $location->website = $request->input('website');

            if ($image = $request->file('logo_img')) {
				$destinationPath = public_path('images/logo_image/');
				$logoImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
				$image->move($destinationPath, $logoImage);
				$location->logo_image = $logoImage;
			}
			
            if ($location->save()) {
                return redirect('/labLocations')->with('save_success', 'Location added successfully.');
            } else {
                return redirect('/labLocations')->with('save_error', 'There is some problem while saving!');
            }
        }
    } 
    public function edit($id){
        $location = LabLocation::find($id);
        return response()->json([
            'status' => 200,
            'locations' => $location,
        ]);
    }
    public function update(Request $request,$id){
        $validator = Validator::make($request->all(),[
            'locationName' => 'required',
            'printableLocationName' => 'required',
            'address' => 'required',
            'director' => 'required',
            'clia' => 'required',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            //'logo_img' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);
        if($validator->fails()){
            return redirect('/labLocations')->with('validation_error', $id)->withErrors($validator)->withInput();
        }else{
        	//Log::info($request->file('logo_img'));
            // $locationId  = $request->input('location_id');
            $location = LabLocation::find($id);
            $location->location = $request->input('locationName');
            $location->printable_location = $request->input('printableLocationName');
            $location->address = $request->input('address');
            $location->director = $request->input('director');
            $location->CLIA = $request->input('clia');
            $location->phone = $request->input('phone');
            $location->fax = $request->input('fax');
            $location->website = $request->input('website');

            if ($image = $request->file('logo_img')) {
				$destinationPath = public_path('images/logo_image/');
				$logoImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
				$image->move($destinationPath, $logoImage);
				$location->logo_image = $logoImage;
			}

            if ($location->update()) {
                return redirect('/labLocations')->with('save_success', 'Lab Location updated successfully.');
            } else {
                return redirect('/labLocations')->with('save_error', 'There is some problem while save!');
            }
        }
    } 
    public function destroy(Request $request){
        $locationId  = $request->input('deleting_id');
        $location = LabLocation::find($locationId);
        if ($location->delete()) {
            return redirect('/labLocations')->with('save_success', 'Location deleted successfully.');
        } else {
            return redirect('/labLocations')->with('save_error', 'There is some problem while Delete!');
        }
    } 
}
