<?php

namespace App\Http\Controllers;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TradesController extends Controller
{
     function __construct()
     {
         // Add your middleware logic here
         $this->middleware('permission:insert-trades', ['only' => ['create']]);
         $this->middleware('permission:edit-trades', ['only' => ['edit', 'update']]);
         $this->middleware('permission:delete-trades', ['only' => ['destroy']]);
     }
 
     public function index()
     {
         $trades = Trade::all();
         return view('trades', ['trades' => $trades])->with('i');
     }
 
     public function create(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'generic' => 'required',
             'brand' => 'required',
         ]);
 
         if ($validator->fails()) {
             return redirect('/trades')->with('validation_error', ' ')->withErrors($validator)->withInput();
         } else {
             $trades = new Trade;
             $trades->generic = $request->input('generic');
             $trades->brand = $request->input('brand');
 
             if ($trades->save()) {
                 return redirect('/trades')->with('save_success', 'Trade created successfully.');
             } else {
                 return redirect('/trades')->with('save_error', 'There is some problem while save!');
             }
         }
     }
 
     public function edit($id)
     {
         $trades = Trade::find($id);
         return response()->json([
             'status' => 200,
             'trades' => $trades,
         ]);
     }
 
     public function update(Request $request, $id)
     {
         $validator = Validator::make($request->all(), [
             'generic' => 'required|unique:trades,generic,' . $id,
             'brand' => 'required',
         ]);
 
         if ($validator->fails()) {
             return redirect('/trades')->with('validation_error', $id)->withErrors($validator)->withInput();
         } else {
             $trades = Trade::find($id);
             $trades->generic = $request->input('generic');
             $trades->brand = $request->input('brand');
 
             if ($trades->update()) {
                 return redirect('/trades')->with('save_success', 'Trade updated successfully.');
             } else {
                 return redirect('/trades')->with('save_error', 'There is some problem while save!');
             }
         }
     }
 
 
     public function destroy(Request $request)
     {
         $tradeId = $request->input('deleting_id');
         $trades = Trade::find($tradeId);
         if ($trades->delete()) {
             return redirect('/trades')->with('save_success', 'Trade deleted successfully.');
         } else {
             return redirect('/trades')->with('save_error', 'There is some problem while Delete!');
         }
     }
}
