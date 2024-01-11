<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Metabolite;
use Illuminate\Support\Facades\Validator;

class MetaboliteController extends Controller
{
    function __construct()
    {
        // Add your middleware logic here
        $this->middleware('permission:insert-metabolites', ['only' => ['create']]);
        $this->middleware('permission:edit-metabolites', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-metabolites', ['only' => ['destroy']]);
    }

    public function index()
    {
        $metabolites = Metabolite::all();
        return view('metabolites', ['metabolites' => $metabolites])->with('i');
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'testName' => 'required',
            'class' => 'required',
            'parent' => 'required',
            'metabolite' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('/metabolites')->with('validation_error', ' ')->withErrors($validator)->withInput();
        } else {
            $metabolite = new Metabolite;
            $metabolite->testName = $request->input('testName');
            $metabolite->class = $request->input('class');
            $metabolite->parent = $request->input('parent');
            $metabolite->metabolite = $request->input('metabolite');

            if ($metabolite->save()) {
                return redirect('/metabolites')->with('save_success', 'Metabolite created successfully.');
            } else {
                return redirect('/metabolites')->with('save_error', 'There is some problem while save!');
            }
        }
    }

    public function edit($id)
    {
        $metabolite = Metabolite::find($id);
        return response()->json([
            'status' => 200,
            'metabolite' => $metabolite,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'testName' => 'required|unique:metabolites,testName,' . $id,
            'class' => 'required',
            'parent' => 'required',
            'metabolite' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('/metabolites')->with('validation_error', $id)->withErrors($validator)->withInput();
        } else {
            $metabolite = Metabolite::find($id);
            $metabolite->testName = $request->input('testName');
            $metabolite->class = $request->input('class');
            $metabolite->parent = $request->input('parent');
            $metabolite->metabolite = $request->input('metabolite');

            if ($metabolite->update()) {
                return redirect('/metabolites')->with('save_success', 'Metabolite updated successfully.');
            } else {
                return redirect('/metabolites')->with('save_error', 'There is some problem while save!');
            }
        }
    }


    public function destroy(Request $request)
    {
        $metaboliteId = $request->input('deleting_id');
        $metabolite = Metabolite::find($metaboliteId);
        if ($metabolite->delete()) {
            return redirect('/metabolites')->with('save_success', 'Metabolite Item deleted successfully.');
        } else {
            return redirect('/metabolites')->with('save_error', 'There is some problem while Delete!');
        }
    }
}
