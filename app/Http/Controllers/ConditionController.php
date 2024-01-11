<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Lang;
use App\Models\Condition;

class ConditionController extends Controller
{
    /**
    * Display listing of the conditions.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(){
        
        $result = Condition::distinct()->get(['prescribed_test','drug_class','conditions','risk_score','order_id']);
        return view('conditions', ['conditions' => $result])->with('i');
    }
    public function edit($id){
        $result = Condition::find($id);
        return response()->json([
            'status' => 200,
            'result' => $result,
        ]);
    }
    public function update(Request $request,$id){
        $resultId  = $request->input('edit_id');
        $result = Condition::find($resultId);
        $result->risk_score = $request->input('severityValue');
        if ($result->update()) {
            return redirect('/conditions')->with('save_success', 'Updated successfully.');
        } else {
            return redirect('/conditions')->with('save_error', 'There is some problem while saving!');
        } 
    } 
}

