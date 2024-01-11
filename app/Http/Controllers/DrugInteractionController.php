<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Lang;
use App\Models\DrugInteraction;

class DrugInteractionController extends Controller
{
    /**
    * Display listing of the interactions.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(){
        $result = DrugInteraction::all();
        return view('drug-interactions', ['interactions' => $result])->with('i');
    }
    public function edit($id){
        $result = DrugInteraction::find($id);
        return response()->json([
            'status' => 200,
            'result' => $result,
        ]);
    }
    public function update(Request $request,$id){
        $resultId  = $request->input('edit_id');
        $result = DrugInteraction::find($resultId);
        $result->risk_score = $request->input('severityValue');
        if ($result->update()) {
            return redirect('/drug-interactions')->with('save_success', 'Updated successfully.');
        } else {
            return redirect('/drug-interactions')->with('save_error', 'There is some problem while saving!');
        } 
    }
}
